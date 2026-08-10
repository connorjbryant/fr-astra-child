<?php

/* Custom rear radius rod WooCommerce notice */
function conditionally_hide_checkout_fields_js() {
    $contains_rrr = false;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        if (strpos($product->get_sku(), 'RRR') !== false) {
            $contains_rrr = true;
            break;
        }
    }
    ?>
    <script>
        (function($){
            $(document).ready(function(){
                var hasRRR = <?php echo $contains_rrr ? 'true' : 'false'; ?>;

                var tshirtFieldWrapper = $('#billing__field'); // Wrapper div
                var tshirtInput = $('#billing_'); // The input itself
                var tshirtLabel = $('label[for="billing_"]');

                if (!hasRRR) {
                    tshirtFieldWrapper.hide();
                    tshirtInput.prop('required', false);
                    tshirtLabel.hide();
                } else {
                    tshirtFieldWrapper.show();
                    tshirtInput.prop('required', false);
                    tshirtLabel.show();
                }
            });
        })(jQuery);
    </script>
    <?php
}
add_action('woocommerce_after_checkout_form', 'conditionally_hide_checkout_fields_js');

// Start WooCommerce session if needed
add_action('init', function () {
    if (class_exists('WC_Session') && !WC()->session) {
        WC()->initialize_session();
    }
}, 1);

// Handle "Add to Cart" action for RRR products
add_action('woocommerce_add_to_cart', 'check_rrr_products_on_add_to_cart', 10, 6);
function check_rrr_products_on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (!WC()->session) {
        return;
    }

    // Skip if override is set or message was already shown
    if (
    (isset($_GET['rrr_override']) && $_GET['rrr_override'] === '1') ||
    (WC()->session && WC()->session->get('rrr_override') === '1')
    ) {
        WC()->session->__unset('rrr_override'); // clear after use
        return;
    }

    $sku_to_alt_map = [
        'RRR-10003-KIT' => 4755,
        'RRR-10006-KIT' => 4758,
        'RRR-10008-KIT' => 4759,
        'RRR-10014-KIT' => 4760,
        'RRR-10015-KIT' => 4761,
    ];

    $matched_skus = [];
    $matched_alt_ids = [];

    // Check the newly added product
    $product = wc_get_product($product_id);
    $sku = $product->get_sku();
    if (strpos($sku, 'RRR') !== false && array_key_exists($sku, $sku_to_alt_map)) {
        $matched_skus[] = $sku;
        $matched_alt_ids[] = $sku_to_alt_map[$sku];
    }

    // Check existing cart items
    foreach (WC()->cart->get_cart() as $item) {
        $item_sku = $item['data']->get_sku();
        if ($item_sku !== $sku && strpos($item_sku, 'RRR') !== false && array_key_exists($item_sku, $sku_to_alt_map)) {
            $matched_skus[] = $item_sku;
            $matched_alt_ids[] = $sku_to_alt_map[$item_sku];
        }
    }

    if (empty($matched_skus)) {
        return;
    }

    // Set session flag to show message only once
    WC()->session->set('rrr_blocked_once', '1');

    // Build the notice
    $sku_list = format_sku_list_naturally($matched_skus);
    $removable_skus_json = esc_attr(json_encode($matched_skus));

    $alt_products = wc_get_products([
        'include' => array_unique($matched_alt_ids),
        'limit'   => -1,
    ]);

    $alt_html = '<div class="rrr-alternatives"><strong>Recommended Alternatives:</strong><div class="rrr-alternatives-grid">';
    foreach ($alt_products as $alt_product) {
        $alt_html .= '<div class="alt-card">';
        $alt_html .= '  <div class="alt-details">';
        $alt_html .= '    <div class="alt-title">' . esc_html($alt_product->get_name()) . '</div>';
        $alt_html .= get_ymm_fitments_list_for_product($alt_product->get_id());
        $alt_html .= '    <div class="alt-price">Price: ' . wc_price($alt_product->get_price()) . '</div>';
        $alt_html .= '  </div>';
        $sku_to_remove = array_search($alt_product->get_id(), $sku_to_alt_map);
        $alt_html .= '<a style="color: white !important; text-decoration: none !important;" href="?add-to-cart=' . $alt_product->get_id() . '&rrr_override=1" class="button alt-add-to-cart"';
        if ($sku_to_remove) {
            $alt_html .= ' data-remove-sku="' . esc_attr($sku_to_remove) . '"';
        }
        $alt_html .= '>Add to Cart</a>';
        $alt_html .= '</div>';
    }
    $alt_html .= '</div></div>';
    $alt_html .= '<div class="rrr-next-action" style="margin-top:20px;">';
    $alt_html .= '<p>Would you like to proceed or update your cart?</p>';
    $alt_html .= '<div class="rrr-buttons">';
    $alt_html .= '<a style="color: white !important; text-decoration: none !important;" href="' . wc_get_cart_url() . '" class="button button-checkout">Proceed to Cart</a>';
    $alt_html .= '<button class="button button-remove-items" data-skus=\'' . $removable_skus_json . '\'>Remove Suggested Item(s)</button>';
    $alt_html .= '</div></div>';

    wc_add_notice(
        '<span class="rrr-notice-marker"></span>' .
        sprintf(
            __('Please be advised that the following part(s) (%s) are not compatible with bumpers or cages bolted between rear Radius Rods.', 'astra'),
            $sku_list
        ) . $alt_html,
        'error'
    );
}

