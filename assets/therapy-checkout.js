'use strict';
const therapyCheckout = document.querySelector('[data-therapy-checkout]');
if (therapyCheckout && typeof window.Razorpay === 'function') {
    const pay = therapyCheckout.querySelector('[data-pay]');
    const status = therapyCheckout.querySelector('[data-status]');
    pay.hidden = false;
    status.textContent = '';
    pay.addEventListener('click', () => {
        pay.disabled = true;
        const retry = (message) => {
            pay.disabled = false;
            status.textContent = message;
            pay.focus();
        };
        try {
            const checkout = new window.Razorpay({
                key: therapyCheckout.dataset.key,
                order_id: therapyCheckout.dataset.order,
                amount: Number(therapyCheckout.dataset.amount),
                currency: 'INR',
                name: 'Nebo Lifestyle Clinic',
                description: 'Session fee',
                handler(response) {
                    for (const key of ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature']) {
                        therapyCheckout.elements.namedItem(key).value = response[key] || '';
                    }
                    status.textContent = 'Verifying payment…';
                    therapyCheckout.submit();
                },
                modal: { ondismiss: () => retry('Checkout closed. Your request is saved.') }
            });
            checkout.open();
        } catch {
            retry('Checkout could not open. Please retry.');
        }
    });
}
