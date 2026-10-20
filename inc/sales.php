<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* November Date Checker */
function fr_nov_dates_check(){
    $today = wp_date('Y-m-d');

    return $today >= '2026-11-27' && $today <= '2026-11-30';
}

/* Black Friday & Cyber Monday Sale */
add_action('woocommerce_cart_calculate_fees', 'late_nov_sale');
function late_nov_sale($cart){
    // Don't do sale stuff if logged in the backend or doing an AJAX request
    if (is_admin() && !wp_doing_ajax() || !fr_nov_dates_check()){
        return;
    }

    // Ignores sale if there's nothing to apply sale to
    if ($cart->is_empty()){
        return;
    }

    // Apply varying amounts off based on cart totals
    $total = $cart->get_subtotal(); // Total without tax

    $discount = 0;
    if ( $total >= 900){
        $discount = 80;
    } elseif ($total >= 550){
        $discount = 60;
    } elseif ($total >= 350){
        $discount = 40;
    } elseif ($total >= 200){
        $discount = 20;
    }

    if ($discount > 0){
        $cart->add_fee( __('Black Friday/Cyber Monday Sale', 'textdomain'), -$discount );
    }
}