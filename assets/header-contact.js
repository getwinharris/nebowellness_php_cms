(() => {
    const groups = Array.from(document.querySelectorAll('.header-contact-details'));
    if (!groups.length) return;
    groups.forEach(group => group.addEventListener('toggle', () => {
        if (group.open) groups.forEach(other => { if (other !== group) other.open = false; });
    }));
    document.addEventListener('click', event => groups.forEach(group => {
        if (group.open && !group.contains(event.target)) group.open = false;
    }));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        groups.forEach(group => {
            if (!group.open) return;
            const returnFocus = group.contains(document.activeElement);
            group.open = false;
            if (returnFocus) group.querySelector('summary').focus();
        });
    });
})();
