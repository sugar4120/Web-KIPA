(function () {
    var button = document.querySelector('[data-mobile-menu-toggle]');
    var menu = document.getElementById('mobile-header-menu');
    function closeMenu() {
        menu.classList.add('hidden');
        button.setAttribute('aria-expanded', 'false');
    }
    if (button && menu) {
        button.addEventListener('click', function () {
            var open = menu.classList.toggle('hidden') === false;
            button.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target) && !button.contains(event.target)) closeMenu();
            if (menu.contains(event.target) && event.target.closest('a')) closeMenu();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                var wasOpen = !menu.classList.contains('hidden');
                closeMenu();
                if (wasOpen) button.focus();
            }
        });
    }
    var current = window.location.pathname.replace(/\/index\.html$/, '/').replace(/\.html$/, '/');
    if (current !== '/' && !current.endsWith('/')) current += '/';
    document.querySelectorAll('#kipa-header a[href]').forEach(function (link) {
        var target = new URL(link.href, window.location.href);
        var active = target.pathname === current && !target.hash;
        link.classList.toggle('active', active);
        if (active) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
            if (target.pathname === '/') {
                link.classList.remove('text-[#0274BD]', 'bg-blue-50/50', 'border-l-4');
            }
        }
    });
    var consultation = document.querySelector('[data-kipa-form="consultation"]');
    if (consultation) {
        var params = new URLSearchParams(window.location.search);
        var notes = consultation.querySelector('[name="notes"]');
        var program = params.get('program');
        var details = params.get('notes') || '';
        if (program) {
            var radio = Array.from(consultation.querySelectorAll('[name="program"]')).find(function (input) {
                return input.value === program;
            });
            if (radio) {
                radio.checked = true;
                if (typeof window.selectProgram === 'function') window.selectProgram(program);
            } else {
                details = 'Chương trình quan tâm: ' + program.slice(0, 100) + '\n' + details;
            }
        }
        if (notes && details && !notes.value) notes.value = details.slice(0, 2000);
    }

    // =========================================================================
    // KIPA HEADER USER AUTHENTICATION STATE (Hiển thị tên học viên khi đã đăng nhập)
    // =========================================================================
    function initHeaderAuthState() {
        var loginBtn = document.getElementById('kipa-login-btn');
        var userProfile = document.getElementById('kipa-user-profile');
        var userMenuBtn = document.getElementById('kipa-user-menu-btn');
        var userDropdown = document.getElementById('kipa-user-dropdown');
        var userDisplayName = document.getElementById('kipa-user-display-name');
        var dropdownName = document.getElementById('kipa-dropdown-user-name');
        var dropdownEmail = document.getElementById('kipa-dropdown-user-email');
        var logoutBtn = document.getElementById('kipa-header-logout-btn');

        var mobileLoginLink = document.getElementById('mobile-login-link');
        var mobileUserLogged = document.getElementById('mobile-user-logged');
        var mobileUserName = document.getElementById('mobile-user-display-name');
        var mobileLogoutBtn = document.getElementById('mobile-logout-btn');

        function renderUser(user) {
            if (user && user.full_name) {
                // Desktop
                if (loginBtn) loginBtn.classList.add('hidden');
                if (userProfile) {
                    userProfile.classList.remove('hidden');
                    userProfile.classList.add('inline-flex');
                }
                if (userDisplayName) userDisplayName.textContent = user.full_name;
                if (dropdownName) dropdownName.textContent = user.full_name;
                if (dropdownEmail) dropdownEmail.textContent = user.email || '';

                // Mobile
                if (mobileLoginLink) mobileLoginLink.classList.add('hidden');
                if (mobileUserLogged) {
                    mobileUserLogged.classList.remove('hidden');
                    mobileUserLogged.classList.add('block');
                }
                if (mobileUserName) mobileUserName.textContent = user.full_name;
            } else {
                // Desktop
                if (loginBtn) loginBtn.classList.remove('hidden');
                if (userProfile) {
                    userProfile.classList.add('hidden');
                    userProfile.classList.remove('inline-flex');
                }
                // Mobile
                if (mobileLoginLink) mobileLoginLink.classList.remove('hidden');
                if (mobileUserLogged) {
                    mobileUserLogged.classList.add('hidden');
                    mobileUserLogged.classList.remove('block');
                }
            }
        }

        // 1. Kiểm tra localStorage để hiển thị tức thì (instant render không giật lag)
        var user = null;
        try {
            var raw = localStorage.getItem('kipa_user');
            if (raw) user = JSON.parse(raw);
        } catch (e) {}
        renderUser(user);

        // 2. Xác thực phiên làm việc trong nền với máy chủ (nếu có API)
        if (window.fetch) {
            fetch('/api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'me' })
            }).then(function (res) {
                if (!res.ok) throw new Error();
                return res.json();
            }).then(function (data) {
                if (data && data.ok && data.user) {
                    try { localStorage.setItem('kipa_user', JSON.stringify(data.user)); } catch (e) {}
                    renderUser(data.user);
                } else if (data && data.ok && !data.user) {
                    try { localStorage.removeItem('kipa_user'); } catch (e) {}
                    renderUser(null);
                }
            }).catch(function () {});
        }

        // 3. Dropdown click / toggle (hỗ trợ cả cảm ứng và phím)
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isHidden = userDropdown.classList.contains('invisible');
                if (isHidden) {
                    userDropdown.classList.remove('invisible', 'opacity-0');
                    userDropdown.classList.add('visible', 'opacity-100');
                    userMenuBtn.setAttribute('aria-expanded', 'true');
                } else {
                    userDropdown.classList.add('invisible', 'opacity-0');
                    userDropdown.classList.remove('visible', 'opacity-100');
                    userMenuBtn.setAttribute('aria-expanded', 'false');
                }
            });
            document.addEventListener('click', function (e) {
                if (userProfile && !userProfile.contains(e.target)) {
                    userDropdown.classList.add('invisible', 'opacity-0');
                    userDropdown.classList.remove('visible', 'opacity-100');
                    userMenuBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // 4. Xử lý Đăng xuất
        async function doLogout() {
            try {
                if (window.fetch) {
                    await fetch('/api/login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'logout' })
                    });
                }
            } catch (e) {}
            try { localStorage.removeItem('kipa_user'); } catch (e) {}
            renderUser(null);
            if (window.location.pathname.includes('/dang-nhap')) {
                window.location.reload();
            }
        }

        if (logoutBtn) logoutBtn.addEventListener('click', doLogout);
        if (mobileLogoutBtn) mobileLogoutBtn.addEventListener('click', doLogout);

        window.addEventListener('kipa-auth-change', function () {
            var updatedUser = null;
            try {
                var raw = localStorage.getItem('kipa_user');
                if (raw) updatedUser = JSON.parse(raw);
            } catch (e) {}
            renderUser(updatedUser);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderAuthState);
    } else {
        initHeaderAuthState();
    }
})();
