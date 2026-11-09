
(function ($) {
    'use strict';

    $(function () {

        if (!('IntersectionObserver' in window)) {
            return;
        }

        if (window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        /*
         * =========================================
         * Page 11 - Home
         * =========================================
         */

        if ($('body').hasClass('page-id-11')) {
            var $homeRoot = $('.elementor[data-elementor-id="11"]');
            var $homeItems = $homeRoot.find('.elementor-widget');

            if ($homeItems.length) {
                var homeObserver;

                try {
                    homeObserver = new IntersectionObserver(function (entries, observer) {
                        $.each(entries, function (_, entry) {
                            if (entry.isIntersecting) {
                                $(entry.target).addClass('fr-scroll-reveal-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.12,
                        rootMargin: '0px 0px -4% 0px'
                    });

                    $homeItems.addClass('fr-scroll-reveal-item');

                    $homeItems.each(function () {
                        homeObserver.observe(this);
                    });

                    $('body').addClass('fr-scroll-reveal-ready');

                } catch (error) {
                    if (homeObserver) homeObserver.disconnect();

                    $('body').removeClass('fr-scroll-reveal-ready');
                    $homeItems.removeClass('fr-scroll-reveal-item fr-scroll-reveal-visible');
                }
            }
        }

        /*
         * =========================================
         * Page 384 - About
         * =========================================
         */

        if ($('body').hasClass('page-id-384')) {
            var $aboutRoot = $('.elementor[data-elementor-id="384"]');
            
            var rules = [
                { selector: '.fr-about-hero__content', direction: 'left', delay: 0 },
                { selector: '.fr-about-hero__image', direction: 'right', delay: 1 },
                { selector: '.fr-about-statement__quote', direction: 'up', delay: 0 },
                { selector: '.fr-about-reviews__header', direction: 'up', delay: 0 },
                { selector: '.fr-about-reviews__slider', direction: 'up', delay: 1 }
            ];

            var $aboutItems = $();

            $.each(rules, function (_, rule) {
                var $element = $aboutRoot.find(rule.selector);

                if ($element.length) {
                    $element.attr({
                        'data-fr-scroll-reveal-item': 'true',
                        'data-fr-scroll-reveal-direction': rule.direction,
                        'data-fr-scroll-reveal-delay': rule.delay
                    });

                    $aboutItems = $aboutItems.add($element);
                }
            });

            if ($aboutItems.length) {
                var aboutObserver;

                try {
                    aboutObserver = new IntersectionObserver(function (entries, observer) {
                        $.each(entries, function (_, entry) {
                            if (entry.isIntersecting) {
                                $(entry.target).addClass('fr-scroll-reveal-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.14,
                        rootMargin: '0px 0px -5% 0px'
                    });

                    $aboutItems.each(function () {
                        var rect = this.getBoundingClientRect();

                        if (rect.bottom > 0 && rect.top < window.innerHeight) {
                            $(this).addClass('fr-scroll-reveal-visible');
                        } else {
                            aboutObserver.observe(this);
                        }
                    });

                    $('body').addClass('fr-scroll-reveal-ready-384');

                } catch (error) {
                    if (aboutObserver) aboutObserver.disconnect();

                    $('body').removeClass('fr-scroll-reveal-ready-384');

                    $aboutItems
                        .removeAttr('data-fr-scroll-reveal-item data-fr-scroll-reveal-direction data-fr-scroll-reveal-delay')
                        .removeClass('fr-scroll-reveal-visible');
                }
            }
        }

    });

})(jQuery);