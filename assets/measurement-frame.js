(() => {
    'use strict';
    // The parent embeds this document with an opaque origin and without a referrer.
    // Accept the first message only from the embedding clinic origin.
    function receive(event) {
        if (event.source !== parent || parent === window || event.origin !== new URL(location.href).origin) return;
        const data = event.data || {};
        if (!['home', 'about'].includes(data.page)) return;
        window.removeEventListener('message', receive);
        const ga = /^G-[A-Z0-9]{4,20}$/.test(data.analytics) ? data.analytics : '';
        const ads = /^AW-[0-9]{4,20}$/.test(data.ads) ? data.ads : '';
        const pixel = /^[0-9]{5,24}$/.test(data.pixel) ? data.pixel : '';
        const url = event.origin + (data.page === 'home' ? '/' : '/about');
        if (ga || ads) {
            window.dataLayer = [];
            function gtag() { window.dataLayer.push(arguments); }
            gtag('consent', 'default', {
                analytics_storage: ga ? 'granted' : 'denied', ad_storage: ads ? 'granted' : 'denied',
                ad_user_data: 'denied', ad_personalization: 'denied'
            });
            gtag('js', new Date());
            const options = {page_location: url, page_referrer: '', page_title: data.page === 'home' ? 'Home' : 'About',
                allow_google_signals: false, allow_ad_personalization_signals: false};
            if (ga) {
                const analyticsOptions = {...options};
                if (['google', 'facebook', 'instagram'].includes(data.source)) analyticsOptions.campaign_source = data.source;
                gtag('config', ga, analyticsOptions);
            }
            if (ads) gtag('config', ads, options);
            const script = document.createElement('script');
            script.async = true;
            script.referrerPolicy = 'no-referrer';
            script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga || ads);
            document.head.append(script);
        }
        if (pixel) {
            // Send only the standard PageView image; loading the Meta SDK would
            // enable its automatic event discovery and advanced matching features.
            const image = new Image(1, 1);
            image.referrerPolicy = 'no-referrer';
            image.src = 'https://www.facebook.com/tr?' + new URLSearchParams({id: pixel, ev: 'PageView', dl: url, noscript: '1'});
            document.body.append(image);
        }
    }
    window.addEventListener('message', receive);
})();