// Clear session flag when cart is emptied or on thank you page
add_action('woocommerce_cart_emptied', function () {
    if (WC()->session) {
        WC()->session->__unset('rrr_blocked_once');
    }
});

add_action('woocommerce_thankyou', function () {
    if (WC()->session) {
        WC()->session->__unset('rrr_blocked_once');
    }
});

// Clear session flag via AJAX
add_action('wp_ajax_clear_rrr_session_flag', 'clear_rrr_session_flag');
add_action('wp_ajax_nopriv_clear_rrr_session_flag', 'clear_rrr_session_flag');
function clear_rrr_session_flag() {
    if (WC()->session) {
        WC()->session->__unset('rrr_blocked_once');
    }
    wp_send_json_success();
}

// Get cart items by SKU
add_action('wp_ajax_get_cart_items_by_sku', 'get_cart_items_by_sku_callback');
add_action('wp_ajax_nopriv_get_cart_items_by_sku', 'get_cart_items_by_sku_callback');
function get_cart_items_by_sku_callback() {
    $sku_map = [];
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $sku = $product->get_sku();
        if (!empty($sku)) {
            $sku_map[$sku] = $cart_item_key;
        }
    }
    error_log(print_r($sku_map, true));
    wp_send_json_success($sku_map);
}

add_action('wp_ajax_woocommerce_remove_cart_item', 'woocommerce_remove_cart_item_callback');
add_action('wp_ajax_nopriv_woocommerce_remove_cart_item', 'woocommerce_remove_cart_item_callback');

function woocommerce_remove_cart_item_callback() {
    if (!isset($_POST['cart_item_key'])) {
        wp_send_json_error('Missing cart_item_key');
    }

    // Force WooCommerce to hydrate cart
    if (null === WC()->cart) {
        wc_load_cart();
    }

    WC()->cart->get_cart();

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);

    if (WC()->cart->remove_cart_item($cart_item_key)) {
        WC()->cart->calculate_totals();
        wp_send_json_success('Item removed');
    } else {
        wp_send_json_error('Failed to remove item');
    }

    error_log("Cart keys at time of removal:");
    error_log(print_r(WC()->cart->get_cart(), true));

}

// Format SKU list naturally
function format_sku_list_naturally($skus) {
    $skus = array_map(function($sku) {
        return '<strong>' . esc_html($sku) . '</strong>';
    }, $skus);
    $count = count($skus);
    if ($count === 0) return '';
    if ($count === 1) return $skus[0];
    if ($count === 2) return $skus[0] . ' and ' . $skus[1];
    $last = array_pop($skus);
    return implode(', ', $skus) . ', and ' . $last;
}

