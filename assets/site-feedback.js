(() => {
    const icons = { success: '✓', error: '✕', warning: '!', info: 'ℹ' };

    window.showToast = (message, requestedType = 'info') => {
        const type = Object.hasOwn(icons, requestedType) ? requestedType : 'info';
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.append(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast toast--' + type;
        const body = document.createElement('div');
        body.className = 'toast__text';
        body.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const icon = document.createElement('span');
        icon.className = 'toast__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = icons[type];
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast__close';
        close.setAttribute('aria-label', 'Dismiss message');
        close.textContent = '×';
        toast.append(icon, body, close);
        container.append(toast);
        body.textContent = String(message);

        const previousFocus = document.activeElement;
        let timer;
        const stopTimer = () => clearTimeout(timer);
        const dismiss = () => {
            stopTimer();
            const restoreFocus = toast.contains(document.activeElement);
            toast.remove();
            if (restoreFocus && previousFocus?.isConnected) previousFocus.focus();
        };
        const startTimer = () => {
            stopTimer();
            if (type === 'success' || type === 'info') timer = setTimeout(dismiss, 7000);
        };
        close.addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', stopTimer);
        toast.addEventListener('mouseleave', () => {
            if (!toast.contains(document.activeElement)) startTimer();
        });
        toast.addEventListener('focusin', stopTimer);
        toast.addEventListener('focusout', event => {
            if (!toast.contains(event.relatedTarget) && !toast.matches(':hover')) startTimer();
        });
        startTimer();
    };
})();
