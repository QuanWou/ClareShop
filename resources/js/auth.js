const authForms = document.querySelectorAll('[data-auth-form]');

const normalizePhone = (value) => value.replace(/[\s.\-()]+/g, '');

const emailLooksValid = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

const passwordChecks = (value) => ({
    length: value.length >= 8,
    letter: /\p{L}/u.test(value),
    number: /\d/.test(value),
});

const validationMessage = (input, form) => {
    const value = input.value.trim();

    if (input.required && value === '') {
        return {
            name: 'Vui lòng nhập họ và tên.',
            email: 'Vui lòng nhập email.',
            password: input.matches('[data-password-confirmation]')
                ? 'Vui lòng nhập lại mật khẩu.'
                : form.dataset.authMode === 'login'
                    ? 'Vui lòng nhập mật khẩu.'
                    : 'Vui lòng tạo mật khẩu.',
        }[input.name] ?? 'Vui lòng hoàn thiện thông tin này.';
    }

    if (input.name === 'name' && value.length < 2) {
        return 'Họ và tên cần có ít nhất 2 ký tự.';
    }

    if (input.name === 'email' && !emailLooksValid(value)) {
        return 'Email chưa đúng định dạng.';
    }

    if (input.matches('[data-vn-phone]') && value !== '') {
        const phone = normalizePhone(value);

        if (!/^(?:\+84|0)(?:3|5|7|8|9)\d{8}$/.test(phone)) {
            return 'Số điện thoại Việt Nam chưa đúng định dạng.';
        }
    }

    if (input.matches('[data-password-source]')) {
        const checks = passwordChecks(input.value);

        if (!checks.length) {
            return 'Mật khẩu cần có ít nhất 8 ký tự.';
        }

        if (!checks.letter) {
            return 'Mật khẩu cần có ít nhất một chữ cái.';
        }

        if (!checks.number) {
            return 'Mật khẩu cần có ít nhất một chữ số.';
        }
    }

    if (input.matches('[data-password-confirmation]')) {
        const password = form.querySelector('[data-password-source]');

        if (password && input.value !== password.value) {
            return 'Xác nhận mật khẩu chưa khớp.';
        }
    }

    return '';
};

const updatePasswordFeedback = (form) => {
    const password = form.querySelector('[data-password-source]');
    const feedback = form.querySelector('[data-password-feedback]');
    const meter = form.querySelector('[data-password-meter]');

    if (!password || !feedback || !meter) {
        return;
    }

    const checks = passwordChecks(password.value);
    const score = password.value === ''
        ? 0
        : Number(checks.length)
            + Number(checks.letter)
            + Number(checks.number)
            + Number(password.value.length >= 12 || /[^\p{L}\d]/u.test(password.value));

    meter.dataset.strength = String(score);
    meter.setAttribute('aria-valuenow', String(score));

    Object.entries(checks).forEach(([rule, isMet]) => {
        feedback.querySelector(`[data-password-rule="${rule}"]`)?.classList.toggle('is-met', isMet);
    });
};

const setFieldState = (input, form, markAsTouched = true) => {
    const field = input.closest('[data-auth-field]');
    const error = field?.querySelector('[data-field-error]');

    if (!field || !error) {
        return true;
    }

    const message = validationMessage(input, form);

    if (markAsTouched) {
        field.classList.add('was-touched');
    }

    field.classList.toggle('has-error', message !== '');
    field.classList.toggle('is-valid', message === '' && input.value.trim() !== '' && field.classList.contains('was-touched'));
    input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
    error.textContent = message;
    error.hidden = message === '';
    error.setAttribute('aria-live', 'polite');

    return message === '';
};

authForms.forEach((form) => {
    const inputs = [...form.querySelectorAll('[data-auth-input]')];
    const submitButton = form.querySelector('[data-auth-submit]');
    const passwordSource = form.querySelector('[data-password-source]');
    const passwordConfirmation = form.querySelector('[data-password-confirmation]');

    form.noValidate = true;
    updatePasswordFeedback(form);

    if (submitButton) {
        submitButton.dataset.idleLabel = submitButton.querySelector('span:first-child')?.textContent ?? '';
    }

    inputs.forEach((input) => {
        input.addEventListener('blur', () => {
            if (input.name === 'email') {
                input.value = input.value.trim().toLowerCase();
            }

            setFieldState(input, form);
        });

        input.addEventListener('input', () => {
            const field = input.closest('[data-auth-field]');

            if (field?.classList.contains('was-touched') || field?.classList.contains('has-error')) {
                setFieldState(input, form);
            }

            if (input.matches('[data-password-source]')) {
                updatePasswordFeedback(form);

                if (passwordConfirmation?.closest('[data-auth-field]')?.classList.contains('was-touched')) {
                    setFieldState(passwordConfirmation, form);
                }
            }
        });
    });

    form.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const input = document.getElementById(toggle.getAttribute('aria-controls'));

            if (!input) {
                return;
            }

            const willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';
            toggle.textContent = willShow ? 'Ẩn' : 'Hiện';
            toggle.setAttribute('aria-label', willShow ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    });

    form.addEventListener('submit', (event) => {
        const firstInvalidInput = inputs.find((input) => !setFieldState(input, form));

        if (firstInvalidInput) {
            event.preventDefault();
            firstInvalidInput.focus();
            firstInvalidInput.closest('[data-auth-field]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });

            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('is-submitting');
            submitButton.querySelector('span:first-child').textContent = passwordSource ? 'Đang tạo tài khoản…' : 'Đang đăng nhập…';
        }
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-auth-submit]').forEach((button) => {
        button.disabled = false;
        button.classList.remove('is-submitting');

        const label = button.querySelector('span:first-child');

        if (label && button.dataset.idleLabel) {
            label.textContent = button.dataset.idleLabel;
        }
    });
});

document.querySelector('[data-auth-alert]')?.focus({ preventScroll: true });
