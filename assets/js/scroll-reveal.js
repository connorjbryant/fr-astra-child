(function ($) {
    'use strict';

    $(function () {
        var $pageRoot = $('.elementor[data-elementor-id="11"]');

        if (!$pageRoot.length || !('IntersectionObserver' in window)) {
            return;
        }

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        var $items = $pageRoot.children('.elementor-section')
            .not('.elementor-inner-section, .elementor-element-0c138d2');

        if (!$items.length) {
            return;
        }

        var observer;

        try {
            observer = new IntersectionObserver(function (entries, activeObserver) {
                $.each(entries, function (_, entry) {
                    if (entry.isIntersecting) {
                        $(entry.target).addClass('fr-scroll-reveal-visible');
                        activeObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -4% 0px'
            });

            $items.addClass('fr-scroll-reveal-item');
            $items.each(function () {
                observer.observe(this);
            });

            $('body').addClass('fr-scroll-reveal-ready');
        } catch (error) {
            if (observer) {
                observer.disconnect();
            }
            $('body').removeClass('fr-scroll-reveal-ready');
            $items.removeClass('fr-scroll-reveal-item fr-scroll-reveal-visible');
        }
    });
})(jQuery);
