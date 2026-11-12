const assert = require('node:assert/strict');
const net = require('node:net');
const { spawn } = require('node:child_process');
const { setTimeout: delay } = require('node:timers/promises');

(async () => {
    const listener = net.createServer();
    await new Promise(resolve => listener.listen(0, '127.0.0.1', resolve));
    const port = listener.address().port;
    await new Promise(resolve => listener.close(resolve));
    const root = `http://127.0.0.1:${port}`;
    const server = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', process.argv[2]], { stdio: 'ignore' });
    let cookie = '';
    let spawnError;
    server.on('error', error => { spawnError = error; });
    async function request(url, data, origin = 'https://kipaviethan.edu.vn') {
        const response = await fetch(root + url, {
            redirect: 'manual',
            headers: { Cookie: cookie, ...(data ? { Origin: origin } : {}) },
            ...(data ? { method: 'POST', body: new URLSearchParams(data) } : {})
        });
        const setCookie = response.headers.get('set-cookie');
        if (setCookie) cookie = setCookie.split(';')[0];
        return response;
    }
    try {
        let ready = false;
        for (let i = 0; i < 50; i++) {
            if (spawnError) throw spawnError;
            try {
                const response = await request('/api/cms.php');
                assert.equal(response.status, 303, 'CMS must require authentication.');
                ready = true;
                break;
            } catch (error) {
                if (server.exitCode !== null) throw error;
                await delay(100);
            }
        }
        assert.ok(ready, 'CMS test server did not start.');
        const login = await (await request('/api/admin.php')).text();
        const csrf = login.match(/name="csrf" value="([^"]+)"/)[1];
        const authenticated = await request('/api/admin.php', {
            action: 'login', csrf, username: 'test-admin', password: 'KIPA-test-password-2026'
        });
        assert.equal(authenticated.status, 303);
        const forcedPage = await (await request('/api/admin.php')).text();
        assert.ok(forcedPage.includes('Bắt buộc đổi mật khẩu'));
        assert.ok(!forcedPage.includes('Đăng ký tư vấn KIPA</h1>'), 'Leads must remain inaccessible until the password is changed.');
        const forcedCsrf = forcedPage.match(/name="csrf" value="([^"]+)"/)[1];
        assert.equal((await request('/api/cms.php')).status, 303);
        assert.equal((await request('/api/cms.php', { action: 'publish', csrf: forcedCsrf })).status, 303);
        assert.equal((await request('/api/admin.php', {
            action: 'confirm_delete', csrf: forcedCsrf, lead_id: '2'
        })).status, 403, 'Direct mutations must be blocked before changing the password.');
        const change = {
            action: 'change_password', csrf: forcedCsrf, old_password: 'KIPA-test-password-2026',
            new_password: 'KIPA-changed-password-2026', new_password_confirm: 'KIPA-changed-password-2026'
        };
        assert.equal((await request('/api/admin.php', { ...change, csrf: 'wrong' })).status, 403);
        assert.equal((await request('/api/admin.php', { ...change, old_password: 'wrong' })).status, 400);
        assert.equal((await request('/api/admin.php', { ...change, new_password: 'short', new_password_confirm: 'short' })).status, 400);
        assert.equal((await request('/api/admin.php', {
            ...change, new_password: change.old_password, new_password_confirm: change.old_password
        })).status, 400, 'The initial password must not be reused.');
        assert.equal((await request('/api/admin.php', { ...change, new_password_confirm: 'different' })).status, 400);
        assert.equal((await request('/api/admin.php', change)).status, 303);
        cookie = '';
        const relogin = await (await request('/api/admin.php')).text();
        const reloginCsrf = relogin.match(/name="csrf" value="([^"]+)"/)[1];
        assert.equal((await request('/api/admin.php', {
            action: 'login', csrf: reloginCsrf, username: 'test-admin', password: change.old_password
        })).status, 400, 'The initial password must no longer work.');
        assert.equal((await request('/api/admin.php', {
            action: 'login', csrf: reloginCsrf, username: 'test-admin', password: change.new_password
        })).status, 303);
        const adminPage = await (await request('/api/admin.php')).text();
        assert.ok(!adminPage.includes('Bắt buộc đổi mật khẩu'), 'The completed password change must persist across logins.');
        const adminCsrf = adminPage.match(/name="csrf" value="([^"]+)"/)[1];
        assert.ok(!adminPage.includes('Tạo tài khoản quản trị viên'), 'Administrator creation must not appear in the dashboard.');
        const newAdmin = {
            action: 'create_admin', csrf: adminCsrf, username: 'admin',
            password: 'KIPA-new-admin-password-2026', password_confirm: 'KIPA-new-admin-password-2026'
        };
        assert.equal((await request('/api/admin.php', newAdmin)).status, 400, 'Administrator creation must be rejected even when authenticated.');
        const home = await (await request('/api/cms.php')).text();
        assert.ok(home.includes('Trung tâm quản trị KIPA'));
        const token = home.match(/name="csrf" value="([^"]+)"/)[1];
        assert.equal((await request('/api/cms.php', { action: 'publish', csrf: 'wrong' })).status, 403);
        assert.equal((await request('/api/cms.php', { action: 'publish', csrf: token }, 'https://example.invalid')).status, 403);
        assert.equal((await request('/api/cms.php?table=posts%60%3BDELETE')).status, 422);
        const post = {
            action: 'save', table: 'posts', csrf: token,
            'fields[title]': 'CMS HTTP TEST', 'fields[slug]': 'cms-http-test',
            'fields[content]': 'CMS HTTP CONTENT', 'fields[post_type]': 'article', 'fields[status]': 'published'
        };
        assert.equal((await request('/api/cms.php', post)).status, 303, 'Authenticated editor must save a post.');
        const listing = await (await request('/api/cms.php?table=posts&q=CMS%20HTTP%20TEST')).text();
        assert.ok(listing.includes('CMS HTTP TEST'));
        const id = listing.match(/table=posts&amp;id=(\d+)/)[1];
        const editor = await (await request(`/api/cms.php?table=posts&id=${id}`)).text();
        const revision = editor.match(/name="revision" value="([^"]+)"/)[1];
        assert.equal((await request('/api/cms.php', { ...post, id, revision, 'fields[title]': 'CMS HTTP EDITED' })).status, 303);
        assert.equal((await request('/api/cms.php', { ...post, id, revision })).status, 422, 'Stale edits must not overwrite newer data.');
        const published = await request('/api/cms.php', { action: 'publish', csrf: token });
        assert.equal(published.status, 200);
        const live = await (await request('/tin-tuc/cms-http-test/')).text();
        assert.ok(live.includes('CMS HTTP EDITED') && live.includes('CMS HTTP CONTENT'));
        const confirmation = await request('/api/cms.php', { action: 'delete', table: 'posts', id, csrf: token });
        assert.ok((await confirmation.text()).includes('Xác nhận xóa'));
        assert.equal((await request('/api/cms.php', { action: 'confirm_delete', table: 'posts', id, csrf: token })).status, 303);
        const forms = [
            ['home_consultation', '/', { name: 'KIPA HTTP TEST', program: 'Du học Hàn Quốc' }],
            ['consultation', '/dang-ky-tu-van/', { name: 'KIPA HTTP TEST', area: 'hai-phong', contact_time: 'bat-ky', program: 'du-hoc' }],
            ['contact', '/lien-he/', { fullname: 'KIPA HTTP TEST', program: 'Du học', topics: ['Chi phí'], branch: 'Hải Phòng', message: 'Test' }],
            ['korean_course', '/tieng-han/', { name: 'KIPA HTTP TEST' }],
            ['labor', '/xuat-khau-lao-dong/', { fullname: 'KIPA HTTP TEST', birth_year: '2000' }],
            ['partnership', '/hop-tac-quoc-te/', { organization: 'KIPA HTTP TEST', org_type: 'thpt', contact_name: 'KIPA HTTP TEST', email: 'test@example.invalid', cooperation_type: 'ngoai-ngu' }]
        ];
        for (const [type, source, fields] of forms) {
            const response = await fetch(root + '/api/submit.php', {
                method: 'POST',
                headers: { Origin: 'https://kipaviethan.edu.vn', 'Content-Type': 'application/json' },
                body: JSON.stringify({ form_type: type, source_page: source, fields: { ...fields, phone: '0900000000', consent: 'yes' }, website: '' })
            });
            assert.equal(response.status, 201, `${type}: ${await response.clone().text()}`);
            assert.equal((await response.json()).ok, true);
        }
        console.log('CMS auth, CSRF, CRUD, stale-edit protection, static publishing and all six live-database form HTTP tests passed.');
    } finally {
        if (server.exitCode === null && server.pid) {
            server.kill();
            await new Promise(resolve => server.once('exit', resolve));
        }
    }
})().catch(error => {
    console.error(error);
    process.exitCode = 1;
});
