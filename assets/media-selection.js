document.querySelectorAll('[data-media-selection]').forEach((form) => {
    const choices = Array.from(form.querySelectorAll('input[name="media_ids[]"]'));
    const confirmation = form.elements.namedItem('confirm_change');
    if (!choices.length || !confirmation) return;
    const count = form.querySelector('[data-selection-count]');
    const button = form.querySelector('button[type="submit"]');
    const filterLabel = form.querySelector('[data-upload-filter]');
    const filter = filterLabel.querySelector('input');
    const protectedTiles = Array.from(form.querySelectorAll('.admin-media-tile')).filter(
        (tile) => !tile.querySelector('input[name="media_ids[]"]')
    );
    filterLabel.hidden = protectedTiles.length === 0;
    function update() {
        protectedTiles.forEach((tile) => { tile.hidden = filter.checked; });
        const selected = choices.filter((choice) => choice.checked).length;
        count.textContent = selected > 50 ? 'Select up to 50 uploads.' : selected + ' selected';
        button.disabled = selected === 0 || selected > 50 || !confirmation.checked;
    }
    form.addEventListener('change', (event) => {
        if (choices.includes(event.target)) confirmation.checked = false;
        update();
    });
    window.addEventListener('pageshow', update);
    update();
});
