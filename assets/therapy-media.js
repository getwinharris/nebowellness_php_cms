(() => {
    const fallbackPath = '/assets/images/nebo-programs.png';
    const fallbackUrl = new URL(fallbackPath, document.baseURI).href;
    for (const image of document.querySelectorAll('[data-therapy-image]')) {
        function showFallback() {
            if (image.dataset.fallbackTried === 'true'
                || image.src === fallbackUrl) {
                image.hidden = true;
                return;
            }
            image.dataset.fallbackTried = 'true';
            image.src = fallbackPath;
        }

        image.addEventListener('error', showFallback);
        if (image.complete && image.naturalWidth === 0) showFallback();
    }
})();
