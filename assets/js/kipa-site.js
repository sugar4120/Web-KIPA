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
})();
