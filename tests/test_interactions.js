const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function inlineScript(file, marker) {
    const html = fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
    const script = [...html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)]
        .map(match => match[1]).find(script => script.includes(marker));
    assert.ok(script, `${file}: missing interactive script`);
    return script;
}

const items = [
    { dataset: { category: 'du-hoc du-hoc-kr' }, textContent: 'Hồ sơ visa Hàn Quốc', style: {} },
    { dataset: { category: 'xuat-khau' }, textContent: 'Visa lao động', style: {} },
    { dataset: { category: 'du-hoc du-hoc-kr' }, textContent: 'Chi phí học tập', style: {} }
];
let onSearch;
const search = { value: '', addEventListener(event, handler) { onSearch = handler; } };
const count = { textContent: '' };
const empty = { hidden: true };
const faq = {
    document: {
        getElementById(id) { return { 'faq-search-input': search, 'faq-count': count, 'faq-empty': empty }[id]; },
        querySelectorAll(selector) { return selector === '.faq-item' ? items : []; }
    }
};
vm.createContext(faq);
vm.runInContext(inlineScript('faq.html', 'function applyFaqFilters'), faq);
faq.filterFaqCategory('du-hoc-kr');
search.value = 'VISA';
onSearch();
assert.deepEqual(items.map(item => item.style.display), ['', 'none', 'none']);
assert.equal(count.textContent, '1 câu hỏi');
search.value = '';
onSearch();
assert.equal(count.textContent, '2 câu hỏi', 'Clearing search must preserve the category.');
faq.filterFaqCategory('all');
assert.equal(count.textContent, '3 câu hỏi');
search.value = 'does-not-exist';
onSearch();
assert.equal(empty.hidden, false);

let destination;
const elements = new Map();
function element() {
    return {
        children: [],
        append(...children) { this.children.push(...children); },
        appendChild(child) { this.children.push(child); },
        replaceChildren() { this.children = []; }
    };
}
for (const id of ['home-quiz-progress', 'home-quiz-question', 'home-quiz-options', 'home-quiz-next']) {
    elements.set(id, element());
}
const quiz = {
    document: {
        addEventListener() {},
        getElementById(id) { return elements.get(id); },
        createElement: element,
        querySelector() {
            const options = elements.get('home-quiz-options').children;
            return options.length ? options[0].children[0] : { value: 'Người đã đi làm' };
        }
    },
    window: { location: { assign(url) { destination = new URL(url, 'https://kipaviethan.edu.vn'); } } }
};
vm.createContext(quiz);
vm.runInContext(inlineScript('index.html', 'const homeQuizQuestions'), quiz);
for (let step = 0; step < 4; step++) {
    quiz.nextQuizStep();
    assert.equal(elements.get('home-quiz-progress').textContent, `Bước ${step + 2} trên 5:`);
    assert.equal(destination, undefined, 'Quiz must not navigate before all five answers.');
}
quiz.nextQuizStep();
assert.equal(destination.pathname, '/dang-ky-tu-van/');
const notes = destination.searchParams.get('notes');
assert.equal(notes.split('\n').length, 5);
assert.ok(notes.includes('Người đã đi làm'));
assert.ok(notes.includes('Ngân sách'));
console.log('FAQ combined filters and the five-step home quiz passed regression tests.');
