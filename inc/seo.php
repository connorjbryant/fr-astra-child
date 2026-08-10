<?php

// Custom function to get unique makes for a product
function get_unique_makes_for_product($product_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'ymm';
    $fitments = $wpdb->get_results(
        $wpdb->prepare("SELECT DISTINCT make FROM $table WHERE product_id = %d", $product_id)
    );
    
    if (empty($fitments)) {
        return '';
    }
    
    $makes = array_map(function($fit) {
        return esc_html(trim($fit->make));
    }, $fitments);
    
    // Remove duplicates and clean up
    $makes = array_unique(array_filter($makes)); // Filter out empty strings too
    
    $count = count($makes);
    if ($count === 0) {
        return '';
    }
    if ($count === 1) {
        return $makes[0];
    }
    
    // Try to extract a common brand across all makes
    $common_brand = '';
    $all_models = [];
    $has_common_brand = true;
    $first_brand = null;
    
    foreach ($makes as $make) {
        if (strpos($make, ' ') !== false) {
            list($make_brand, $model) = explode(' ', $make, 2);
            $all_models[] = trim($model);
            if ($first_brand === null) {
                $first_brand = trim($make_brand);
            } elseif (trim($make_brand) !== $first_brand) {
                $has_common_brand = false;
                break; // Brands differ, stop and use raw
            }
        } else {
            // No space: treat as full make (no brand to extract)
            $all_models[] = $make;
            $has_common_brand = false;
            break;
        }
    }
    
    if (!$has_common_brand) {
        // No common brand or mixed formats: use raw makes
        $all_models = $makes;
    } else {
        // Common brand found: use it as prefix, and models for joining
        $common_brand = $first_brand;
    }
    
    // Now join naturally with commas (Oxford style for 3+)
    if ($count === 2) {
        $joined = implode(' and ', $all_models);
    } else {
        // 3+ items: comma-separate all but last, then 'and last'
        $last = array_pop($all_models);
        $joined = implode(', ', $all_models) . ', and ' . $last;
    }
    
    // Prefix with brand if applicable (e.g., "Polaris RZR, General, and Maverick")
    if ($common_brand) {
        return $common_brand . ' ' . $joined;
    }
    
    return $joined;
}

// Modify product page title to include makes for Yoast SEO
add_filter('wpseo_title', 'custom_woocommerce_product_title_with_makes', 10, 1);
function custom_woocommerce_product_title_with_makes($title) {
    if (is_product()) {
        global $post;
        $product_id = $post->ID;
        
        // Get the original product title
        $product = wc_get_product($product_id);
        $original_title = $product->get_title();
        
        // Split the title at the '|' character
        $title_parts = explode('|', $original_title, 2);
        $main_title = trim($title_parts[0]);
        $suffix = isset($title_parts[1]) ? ' | ' . trim($title_parts[1]) : '';
        
        // Capitalize only the main title (before the '|')
        $capitalized_title = ucwords(strtolower($main_title)) . $suffix;

        // Replace "HC" with "High Clearance" in SEO title only
        $capitalized_title = str_replace('Hc', 'High Clearance', $capitalized_title);
        
        // Get unique makes
        $makes = get_unique_makes_for_product($product_id);
        
        if (!empty($makes)) {
            // Check if "Can-Am" or "Canam" is already in the main title (case-insensitive)
            $lower_main_title = strtolower($main_title);
            if (strpos($lower_main_title, 'can-am') === false && strpos($lower_main_title, 'canam') === false) {
                $title = $makes . ' ' . $capitalized_title;
            } else {
                $title = $capitalized_title;
            }
        } else {
            $title = $capitalized_title;
        }
    }
    
    return $title;
}

// Add vehicle makes under product title on single-product pages
add_action('woocommerce_single_product_summary', 'display_vehicle_makes_under_title', 10);
function display_vehicle_makes_under_title() {
    if (is_product()) {
        global $post;
        $product_id = $post->ID;
        
        // Get unique makes using existing function
        $makes = get_unique_makes_for_product($product_id);
        
        if (!empty($makes)) {
            echo '<h2 class="vehicle-makes">Fits ' . esc_html($makes) . '</h2>';
        }
    }
}

/* SEO product categories */
add_action('wp_footer', function () {
    if (!is_product_category()) return;

    $term = get_queried_object();
    if (!$term || is_wp_error($term)) return;

    // Query first 12 products in this category for the ItemList
    $q = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'tax_query'      => [[
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $term->term_id,
        ]],
        'posts_per_page' => 12,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    $items = [];
    $pos = 1;
    foreach ($q->posts as $pid) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'url'      => get_permalink($pid),
            'name'     => get_the_title($pid),
            'sku'      => get_post_meta($pid, '_sku', true),
        ];
    }

    $data = [
        '@context' => 'https://schema.org',
        '@type'    => 'CollectionPage',
        'name'     => single_term_title('', false),
        'mainEntity' => [
            '@type'           => 'ItemList',
            'itemListElement' => $items,
        ]
    ];

    echo '<script type="application/ld+json">'.wp_json_encode($data).'</script>';
});