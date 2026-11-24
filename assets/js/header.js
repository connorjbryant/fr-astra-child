jQuery(function ($) {

    const $header = $('.masthead');
    const $nav = $header.find('#primary-navigation');
    const $toggle = $header.find('.menu-toggle');
    const $searchToggle = $header.find('.mobile-search-toggle');
    const $searchPanel = $header.find('.header-search-panel');

    const mobile = window.matchMedia('(max-width: 980px)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let submenuIndex = 0;

    function duration(ms) {
        return reducedMotion.matches ? 0 : ms;
    }


    /* Create dropdown chevrons */

    $nav.find('.menu-item-has-children').each(function () {

        const $item = $(this);
        const $link = $item.children('a').first();
        const $submenu = $item.children('.sub-menu');

        if (!$submenu.length || $item.children('.submenu-toggle').length) {
            return;
        }

        const submenuId = 'frp-submenu-' + (++submenuIndex);

        $submenu.attr('id', submenuId);

        $('<button>', {
            type: 'button',
            class: 'submenu-toggle',
            'aria-label': 'Toggle ' + $link.text().trim() + ' submenu',
            'aria-expanded': 'false',
            'aria-controls': submenuId
        }).insertAfter($link);

    });


    /* Reset dropdowns */

    function resetSubmenus() {

        $nav.find('.submenu-open')
            .removeClass('submenu-open');

        $nav.find('.submenu-toggle')
            .attr('aria-expanded', 'false');

        $nav.find('.sub-menu')
            .stop(true, true)
            .removeAttr('style');

    }


    /* Open or close mobile navigation */

    function setMenu(open) {

        $toggle
            .toggleClass('is-open', open)
            .attr('aria-expanded', String(open))
            .attr('aria-label', open ? 'Close menu' : 'Open menu');

        $nav.toggleClass('is-open', open);

        if (open) {

            $nav.stop(true, true).slideDown(duration(220));

        } else {

            $nav.stop(true, true).slideUp(duration(220), function () {
                resetSubmenus();
            });

        }

    }


    /* Open or close mobile search */

    function setSearch(open) {

        $searchToggle
            .toggleClass('is-open', open)
            .attr('aria-expanded', String(open))
            .attr('aria-label', open ? 'Close search' : 'Search products');

        if (open) {

            $searchPanel.stop(true, true).slideDown(duration(200), function () {

                $searchPanel
                    .find('.dgwt-wcas-search-input')
                    .trigger('focus');

            });

        } else {

            $searchPanel
                .find('.dgwt-wcas-search-input')
                .trigger('blur');

            $searchPanel.stop(true, true).slideUp(duration(200));

        }

    }


    /* Hamburger click */

    $toggle.on('click', function () {

        if (!mobile.matches) {
            return;
        }

        const open = !$nav.hasClass('is-open');

        if (open && $searchToggle.hasClass('is-open')) {
            setSearch(false);
        }

        setMenu(open);

    });


    /* Mobile search click */

    $searchToggle.on('click', function () {

        if (!mobile.matches) {
            return;
        }

        const open = !$(this).hasClass('is-open');

        if (open && $nav.hasClass('is-open')) {
            setMenu(false);
        }

        setSearch(open);

    });


    /* Dropdown click */

    $nav.on('click', '.submenu-toggle', function () {

        const $button = $(this);
        const $item = $button.parent('li');
        const $submenu = $item.children('.sub-menu');

        const open = !$item.hasClass('submenu-open');

        $item.toggleClass('submenu-open', open);

        $button.attr('aria-expanded', String(open));

        if (mobile.matches) {

            if (open) {

                $submenu.stop(true, true).slideDown(duration(200));

            } else {

                $submenu.stop(true, true).slideUp(duration(200));

            }

        }

        if (!open) {

            $submenu.find('.submenu-open')
                .removeClass('submenu-open');

            $submenu.find('.submenu-toggle')
                .attr('aria-expanded', 'false');

            $submenu.find('.sub-menu')
                .stop(true, true)
                .removeAttr('style');

        }

    });

    /* Desktop dropdown hover */

    $nav.on('mouseenter', '.menu-item-has-children', function () {

        if (mobile.matches) {
            return;
        }

        $(this)
            .addClass('submenu-open')
            .children('.submenu-toggle')
            .attr('aria-expanded', 'true');

    });

    $nav.on('mouseleave', '.menu-item-has-children', function () {

        if (mobile.matches) {
            return;
        }

        $(this)
            .removeClass('submenu-open')
            .children('.submenu-toggle')
            .attr('aria-expanded', 'false');

        $(this).find('.submenu-open')
            .removeClass('submenu-open');

        $(this).find('.submenu-toggle')
            .attr('aria-expanded', 'false');

    });


    /* Outside click */

    $(document).on('click', function (e) {

        const $target = $(e.target);

        if (mobile.matches) {

            /* Close search when clicking outside */

            if (
                $searchToggle.hasClass('is-open') &&
                !$target.closest(
                    '.product-search, .dgwt-wcas-suggestions-wrapp'
                ).length
            ) {

                setSearch(false);

            }


            /* Close navigation when clicking outside */

            if (
                $nav.hasClass('is-open') &&
                !$target.closest(
                    '#primary-navigation, .menu-toggle'
                ).length
            ) {

                setMenu(false);

            }

        } else {

            /* Close desktop dropdowns */

            if (!$target.closest($nav).length) {
                resetSubmenus();
            }

        }

    });


    /* Escape key */

    $(document).on('keydown', function (e) {

        if (e.key !== 'Escape') {
            return;
        }

        if (mobile.matches) {

            if ($searchToggle.hasClass('is-open')) {

                setSearch(false);
                $searchToggle.trigger('focus');

            } else if ($nav.hasClass('is-open')) {

                setMenu(false);
                $toggle.trigger('focus');

            }

        } else {

            const $button = $nav
                .find('.submenu-open > .submenu-toggle')
                .first();

            resetSubmenus();

            if ($button.length) {
                $button.trigger('focus');
            }

        }

    });


    /* Reset when crossing the mobile breakpoint */

    let wasMobile = mobile.matches;

    $(window).on('resize', function () {

        if (mobile.matches === wasMobile) {
            return;
        }

        wasMobile = mobile.matches;

        $nav.stop(true, true)
            .removeClass('is-open')
            .removeAttr('style');

        $toggle
            .removeClass('is-open')
            .attr('aria-expanded', 'false')
            .attr('aria-label', 'Open menu');

        $searchPanel
            .stop(true, true)
            .removeAttr('style');

        $searchToggle
            .removeClass('is-open')
            .attr('aria-expanded', 'false')
            .attr('aria-label', 'Search products');

        resetSubmenus();

    });

});