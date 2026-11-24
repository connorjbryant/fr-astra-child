
<?php

if (!defined('ABSPATH')) {
    exit;
}

/* November Date Checker */
function fr_nov_dates_check(){
    $today = wp_date('Y-m-d');

    return $today >= '2026-11-27' && $today <= '2026-11-30';
}

/* Coupons that cannot stack with the BFCM sale */
function fr_nov_restricted_coupons(){
    return array(
        'frsave26',
        'frsave15_once',
        'frsave25',
        '100-rrr25',
        'frsave15',
        'save10006rr',
        'save10001rr',
        'save10010rr',
        'save10018rr',
        'save10012rr',
        'save10015rr',
        'save10016rr',
    );
}

/* Black Friday & Cyber Monday Sale */
add_action('woocommerce_cart_calculate_fees', 'late_nov_sale');
function late_nov_sale($cart){

    if ((is_admin() && !wp_doing_ajax()) || !fr_nov_dates_check()){
        return;
    }

    if ($cart->is_empty()){
        return;
    }

    // Cart subtotal before coupons and tax
    $total = $cart->get_subtotal();

    $discount = 0;

    if ($total >= 900){
        $discount = 80;
    } elseif ($total >= 550){
        $discount = 60;
    } elseif ($total >= 350){
        $discount = 40;
    } elseif ($total >= 200){
        $discount = 20;
    }

    if ($discount > 0){
        $cart->add_fee(
            __('Black Friday/Cyber Monday Sale', 'textdomain'),
            -$discount
        );
    }
}

/* Prevent specific coupons from stacking with the BFCM sale */
add_filter('woocommerce_coupon_is_valid', 'fr_disable_nov_coupons', 10, 3);
function fr_disable_nov_coupons($valid, $coupon, $discounts){

    if (!$valid || !fr_nov_dates_check()){
        return $valid;
    }

    $cart = WC()->cart;

    if (!$cart || $cart->is_empty()){
        return $valid;
    }

    $code = strtolower($coupon->get_code());

    // Allow all coupons not on the restricted list
    if (!in_array($code, fr_nov_restricted_coupons(), true)){
        return $valid;
    }

    // Block restricted coupons when the BFCM discount applies
    if ($cart->get_subtotal() >= 200){
        return false;
    }

    return $valid;
}

/* Remove restricted coupons if the cart becomes eligible for BFCM */
add_action('woocommerce_before_calculate_totals', 'fr_remove_nov_restricted_coupons', 20);
function fr_remove_nov_restricted_coupons($cart){

    if ((is_admin() && !wp_doing_ajax()) || !fr_nov_dates_check()){
        return;
    }

    if ($cart->is_empty()){
        return;
    }

    // Calculate current subtotal before discounts and tax
    $subtotal = 0;

    foreach ($cart->get_cart() as $item){
        $subtotal += wc_get_price_excluding_tax(
            $item['data'],
            array('qty' => $item['quantity'])
        );
    }

    if ($subtotal < 200){
        return;
    }

    foreach ($cart->get_applied_coupons() as $code){

        if (in_array(strtolower($code), fr_nov_restricted_coupons(), true)){

            $cart->remove_coupon($code);

            wc_add_notice(
                sprintf(
                    __('Coupon "%s" cannot be combined with the Black Friday/Cyber Monday Sale.', 'textdomain'),
                    $code
                ),
                'notice'
            );
        }
    }
}

/* Custom error message for restricted coupons */
add_filter('woocommerce_coupon_error', 'fr_nov_coupon_error', 10, 3);
function fr_nov_coupon_error($message, $error_code, $coupon){

    if (!fr_nov_dates_check() || !WC()->cart || !$coupon){
        return $message;
    }

    if (
        $error_code === WC_Coupon::E_WC_COUPON_INVALID_FILTERED &&
        WC()->cart->get_subtotal() >= 200 &&
        in_array(strtolower($coupon->get_code()), fr_nov_restricted_coupons(), true)
    ){
        return __('This coupon cannot be combined with the Black Friday/Cyber Monday Sale.', 'textdomain');
    }

    return $message;
}