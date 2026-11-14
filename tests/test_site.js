const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(process.argv[2] || 'dist');
const files = fs.readdirSync(root).filter(name => name.endsWith('.html') && !['header.html', 'footer.html'].includes(name));
assert.equal(files.length, 23);
const pages = Object.fromEntries(files.map(file => [file, fs.readFileSync(path.join(root, file), 'utf8')]));

for (const [file, html] of Object.entries(pages)) {
    const ids = [...html.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
    assert.equal(new Set(ids).size, ids.length, `${file}: duplicate element IDs`);
    for (const match of html.matchAll(/<script\b([^>]*)>([\s\S]*?)<\/script>/g)) {
        if (!/src=|application\/ld\+json/.test(match[1])) {
            new vm.Script(match[2], { filename: file });
        }
    }
    for (const match of html.matchAll(/\b(?:href|src)=["']([^"']+)["']/g)) {
        const value = match[1];
        if (value.includes('${')) continue;
        if (/^(?:https?:|data:|tel:|mailto:|javascript:|\/\/)/.test(value)) continue;
        assert.notEqual(value, '#', `${file}: placeholder link`);
        const url = new URL(value, `https://kipaviethan.edu.vn/${file}`);
        const relative = decodeURIComponent(url.pathname).replace(/^\//, '');
        const target = relative === '' ? 'index.html'
            : relative.endsWith('/') ? `${relative}index.html` : relative;
        assert.ok(fs.existsSync(path.join(root, target)), `${file}: missing resource ${value}`);
        if (url.hash && target.endsWith('.html')) {
            const content = fs.readFileSync(path.join(root, target), 'utf8');
            const ids = [...content.matchAll(/\bid="([^"]+)"/g)].map(match => match[1]);
            assert.ok(ids.includes(decodeURIComponent(url.hash.slice(1))), `${file}: missing anchor ${value}`);
        }
    }
}
const home = pages['index.html'];
const consultationForm = home.match(/<form\b(?=[^>]*data-kipa-form="home_consultation")[^>]*>[\s\S]*?<\/form>/);
assert.ok(consultationForm, 'The homepage consultation form must exist.');
assert.doesNotMatch(consultationForm[0], /handleConsultationSubmit|alert\(/);
assert.match(home, /name="consent"[^>]*required/);
assert.match(home, /id="story-modal"/);
const letter = home.match(/<dialog\b[^>]*id="letter-modal"[^>]*>([\s\S]*?)<\/dialog>/);
assert.ok(letter, 'The letter must be a native dialog.');
assert.match(letter[1], /<form\b/);
assert.match(letter[1], /name="author"/);
assert.match(letter[1], /name="letter"/);
assert.match(home, /form_type: 'future_letter'/);
assert.match(home, /localStorage\.setItem\('kipa_future_letters'/);
assert.match(home, /fetch\('\/api\/submit\.php'/);
assert.match(pages['faq.html'], /function applyFaqFilters/);
console.log('All 23 built pages passed link, anchor, script and form integrity checks.');
