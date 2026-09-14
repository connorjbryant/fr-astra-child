<?php

if ( ! defined ( 'ABSPATH' )){
    exit;
}

/**
 * Capture YMM search parameters and store them in cookies for 24 hours.
 */
add_action('init', 'fr_capture_ymm_parameters');

function fr_capture_ymm_parameters() {

    $cookie_options = array(
        'expires'  => time() + DAY_IN_SECONDS,
        'path'     => COOKIEPATH ? COOKIEPATH : '/',
        'domain'   => COOKIE_DOMAIN,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    );

    if (isset($_GET['_year'])) {
        $year = sanitize_text_field(wp_unslash($_GET['_year']));

        setcookie(
            'fr_ymm_year',
            $year,
            $cookie_options
        );

        // Make available during the current request too.
        $_COOKIE['fr_ymm_year'] = $year;
    }

    if (isset($_GET['_make'])) {
        $make = sanitize_text_field(wp_unslash($_GET['_make']));

        setcookie(
            'fr_ymm_make',
            $make,
            $cookie_options
        );

        $_COOKIE['fr_ymm_make'] = $make;
    }

    if (isset($_GET['_model'])) {
        $model = sanitize_text_field(wp_unslash($_GET['_model']));

        setcookie(
            'fr_ymm_model',
            $model,
            $cookie_options
        );

        $_COOKIE['fr_ymm_model'] = $model;
    }
}


/**
 * Add remembered YMM selection to cart items.
 */
add_filter('woocommerce_add_cart_item_data', 'fr_add_ymm_to_cart_item', 10, 3);

function fr_add_ymm_to_cart_item($cart_item_data, $product_id, $variation_id) {

    if (isset($_COOKIE['fr_ymm_year'])) {
        $cart_item_data['fr_ymm_year'] =
            sanitize_text_field(wp_unslash($_COOKIE['fr_ymm_year']));
    }

    if (isset($_COOKIE['fr_ymm_make'])) {
        $cart_item_data['fr_ymm_make'] =
            sanitize_text_field(wp_unslash($_COOKIE['fr_ymm_make']));
    }

    if (isset($_COOKIE['fr_ymm_model'])) {
        $cart_item_data['fr_ymm_model'] =
            sanitize_text_field(wp_unslash($_COOKIE['fr_ymm_model']));
    }

    return $cart_item_data;
}


/**
 * Save YMM information to the WooCommerce order item.
 */
add_action(
    'woocommerce_checkout_create_order_line_item',
    'fr_save_ymm_to_order_item',
    10,
    4
);

function fr_save_ymm_to_order_item($item, $cart_item_key, $values, $order) {

    $year  = isset($values['fr_ymm_year']) ? $values['fr_ymm_year'] : '';
    $make  = isset($values['fr_ymm_make']) ? $values['fr_ymm_make'] : '';
    $model = isset($values['fr_ymm_model']) ? $values['fr_ymm_model'] : '';

    if (!$year && !$make && !$model) {
        return;
    }

    /*
     * Save individual values.
     */
    if ($year) {
        $item->add_meta_data('Vehicle Year', $year, true);
    }

    if ($make) {
        $item->add_meta_data('Vehicle Make', $make, true);
    }

    if ($model) {
        $item->add_meta_data('Vehicle Model', $model, true);
    }

    /*
     * Build the exact YMM search URL.
     */
    $ymm_url = add_query_arg(
        array(
            's'           => '',
            'ymm_search'  => '1',
            'post_type'   => 'product',
            '_year'       => $year,
            '_make'       => $make,
            '_model'      => $model,
        ),
        home_url('/')
    );

    $item->add_meta_data(
        '_fr_ymm_search_url',
        esc_url_raw($ymm_url),
        true
    );
}

/**
 * Clear remembered YMM cookies after a successful WooCommerce order.
 */
add_action('woocommerce_thankyou', 'fr_clear_ymm_cookies_after_order', 20);

function fr_clear_ymm_cookies_after_order($order_id) {

    if (!$order_id) {
        return;
    }

    $cookies = array(
        'fr_ymm_year',
        'fr_ymm_make',
        'fr_ymm_model',
    );

    foreach ($cookies as $cookie_name) {

        if (isset($_COOKIE[$cookie_name])) {

            setcookie(
                $cookie_name,
                '',
                array(
                    'expires'  => time() - HOUR_IN_SECONDS,
                    'path'     => COOKIEPATH ? COOKIEPATH : '/',
                    'domain'   => COOKIE_DOMAIN,
                    'secure'   => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                )
            );

            unset($_COOKIE[$cookie_name]);
        }
    }
}