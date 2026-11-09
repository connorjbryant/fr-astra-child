
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

        
        /*
        * =========================================
        * Blog - /blog/
        * =========================================
        */

        if ($('body').hasClass('page-id-3542')) {

            var $blogRoot = $('.elementor[data-elementor-id="3542"]');

            // Find the post grid dynamically.
            var $blogCards = $blogRoot.find('.elementor-post, .e-loop-item');
            var $blogGrids = $blogCards.parent();

            // Find the heading before the first post grid.
            var $blogHeading = $();

            if ($blogGrids.length) {
                $blogHeading = $blogRoot
                    .find('.elementor-heading-title')
                    .filter(function () {
                        return $(this).offset().top <
                            $blogGrids.first().offset().top;
                    })
                    .last();
            }

            var $blogItems = $blogHeading.add($blogCards);
            var blogObserver;

            if ($blogItems.length) {
                try {
                    blogObserver = new IntersectionObserver(function (entries, observer) {
                        $.each(entries, function (_, entry) {
                            if (entry.isIntersecting) {
                                $(entry.target).addClass('fr-blog-reveal-visible');
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        threshold: 0.12,
                        rootMargin: '0px 0px -4% 0px'
                    });

                    // Heading animation.
                    $blogHeading.attr({
                        'data-fr-blog-reveal': 'true',
                        'data-fr-blog-direction': 'up',
                        'data-fr-blog-delay': '0'
                    });

                    // Blog card animations.
                    $blogGrids.each(function () {
                        var $grid = $(this);
                        var gridRect = this.getBoundingClientRect();

                        $grid.find('.elementor-post').each(function (index) {
                            var rect = this.getBoundingClientRect();
                            var direction = 'up';

                            if (window.innerWidth > 767) {
                                var cardCenter = rect.left + rect.width / 2;
                                var gridCenter = gridRect.left + gridRect.width / 2;

                                direction = cardCenter < gridCenter ? 'left' : 'right';
                            }

                            $(this).attr({
                                'data-fr-blog-reveal': 'true',
                                'data-fr-blog-direction': direction,
                                'data-fr-blog-delay': index % 2 ? '1' : '0'
                            });
                        });
                    });

                    $blogItems.each(function () {
                        var rect = this.getBoundingClientRect();

                        if (rect.bottom > 0 && rect.top < window.innerHeight) {
                            $(this).addClass('fr-blog-reveal-visible');
                        } else {
                            blogObserver.observe(this);
                        }
                    });

                    $('body').addClass('fr-blog-reveal-ready');

                } catch (error) {
                    if (blogObserver) blogObserver.disconnect();

                    $('body').removeClass('fr-blog-reveal-ready');

                    $blogItems
                        .removeAttr('data-fr-blog-reveal data-fr-blog-direction data-fr-blog-delay')
                        .removeClass('fr-blog-reveal-visible');
                }
            }
        }


    });

})(jQuery);