(() => {
    // Serialize updates so the shared cart total follows the same order as the server.
    let queue = Promise.resolve();
    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-cart-add],[data-cart-change]');
        const control = form?.closest('[data-cart-control]');
        if (!control || !window.fetch) return;
        event.preventDefault();
        if (control.getAttribute('aria-busy') === 'true') return;

        const cards = [...document.querySelectorAll('[data-cart-control]')].filter(card => card.dataset.slug === control.dataset.slug);
        const buttons = cards.flatMap(card => [...card.querySelectorAll('button:enabled')]);
        const body = new FormData(form);
        cards.forEach(card => card.setAttribute('aria-busy', 'true'));
        buttons.forEach(button => { button.disabled = true; });
        queue = queue.then(async () => {
            try {
                const response = await fetch(form.action, {method: 'POST', body, headers: {Accept: 'application/json'}});
                const data = await response.json();
                if (!response.ok || data.slug !== control.dataset.slug || !Number.isInteger(data.quantity) || !Number.isInteger(data.cart_count)) {
                    throw new Error(data.error || 'Unable to update the cart.');
                }
                for (const card of cards) {
                    card.querySelector('[data-cart-add]').hidden = data.quantity > 0;
                    card.querySelector('[data-cart-stepper]').hidden = data.quantity <= 0;
                    card.querySelector('[data-cart-quantity]').textContent = data.quantity;
                }
                document.querySelectorAll('.cart-count, #mobile-cart-count').forEach(count => { count.textContent = data.cart_count; });
                const tray = document.getElementById('mobile-cart-tray');
                if (tray) tray.hidden = data.cart_count === 0;
                const label = document.getElementById('mobile-cart-label');
                if (label) label.textContent = data.cart_count === 1 ? 'item' : 'items';
            } catch (error) {
                window.showToast(error.message || 'Unable to update the cart.', 'error');
            } finally {
                buttons.forEach(button => { button.disabled = false; });
                cards.forEach(card => card.removeAttribute('aria-busy'));
                if (form.hidden && document.activeElement === document.body) {
                    control.querySelector('[data-cart-stepper]:not([hidden]) button, [data-cart-add]:not([hidden]) button')?.focus({preventScroll: true});
                }
            }
        });
    });
})();
