(function ($) {

    $(document).ready(function () {

        /*
         * =========================================
         * Mobile Navigation Layout
         * =========================================
         */

        var $navSquare1 = $('[data-id="4eb77b7"]');
        var $navSquare2 = $('[data-id="65d3d3b"]');
        var $navSquare3 = $('[data-id="dc2ad9d"]');
        var $navSquare4 = $('[data-id="dc2ad9d"]');

        if ($navSquare1.length) {
            $navSquare1.addClass("square-one");
        }

        if ($navSquare2.length) {
            $navSquare2.addClass("square-two");
        }

        if ($navSquare3.length) {
            $navSquare3.addClass("square-three");
        }

        if ($navSquare4.length) {
            $navSquare4.addClass("square-four");
        }


        /*
         * =========================================
         * FRP Holiday Snow
         * =========================================
         */

        function startFrpSnow() {

            if (!$('body').hasClass('has-frp-announce')) {
                return;
            }

            // Respect reduced-motion accessibility preference.
            if (
                window.matchMedia(
                    '(prefers-reduced-motion: reduce)'
                ).matches
            ) {
                return;
            }

            var $banner = $('.frp-announce').first();

            if (!$banner.length) {
                return;
            }

            var $layer = $banner.find('.frp-snow').first();

            if (!$layer.length) {
                return;
            }

            // Prevent double initialization.
            if ($layer.data('frpSnowRunning')) {
                return;
            }

            $layer.data('frpSnowRunning', true);

            var glyphs = ['✵', '✶', '✳', '❄'];
            var flakeCount = 80;

            function spawn() {

                var $flake = $(
                    '<div class="frp-snowflake" aria-hidden="true"></div>'
                );

                $flake.text(
                    glyphs[
                        Math.floor(
                            Math.random() * glyphs.length
                        )
                    ]
                );

                var bannerH = $banner.outerHeight() || 60;

                var startX = Math.random() * 100;
                var startY = (Math.random() * -100) - 40;
                var size = (Math.random() * 1.2) + 0.6;
                var dur = (Math.random() * 6) + 5;
                var delay = Math.random() * 2;
                var rot = (Math.random() * 720) - 360;
                var drift = (Math.random() - 0.5) * 40;
                var fall = bannerH + 180;
                var op = (Math.random() * 0.5) + 0.5;

                $flake.css({
                    left: startX + '%',
                    top: startY + 'px',
                    fontSize: size + 'rem',
                    transform: 'translateX(' + drift + 'px)',
                    animation:
                        'frpSnowFall ' +
                        dur +
                        's linear ' +
                        delay +
                        's forwards'
                });

                $flake.css(
                    '--fall-distance',
                    fall + 'px'
                );

                $flake.css(
                    '--rotation',
                    rot + 'deg'
                );

                $flake.css(
                    '--start-opacity',
                    op
                );

                $layer.append($flake);

                setTimeout(function () {

                    $flake.remove();

                    // Only continue if the snow layer still exists.
                    if (
                        $layer.length &&
                        $.contains(document, $layer[0])
                    ) {
                        spawn();
                    }

                }, (dur + delay) * 1000);
            }

            for (var i = 0; i < flakeCount; i++) {
                spawn();
            }
        }

        startFrpSnow();

        // Header/banner can sometimes be injected late.
        setTimeout(startFrpSnow, 500);
        setTimeout(startFrpSnow, 1500);


        /*
         * =========================================
         * Shop Styling Helpers
         * =========================================
         */

        $(".ht-product-cus-tab")
            .addClass("has-image-flex");

        $(".ht-product-content-inner")
            .addClass("has-fixed-height");

        $(".ht-product-content")
            .addClass("has-shop-space-new");

        $(".ht-product-action")
            .addClass("has-centered-button");

        $(".woolentor-cart")
            .addClass("has-offset-text");

        $(".ht-product-image img")
            .addClass("has-crop");


        /*
         * =========================================
         * Spin Wheel / Tawk
         * =========================================
         */

        var $spinButton = $('#swr-wheel-button');

        function wheelIsOpen() {
            return $('.swr-card').is(':visible');
        }

        function hideTawkWidget() {

            if (
                window.Tawk_API &&
                typeof window.Tawk_API.hideWidget === 'function'
            ) {
                window.Tawk_API.hideWidget();
            }

            $('iframe[src*="tawk.to"]').each(function () {

                $(this)
                    .closest('div')
                    .css('display', 'none');

            });
        }

        function hideFloating() {

            $spinButton.hide();

            hideTawkWidget();
        }

        function showFloating() {

            if (wheelIsOpen()) {
                return;
            }

            $spinButton.show();

            // Tawk intentionally stays hidden.
            hideTawkWidget();
        }

        function moveTawkLeftMobile() {

            if (window.innerWidth > 768) {
                return;
            }

            // Tawk intentionally stays hidden.
            hideTawkWidget();
        }


        /*
         * Watch for Tawk injecting or replacing
         * its iframe.
         */
        var tawkObserver = new MutationObserver(
            function () {

                if (
                    $('iframe[src*="tawk.to"]').length
                ) {
                    hideTawkWidget();
                }

            }
        );

        tawkObserver.observe(
            document.body,
            {
                childList: true,
                subtree: true
            }
        );

        setInterval(function () {

            if (wheelIsOpen()) {

                $('body')
                    .addClass('frp-wheel-open');

                hideFloating();

            } else {

                $('body')
                    .removeClass('frp-wheel-open');

                moveTawkLeftMobile();
            }

        }, 300);


        /*
         * Hide floating buttons while scrolling.
         */
        $(window).on(
            'scroll',
            function () {

                hideFloating();

                clearTimeout(
                    window.frpFloatingTimeout
                );

                window.frpFloatingTimeout =
                    setTimeout(
                        showFloating,
                        1200
                    );
            }
        );


        /*
         * Hide floating buttons while interacting
         * with form controls / vehicle search.
         */
        $(document).on(
            'focus click touchstart',
            'select, input, textarea, .search-by-vehicle *',
            function () {

                hideFloating();

            }
        );


        $(window).on(
            'resize load',
            moveTawkLeftMobile
        );


        /*
         * Hide Tawk immediately.
         */
        hideTawkWidget();

        /*
         * Short temporary fallback while Tawk
         * finishes loading.
         *
         * The MutationObserver above handles
         * subsequent iframe changes.
         */
        var hideInterval =
            setInterval(
                hideTawkWidget,
                100
            );

        setTimeout(
            function () {

                clearInterval(
                    hideInterval
                );

            },
            3000
        );


        /*
         * =========================================
         * Google Search / Google Ads
         * Parts Search Redirect
         * =========================================
         */

        var PARTS_SEARCH_URL =
            '/parts-search/';

        var ONLY_RUN_ON_HOMEPAGE =
            true;

        var DEBUG_LOG =
            false;


        /*
         * Check URL parameters first.
         * Fall back to Google referrer.
         */
        function getSearchQuery() {

            var urlParams =
                new URLSearchParams(
                    window.location.search
                );

            var fromUrl =
                urlParams.get('ref_search') ||
                urlParams.get('utm_term');

            if (fromUrl) {
                return fromUrl;
            }


            var ref =
                document.referrer;

            if (
                !ref ||
                ref.indexOf('google.') === -1
            ) {
                return null;
            }


            try {

                var refUrl =
                    new URL(ref);

                return refUrl
                    .searchParams
                    .get('q');

            } catch (err) {

                return null;

            }
        }


        /*
         * Determine whether the search looks
         * like a vehicle/parts search.
         */
        function looksLikeVehicleIntent(query) {

            if (!query) {
                return false;
            }

            query =
                query
                    .toLowerCase()
                    .trim();


            var hasYear =
                /\b(19|20)\d{2}\b/
                    .test(query);


            var vehicleTerms = [

                'polaris',
                'can am',
                'can-am',
                'rzr',
                'ranger',
                'general',
                'xpedition',

                'maverick',
                'x3',
                'defender',
                'commander',
                'turbo',

                'xp 1000',
                'turbo r',
                'pro r',
                'adv5',
                'xp',
                'northstar',
                'xp5',
                'adv',
                'ev',
                'crew',
                'etx',
                'high lifter',

                'kawasaki',
                'teryx',
                'krx',
                'krx 1000',

                'honda',
                'pioneer',
                'talon',

                'jeep',
                'wrangler',
                'jl',
                'jk',
                'jt',
                'gladiator',
                'rubicon',
                'sahara',
                'sport'

            ];


            var partTerms = [

                'sway bar',
                'bushing',
                'bushings',

                'radius rod',
                'radius rods',

                'tie rod',
                'tie rods',

                'a arm',
                'a-arm',
                'a arms',
                'a-arms',

                'control arm',
                'control arms',

                'ball joint',
                'ball joints',

                'suspension',
                'kit',
                'upgrade',
                'replacement',

                'end link',
                'rod end',
                'center plate',
                'control arm joints',

                'rear',
                'front'

            ];


            var hasVehicleTerm =
                vehicleTerms.some(
                    function (term) {

                        return (
                            query.indexOf(term) !== -1
                        );

                    }
                );


            var hasPartTerm =
                partTerms.some(
                    function (term) {

                        return (
                            query.indexOf(term) !== -1
                        );

                    }
                );


            return (
                (hasYear && hasVehicleTerm) ||
                (hasVehicleTerm && hasPartTerm)
            );
        }


        /*
         * Determine whether the redirect is
         * allowed to run.
         */
        function shouldRunRedirect() {

            if (
                ONLY_RUN_ON_HOMEPAGE &&
                window.location.pathname !== '/' &&
                window.location.pathname !== ''
            ) {
                return false;
            }


            if (
                window.location.pathname
                    .indexOf('/parts-search') !== -1
            ) {
                return false;
            }


            if (
                sessionStorage.getItem(
                    'frp_google_parts_redirect_done'
                ) === '1'
            ) {
                return false;
            }


            return true;
        }


        var query =
            getSearchQuery();


        if (
            shouldRunRedirect() &&
            looksLikeVehicleIntent(query)
        ) {

            sessionStorage.setItem(
                'frp_google_parts_redirect_done',
                '1'
            );


            var destination =
                PARTS_SEARCH_URL +
                '?ref_search=' +
                encodeURIComponent(query);


            if (DEBUG_LOG) {

                console.log(
                    'FRP redirecting to:',
                    destination
                );

            }


            window.location.href =
                destination;
        }


        /*
         * =========================================
         * Google Pay Tracking
         * =========================================
         */

        var frGpayFocusLogged =
            false;


        $(window).on(
            'blur',
            function () {

                if (frGpayFocusLogged) {
                    return;
                }


                var active =
                    document.activeElement;


                if (
                    active &&
                    active.tagName === 'IFRAME' &&
                    active.title &&
                    active.title.indexOf(
                        'Secure express checkout frame'
                    ) !== -1
                ) {

                    frGpayFocusLogged =
                        true;


                    navigator.sendBeacon(
                        '/wp-admin/admin-ajax.php',
                        new URLSearchParams({
                            action:
                                'fr_gpay_clicked',
                            source:
                                'google_pay_iframe_focused'
                        })
                    );
                }
            }
        );


        /*
         * =========================================
         * RRR Notices
         * =========================================
         */

        $('.rrr-notice-marker')
            .each(
                function (index) {

                    /*
                     * Keep first notice.
                     * Hide duplicates.
                     */
                    if (index > 0) {

                        $(this)
                            .closest('li')
                            .hide();

                    }

                }
            );


        /*
         * =========================================
         * GA4 Tracking
         * =========================================
         */

        // Sticky Parts Search button.
        $(document).on(
            'click',
            '.sticky-tab',
            function () {

                if (
                    typeof window.gtag ===
                    'function'
                ) {

                    window.gtag(
                        'event',
                        'sticky_parts_search_click',
                        {
                            event_category:
                                'Navigation',

                            event_label:
                                'Sticky Parts Button'
                        }
                    );

                }

            }
        );


        /*
         * Shop / brand button tracking.
         */
        $(document).on(
            'click',
            '.js-frp-brand-track',
            function () {

                var $link =
                    $(this);

                var href =
                    $link.attr('href') || '';

                var brand =
                    $link.data('brand') || '';

                var clickType =
                    $link.data('click-type') ||
                    'link';


                if (
                    typeof window.gtag ===
                    'function'
                ) {

                    window.gtag(
                        'event',
                        'parts_brand_click',
                        {
                            brand:
                                brand,

                            click_type:
                                clickType,

                            link_url:
                                href,

                            click_from_page:
                                window.location.pathname,

                            page_title:
                                document.title
                        }
                    );

                }

            }
        );

    });

})(jQuery);