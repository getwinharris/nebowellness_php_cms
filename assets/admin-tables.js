(() => {
    for (const wrap of document.querySelectorAll('.table-wrap')) {
        const table = wrap.querySelector('table');
        if (!table) continue;

        const hint = document.createElement('p');
        hint.className = 'admin-table-hint';
        hint.textContent = 'Scroll sideways to see more columns.';
        hint.hidden = true;
        wrap.before(hint);
        const updateOverflow = () => {
            const overflow = wrap.scrollWidth > wrap.clientWidth + 1;
            hint.hidden = !overflow;
            if (overflow) wrap.tabIndex = 0;
            else if (document.activeElement !== wrap) wrap.removeAttribute('tabindex');
        };
        if ('ResizeObserver' in window) new ResizeObserver(updateOverflow).observe(wrap);
        else window.addEventListener('resize', updateOverflow);
        wrap.addEventListener('blur', updateOverflow);
        updateOverflow();

        const rows = Array.from(table.tBodies).flatMap(body => Array.from(body.rows))
            .filter(row => !row.hidden && row.cells.length > 1);
        if (rows.length < 2) continue;
        const toolbar = document.createElement('div');
        toolbar.className = 'admin-table-tools';
        const label = document.createElement('label');
        label.textContent = 'Filter rows';
        const input = document.createElement('input');
        input.type = 'search';
        label.append(input);
        const status = document.createElement('p');
        status.setAttribute('role', 'status');
        toolbar.append(label, status);
        hint.before(toolbar);
        const updateRows = () => {
            const query = input.value.toLocaleLowerCase().trim();
            let count = 0;
            for (const row of rows) {
                row.classList.toggle('admin-table-row--filtered', !row.textContent.toLocaleLowerCase().includes(query));
                if (!row.classList.contains('admin-table-row--filtered')) count++;
            }
            status.textContent = count === 0 ? 'No matching rows' : `${count} of ${rows.length} rows`;
            updateOverflow();
        };
        input.addEventListener('input', updateRows);
        updateRows();
    }
})();
