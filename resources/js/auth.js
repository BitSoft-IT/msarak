function toggleVisibility(button) {
    const input = document.getElementById(button.dataset.target);
    if (!input) return;

    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(isHidden));
    button.setAttribute('aria-label', isHidden ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');

    button.querySelector('[data-icon-show]')?.classList.toggle('hidden', isHidden);
    button.querySelector('[data-icon-hide]')?.classList.toggle('hidden', !isHidden);
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (button) toggleVisibility(button);
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('.auth-form');
    if (!form) return;

    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;

    button.dataset.originalText = button.textContent;
    button.textContent = 'جارٍ المعالجة…';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
});