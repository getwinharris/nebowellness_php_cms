/* ============================================================================
   Nebo home: "The question that follows" signature move
   ----------------------------------------------------------------------------
   Page-local enhancement for views/public/home.php. The chapter is authored as
   four real links, so with JavaScript off every answer still reaches the contact
   page with ?focus=. This file only upgrades the choice: it marks the answer,
   remembers it for the session, and carries it into the closing CTA card.
   The scroll-craft engine is not touched and no data-sc-* name is reused here.
   ========================================================================== */
(function () {
    'use strict';

    var STORE_KEY = 'nebo.focus';
    var FOCUS = {
        gut: {
            name: 'Gut Health',
            cta: 'Book a gut-health consultation',
            href: '/contact?focus=gut#contact-form'
        },
        metabolic: {
            name: 'Metabolic Diseases',
            cta: 'Book a metabolic-health consultation',
            href: '/contact?focus=metabolic#contact-form'
        },
        lifestyle: {
            name: 'Lifestyle Transformation',
            cta: 'Book a lifestyle consultation',
            href: '/contact?focus=lifestyle#contact-form'
        },
        fertility: {
            name: 'Maternal & Fertility Wellness',
            cta: 'Book a fertility consultation',
            href: '/contact?focus=fertility#contact-form'
        }
    };

    var question = document.querySelector('[data-nebo-question]');
    if (!question) return;

    var answers = Array.prototype.slice.call(question.querySelectorAll('[data-nebo-answer]'));
    var cta = document.querySelector('[data-nebo-cta]');
    var ctaLabel = cta ? cta.querySelector('[data-nebo-cta-label]') : null;
    var chip = document.querySelector('[data-nebo-focus-chip]');

    function storedFocus() {
        try {
            var value = window.sessionStorage.getItem(STORE_KEY);
            return Object.prototype.hasOwnProperty.call(FOCUS, value) ? value : '';
        } catch (error) {
            /* Storage can be blocked in private windows; the page still works. */
            return '';
        }
    }

    function rememberFocus(key) {
        try {
            window.sessionStorage.setItem(STORE_KEY, key);
        } catch (error) {
            /* Without storage the choice lives in this page view only. */
        }
    }

    function applyFocus(key) {
        var choice = FOCUS[key];
        if (!choice) return;
        answers.forEach(function (answer) {
            var selected = answer.getAttribute('data-nebo-answer') === key;
            if (selected) {
                answer.setAttribute('aria-current', 'true');
            } else {
                answer.removeAttribute('aria-current');
            }
            var mark = answer.querySelector('[data-nebo-answer-mark]');
            if (mark) mark.textContent = selected ? '\u2713' : '\u2192';
        });
        if (ctaLabel) ctaLabel.textContent = choice.cta;
        if (cta) cta.setAttribute('href', choice.href);
        if (chip) chip.textContent = 'Your focus: ' + choice.name;
    }

    answers.forEach(function (answer) {
        answer.addEventListener('click', function (event) {
            var key = answer.getAttribute('data-nebo-answer');
            if (!Object.prototype.hasOwnProperty.call(FOCUS, key)) return;
            // A modified click still opens the real link in a new tab or window,
            // where the contact page prefill reads ?focus= on its own.
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            applyFocus(key);
            rememberFocus(key);
        });
    });

    var restored = storedFocus();
    if (restored) applyFocus(restored);
})();
