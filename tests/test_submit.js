const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const { spawn } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');

(async () => {
    const root = fs.mkdtempSync(path.join(os.tmpdir(), 'kipa-submit-test-'));
    let server;
    try {
        fs.mkdirSync(path.join(root, 'api'));
        for (const file of ['bootstrap.php', 'content.php', 'content-model.json', 'submit.php']) {
            fs.copyFileSync(path.join(__dirname, '../api', file), path.join(root, 'api', file));
        }
        const listener = net.createServer();
        await new Promise(resolve => listener.listen(0, '127.0.0.1', resolve));
        const port = listener.address().port;
        await new Promise(resolve => listener.close(resolve));
        server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', root], { stdio: 'ignore' });
        let spawnError;
        server.on('error', error => { spawnError = error; });
        const url = `http://127.0.0.1:${port}/api/submit.php`;
        let ready = false;
        for (let i = 0; i < 50; i++) {
            if (spawnError) throw spawnError;
            try {
                const response = await fetch(url);
                assert.equal(response.status, 405);
                ready = true;
                break;
            } catch (error) {
                if (server.exitCode !== null) throw error;
                await delay(100);
            }
        }
        assert.ok(ready, 'PHP test server failed to start.');
        const forms = [
            ['home_consultation', '/', { name: 'KIPA TEST', program: 'Du học Hàn Quốc' }],
            ['consultation', '/dang-ky-tu-van/', { name: 'KIPA TEST', area: 'hai-phong', contact_time: 'bat-ky', program: 'du-hoc' }],
            ['contact', '/lien-he/', { fullname: 'KIPA TEST', program: 'Du học', topics: ['Chi phí'], branch: 'Hải Phòng', message: 'Test' }],
            ['korean_course', '/tieng-han/', { name: 'KIPA TEST' }],
            ['labor', '/xuat-khau-lao-dong/', { fullname: 'KIPA TEST', birth_year: '2000' }],
            ['partnership', '/hop-tac-quoc-te/', { organization: 'KIPA TEST', org_type: 'thpt', contact_name: 'KIPA TEST', email: 'test@example.invalid', cooperation_type: 'ngoai-ngu' }]
        ];
        async function send(body, status, headers = {}) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { Origin: 'https://kipaviethan.edu.vn', 'Content-Type': 'application/json', ...headers },
                body: JSON.stringify(body)
            });
            assert.equal(response.status, status, `${body.form_type}: ${await response.clone().text()}`);
            if (response.headers.get('Content-Type').includes('application/json')) {
                const result = await response.json();
                assert.ok(typeof result.error === 'string');
                assert.notEqual(result.ok, true, 'An unsaved request must never report success.');
            }
        }
        for (const [type, source, values] of forms) {
            const body = { form_type: type, source_page: source, fields: { ...values, phone: '0900000000', consent: 'yes' }, website: '' };
            // Valid input must reach configuration loading, not fail validation or pretend to save.
            await send(body, 503);
            const { consent, ...noConsent } = body.fields;
            await send({ ...body, fields: noConsent }, 422);
            await send({ ...body, fields: { ...body.fields, phone: '0'.repeat(7) + ' '.repeat(40) + '1' } }, 422);
        }
        await send({ form_type: 'unknown' }, 400);
        await send({}, 403, { Origin: 'https://example.invalid' });
        await send({}, 415, { 'Content-Type': 'text/plain' });
        console.log('All six backend forms passed validation and honest-failure HTTP checks.');
    } finally {
        if (server && server.exitCode === null && server.pid) {
            server.kill();
            await new Promise(resolve => server.once('exit', resolve));
        }
        fs.rmSync(root, { recursive: true });
    }
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