// Get YMM fitments for product
function get_ymm_fitments_list_for_product($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ymm';
    $fitments = $wpdb->get_results(
        $wpdb->prepare("SELECT make, model, year_from, year_to FROM $table WHERE product_id = %d", $product_id)
    );
    if (empty($fitments)) {
        return '';
    }
    $max_visible = 3;
    $output = '<div class="fitment-section">';
    $output .= '<div>Compatible with:</div>';
    $output .= '<ol class="alt-fitments">';
    $hidden_items = '';
    foreach ($fitments as $index => $fit) {
        $year_display = ($fit->year_from == $fit->year_to)
            ? esc_html($fit->year_from)
            : esc_html($fit->year_from . '–' . $fit->year_to);
        $full_model = trim($fit->make . ' ' . $fit->model);
        $clean_full = preg_replace('/\b(\w+)\s+\1\b/i', '$1', $full_model);
        $line = sprintf(
            '<li>%s (%s)</li>',
            esc_html($clean_full),
            $year_display
        );
        if ($index < $max_visible) {
            $output .= $line;
        } else {
            $hidden_items .= $line;
        }
    }
    $output .= '</ol>';
    if (!empty($hidden_items)) {
        $output .= '<div class="js-fitment-hidden"><ul class="alt-fitments">' . $hidden_items . '</ul></div>';
        $output .= '<button class="js-fitment-toggle" type="button">View Full List ▼</button>';
    }
    $output .= '</div>';
    return $output;
}

add_action('wp_ajax_set_rrr_override_flag', 'set_rrr_override_flag');
add_action('wp_ajax_nopriv_set_rrr_override_flag', 'set_rrr_override_flag');

function set_rrr_override_flag() {
    if (WC()->session) {
        WC()->session->set('rrr_override', '1');
    }
    wp_send_json_success();
}

add_action('wp_ajax_get_rrr_notice_for_sku', 'get_rrr_notice_for_sku_callback');
add_action('wp_ajax_nopriv_get_rrr_notice_for_sku', 'get_rrr_notice_for_sku_callback');

function get_rrr_notice_for_sku_callback() {
    $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';
    if (!$sku) wp_send_json_error();

    $sku_to_alt_map = [
        'RRR-10003-KIT' => 4755,
        'RRR-10006-KIT' => 4758,
        'RRR-10008-KIT' => 4759,
        'RRR-10014-KIT' => 4760,
        'RRR-10015-KIT' => 4761,
    ];

    if (!isset($sku_to_alt_map[$sku])) {
        wp_send_json_error();
    }

    $alt_id = $sku_to_alt_map[$sku];
    $alt_product = wc_get_product($alt_id);
    if (!$alt_product) {
        wp_send_json_error();
    }

    ob_start();

    $sku_list = '<strong>' . esc_html($sku) . '</strong>';
    $alt_html = '<div class="rrr-alternatives"><strong>Recommended Alternatives:</strong><div class="rrr-alternatives-grid">';

    $alt_html .= '<div class="alt-card">';
    $alt_html .= '  <div class="alt-details">';
    $alt_html .= '    <div class="alt-title">' . esc_html($alt_product->get_name()) . '</div>';
    $alt_html .= get_ymm_fitments_list_for_product($alt_product->get_id());
    $alt_html .= '    <div class="alt-price">Price: ' . wc_price($alt_product->get_price()) . '</div>';
    $alt_html .= '  </div>';

    // Add to Cart button with optional data-remove-sku
    $alt_html .= '<a style="color: white !important; text-decoration: none !important;" href="?add-to-cart=' . $alt_product->get_id() . '&rrr_override=1" class="button alt-add-to-cart" data-remove-sku="' . esc_attr($sku) . '">Add to Cart</a>';
    $alt_html .= '</div>'; // .alt-card
    $alt_html .= '</div></div>'; // .rrr-alternatives-grid

    // Proceed/Remove buttons
    $alt_html .= '<div class="rrr-next-action" style="margin-top:20px;">';
    $alt_html .= '<p>Would you like to proceed or update your cart?</p>';
    $alt_html .= '<div class="rrr-buttons">';
    $alt_html .= '<button class="button button-remove-items" data-skus=\'' . wp_json_encode([$sku]) . '\'>Remove Suggested Item(s)</button>';
    $alt_html .= '</div></div>';

    ?>

    <div class="woocommerce-error rrr-archive-notice">
        <p><?php printf(
            'Please be advised that the following part(s) (%s) are not compatible with bumpers or cages bolted between rear Radius Rods.',
            $sku_list
        ); ?></p>
        <?php echo $alt_html; ?>
    </div>

    <?php
    $html = ob_get_clean();
    wp_send_json_success(['notice_html' => $html]);
}