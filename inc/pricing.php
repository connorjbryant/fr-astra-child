<?php

// Create custom roles if not already present
add_action('init', function () {
    if (!get_role('employee')) {
        add_role('employee', 'Employee', ['read' => true]);
    }
    if (!get_role('wholesale_distributor')) {
        add_role('wholesale_distributor', 'Wholesale Distributor', ['read' => true]);
    }
});

// AJAX handler to load pricing display block
add_action('wp_ajax_get_pricing_info', 'load_pricing_goods_block');
add_action('wp_ajax_nopriv_get_pricing_info', 'load_pricing_goods_block');
function load_pricing_goods_block() {
    if (isset($_GET['search_term'])) {
        $search_term = sanitize_text_field($_GET['search_term']);
        // Fetch products with _cogs_price or _wholesale_price
        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_cogs_price',
                    'value' => '',
                    'compare' => '!=',
                ],
                [
                    'key' => '_wholesale_price',
                    'value' => '',
                    'compare' => '!=',
                ],
            ],
        ]);
        $valid_products = [];
        foreach ($products as $product) {
            $title = strtolower($product->post_title);
            $sku = strtolower(get_post_meta($product->ID, '_sku', true));
            $lower_term = strtolower($search_term);
            if (strpos($title, $lower_term) !== false || strpos($sku, $lower_term) !== false) {
                if (function_exists('get_ymm_fitments_list_for_product')) {
                    $fitments_html = get_ymm_fitments_list_for_product($product->ID);
                    if (!empty(trim(strip_tags($fitments_html)))) {
                        $valid_products[] = $product;
                    }
                }
            }
        }
        if (empty($valid_products)) {
            echo '<p>No matching products found with COGS or Wholesale pricing.</p>';
            wp_die();
        }
        // Sort by title
        usort($valid_products, function($a, $b) {
            return strcmp($a->post_title, $b->post_title);
        });
        // Output multiple products
        foreach ($valid_products as $product) {
            output_product_pricing_card($product);
        }
        wp_die();
    } elseif (isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);
        if (!$product_id) {
            echo '<p>Invalid product.</p>';
            wp_die();
        }
        $product_post = get_post($product_id);
        if (!$product_post) {
            echo '<p>Product not found.</p>';
            wp_die();
        }
        output_product_pricing_card($product_post);
        wp_die();
    } else {
        echo '<p>Invalid request.</p>';
        wp_die();
    }
}
// Helper function to output the pricing card HTML
function output_product_pricing_card($product) {
    $product_id = $product->ID;
    $wc_product = wc_get_product($product_id);
    if (!$wc_product) return;
    $sku = $wc_product->get_sku();
    $title = $product->post_title;
    $product_url = get_permalink($product_id);
    $msrp = get_post_meta($product_id, '_regular_price', true);
    $sale = get_post_meta($product_id, '_sale_price', true);
    $price = $sale !== '' ? $sale : $msrp;
    $wsale = get_post_meta($product_id, '_wholesale_price', true);
    $cogs = get_post_meta($product_id, '_cogs_price', true);
    echo '<div class="promo-card">';
    echo '<h4>' . esc_html($title) . ' (<a href="' . esc_url($product_url) . '" target="_blank" class="sku-link">View Product</a>)</h4>';
    echo '<p>';
    if (current_user_can('employee') && $cogs !== '') {
        echo '<strong>COGS:</strong> $' . number_format((float)$cogs, 2);
    } elseif (current_user_can('wholesale_distributor') && $wsale !== '') {
        echo '<strong>Wholesale:</strong> $' . number_format((float)$wsale, 2);
    } elseif (current_user_can('administrator')) {
        echo '<strong>COGS:</strong> $' . number_format((float)$cogs, 2) . '<br>';
        echo '<strong>Wholesale:</strong> $' . number_format((float)$wsale, 2) . '<br>';
        echo '<strong>MSRP:</strong> $' . number_format((float)$msrp, 2) . '<br>';
        echo '<strong>Sale Price:</strong> $' . number_format((float)$sale, 2);
    } else {
        echo '<strong>Price:</strong> $' . number_format((float)$price, 2);
    }
    echo '</p>';
    // --- Fitments ---
    global $wpdb;
    $fitments = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ymm WHERE product_id = %d", $product_id));
    if (!empty($fitments)) {
        echo '<button class="toggle-fitments">Show compatible vehicles ▼</button>';
        echo '<div class="fitments-wrap" style="display: none;">';
        echo '<h3 style="text-align: center;">Compatible with:</h3>';
        echo '<table class="fitments-table">';
        echo '<thead><tr><th>Year(s)</th><th>Make</th><th>Model</th></tr></thead><tbody>';
        foreach ($fitments as $fit) {
            $year_display = ($fit->year_from == $fit->year_to) ? $fit->year_from : $fit->year_from . ' - ' . $fit->year_to;
            echo '<tr>';
            echo '<td>' . esc_html($year_display) . '</td>';
            echo '<td>' . esc_html($fit->make) . '</td>';
            echo '<td>' . esc_html($fit->model) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    } else {
        echo '<p>No fitments found.</p>';
    }
    echo '</div>';
}

// Simple products
add_filter('woocommerce_product_get_price', 'rrr_dynamic_price_by_role', 10, 2);
add_filter('woocommerce_product_get_regular_price', 'rrr_dynamic_price_by_role', 10, 2);

// Variations
add_filter('woocommerce_product_variation_get_price', 'rrr_dynamic_price_by_role', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'rrr_dynamic_price_by_role', 10, 2);

// Let WooCommerce display prices as-is (important for showing sale prices!)
add_filter('woocommerce_get_variation_prices_hash', function($hash, $product) {
    $user = wp_get_current_user();
    $hash[] = implode(',', $user->roles);
    return $hash;
}, 10, 2);

// MAIN PRICE LOGIC
function rrr_dynamic_price_by_role($price, $product) {
    if (is_admin()) return $price;
    if (!is_user_logged_in()) return $price;

    $user = wp_get_current_user();
    $product_id = $product->get_id();

    $wholesale = get_post_meta($product_id, '_wholesale_price', true);
    $cogs      = get_post_meta($product_id, '_cogs_price', true);

    if (in_array('employee', $user->roles) && $cogs !== '') {
        return round((float)$cogs, 2);
    }

    if (in_array('wholesale_distributor', $user->roles) && $wholesale !== '') {
        return round((float)$wholesale, 2);
    }

    return $price; // Default WooCommerce pricing (sale or regular)
}