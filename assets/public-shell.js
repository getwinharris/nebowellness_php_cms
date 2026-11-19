(() => {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', {scope: '/'}).catch(() => {});
    }

    document.documentElement.classList.add('motion-ready');
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('.reveal,.panel,.product-card,.consultant-card').forEach(element =>
            element.classList.add('revealed'));
        return;
    }
    const header = document.getElementById('site-header');
    if (header) {
        const marker = document.createElement('div');
        marker.className = 'header-scroll-marker';
        marker.setAttribute('aria-hidden', 'true');
        document.body.prepend(marker);
        new IntersectionObserver(([entry]) => {
            header.classList.toggle('scrolled', !entry.isIntersecting);
        }).observe(marker);
    }
    const reveals = new IntersectionObserver(entries => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            entry.target.classList.add('revealed');
            reveals.unobserve(entry.target);
        }
    }, {threshold: 0.1, rootMargin: '0px 0px -50px 0px'});
    document.querySelectorAll('.reveal,.panel,.product-card,.consultant-card').forEach(element => reveals.observe(element));
})();
