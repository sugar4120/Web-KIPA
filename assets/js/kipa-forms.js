(function () {
    function showStatus(form, message, isError) {
        var status = form.querySelector('[data-form-status]');
        if (!status) {
            status = document.createElement('p');
            status.setAttribute('data-form-status', '');
            status.setAttribute('aria-live', 'polite');
            form.appendChild(status);
        }
        status.textContent = message;
        status.className = isError ? 'text-sm text-red-700' : 'text-sm text-emerald-700';
    }

    async function submit(event) {
        event.preventDefault();
        var form = event.target;
        if (form.dataset.submitting === 'true') return;
        form.dataset.submitting = 'true';
        var button = form.querySelector('[type="submit"]');
        var originalText = button ? button.textContent : '';
        var fields = {};
        var website = '';

        new FormData(form).forEach(function (value, key) {
            value = String(value).trim();
            if (key === 'website') {
                website = value;
            } else if (key === 'topics') {
                fields[key] = (fields[key] || []).concat(value);
            } else if (Object.prototype.hasOwnProperty.call(fields, key)) {
                fields[key] = Array.isArray(fields[key]) ? fields[key].concat(value) : [fields[key], value];
            } else {
                fields[key] = value;
            }
        });

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Đang gửi...';
        }
        showStatus(form, 'Đang gửi thông tin của bạn...', false);

        var controller = new AbortController();
        var timeout = setTimeout(function () { controller.abort(); }, 20000);
        try {
            var response = await fetch('/api/submit.php', {
                method: 'POST',
                signal: controller.signal,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    form_type: form.dataset.kipaForm,
                    source_page: form.dataset.sourcePage || window.location.pathname,
                    fields: fields,
                    website: website
                })
            });
            if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
                throw new Error('Máy chủ chưa thể xử lý biểu mẫu. Vui lòng thử lại sau.');
            }
            var result = await response.json();
            if (!response.ok || !result || result.ok !== true) {
                throw new Error(result && result.error || 'Chưa thể gửi thông tin. Vui lòng thử lại.');
            }
            if (form.dataset.successModal) {
                var modal = document.querySelector(form.dataset.successModal);
                if (modal) {
                    var nameHolder = modal.querySelector('[data-modal-name]');
                    if (nameHolder && (result.contact_name || fields.name || fields.fullname)) {
                        nameHolder.textContent = result.contact_name || fields.name || fields.fullname;
                    }
                    var emailHolder = modal.querySelector('[data-modal-email]');
                    if (emailHolder && fields.email) {
                        emailHolder.textContent = fields.email;
                    }
                    var classHolder = modal.querySelector('[data-modal-class]');
                    if (classHolder && (fields.class_choice || fields.course)) {
                        classHolder.textContent = fields.class_choice || fields.course;
                    }
                    if (typeof modal.showModal === 'function') {
                        modal.showModal();
                    } else {
                        modal.classList.remove('hidden');
                    }
                }
                showStatus(form, 'Đăng ký thành công! Hãy tham gia nhóm Zalo bên dưới.', false);
                form.reset();
                if (button) {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                    button.textContent = originalText;
                }
                var eventSuccess = new CustomEvent('kipa:form:success', { detail: { form: form, result: result, fields: fields } });
                document.dispatchEvent(eventSuccess);
                return;
            }
            window.location.assign('/cam-on/');
        } catch (error) {
            var message = error && error.name === 'AbortError'
                ? 'Kết nối quá lâu. Thông tin có thể đã được gửi; vui lòng kiểm tra trước khi thử lại.'
                : error instanceof TypeError ? 'Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.'
                : error instanceof Error ? error.message : 'Lỗi kết nối. Vui lòng thử lại.';
            showStatus(form, message, true);
            if (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.textContent = originalText;
            }
        } finally {
            clearTimeout(timeout);
            delete form.dataset.submitting;
        }
    }

    document.addEventListener('submit', function (event) {
        if (event.target instanceof HTMLFormElement && event.target.matches('[data-kipa-form]')) {
            submit(event);
        }
    });
})();
