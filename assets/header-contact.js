(() => {
    const contact = document.querySelector('.header-contact-details');
    if (!contact) return;

    document.addEventListener('click', event => {
        if (contact.open && !contact.contains(event.target)) contact.open = false;
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || !contact.open) return;
        const returnFocus = contact.contains(document.activeElement);
        contact.open = false;
        if (returnFocus) contact.querySelector('summary').focus();
    });
})();
