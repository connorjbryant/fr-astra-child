<?php

if ( ! defined ( 'ABSPATH' )){
    exit;
}

/**
 * Temporary Sept 22–30, 2026 order delay notice.
 * Automatically stops displaying October 1, 2026.
 */

function fr_show_september_order_delay_notice() {
    $today = wp_date('Y-m-d');

    return $today >= '2026-09-22' && $today <= '2026-09-30';
}

/**
 * Cart page.
 */
add_action('woocommerce_before_cart', 'fr_september_cart_delay_notice', 5);

function fr_september_cart_delay_notice() {
    if (!fr_show_september_order_delay_notice()) {
        return;
    }

    echo '<div class="woocommerce-info fr-order-delay-notice">';
    echo '<strong>Orders placed Sept. 23–30 may experience a 1–1½ week delay as we expand our machining capacity. Thank you for your patience!</strong>';
    echo '</div>';
}

/**
 * Before checkout.
 */
add_action('woocommerce_before_checkout_form', 'fr_september_order_delay_notice', 5);

function fr_september_order_delay_notice() {
    if (!fr_show_september_order_delay_notice()) {
        return;
    }

    echo '<div class="woocommerce-info fr-order-delay-notice">';
    echo '<strong>Orders placed Sept. 23–30 may be delayed by 1–1½ weeks as we expand our machining capacity.</strong>';
    echo '</div>';
}

/**
 * WooCommerce order emails.
 */
add_action('woocommerce_email_order_details', 'fr_september_email_delay_notice', 5, 4);

function fr_september_email_delay_notice($order, $sent_to_admin, $plain_text, $email) {
    if (!fr_show_september_order_delay_notice()) {
        return;
    }

    if ($plain_text) {
        echo "\nSept 23–30 orders may be delayed 1–1½ weeks as we add machining capacity.\n\n";
        return;
    }

    echo '<p style="margin: 0 0 20px;">';
    echo '<strong>Sept 23–30 orders may be delayed 1–1½ weeks as we add machining capacity.</strong>';
    echo '</p>';
}