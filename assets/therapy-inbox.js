'use strict';
const therapyFilter = document.querySelector('[data-therapy-filter]');
if (therapyFilter) {
    const control = therapyFilter.querySelector('select');
    const requests = [...document.querySelectorAll('[data-therapy-status]')];
    const count = document.querySelector('[data-therapy-count]');
    therapyFilter.hidden = false;
    control.addEventListener('change', () => {
        let shown = 0;
        for (const request of requests) {
            const status = request.dataset.therapyStatus;
            const matches = !control.value || control.value === status ||
                (control.value === 'paid_waiting' && status === 'approved' && request.dataset.paid === 'true') ||
                (control.value === 'closed' && ['completed', 'cancelled', 'no_show'].includes(status));
            request.hidden = !matches;
            if (matches) shown++;
        }
        count.hidden = false;
        count.textContent = shown === 1 ? '1 request shown' : shown + ' requests shown';
    });
    function openLinkedRequest() {
        const request = document.getElementById(location.hash.slice(1));
        if (!requests.includes(request)) return;
        if (request.hidden) {
            control.value = '';
            control.dispatchEvent(new Event('change'));
        }
        request.open = true;
    }
    openLinkedRequest();
    window.addEventListener('hashchange', openLinkedRequest);
}
