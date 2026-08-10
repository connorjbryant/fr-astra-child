<?php
/**
 * Flex Rock child-theme performance optimizations.
 *
 * Contains:
 * - Elementor font cleanup
 * - WooLentor CSS suppression
 * - Resource hints
 * - LCP image priority
 * - Script defer strategy
 * - Critical script handling
 * - Slick / MediaElement asset handling
 * - Frontend asset cleanup
 * - Slick layout stabilization
 * - Separate WordPress block assets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * =========================================
 * Request Helpers
 * =========================================
 */

/**
 * Check common frontend request types.
 *
 * @param string $type Request type.
 * @return bool
 */
function fr_is_request( $type ) {

    switch ( $type ) {

        case 'cartlike':
            return (
                function_exists( 'is_cart' )
                && (
                    is_cart()
                    || is_checkout()
                    || is_account_page()
                )
            );

        case 'product':
            return (
                function_exists( 'is_product' )
                && is_product()
            );
    }

    return false;
}


/*
 * =========================================
 * Elementor Font Cleanup
 * =========================================
 */

/**
 * Remove Elementor-generated local font styles that
 * are not needed by the child theme.
 */
function fr_remove_elementor_font_styles() {

    if (
        is_admin()
        || is_customize_preview()
    ) {
        return;
    }


    /*
     * Known Elementor font handles.
     */
    $handles = array(
        'elementor-gf-local-prompt',
        'elementor-gf-local-roboto',
        'elementor-gf-local-robotoslab',
    );


    foreach ( $handles as $handle ) {

        wp_dequeue_style( $handle );
        wp_deregister_style( $handle );
    }


    /*
     * Fallback:
     * catch dynamically generated Elementor font handles.
     */
    global $wp_styles;

    if (
        empty( $wp_styles )
        || empty( $wp_styles->registered )
    ) {
        return;
    }


    foreach (
        $wp_styles->registered
        as $handle => $style
    ) {

        $src = $style->src ?? '';


        if (
            ! $src
            || strpos(
                $src,
                '/uploads/elementor/google-fonts/css/'
            ) === false
        ) {
            continue;
        }


        $is_target_font =
            preg_match(
                '~/prompt(?:\.min)?\.css~i',
                $src
            )
            || preg_match(
                '~/roboto(?:\.min)?\.css~i',
                $src
            )
            || preg_match(
                '~/robotoslab(?:\.min)?\.css~i',
                $src
            );


        if ( $is_target_font ) {

            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
    }
}


add_action(
    'wp_enqueue_scripts',
    'fr_remove_elementor_font_styles',
    999
);


/**
 * Run the Elementor font cleanup one final time
 * immediately before styles are printed.
 */
add_action(
    'wp_print_styles',
    'fr_remove_elementor_font_styles',
    999
);


/**
 * Last-resort protection against Elementor font
 * styles that survive the dequeue process.
 */
function fr_filter_elementor_font_style_tag(
    $html,
    $handle,
    $href
) {

    $known_handles = array(
        'elementor-gf-local-prompt',
        'elementor-gf-local-roboto',
        'elementor-gf-local-robotoslab',
    );


    if (
        in_array(
            $handle,
            $known_handles,
            true
        )
    ) {
        return '';
    }


    if (
        strpos(
            $href,
            '/uploads/elementor/google-fonts/css/'
        ) !== false
    ) {

        $is_target_font =
            strpos(
                $href,
                'prompt.css'
            ) !== false
            || strpos(
                $href,
                'roboto.css'
            ) !== false
            || strpos(
                $href,
                'robotoslab.css'
            ) !== false;


        if ( $is_target_font ) {
            return '';
        }
    }


    return $html;
}


add_filter(
    'style_loader_tag',
    'fr_filter_elementor_font_style_tag',
    9999,
    3
);


/*
 * =========================================
 * WooLentor CSS Cleanup
 * =========================================
 */

/**
 * Prevent WooLentor's large widget stylesheet from
 * loading on pages where its widgets are not needed.
 */
function fr_filter_woolentor_styles(
    $html,
    $handle,
    $href
) {

    $is_woolentor =
        $handle === 'woolentor-widgets'
        || strpos(
            $href,
            'woolentor-widgets.css'
        ) !== false;


    $should_remove =
        is_front_page()
        || is_page(
            array(
                'parts-search',
                'why-us',
                'blogs',
                'contact-us',
            )
        );


    if (
        $is_woolentor
        && $should_remove
    ) {
        return '';
    }


    return $html;
}


add_filter(
    'style_loader_tag',
    'fr_filter_woolentor_styles',
    10,
    3
);


/*
 * =========================================
 * Resource Hints
 * =========================================
 */

/**
 * Keep frontend preconnects limited to the important
 * third-party origins.
 */
function fr_resource_hints(
    $urls,
    $relation_type
) {

    if ( 'preconnect' !== $relation_type ) {
        return $urls;
    }


    $origins = array(
        'https://www.googletagmanager.com',
        'https://scripts.clarity.ms',
        'https://googleads.g.doubleclick.net',
        'https://www.youtube.com',
    );


    $output = array();


    foreach (
        array_unique( $origins )
        as $origin
    ) {

        $output[] = array(
            'href'        => $origin,
            'crossorigin' => 'anonymous',
        );
    }


    return $output;
}


add_filter(
    'wp_resource_hints',
    'fr_resource_hints',
    20,
    2
);


/*
 * =========================================
 * LCP Image Priority
 * =========================================
 */

/**
 * Give the first WordPress attachment image on important
 * landing pages a high fetch priority.
 */
function fr_prioritize_lcp_image(
    $attr,
    $attachment,
    $size
) {

    static $did_lcp = false;


    if ( $did_lcp ) {
        return $attr;
    }


    $is_shop =
        function_exists( 'is_shop' )
        && is_shop();


    if (
        is_front_page()
        || is_home()
        || $is_shop
    ) {

        $attr['fetchpriority'] = 'high';
        $attr['loading']       = 'eager';
        $attr['decoding']      = 'async';

        $did_lcp = true;
    }


    return $attr;
}


add_filter(
    'wp_get_attachment_image_attributes',
    'fr_prioritize_lcp_image',
    10,
    3
);


/*
 * =========================================
 * Script Defer Strategy
 * =========================================
 */

/**
 * Defer non-critical frontend scripts.
 */
function fr_defer_noncritical_scripts(
    $tag,
    $handle,
    $src
) {

    /*
     * Leave the inventory adjustment tool alone.
     */
    if (
        is_page( 'inventory-adjust' )
        || is_page( 6645 )
    ) {

        $tag = str_replace(
            array(
                ' defer ',
                ' async ',
            ),
            ' ',
            $tag
        );


        $tag = str_replace(
            array(
                ' defer>',
                ' async>',
            ),
            '>',
            $tag
        );


        return $tag;
    }


    if ( is_admin() ) {
        return $tag;
    }


    /*
     * Scripts that must not be deferred.
     */
    $no_defer = array(

        // jQuery.
        'jquery',
        'jquery-core',
        'jquery-migrate',

        // WordPress.
        'wp-embed',
        'wp-hooks',
        'wp-i18n',
        'wp-polyfill',
        'wp-dom-ready',
        'wp-element',
        'wp-components',
        'wp-api-fetch',
        'wp-escape-html',
        'wp-primitives',

        // Slick.
        'slick',
        'slick-js',
        'slick-carousel',
        'jquery-slick',

        // MediaElement.
        'mediaelement',
        'wp-mediaelement',
        'mejs',

        // Elementor.
        'elementor-frontend',
        'imagesloaded',

        // Flex Rock helper.
        'fr-lite-boot',
    );


    if (
        in_array(
            $handle,
            $no_defer,
            true
        )
    ) {

        $tag = str_replace(
            array(
                ' defer ',
                ' async ',
            ),
            ' ',
            $tag
        );


        $tag = str_replace(
            array(
                ' defer>',
                ' async>',
            ),
            '>',
            $tag
        );


        return $tag;
    }


    /*
     * Add defer to everything else.
     */
    if (
        strpos(
            $tag,
            ' defer'
        ) === false
        && strpos(
            $tag,
            ' async'
        ) === false
    ) {

        $tag = str_replace(
            '<script ',
            '<script defer ',
            $tag
        );
    }


    return $tag;
}


add_filter(
    'script_loader_tag',
    'fr_defer_noncritical_scripts',
    10,
    3
);


/*
 * =========================================
 * Critical Script Placement
 * =========================================
 */

/**
 * Keep scripts required early by Elementor/Slick/MediaElement
 * in the document head.
 */
function fr_prepare_critical_scripts(
    $wp_scripts
) {

    if ( is_admin() ) {
        return;
    }


    $head_scripts = array(
        'jquery-core',
        'jquery-migrate',
        'jquery',

        'wp-embed',

        'mediaelement',
        'wp-mediaelement',
        'mejs',

        'slick',
        'slick-js',
        'slick-carousel',
        'jquery-slick',

        'elementor-frontend',
    );


    foreach (
        $head_scripts
        as $handle
    ) {

        if (
            isset(
                $wp_scripts
                    ->registered[ $handle ]
            )
        ) {

            $wp_scripts
                ->registered[ $handle ]
                ->extra['group'] = 0;
        }
    }


    /*
     * Ensure Slick declares jQuery as a dependency.
     */
    $slick_handles = array(
        'slick',
        'slick-js',
        'slick-carousel',
        'jquery-slick',
    );


    foreach (
        $slick_handles
        as $handle
    ) {

        if (
            ! isset(
                $wp_scripts
                    ->registered[ $handle ]
            )
        ) {
            continue;
        }


        $deps = (array)
            $wp_scripts
                ->registered[ $handle ]
                ->deps;


        if (
            ! in_array(
                'jquery',
                $deps,
                true
            )
        ) {

            $deps[] = 'jquery';

            $wp_scripts
                ->registered[ $handle ]
                ->deps = $deps;
        }
    }
}


add_action(
    'wp_default_scripts',
    'fr_prepare_critical_scripts',
    9
);


/*
 * =========================================
 * Slick / MediaElement CSS
 * =========================================
 */

/**
 * Ensure the styles required by sliders and WordPress
 * media players are available.
 */
function fr_enqueue_required_library_styles() {

    if ( is_admin() ) {
        return;
    }


    $media_styles = array(
        'wp-mediaelement',
        'mediaelement',
    );


    foreach (
        $media_styles
        as $handle
    ) {

        if (
            wp_style_is(
                $handle,
                'registered'
            )
        ) {
            wp_enqueue_style( $handle );
        }
    }


    $slick_styles = array(
        'slick',
        'slick-css',
        'slick-theme',
    );


    foreach (
        $slick_styles
        as $handle
    ) {

        if (
            wp_style_is(
                $handle,
                'registered'
            )
        ) {
            wp_enqueue_style( $handle );
        }
    }
}


add_action(
    'wp_enqueue_scripts',
    'fr_enqueue_required_library_styles',
    20
);


/*
 * =========================================
 * Remove Unneeded Frontend Assets
 * =========================================
 */

/**
 * Remove expensive assets on pages where they are unnecessary.
 */
function fr_remove_unused_frontend_assets() {

    if ( is_admin() ) {
        return;
    }


    /*
     * WooCommerce PhotoSwipe is only needed on
     * individual product pages.
     */
    if (
        ! fr_is_request( 'product' )
    ) {

        wp_dequeue_script(
            'photoswipe'
        );

        wp_dequeue_script(
            'photoswipe-ui-default'
        );

        wp_dequeue_style(
            'photoswipe'
        );

        wp_dequeue_style(
            'photoswipe-default-skin'
        );
    }


    /*
     * Google Site Kit dashboard widgets are not
     * needed on the public frontend.
     */
    wp_dequeue_script(
        'googlesitekit-widgets'
    );
}


add_action(
    'wp_enqueue_scripts',
    'fr_remove_unused_frontend_assets',
    99
);


/*
 * =========================================
 * Slick Layout Stabilization
 * =========================================
 */

/**
 * Ask already-initialized Slick sliders to re-measure
 * once the page and Elementor widgets settle.
 */
function fr_register_slick_stabilizer() {

    if ( is_admin() ) {
        return;
    }


    $deps = array(
        'jquery',
    );


    $slick_handles = array(
        'slick',
        'slick-js',
        'slick-carousel',
        'jquery-slick',
    );


    foreach (
        $slick_handles
        as $handle
    ) {

        if (
            wp_script_is(
                $handle,
                'registered'
            )
        ) {
            $deps[] = $handle;
        }
    }


    if (
        wp_script_is(
            'elementor-frontend',
            'registered'
        )
    ) {
        $deps[] = 'elementor-frontend';
    }


    wp_register_script(
        'fr-lite-boot',
        false,
        array_unique( $deps ),
        '1.0.0',
        true
    );


    $boot = <<<'JS'
(function () {

    if (window.__frLiteBoot) {
        return;
    }

    window.__frLiteBoot = true;


    function refreshSlick() {

        if (!window.jQuery) {
            return;
        }


        jQuery(
            '.js-slick, .slick-initialized'
        ).each(function () {

            var $slider =
                jQuery(this);


            if (
                !$slider.hasClass(
                    'slick-initialized'
                )
            ) {
                return;
            }


            try {
                $slider.slick(
                    'setPosition'
                );
            } catch (error) {}


            setTimeout(
                function () {

                    try {
                        $slider.slick(
                            'refresh'
                        );
                    } catch (error) {}

                },
                0
            );
        });
    }


    /*
     * DOM ready.
     */
    if (window.jQuery) {

        jQuery(function () {

            setTimeout(
                refreshSlick,
                0
            );

        });
    }


    /*
     * Full window load.
     */
    window.addEventListener(
        'load',
        function () {

            setTimeout(
                refreshSlick,
                0
            );

        },
        {
            once: true
        }
    );


    /*
     * Elementor widget initialization.
     */
    if (
        window.elementorFrontend
        && window.elementorFrontend.hooks
    ) {

        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/global',
            function () {

                setTimeout(
                    refreshSlick,
                    0
                );

            }
        );
    }

})();
JS;


    wp_add_inline_script(
        'fr-lite-boot',
        $boot
    );


    wp_enqueue_script(
        'fr-lite-boot'
    );
}


add_action(
    'wp_enqueue_scripts',
    'fr_register_slick_stabilizer',
    60
);


/*
 * =========================================
 * WordPress Core Block Assets
 * =========================================
 */

/**
 * Load core block styles only when the corresponding
 * blocks are actually rendered.
 */
add_filter(
    'should_load_separate_core_block_assets',
    '__return_true'
);