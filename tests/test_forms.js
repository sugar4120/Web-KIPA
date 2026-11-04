const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/kipa-forms.js'), 'utf8');

async function checkForm(entries, response, shouldRedirect) {
    let handler;
    let submitted;
    let redirect;
    let prevented = false;
    let requestCount = 0;
    const button = {
        textContent: 'Send',
        disabled: false,
        setAttribute() {},
        removeAttribute() {}
    };
    const status = {};
    class HTMLFormElement {
        dataset = { kipaForm: 'contact', sourcePage: '/lien-he/' };
        querySelector(selector) {
            return selector === '[type="submit"]' ? button : status;
        }
        matches() { return true; }
    }
    const form = new HTMLFormElement();
    const document = { addEventListener(name, callback) { handler = callback; } };
    vm.runInNewContext(source, {
        document,
        HTMLFormElement,
        FormData: class {
            constructor(target) {
                assert.equal(target, form, 'FormData must receive the submitted form, not document.');
            }
            forEach(callback) { entries.forEach(([key, value]) => callback(value, key)); }
        },
        fetch: async (url, options) => {
            requestCount++;
            assert.equal(url, '/api/submit.php');
            submitted = JSON.parse(options.body);
            assert.equal(button.disabled, true);
            return {
                ok: response.ok,
                headers: { get: () => response.contentType || 'application/json' },
                json: async () => response.body
            };
        },
        window: { location: { pathname: '/lien-he/', assign(url) { redirect = url; } } },
        Error, TypeError, AbortController, setTimeout, clearTimeout
    });
    handler({ target: form, currentTarget: document, preventDefault() { prevented = true; } });
    handler({ target: form, currentTarget: document, preventDefault() {} });
    await new Promise(setImmediate);
    assert.equal(prevented, true);
    assert.ok(submitted, 'The form must reach the backend.');
    assert.equal(requestCount, 1, 'Double submit must not create concurrent requests.');
    assert.equal(submitted.form_type, 'contact');
    assert.equal(submitted.source_page, '/lien-he/');
    assert.equal(submitted.website, '');
    assert.equal(submitted.fields.consent, 'yes');
    if (shouldRedirect) {
        assert.equal(redirect, '/cam-on/');
    } else {
        assert.equal(redirect, undefined);
        assert.equal(status.textContent, response.body.error);
        assert.equal(button.disabled, false);
        assert.equal(button.textContent, 'Send');
    }
    return submitted;
}

(async () => {
    const fields = [['fullname', 'KIPA TEST'], ['consent', 'yes'], ['website', '']];
    const success = { ok: true, body: { ok: true, id: 1 } };
    const single = await checkForm([...fields, ['topics', 'Costs']], success, true);
    assert.deepEqual(single.fields.topics, ['Costs']);
    const multiple = await checkForm([...fields, ['topics', 'Costs'], ['topics', 'Study']], success, true);
    assert.deepEqual(multiple.fields.topics, ['Costs', 'Study']);
    await checkForm(fields, { ok: false, body: { error: 'Please retry.' } }, false);
    await checkForm(fields, {
        ok: false, contentType: 'text/html',
        body: { error: 'Máy chủ chưa thể xử lý biểu mẫu. Vui lòng thử lại sau.' }
    }, false);
    console.log('Form delegation, checkbox serialization, and failure handling tests passed.');
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
