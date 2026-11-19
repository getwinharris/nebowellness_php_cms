(() => {
    const trigger = document.querySelector('[data-page-finder]');
    const nav = trigger && document.getElementById(trigger.dataset.pageFinder);
    if (!nav || typeof HTMLDialogElement === 'undefined' || !HTMLDialogElement.prototype.showModal) return;

    const pages = new Map();
    for (const link of nav.querySelectorAll('a[href]')) {
        const url = new URL(link.href);
        const label = link.textContent.replace(/\s*[▾↗]\s*$/, '').trim();
        if (url.origin !== location.origin || url.pathname === '/logout' || !label) continue;
        if (!pages.has(url.href)) pages.set(url.href, label);
    }
    if (!pages.size) return;

    const dialog = document.createElement('dialog');
    dialog.className = 'page-finder';
    dialog.setAttribute('aria-labelledby', 'page-finder-title');
    const header = document.createElement('div');
    header.className = 'page-finder__header';
    const title = document.createElement('h2');
    title.id = 'page-finder-title';
    title.textContent = trigger.getAttribute('aria-label');
    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn btn-outline btn-sm';
    close.textContent = 'Close';
    header.append(title, close);
    const input = document.createElement('input');
    input.type = 'search';
    input.className = 'page-finder__input';
    input.placeholder = 'Type a destination';
    input.setAttribute('aria-label', 'Filter destinations');
    const status = document.createElement('p');
    status.className = 'page-finder__status';
    status.setAttribute('role', 'status');
    const list = document.createElement('ul');
    list.className = 'page-finder__results';
    const entries = Array.from(pages, ([href, label]) => {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = href;
        link.textContent = label;
        link.addEventListener('click', () => dialog.close());
        item.append(link);
        list.append(item);
        return { item, link, search: label.toLocaleLowerCase() };
    });
    dialog.append(header, input, status, list);
    document.body.append(dialog);

    function filter() {
        const words = input.value.trim().toLocaleLowerCase().split(/\s+/);
        let visible = 0;
        for (const entry of entries) {
            entry.item.hidden = !words.every(word => entry.search.includes(word));
            if (!entry.item.hidden) visible++;
        }
        status.textContent = visible === 0 ? 'No matching destinations.' : `${visible} destination${visible === 1 ? '' : 's'}`;
    }

    trigger.addEventListener('click', () => {
        input.value = '';
        filter();
        dialog.showModal();
        input.focus();
    });
    close.addEventListener('click', () => dialog.close());
    input.addEventListener('input', filter);
    input.addEventListener('keydown', event => {
        if ((event.key === 'Enter' && !event.isComposing) || event.key === 'ArrowDown') {
            const first = entries.find(entry => !entry.item.hidden);
            if (!first) return;
            event.preventDefault();
            if (event.key === 'Enter') first.link.click();
            else first.link.focus();
        }
    });
    trigger.hidden = false;
})();
