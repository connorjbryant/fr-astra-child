<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Coupon Tax Exempt Checkbox + Frontend Logic
 */

/**
 * 1) Add a checkbox to the coupon edit screen
 */
add_action('add_meta_boxes', function () {
    add_meta_box(
        'frp_coupon_tax_exempt',
        __('Tax Exempt Settings', 'flexrock'),
        'frp_render_coupon_tax_exempt_box',
        'shop_coupon',
        'side',
        'default'
    );
});

function frp_render_coupon_tax_exempt_box($post) {
    wp_nonce_field('frp_save_coupon_tax_exempt', 'frp_coupon_tax_exempt_nonce');

    $enabled = get_post_meta($post->ID, '_frp_tax_exempt_coupon', true);

    ?>
    <p>
        <label>
            <input type="checkbox" name="frp_tax_exempt_coupon" value="1" <?php checked($enabled, 'yes'); ?> />
            <?php esc_html_e('Make cart tax exempt when this coupon is applied', 'flexrock'); ?>
        </label>
    </p>
    <p style="margin:0; color:#666; font-size:12px;">
        <?php esc_html_e('If checked, applying this coupon will mark the customer as tax exempt for the cart/session.', 'flexrock'); ?>
    </p>
    <?php
}

/**
 * 2) Save the checkbox value
 */
add_action('save_post_shop_coupon', function ($post_id, $post, $update) {
    if (!isset($_POST['frp_coupon_tax_exempt_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['frp_coupon_tax_exempt_nonce'], 'frp_save_coupon_tax_exempt')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $is_enabled = isset($_POST['frp_tax_exempt_coupon']) ? 'yes' : 'no';
    update_post_meta($post_id, '_frp_tax_exempt_coupon', $is_enabled);
}, 10, 3);

/**
 * 3) Apply tax exempt status if ANY applied coupon has the checkbox enabled
 */
add_action('woocommerce_before_calculate_totals', 'frp_apply_tax_exempt_for_coupon_meta', 10, 1);
function frp_apply_tax_exempt_for_coupon_meta($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!$cart || !is_a($cart, 'WC_Cart')) {
        return;
    }

    $make_tax_exempt = false;

    foreach ($cart->get_applied_coupons() as $coupon_code) {
        try {
            $coupon = new WC_Coupon($coupon_code);

            if (!$coupon || !$coupon->get_id()) {
                continue;
            }

            $is_tax_exempt_coupon = get_post_meta($coupon->get_id(), '_frp_tax_exempt_coupon', true);

            if ($is_tax_exempt_coupon === 'yes') {
                $make_tax_exempt = true;
                break;
            }
        } catch (Exception $e) {
            // Skip bad coupon safely
            continue;
        }
    }

    if (WC()->customer) {
        WC()->customer->set_is_vat_exempt($make_tax_exempt);
    }
}