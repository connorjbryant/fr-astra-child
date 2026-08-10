<?php
/**
 * FR Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package FR Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_FR_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'fr-astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_FR_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

// Register a custom widget
function register_custom_search_widget() {
    register_widget( 'Custom_Search_Widget' );
}
add_action( 'widgets_init', 'register_custom_search_widget' );

/* Price check widget */
class Custom_Search_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'custom_search_widget',
            'Custom Search Widget',
            array( 'description' => 'A custom search widget' )
        );
    }

    public function widget( $args, $instance ) {
        // Display custom search form here
        ?>
        <form action="<?php echo home_url( '/' ); ?>" method="get">
            <div class="search-filters">
                <label for="year">Year</label>
                <select name="year" id="year">
                    <option value="">Select Year</option>
                    <?php
                    $years = get_field('year', 'option');
                    if ($years) {
                        foreach ($years as $year) {
                            echo '<option value="' . esc_attr( $year ) . '">' . esc_html( $year ) . '</option>';
                        }
                    }
                    ?>
                </select>

                <label for="make">Make</label>
                <select name="make" id="make">
                    <option value="">Select Make</option>
                    <?php
                    $makes = get_field('make', 'option');
                    if ($makes) {
                        foreach ($makes as $make) {
                            echo '<option value="' . esc_attr( $make ) . '">' . esc_html( $make ) . '</option>';
                        }
                    }
                    ?>
                </select>

                <label for="model">Model</label>
                <select name="model" id="model">
                    <option value="">Select Model</option>
                    <?php
                    $models = get_field('model', 'option');
                    if ($models) {
                        foreach ($models as $model) {
                            echo '<option value="' . esc_attr( $model ) . '">' . esc_html( $model ) . '</option>';
                        }
                    }
                    ?>
                </select>

                <button type="submit">Search</button>
            </div>
        </form>
        <?php
    }

    public function form( $instance ) {
        // Widget settings form (optional)
    }

    public function update( $new_instance, $old_instance ) {
        // Update widget settings (optional)
    }
}

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

                var tshirtFieldWrapper = $('#billing__field');      // Wrapper div
                var tshirtInput = $('#billing_');                   // The input itself
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

// Register and enqueue custom scripts and styles
function register_woocommerce_custom_script() {
    $js_path = get_template_directory() . '/inc/assets/js/woocommerce-custom.js';
    $js_uri = ASTRA_THEME_URI . 'inc/assets/js/woocommerce-custom.js';
    $version = file_exists($js_path) ? filemtime($js_path) : ASTRA_THEME_VERSION;
    wp_register_script(
        'astra-woocommerce-custom',
        $js_uri,
        array('jquery'),
        $version,
        true
    );
    // Use a safe, custom JS object name
    wp_localize_script('astra-woocommerce-custom', 'astra_wc_custom_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'cart_url' => wc_get_cart_url(),
    ));
}
add_action('wp_enqueue_scripts', 'register_woocommerce_custom_script', 5);

function enqueue_woocommerce_custom_script() {
    if (class_exists('WooCommerce') && (is_woocommerce() || is_cart() || is_product() || is_page('shop-2'))) {
        wp_enqueue_script('astra-woocommerce-custom');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_woocommerce_custom_script', 20);

/* Misc CSS and JS additions */
function register_woocommerce_custom_styles() {
    $css_path = get_template_directory() . '/inc/assets/css/woocommerce-custom.css';
    $css_uri = ASTRA_THEME_URI . 'inc/assets/css/woocommerce-custom.css';
    $version = file_exists($css_path) ? filemtime($css_path) : ASTRA_THEME_VERSION;
    wp_register_style(
        'astra-woocommerce-custom-style',
        $css_uri,
        array(),
        $version,
        'all'
    );
}
add_action('wp_enqueue_scripts', 'register_woocommerce_custom_styles', 5);

add_filter('woocommerce_loop_add_to_cart_link', function($html, $product) {
    $sku = $product->get_sku();
    if ($sku) {
        $html = str_replace('<a ', '<a data-product_sku="' . esc_attr($sku) . '" ', $html);
    }
    return $html;
}, 10, 2);

function enqueue_woocommerce_custom_styles() {
    if (class_exists('WooCommerce') && (is_woocommerce() || is_cart() || is_product() || is_page('shop-2'))) {
        wp_enqueue_style('astra-woocommerce-custom-style');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_woocommerce_custom_styles', 20);

// General custom CSS styles
function register_custom_theme_styles() {
    $css_path = get_stylesheet_directory() . '/assets/css/custom-style.css';
    $css_uri  = get_stylesheet_directory_uri() . '/assets/css/custom-style.css';

    $version = file_exists($css_path) ? filemtime($css_path) : null;

    wp_enqueue_style(
        'fr-custom-style',
        $css_uri,
        array(),
        $version,
        'all'
    );
}
add_action('wp_enqueue_scripts', 'register_custom_theme_styles', 20);

// General custom JavaScript
function enqueue_custom_theme_scripts() {
    $js_path = get_stylesheet_directory() . '/assets/js/custom-script.js';
    $js_uri  = get_stylesheet_directory_uri() . '/assets/js/custom-script.js';

    $version = file_exists($js_path) ? filemtime($js_path) : null;

    wp_enqueue_script(
        'fr-custom-script',
        $js_uri,
        array('jquery'),
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'enqueue_custom_theme_scripts', 20);

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

/**
 * Exclude UPS Expedited for Canadian shipping addresses.
 *
 * This runs after your existing shipping filter and removes any rate
 * whose label contains "UPS Expedited" when the destination country is Canada.
 */
add_filter('woocommerce_package_rates', 'fr_remove_ups_expedited_for_canada', 200, 2);
function fr_remove_ups_expedited_for_canada($rates, $package) {

    if (empty($rates) || !is_array($rates)) {
        return $rates;
    }

    // Determine destination country from the shipping package.
    $country = strtoupper($package['destination']['country'] ?? '');

    // Only modify rates for Canada.
    if ($country !== 'CA') {
        return $rates;
    }

    foreach ($rates as $rate_id => $rate) {
        $label = strtolower($rate->get_label());

        // Remove UPS Expedited.
        if (strpos($label, 'ups expedited') !== false) {
            unset($rates[$rate_id]);
        }
    }

    return $rates;
}

/* add_filter('woocommerce_package_rates', function ($rates, $package) {$keep = 'wc-shippo-shipping:ups_ground';
    $has_ground = isset($rates[$keep]);
    foreach ($rates as $id => $rate) {
        $method_id = method_exists($rate,'get_method_id') ? $rate->get_method_id() : ($rate->method_id ?? '');
        if ($method_id === 'wc-shippo-shipping' && (!$has_ground || $id !== $keep)) {
            if ($has_ground) unset($rates[$id]);
        }
    }
    return $rates;
}, 100, 2);*/

/*add_filter('woocommerce_package_rates', function ($rates, $package) {
    // ===== COUPON: ship26 => Free Shipping =====
    $applied_coupons = WC()->cart ? array_map('strtolower', WC()->cart->get_applied_coupons()) : [];

    if (in_array('ship26', $applied_coupons, true)) {
        return [
            'free_shipping_ship26' => new WC_Shipping_Rate(
                'free_shipping_ship26',
                __('Free Shipping', 'your-textdomain'),
                0,
                [],
                'free_shipping'
            ),
        ];
    }

    // ===== 0) Date-limited FREE SHIPPING promo by SKU =====
    $now         = current_time('timestamp');
    $promo_start = strtotime('2025-11-27 00:00:00');
    $promo_end   = strtotime('2025-11-30 23:59:59');

    $promo_active = ($now >= $promo_start && $now <= $promo_end);

    if ($promo_active && !empty($package['contents'])) {
        $sku_prefixes = [
            'FSBH',
            'RSBH',
            'ENDL',
            'RRR',
            'QDRS',
            'FCA',
            'FCAB',
            'FCAJ',
            'RSBB',
            'FSBB',
            'RE'
        ];

        $wheel_free_ids = [];
        if (class_exists('Simple_Woo_Rewards')) {
            $wheel_free_ids[] = \Simple_Woo_Rewards::FREE_TEE_PRODUCT_ID;
            $wheel_free_ids[] = \Simple_Woo_Rewards::FREE_COOZIE_PRODUCT_ID;
            $wheel_free_ids[] = \Simple_Woo_Rewards::FREE_HAT_PRODUCT_ID;
        }

        $all_match      = true;
        $has_match      = false;
        $has_wheel_free = false;

        foreach ($package['contents'] as $item) {
            if (empty($item['data']) || !is_a($item['data'], 'WC_Product')) {
                continue;
            }

            $product = $item['data'];

            $is_wheel_free = false;

            if (!empty($item['swr_free'])) {
                $is_wheel_free = true;
            }

            if (!$is_wheel_free && (int) $product->get_meta('swr_free') === 1) {
                $is_wheel_free = true;
            }

            if (
                !$is_wheel_free
                && !empty($wheel_free_ids)
                && in_array((int) $product->get_id(), $wheel_free_ids, true)
            ) {
                $is_wheel_free = true;
            }

            if ($is_wheel_free) {
                $has_wheel_free = true;
            }

            if (!$product->needs_shipping()) {
                continue;
            }

            $sku               = $product->get_sku();
            $matches_this_item = false;

            if (!empty($sku)) {
                foreach ($sku_prefixes as $prefix) {
                    if (strpos($sku, $prefix) === 0) {
                        $matches_this_item = true;
                        $has_match         = true;
                        break;
                    }
                }
            }

            if (!$matches_this_item) {
                $all_match = false;
            }
        }

        if ($has_wheel_free) {
            return [
                'free_shipping_wheel_promo' => new WC_Shipping_Rate(
                    'free_shipping_wheel_promo',
                    __('Free Shipping', 'your-textdomain'),
                    0,
                    [],
                    'free_shipping'
                ),
            ];
        }

        if ($has_match && $all_match) {
            return [
                'free_shipping_sku_promo' => new WC_Shipping_Rate(
                    'free_shipping_sku_promo',
                    __('Free Shipping', 'your-textdomain'),
                    0,
                    [],
                    'free_shipping'
                ),
            ];
        }
    }

    // ===== existing logic continues below =====
    $hats_term = get_term_by('name', 'Hats', 'product_shipping_class');
    $hats_id   = $hats_term ? (int) $hats_term->term_id : 0;

    $country = isset($package['destination']['country'])
        ? strtoupper($package['destination']['country'])
        : '';
    $state = isset($package['destination']['state'])
        ? strtoupper($package['destination']['state'])
        : '';

    $all_hats = true;
    foreach ($package['contents'] as $item) {
        if (empty($item['data']) || !is_a($item['data'], 'WC_Product')) {
            continue;
        }

        $product = $item['data'];

        if ($product->needs_shipping() && (int) $product->get_shipping_class_id() !== $hats_id) {
            $all_hats = false;
            break;
        }
    }

    if ($all_hats && $hats_id !== 0) {
        return [
            'free_shipping_hats' => new WC_Shipping_Rate(
                'free_shipping_hats',
                'Free Shipping',
                0,
                [],
                'free_shipping'
            ),
        ];
    }

    $is_pr = ($country === 'PR') || ($country === 'US' && $state === 'PR');
    $is_ca = ($country === 'CA');

    if ($is_ca || $is_pr) {
        $keep_id = 'wc-shippo-shipping:ups_standard';

        if (isset($rates[$keep_id])) {
            foreach ($rates as $id => $rate) {
                $method_id = method_exists($rate, 'get_method_id')
                    ? $rate->get_method_id()
                    : ($rate->method_id ?? '');

                if ($method_id === 'wc-shippo-shipping' && $id !== $keep_id) {
                    unset($rates[$id]);
                }
            }
        }

        return $rates;
    }

    $keep_ground_id = 'wc-shippo-shipping:ups_ground';
    $has_ground     = isset($rates[$keep_ground_id]);

    if ($has_ground) {
        foreach ($rates as $id => $rate) {
            $method_id = method_exists($rate, 'get_method_id')
                ? $rate->get_method_id()
                : ($rate->method_id ?? '');

            if ($method_id === 'wc-shippo-shipping' && $id !== $keep_ground_id) {
                unset($rates[$id]);
            }
        }
    }

    return $rates;
}, 100, 2);*/

// add_filter('woocommerce_package_rates', function($rates, $package) {
//     error_log('=== PACKAGE DESTINATION ===');
//     error_log('Address: ' . ($package['destination']['address'] ?? 'EMPTY'));
//     error_log('Address 2: ' . ($package['destination']['address_2'] ?? ''));
//     error_log('City: ' . ($package['destination']['city'] ?? ''));
//     error_log('State: ' . ($package['destination']['state'] ?? ''));
//     error_log('Postcode: ' . ($package['destination']['postcode'] ?? ''));
//     error_log('Country: ' . ($package['destination']['country'] ?? ''));
//     return $rates;
// }, 5, 2);

// add_filter('woocommerce_package_rates', function($rates, $package) {
//     error_log('=== SHIPPING RATES START ===');
//     foreach ($rates as $rate_id => $rate) {
//         $method_id = method_exists($rate, 'get_method_id')
//             ? $rate->get_method_id()
//             : (isset($rate->method_id) ? $rate->method_id : '');
//         $label = method_exists($rate, 'get_label')
//             ? $rate->get_label()
//             : (isset($rate->label) ? $rate->label : '');
//         error_log('RATE ID: ' . $rate_id . ' | METHOD: ' . $method_id . ' | LABEL: ' . $label);
//     }
//     error_log('=== SHIPPING RATES END ===');
//     return $rates;
// }, 9999, 2);

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

add_filter( 'woocommerce_display_item_meta', 'rename_swr_free_meta_label', 10, 3 );
function rename_swr_free_meta_label( $html, $item, $args ) {
    // Replace 'swr_free' with 'Free Item(s)' in the HTML output
    $html = str_replace( '<strong class="wc-item-meta-label">swr_free:</strong>', '<strong class="wc-item-meta-label">Free Item(s):</strong>', $html );
    return $html;
}

add_filter('woocommerce_email_styles', function ($css) {
    $brand = '#fb0505';
    $text  = '#111111';

    $css .= "
        /* Links */
        a, a:link, a:visited { color: {$brand} !important; }
        a:hover, a:active { color: {$brand} !important; text-decoration: underline !important; }

        /* Headings */
        h1, h2, h3 { color: {$text} !important; }

        /* Site title above the header (Gmail-safe) */
        td[id$=\"template_header_image\"] p,
        td[id$=\"template_header_image\"] p a {
            color: {$text} !important;
        }

        /* Body text */
        #body_content, #body_content p, #body_content div, .td { color: {$text} !important; }

        /* Footer links */
        #template_footer a { color: {$brand} !important; }
    ";
    return $css;
});

// 0) Tell Elementor not to print ANY Google Fonts (local or remote)
// add_filter('elementor/frontend/print_google_fonts', '__return_false'); // official filter

// 1) Late dequeues (after most things register/enqueue)
add_action('wp_enqueue_scripts', function () {
    if (is_admin() || is_customize_preview()) return;

    // Known handles
    foreach ([
        'elementor-gf-local-prompt',
        'elementor-gf-local-roboto',
        'elementor-gf-local-robotoslab',
        // add any other families you spot in your logs
    ] as $h) {
        wp_dequeue_style($h);
        wp_deregister_style($h);
    }

    // Fallback by URL match
    global $wp_styles;
    if (!empty($wp_styles->registered)) {
        foreach ($wp_styles->registered as $handle => $style) {
            $src = $style->src ?? '';
            if ($src && strpos($src, '/uploads/elementor/google-fonts/css/') !== false) {
                if (preg_match('~/prompt(?:\.min)?\.css~i', $src)
                    || preg_match('~/roboto(?:\.min)?\.css~i', $src)
                    || preg_match('~/robotoslab(?:\.min)?\.css~i', $src)) {
                    wp_dequeue_style($handle);
                    wp_deregister_style($handle);
                }
            }
        }
    }
}, 999);

// 2) Extra safety: run again right before styles print
add_action('wp_print_styles', function () {
    global $wp_styles;
    if (empty($wp_styles->queue)) return;

    foreach ($wp_styles->queue as $handle) {
        $src = $wp_styles->registered[$handle]->src ?? '';
        if ($src && strpos($src, '/uploads/elementor/google-fonts/css/') !== false) {
            if (strpos($src, 'prompt.css') !== false
                || strpos($src, 'roboto.css') !== false
                || strpos($src, 'robotoslab.css') !== false) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }
    }
}, 999);

// 3) Last resort: strip the <link> tag if anything slips through (runs very late)
add_filter('style_loader_tag', function ($html, $handle, $href) {
    $is_elementor_font = (
        $handle === 'elementor-gf-local-prompt' ||
        $handle === 'elementor-gf-local-roboto' ||
        $handle === 'elementor-gf-local-robotoslab' ||
        (strpos($href, '/uploads/elementor/google-fonts/css/') !== false &&
            (strpos($href, 'prompt.css') !== false ||
             strpos($href, 'roboto.css') !== false ||
             strpos($href, 'robotoslab.css') !== false))
    );
    return $is_elementor_font ? '' : $html;
}, 9999, 3);

// 4) (Optional) Log what's still in the queue for admins
add_action('wp_print_styles', function () {
    if (!current_user_can('administrator')) return;
    global $wp_styles;
    $dump = [];
    foreach (($wp_styles->queue ?? []) as $h) {
        $dump[$h] = $wp_styles->registered[$h]->src ?? '(no src)';
    }
    error_log('[Dequeue debug] styles about to print: ' . print_r($dump, true));
}, 1000);

// Last resort: Suppress Woolentor (specific pages)
add_filter('style_loader_tag', function ($html, $handle, $href) {

    // Block Woolentor CSS on homepage and specific pages
    if (
        ($handle === 'woolentor-widgets' || strpos($href, 'woolentor-widgets.css') !== false) &&
        (is_front_page() || is_page(['parts-search', 'why-us', 'blogs', 'contact-us']))
    ) {
        return ''; // Block Woolentor CSS
    }

    return $html;
}, 10, 3);

/**
 * Flex Rock — Performance LITE (stable Slick + videos)
 * - Cap preconnects at ≤4 (Lighthouse)
 * - Give LCP image high priority
 * - Defer everything except: jQuery, wp-embed, Slick, MediaElement, Elementor frontend (minimal)
 * - Ensure Slick/MediaElement CSS are present
 * - Nudge Slick to re-measure once layout/iframes/videos are settled
 * - Keep Lite-YouTube OFF for stability (can re-enable later)
 */

/* ---------------------------------
 * Helpers
 * --------------------------------- */
function fr_is_request( $type ) {
  switch ( $type ) {
    case 'cartlike':
      return function_exists('is_cart') && ( is_cart() || is_checkout() || is_account_page() );
    case 'product':
      return function_exists('is_product') && is_product();
  }
  return false;
}

/* ---------------------------------
 * Resource Hints (cap at 4)
 * --------------------------------- */
add_filter('wp_resource_hints', function( $urls, $relation_type ){
  if ( 'preconnect' !== $relation_type ) return $urls;
  $keep = [
    'https://www.googletagmanager.com',
    'https://scripts.clarity.ms',
    'https://googleads.g.doubleclick.net',
    'https://www.youtube.com',
  ];
  $out = [];
  foreach (array_unique($keep) as $origin) {
    $out[] = ['href' => $origin, 'crossorigin' => 'anonymous'];
  }
  return $out;
}, 20, 2);

/* ---------------------------------
 * LCP image: fetchpriority=high (home/shop/front)
 * --------------------------------- */
add_filter('wp_get_attachment_image_attributes', function( $attr, $attachment, $size ){
  static $did_lcp = false;
  if ( $did_lcp ) return $attr;
  if ( is_front_page() || is_home() || is_shop() ) {
    $attr['fetchpriority'] = 'high';
    $attr['loading']       = 'eager';
    $attr['decoding']      = 'async';
    $did_lcp = true;
  }
  return $attr;
}, 10, 3);

/* ---------------------------------
 * Defer non-critical scripts (very small allowlist)
 * --------------------------------- */
add_filter('script_loader_tag', function( $tag, $handle, $src ){
    
  // Do NOT mess with script loading on this page (admin-like tool page)
  if ( is_page('inventory-adjust') || is_page(6645) ) {
    // Also strip defer/async if something already added it
    $tag = str_replace([' defer ', ' async '], ' ', $tag);
    $tag = str_replace([' defer>', ' async>'], '>', $tag);
    return $tag;
  }
  
  if ( is_admin() ) return $tag;

  $no_defer = [
    // jQuery core
    'jquery','jquery-core','jquery-migrate',
    // WP oEmbed helpers (YouTube sizing etc.)
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
    // Slick
    'slick','slick-js','slick-carousel','jquery-slick',
    // MediaElement (WP video)
    'mediaelement','wp-mediaelement','mejs',
    // Minimal Elementor runtime needed on many pages
    'elementor-frontend','imagesloaded',
    // Our tiny boot script (added below)
    'fr-lite-boot',
  ];

  if ( in_array($handle, $no_defer, true) ) {
    // strip any async/defer a plugin added
    $tag = str_replace([' defer ', ' async '], ' ', $tag);
    $tag = str_replace([' defer>', ' async>'], '>', $tag);
    return $tag;
  }

  // Everyone else: add defer
  if ( strpos($tag, ' defer') === false && strpos($tag, ' async') === false ) {
    $tag = str_replace('<script ', '<script defer ', $tag);
  }
  return $tag;
}, 10, 3);

/* ---------------------------------
 * Force only the bare essentials to the HEAD (group 0)
 * (keeps Slick/MEJS ready before widgets touch them)
 * --------------------------------- */
add_action('wp_default_scripts', function( $wp_scripts ){
  if ( is_admin() ) return;

  foreach ( ['jquery-core','jquery-migrate','jquery','wp-embed','mediaelement','wp-mediaelement','mejs','slick','slick-js','slick-carousel','jquery-slick','elementor-frontend'] as $h ) {
    if ( isset($wp_scripts->registered[$h]) ) {
      $wp_scripts->registered[$h]->extra['group'] = 0; // header
    }
  }

  // Ensure Slick depends on jQuery (some bundles forget)
  foreach ( ['slick','slick-js','slick-carousel','jquery-slick'] as $h ) {
    if ( isset($wp_scripts->registered[$h]) ) {
      $deps = (array) $wp_scripts->registered[$h]->deps;
      if ( ! in_array('jquery', $deps, true) ) {
        $deps[] = 'jquery';
        $wp_scripts->registered[$h]->deps = $deps;
      }
    }
  }
}, 9);

/* ---------------------------------
 * Ensure CSS for Slick & MediaElement present
 * (prevents “all slides printed” look)
 * --------------------------------- */
add_action('wp_enqueue_scripts', function(){
  if ( is_admin() ) return;

  if ( wp_style_is('wp-mediaelement', 'registered') ) wp_enqueue_style('wp-mediaelement');
  if ( wp_style_is('mediaelement', 'registered') )    wp_enqueue_style('mediaelement');

  foreach ( ['slick','slick-css','slick-theme'] as $h ) {
    if ( wp_style_is($h, 'registered') ) wp_enqueue_style($h);
  }
}, 20);

/* ---------------------------------
 * Trim heavy libs globally where safe
 * --------------------------------- */
add_action('wp_enqueue_scripts', function(){
  if ( is_admin() ) return;

  // Only load Photoswipe on product pages
  if ( ! fr_is_request('product') ) {
    wp_dequeue_script('photoswipe');
    wp_dequeue_script('photoswipe-ui-default');
    wp_dequeue_style('photoswipe');
    wp_dequeue_style('photoswipe-default-skin');
  }

  wp_dequeue_script('googlesitekit-widgets'); // dashboard widgets not needed on frontend
}, 99);

/* ---------------------------------
 * Tiny boot: after DOM/iframes/videos settle, ask Slick to re-measure
 * (does NOT initialize carousels—assumes theme/plugin does that)
 * --------------------------------- */
add_action('wp_enqueue_scripts', function(){
  if ( is_admin() ) return;

  $deps = ['jquery'];
  foreach ( ['slick','slick-js','slick-carousel','jquery-slick'] as $h ) {
    if ( wp_script_is($h, 'registered') ) $deps[] = $h;
  }
  if ( wp_script_is('elementor-frontend','registered') ) $deps[] = 'elementor-frontend';

  wp_register_script(
    'fr-lite-boot',
    false,
    array_unique($deps),
    '1.0.0',
    true
  );

  $boot = <<<JS
(function(){
  if (window.__frLiteBoot) return; window.__frLiteBoot = 1;
  function refresh(){
    if (!window.jQuery) return;
    jQuery('.js-slick,.slick-initialized').each(function(){
      var \$c = jQuery(this);
      if (\$c.hasClass('slick-initialized')) {
        try { \$c.slick('setPosition'); } catch(e){}
        setTimeout(function(){ try{ \$c.slick('refresh'); }catch(e){} }, 0);
      }
    });
  }
  // DOM ready + window load (fonts/iframes/videos)
  if (window.jQuery) jQuery(function(){ setTimeout(refresh, 0); });
  window.addEventListener('load', function(){ setTimeout(refresh, 0); }, {once:true});

  // If Elementor injects/activates widgets
  if (window.elementorFrontend && window.elementorFrontend.hooks) {
    elementorFrontend.hooks.addAction('frontend/element_ready/global', function(){ setTimeout(refresh, 0); });
  }
})();
JS;

  wp_add_inline_script('fr-lite-boot', $boot);
  wp_enqueue_script('fr-lite-boot');
}, 60);

/* ---------------------------------
 * Keep Lite-YouTube OFF while stabilizing (can toggle later)
 * --------------------------------- */
if ( ! defined('FR_LITE_YT') ) define('FR_LITE_YT', false);
add_filter('embed_oembed_html', function( $html ){ return FR_LITE_YT ? $html : $html; }, 10, 1);

add_filter('should_load_separate_core_block_assets', '__return_true');

// Abandon cart → website report integration (REST)
add_action('rest_api_init', function () {
  register_rest_route('frp/v1', '/abandon-feedback', [
    'methods'  => 'GET',
    'permission_callback' => function (\WP_REST_Request $req) {
      $provided = sanitize_text_field((string) $req->get_param('key'));
      $expected = 'frp_23dsGz1p9nA4K7mQwT2bYcR5x'; // must match your Apps Script CFG.WP_FEEDBACK_KEY
      if (!$expected || !hash_equals($expected, $provided)) {
        return new WP_Error('rest_forbidden', __('Unauthorized', 'wcas'), ['status' => 401]);
      }
      return true;
    },
    'callback' => function (\WP_REST_Request $req) {
      global $wpdb;
      $table = $wpdb->prefix . 'wc_abandon_feedback';

      // Inputs
      $after  = preg_replace('/[^0-9\-]/', '', (string) ($req->get_param('after')  ?: '2000-01-01')) . ' 00:00:00';
      $before = preg_replace('/[^0-9\-]/', '', (string) ($req->get_param('before') ?: '2100-01-01')) . ' 00:00:00';

      // Select (keep columns tolerant to older schema)
      $sql = "
        SELECT
          created_at                              AS ts,
          COALESCE(session_id, '')                AS session,
          COALESCE(cart_item_key, '')             AS cart_item_key,
          COALESCE(product_id, 0)                 AS product_id,
          COALESCE(product_title, '')             AS product_title,
          COALESCE(email, '')                     AS email,
          COALESCE(reason, '')                    AS reason
        FROM {$table}
        WHERE created_at >= %s
          AND created_at <  %s
        ORDER BY created_at ASC
        LIMIT 2000
      ";
      $rows = $wpdb->get_results($wpdb->prepare($sql, $after, $before), ARRAY_A) ?: [];

      return rest_ensure_response(['items' => $rows]);
    }
  ]);
});

// --- FRONT-END: Inject Custom Amount UI ---
add_action( 'yith_ywgc_show_gift_card_amount_selection', function( $product ) {
    ?>
    <!-- BEGIN: Custom amount field -->
    <button
        class="ywgc-predefined-amount-button ywgc-amount-buttons ywgc-custom-trigger"
        type="button"
        data-custom="1"
        data-price=""
        data-wc-price=""
        style="margin-left:.5rem;"
    >
        <span class="woocommerce-Price-amount amount">
            <span class="woocommerce-Price-currencySymbol">$</span>&nbsp;Custom
        </span>
    </button>

    <input
        type="hidden"
        class="ywgc-predefined-amount-button ywgc-amount-buttons ywgc-custom-hidden"
        value=""
        data-price=""
        data-wc-price=""
    />

    <div class="ywgc-custom-wrapper" style="display:none; margin-top:0.75rem;">
        <label for="ywgc-custom-amount" style="display:block; font-size:14px; font-weight:600;">
            Enter amount
        </label>
        <input
            id="ywgc-custom-amount"
            type="number"
            min="5"
            max="1000"
            step="1"
            style="max-width:140px;"
        />
        <small style="display:block; font-size:12px; color:#666;">
            Minimum $5. Maximum $1000.
        </small>
    </div>

    <script>
    (function(){
        var root = document.querySelector('.gift-cards-list');
        if (!root) return;

        var minAmount = 5, maxAmount = 1000;

        var presetButtons = [].slice.call(root.querySelectorAll('button.ywgc-predefined-amount-button.ywgc-amount-buttons'));
        var presetHiddens = [].slice.call(root.querySelectorAll('input.ywgc-predefined-amount-button.ywgc-amount-buttons'));

        var customBtn    = root.querySelector('button.ywgc-custom-trigger');
        var customHidden = root.querySelector('input.ywgc-custom-hidden');
        var customWrap   = root.querySelector('.ywgc-custom-wrapper');
        var customInput  = root.querySelector('#ywgc-custom-amount');

        function clearSelections() {
            presetButtons.forEach(function(btn){ btn.classList.remove('selected_button'); });
            presetHiddens.forEach(function(h){
                h.classList.remove('selected_button');
                h.removeAttribute('name');
            });
        }

        function selectPreset(btn, hidden) {
            clearSelections();
            btn.classList.add('selected_button');
            hidden.classList.add('selected_button');
            hidden.setAttribute('name', 'gift_amounts');
        }

        // Click on preset buttons
        presetButtons.forEach(function(btn){
            if (btn === customBtn) return;
            btn.addEventListener('click', function(){
                if (customWrap) customWrap.style.display = 'none';
                var val = btn.getAttribute('value');
                var matchHidden = presetHiddens.find(function(h){ return h.value === val; });
                if (matchHidden) selectPreset(btn, matchHidden);
            });
        });

        // Click on Custom button
        if (customBtn) {
            customBtn.addEventListener('click', function(e){
                e.preventDefault();
                if (!customWrap) return;

                customWrap.style.display = 'block';
                customInput && customInput.focus();

                clearSelections();
                customBtn.classList.add('selected_button');
                customHidden.classList.add('selected_button');
                customHidden.setAttribute('name', 'gift_amounts');

                if (customInput && customInput.value) applyCustomValue(customInput.value);
            });
        }

        function applyCustomValue(rawVal){
            var val = parseFloat(rawVal);
            if (isNaN(val)) return;

            if (val < minAmount) val = minAmount;
            if (val > maxAmount) val = maxAmount;
            customInput.value = val;

            var wcPrice = '$ ' + val.toFixed(2);
            customHidden.value = val;
            customHidden.setAttribute('data-price', val);
            customHidden.setAttribute('data-wc-price', wcPrice);

            var preview = document.querySelector('.ywgc-form-preview-amount');
            if (preview) preview.textContent = wcPrice;
        }

        customInput && customInput.addEventListener('input', function(){
            clearSelections();
            customBtn.classList.add('selected_button');
            customHidden.classList.add('selected_button');
            customHidden.setAttribute('name', 'gift_amounts');
            applyCustomValue(customInput.value);
        });

        customInput && customInput.addEventListener('change', function(){
            applyCustomValue(customInput.value);
        });
    })();
    </script>
    <!-- END: Custom amount field -->
    <?php
}, 20 ); // Priority after YITH’s default

add_filter( 'woocommerce_add_to_cart_validation', function( $passed, $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product || $product->get_type() !== 'gift-card' ) {
        return $passed;
    }

    $min = 5;
    $max = 1000;

    if ( isset( $_POST['gift_amounts'] ) ) {
        $amount = floatval( wp_unslash( $_POST['gift_amounts'] ) );
        if ( $amount < $min || $amount > $max ) {
            wc_add_notice( "Gift card amount must be between $$min and $$max.", 'error' );
            return false;
        }
    }
    return $passed;
}, 10, 2 );

// 1. Make sure WooCommerce doesn't inject its normal upsell block
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_add_to_cart_button', 'woocommerce_upsell_display', 10 );

// 2. Add our custom upsell block after SKU / Category
add_action( 'woocommerce_product_meta_end', 'fr_render_recommended_parts_block', 20 );

function fr_render_recommended_parts_block() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    // Get upsell IDs assigned to this product in the admin
    $upsell_ids = $product->get_upsell_ids();

    if ( empty( $upsell_ids ) ) {
        return; // nothing to show
    }

    // Query those products
    $args = [
        'post_type'      => 'product',
        'post__in'       => $upsell_ids,
        'orderby'        => 'post__in', // keep same order you chose in admin
        'posts_per_page' => -1,
    ];

    $upsell_query = new WP_Query( $args );

    if ( ! $upsell_query->have_posts() ) {
        return;
    }

    ?>
    <div class="fr-recommended-parts">
        <div class="fr-recommended-heading">You may also like…</div>

        <?php while ( $upsell_query->have_posts() ) : $upsell_query->the_post();
            $upsell_product = wc_get_product( get_the_ID() );
            if ( ! $upsell_product ) continue;

            $price_html   = $upsell_product->get_price_html();
            $title        = $upsell_product->get_name();
            $permalink    = get_permalink( $upsell_product->get_id() );
            $thumbnail    = $upsell_product->get_image( 'woocommerce_thumbnail' ); // <img ...>
            $add_to_cart_url  = $upsell_product->add_to_cart_url();
            $add_to_cart_text = $upsell_product->add_to_cart_text();
            $add_to_cart_class= implode( ' ', array_filter( [
                'button',
                'fr-rec-addtocart',
                'product_type_' . $upsell_product->get_type(),
                $upsell_product->supports( 'ajax_add_to_cart' ) ? 'ajax_add_to_cart' : ''
            ] ) );
            ?>

            <div class="fr-rec-item">
                <a class="fr-rec-thumb" href="<?php echo esc_url( $permalink ); ?>">
                    <?php echo $thumbnail; ?>
                </a>

                <div class="fr-rec-info">
                    <a class="fr-rec-title" href="<?php echo esc_url( $permalink ); ?>">
                        <?php echo esc_html( $title ); ?>
                    </a>

                    <div class="fr-rec-price">
                        <?php echo wp_kses_post( $price_html ); ?>
                    </div>
                </div>

                <div class="fr-rec-cta">
                    <a
                        href="<?php echo esc_url( $add_to_cart_url ); ?>"
                        data-quantity="1"
                        class="<?php echo esc_attr( $add_to_cart_class ); ?> fr-rec-addtocart"
                        data-product_id="<?php echo esc_attr( $upsell_product->get_id() ); ?>"
                        data-product_sku="<?php echo esc_attr( $upsell_product->get_sku() ); ?>"
                        aria-label="<?php echo esc_attr( $add_to_cart_text ); ?>"
                        rel="nofollow"
                    >
                        <?php echo esc_html( $add_to_cart_text ); ?>
                    </a>
                </div>
            </div>


        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    </div>
    <?php
}

/* Prevent coupon stacking */
add_filter('woocommerce_coupon_is_valid', function ($valid, $coupon) {
    if (!$valid || !WC()->cart) {
        return $valid;
    }

    $applied = WC()->cart->get_applied_coupons();

    // If any other coupon is already applied, block this one.
    if (!empty($applied)) {
        $code = strtolower($coupon->get_code());

        // If the only applied coupon is this same one, allow (WC usually handles this anyway)
        if (!(count($applied) === 1 && strtolower($applied[0]) === $code)) {
            wc_add_notice(__('Only one coupon can be used per order.'), 'error');
            return false;
        }
    }

    return $valid;
}, 10, 2);

// Force 12 products per page on ALL shop, archive, and search pages – beats any theme override
add_filter( 'loop_shop_per_page', function( $products ) {
    return 12;
}, 999999 );

add_filter( 'woocommerce_shortcode_products_query', function( $query_args ) {
    $query_args['posts_per_page'] = 12;
    return $query_args;
}, 999999, 3 );

// Holiday announcement bar (Dec 23 - Jan 2)
add_filter('body_class', function ($classes) {
  $tz = new DateTimeZone('America/Indiana/Indianapolis');
  $now = new DateTime('now', $tz);

  $start = new DateTime('2025-12-23 00:00:00', $tz);
  $end   = new DateTime('2026-01-02 00:00:00', $tz);

  if ($now >= $start && $now < $end) {
    $classes[] = 'has-frp-announce';
  }

  return $classes;
});

add_filter('xmlrpc_enabled', '__return_false');

add_filter('rest_endpoints', function ($endpoints) {
  if (isset($endpoints['/wp/v2/users'])) {
    unset($endpoints['/wp/v2/users']);
  }
  if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
    unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
  }
  return $endpoints;
});

add_filter('show_admin_bar', function ($show) {
    if (is_admin()) return $show;

    // Hide only on the inventory adjuster page
    if (is_page(6645)) { // <-- page ID
        return false;
    }

    return $show;
}, 9999);

add_action('wp_body_open', function () {
  $tz = new DateTimeZone('America/Indiana/Indianapolis');
  $now = new DateTime('now', $tz);

  $start = new DateTime('2025-12-23 00:00:00', $tz);
  $end   = new DateTime('2026-01-02 00:00:00', $tz);

  if ($now < $start || $now >= $end) return;
  ?>
  <div class="frp-announce">
    <div class="frp-snow" aria-hidden="true"></div>

    <div class="frp-announce__inner">
      <span class="frp-announce__label">Holiday Closure</span>
      <span class="frp-announce__message">
        We’re closed <strong>Dec 24 – Jan 1</strong>.
        Orders placed during this window ship the
        <strong>second week of January</strong>.
      </span>
    </div>
  </div>
  <?php
});

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

// If loose inventory ever gets off for whatever reason, run /wp-admin/?fr_run_nonwoo_backfill=1
// after inputing desired date things went screwy to see what they should be if orders were not taking Components
// out of inventory. Then - if that looks correct - run /wp-admin/?fr_run_nonwoo_backfill=1&live=1 to actually
// put those changes into affect. Uncomment the temp runner below though

// ===============================
// 1. HISTORICAL FUNCTION
// ===============================
/*function fr_cbompro_backfill_nonwoo_bom_components_since(
    string $start_date = '2026-03-10 00:00:00',
    array $statuses = ['wc-processing', 'wc-completed'],
    int $limit = 500,
    bool $dry_run = true
): array {

    $results = [
        'start_date'     => $start_date,
        'orders_checked' => 0,
        'orders_updated' => 0,
        'loose_changes'  => [],
    ];

    $orders = wc_get_orders([
        'status'        => $statuses,
        'limit'         => $limit,
        'type'          => 'shop_order',
        'orderby'       => 'date',
        'order'         => 'ASC',
        'return'        => 'objects',
        'date_created'  => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        $results['orders_checked']++;

        if ($order->get_meta(\FR\ComponentsBOMPro\OrderBOMStock::META_REDUCED) !== 'yes') continue;
        if ($order->get_meta('_fr_cbompro_nonwoo_backfill_done') === 'yes') continue;

        $order_loose_changes = [];

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $qty = (int) $item->get_quantity();
            if ($qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;
            $ordered_sku        = strtoupper(trim((string) $product->get_sku()));

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($bom_product_id);
            if (empty($rows)) continue;

            foreach ($rows as $row) {
                $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
                if ($sku === '') continue;

                $row_qty = max(1, (int) ($row['qty'] ?? 1));
                $needed  = $row_qty * $qty;

                if ($needed <= 0) continue;

                $pid = (int) wc_get_product_id_by_sku($sku);

                if ($pid > 0) continue; // skip Woo products
                if ($ordered_sku !== '' && $sku === $ordered_sku) continue;

                if (!isset($order_loose_changes[$sku])) {
                    $order_loose_changes[$sku] = 0;
                }

                $order_loose_changes[$sku] += $needed;
            }
        }

        if (empty($order_loose_changes)) {
            if (!$dry_run) {
                $order->update_meta_data('_fr_cbompro_nonwoo_backfill_done', 'yes');
                $order->save();
            }
            continue;
        }

        foreach ($order_loose_changes as $sku => $qty_to_reduce) {
            if (!isset($results['loose_changes'][$sku])) {
                $results['loose_changes'][$sku] = 0;
            }

            $results['loose_changes'][$sku] += (int) $qty_to_reduce;

            if (!$dry_run) {
                \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                    $sku,
                    -1 * (int) $qty_to_reduce,
                    "Historical backfill for order #" . $order->get_id()
                );
            }
        }

        if (!$dry_run) {
            $order->update_meta_data('_fr_cbompro_nonwoo_backfill_done', 'yes');
            $order->save();
        }

        $results['orders_updated']++;
    }

    return $results;
}*/


// ===============================
// 2. TEMP RUNNER (REMOVE AFTER)
// ===============================
/*add_action('admin_init', function () {

    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_run_nonwoo_backfill'])) return;

    $dry_run = !isset($_GET['live']);

    $result = fr_cbompro_backfill_nonwoo_bom_components_since(
        '2026-03-10 00:00:00',
        ['wc-processing', 'wc-completed'],
        500,
        $dry_run
    );

    echo '<pre>';
    echo $dry_run ? "DRY RUN\n\n" : "LIVE RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});*/

function fr_cbompro_collect_component_usage_recursive(
    int $product_id,
    int $multiplier = 1,
    array &$accum = [],
    array $visited = []
): array {
    if ($product_id <= 0) {
        return $accum;
    }

    if (in_array($product_id, $visited, true)) {
        return $accum;
    }

    $visited[] = $product_id;

    $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
    if (empty($rows)) {
        return $accum;
    }

    foreach ($rows as $row) {
        $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if ($row_sku === '') {
            continue;
        }

        $row_qty = max(1, (int) ($row['qty'] ?? 1));
        $needed  = $row_qty * $multiplier;

        // Recurse through subassemblies only
        if (str_ends_with($row_sku, '-SA')) {
            $sa_product_id = (int) wc_get_product_id_by_sku($row_sku);
            if ($sa_product_id > 0) {
                fr_cbompro_collect_component_usage_recursive($sa_product_id, $needed, $accum, $visited);
                continue;
            }
        }

        if (!isset($accum[$row_sku])) {
            $accum[$row_sku] = 0;
        }

        $accum[$row_sku] += $needed;
    }

    return $accum;
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_component'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $baseline_qty = isset($_GET['baseline'])
        ? (int) $_GET['baseline']
        : null;

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 2000;

    $live = isset($_GET['live']);

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    if ($baseline_qty === null) {
        wp_die('Missing baseline param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $current_loose_qty = $wpdb->get_var(
        $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
    );
    $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

    // ------------------------------------------------------------
    // 1) Pull all logged rows after baseline
    // ------------------------------------------------------------
    $logged_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE sku = %s
               AND created_at >= %s
             ORDER BY created_at ASC, id ASC",
            $sku,
            $start_date
        ),
        ARRAY_A
    );

    // Ignore the exact-stock row that established the chosen baseline
    $filtered_logged_rows = [];
    foreach ($logged_rows as $row) {
        $note    = (string) ($row['note'] ?? '');
        $new_qty = isset($row['new_qty']) && $row['new_qty'] !== '' ? (int) $row['new_qty'] : null;

        if (
            stripos($note, 'Exact stock set') !== false &&
            $new_qty !== null &&
            $new_qty === (int) $baseline_qty
        ) {
            continue;
        }

        $filtered_logged_rows[] = $row;
    }
    $logged_rows = $filtered_logged_rows;

    $logged_total_delta        = 0;
    $logged_already_consumed   = 0;
    $logged_location_only_rows = [];
    $logged_other_rows         = [];

    foreach ($logged_rows as $row) {
        $delta = (int) ($row['delta'] ?? 0);
        $note  = (string) ($row['note'] ?? '');

        $logged_total_delta += $delta;

        if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
            $logged_location_only_rows[] = $row;
            continue;
        }

        if (
            $delta < 0 && (
                stripos($note, 'BOM component auto-reduced for order #') !== false ||
                stripos($note, 'Historical backfill for order #') !== false ||
                stripos($note, 'Subassembly build for ') !== false
            )
        ) {
            $logged_already_consumed += abs($delta);
            continue;
        }

        $logged_other_rows[] = $row;
    }

    // ------------------------------------------------------------
    // 2) Reconstruct "should have consumed" from orders recursively
    // ------------------------------------------------------------
    $projected_consumption = 0;
    $projected_rows = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            fr_cbompro_collect_component_usage_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$sku])) {
                continue;
            }

            $needed = (int) $expanded[$sku];
            $projected_consumption += $needed;

            $projected_rows[] = [
                'order_id'      => $order->get_id(),
                'order_date'    => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'ordered_sku'   => $product->get_sku(),
                'ordered_qty'   => $order_qty,
                'needed'        => $needed,
            ];
        }
    }

    // ------------------------------------------------------------
    // 3) Missing consumption still not reflected in the log
    // ------------------------------------------------------------
    $missing_consumption = max(0, $projected_consumption - $logged_already_consumed);

    // ------------------------------------------------------------
    // 4) Shadow expected qty today
    // ------------------------------------------------------------
    // Start at trusted baseline
    // Add all logged deltas after baseline
    // Then subtract ONLY the missing projected consumption
    // (because already-consumed reductions are already inside logged_total_delta)
    $expected_qty_today = $baseline_qty + $logged_total_delta - $missing_consumption;
    $correction_needed  = $expected_qty_today - $current_loose_qty;

    $result = [
        'sku'                    => $sku,
        'start_date'             => $start_date,
        'baseline_qty'           => $baseline_qty,
        'current_loose_qty'      => $current_loose_qty,

        'logged_total_delta'     => $logged_total_delta,
        'logged_already_consumed'=> $logged_already_consumed,
        'projected_consumption'  => $projected_consumption,
        'missing_consumption'    => $missing_consumption,

        'expected_qty_today'     => $expected_qty_today,
        'correction_needed'      => $correction_needed,

        'location_only_rows'     => $logged_location_only_rows,
        'logged_other_rows'      => $logged_other_rows,
        'projected_rows'         => $projected_rows,
    ];

    // ------------------------------------------------------------
    // 5) Optional live correction
    // ------------------------------------------------------------
    if ($live && $correction_needed !== 0) {
        \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
            $sku,
            $correction_needed,
            sprintf(
                'Shadow replay reconcile from %s. Baseline=%d, expected=%d, current=%d',
                $start_date,
                $baseline_qty,
                $expected_qty_today,
                $current_loose_qty
            )
        );

        $result['live_applied'] = true;
    } else {
        $result['live_applied'] = false;
    }

    echo '<pre>';
    echo $live ? "SHADOW REPLAY LIVE RUN\n\n" : "SHADOW REPLAY DRY RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_debug_sku_snapshot'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 10;

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $loose_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT sku, qty, location
             FROM {$loose_table}
             WHERE sku = %s
             LIMIT 1",
            $sku
        ),
        ARRAY_A
    );

    $recent_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT created_at, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE sku = %s
             ORDER BY created_at DESC, id DESC
             LIMIT %d",
            $sku,
            $limit
        ),
        ARRAY_A
    );

    $result = [
        'sku' => $sku,
        'loose_snapshot' => $loose_row ?: [
            'sku' => $sku,
            'qty' => null,
            'location' => null,
        ],
        'recent_adjustments' => $recent_rows,
    ];

    echo '<pre>';
    echo "SKU SNAPSHOT DEBUG\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

function fr_cbompro_collect_component_usage_recursive_multi(
    int $product_id,
    int $multiplier = 1,
    array &$accum = [],
    array $visited = []
): array {
    if ($product_id <= 0) {
        return $accum;
    }

    if (in_array($product_id, $visited, true)) {
        return $accum;
    }

    $visited[] = $product_id;

    $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
    if (empty($rows)) {
        return $accum;
    }

    foreach ($rows as $row) {
        $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        if ($row_sku === '') continue;

        $row_qty = max(1, (int) ($row['qty'] ?? 1));
        $needed  = $row_qty * $multiplier;

        if (str_ends_with($row_sku, '-SA')) {
            $sa_product_id = (int) wc_get_product_id_by_sku($row_sku);
            if ($sa_product_id > 0) {
                fr_cbompro_collect_component_usage_recursive_multi($sa_product_id, $needed, $accum, $visited);
                continue;
            }
        }

        if (!isset($accum[$row_sku])) {
            $accum[$row_sku] = 0;
        }

        $accum[$row_sku] += $needed;
    }

    return $accum;
}

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_audit'])) return;

    $skus = isset($_GET['sku'])
        ? array_values(array_filter(array_map(
            fn($s) => strtoupper(trim($s)),
            explode(',', sanitize_text_field(wp_unslash($_GET['sku'])))
        )))
        : [];

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 2000;

    $live = isset($_GET['live']);

    if (empty($skus)) {
        wp_die('Missing sku param');
    }

    // Baselines passed like: baseline=HDW-80011:33,HDW-80012:41
    $baseline_map = [];
    $baseline_raw = isset($_GET['baseline'])
        ? sanitize_text_field(wp_unslash($_GET['baseline']))
        : '';

    if ($baseline_raw !== '') {
        foreach (explode(',', $baseline_raw) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '' || strpos($chunk, ':') === false) continue;
            [$bsku, $bqty] = array_map('trim', explode(':', $chunk, 2));
            $baseline_map[strtoupper($bsku)] = (int) $bqty;
        }
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    // ------------------------------------------------------------
    // 1) Precompute projected consumption from orders for ALL SKUs
    // ------------------------------------------------------------
    $projected_by_sku = [];
    $projected_rows_by_sku = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            fr_cbompro_collect_component_usage_recursive_multi($bom_product_id, $order_qty, $expanded);

            foreach ($expanded as $component_sku => $needed) {
                if (!in_array($component_sku, $skus, true)) continue;

                if (!isset($projected_by_sku[$component_sku])) {
                    $projected_by_sku[$component_sku] = 0;
                }
                $projected_by_sku[$component_sku] += (int) $needed;

                $projected_rows_by_sku[$component_sku][] = [
                    'order_id'    => $order->get_id(),
                    'order_date'  => $order->get_date_created()
                        ? $order->get_date_created()->date('Y-m-d H:i:s')
                        : '',
                    'ordered_sku' => $product->get_sku(),
                    'ordered_qty' => $order_qty,
                    'needed'      => (int) $needed,
                ];
            }
        }
    }

    // ------------------------------------------------------------
    // 2) Audit each SKU
    // ------------------------------------------------------------
    $results = [];

    foreach ($skus as $sku) {
        if (!isset($baseline_map[$sku])) {
            $results[$sku] = ['error' => 'Missing baseline for SKU'];
            continue;
        }

        $baseline_qty = (int) $baseline_map[$sku];

        $current_loose_qty = $wpdb->get_var(
            $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
        );
        $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

        $logged_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
                 FROM {$log_table}
                 WHERE sku = %s
                   AND created_at >= %s
                 ORDER BY created_at ASC, id ASC",
                $sku,
                $start_date
            ),
            ARRAY_A
        );

        $filtered_logged_rows = [];
        foreach ($logged_rows as $row) {
            $note    = (string) ($row['note'] ?? '');
            $new_qty = isset($row['new_qty']) && $row['new_qty'] !== '' ? (int) $row['new_qty'] : null;

            if (
                stripos($note, 'Exact stock set') !== false &&
                $new_qty !== null &&
                $new_qty === $baseline_qty
            ) {
                continue;
            }

            $filtered_logged_rows[] = $row;
        }
        $logged_rows = $filtered_logged_rows;

        $logged_total_delta      = 0;
        $logged_already_consumed = 0;
        $location_only_rows      = [];
        $logged_other_rows       = [];

        foreach ($logged_rows as $row) {
            $delta = (int) ($row['delta'] ?? 0);
            $note  = (string) ($row['note'] ?? '');

            $logged_total_delta += $delta;

            if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
                $location_only_rows[] = $row;
                continue;
            }

            if (
                $delta < 0 && (
                    stripos($note, 'BOM component auto-reduced for order #') !== false ||
                    stripos($note, 'Historical backfill for order #') !== false ||
                    stripos($note, 'Subassembly build for ') !== false
                )
            ) {
                $logged_already_consumed += abs($delta);
                continue;
            }

            $logged_other_rows[] = $row;
        }

        $projected_consumption = (int) ($projected_by_sku[$sku] ?? 0);
        $missing_consumption   = max(0, $projected_consumption - $logged_already_consumed);

        $expected_qty_today = $baseline_qty + $logged_total_delta - $missing_consumption;
        $correction_needed  = $expected_qty_today - $current_loose_qty;

        $results[$sku] = [
            'baseline_qty'            => $baseline_qty,
            'current_loose_qty'       => $current_loose_qty,
            'logged_total_delta'      => $logged_total_delta,
            'logged_already_consumed' => $logged_already_consumed,
            'projected_consumption'   => $projected_consumption,
            'missing_consumption'     => $missing_consumption,
            'expected_qty_today'      => $expected_qty_today,
            'correction_needed'       => $correction_needed,
            'location_only_rows'      => $location_only_rows,
            'logged_other_rows'       => $logged_other_rows,
            'projected_rows'          => $projected_rows_by_sku[$sku] ?? [],
        ];

        if ($live && $correction_needed !== 0) {
            \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                $sku,
                $correction_needed,
                sprintf(
                    'Shadow replay audit reconcile from %s. Baseline=%d, expected=%d, current=%d',
                    $start_date,
                    $baseline_qty,
                    $expected_qty_today,
                    $current_loose_qty
                )
            );
            $results[$sku]['live_applied'] = true;
        } else {
            $results[$sku]['live_applied'] = false;
        }
    }

    echo '<pre>';
    echo $live ? "SHADOW REPLAY AUDIT LIVE RUN\n\n" : "SHADOW REPLAY AUDIT DRY RUN\n\n";
    print_r($results);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_prove_component_orders'])) return;

    $target_sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $statuses = isset($_GET['statuses'])
        ? array_values(array_filter(array_map('trim', explode(',', sanitize_text_field(wp_unslash($_GET['statuses']))))))
        : ['wc-processing', 'wc-completed'];

    $limit = isset($_GET['limit']) ? max(1, (int) $_GET['limit']) : 5000;

    if ($target_sku === '') {
        wp_die('Missing sku param');
    }

    $expand_recursive = function(int $product_id, int $multiplier = 1, array &$accum = [], array $visited = []) use (&$expand_recursive) {
        if ($product_id <= 0) return $accum;
        if (in_array($product_id, $visited, true)) return $accum;

        $visited[] = $product_id;

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (empty($rows)) return $accum;

        foreach ($rows as $row) {
            $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($row_sku === '') continue;

            $row_qty = max(1, (int) ($row['qty'] ?? 1));
            $needed  = $row_qty * $multiplier;

            if (str_ends_with($row_sku, '-SA')) {
                $child_id = (int) wc_get_product_id_by_sku($row_sku);
                if ($child_id > 0) {
                    $expand_recursive($child_id, $needed, $accum, $visited);
                    continue;
                }
            }

            if (!isset($accum[$row_sku])) {
                $accum[$row_sku] = 0;
            }
            $accum[$row_sku] += $needed;
        }

        return $accum;
    };

    $matches = [];
    $orders = wc_get_orders([
        'status'       => $statuses,
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $ordered_product_id = (int) $product->get_id();
            $parent_id          = (int) $product->get_parent_id();
            $bom_product_id     = $ordered_product_id;

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            $expand_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$target_sku])) {
                continue;
            }

            $matches[] = [
                'order_id'      => $order->get_id(),
                'order_date'    => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'status'        => $order->get_status(),
                'ordered_sku'   => $product->get_sku(),
                'ordered_qty'   => $order_qty,
                'component_sku' => $target_sku,
                'needed'        => (int) $expanded[$target_sku],
            ];
        }
    }

    $total = 0;
    foreach ($matches as $m) {
        $total += (int) $m['needed'];
    }

    echo '<pre>';
    echo "PROVE COMPONENT ORDERS\n\n";
    echo "Target SKU: {$target_sku}\n";
    echo "Start date: {$start_date}\n";
    echo "Statuses: " . implode(', ', $statuses) . "\n";
    echo "Total projected usage: {$total}\n\n";
    print_r($matches);
    echo '</pre>';
    exit;
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;
    if (!isset($_GET['fr_shadow_replay_absolute'])) return;

    $sku = isset($_GET['sku'])
        ? strtoupper(trim(sanitize_text_field(wp_unslash($_GET['sku']))))
        : '';

    $start_date = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-01-01 00:00:00';

    $limit = isset($_GET['limit'])
        ? max(1, (int) $_GET['limit'])
        : 5000;

    if ($sku === '') {
        wp_die('Missing sku param');
    }

    global $wpdb;

    $loose_table = $wpdb->prefix . 'fr_cbompro_loose_inventory';
    $log_table   = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    $current_loose_qty = $wpdb->get_var(
        $wpdb->prepare("SELECT qty FROM {$loose_table} WHERE sku = %s", $sku)
    );
    $current_loose_qty = ($current_loose_qty === null) ? 0 : (int) $current_loose_qty;

    // ------------------------------------------------------------
    // 1) Pull all log rows in date range
    // ------------------------------------------------------------
    $logged_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, delta, prev_qty, new_qty, note, source, user_id
             FROM {$log_table}
             WHERE sku = %s
               AND created_at >= %s
             ORDER BY created_at ASC, id ASC",
            $sku,
            $start_date
        ),
        ARRAY_A
    );

    // ------------------------------------------------------------
    // 2) Determine starting qty from first row that has prev_qty
    // ------------------------------------------------------------
    $starting_qty = null;
    foreach ($logged_rows as $row) {
        if (isset($row['prev_qty']) && $row['prev_qty'] !== '' && $row['prev_qty'] !== null) {
            $starting_qty = (int) $row['prev_qty'];
            break;
        }
    }

    if ($starting_qty === null) {
        $starting_qty = $current_loose_qty;
    }

    // ------------------------------------------------------------
    // 3) Sum NON-reset logged deltas
    // ------------------------------------------------------------
    $logged_non_reset_delta = 0;
    $location_rows = [];
    $reset_rows = [];
    $other_logged_rows = [];

    foreach ($logged_rows as $row) {
        $delta = (int) ($row['delta'] ?? 0);
        $note  = (string) ($row['note'] ?? '');

        if ($delta === 0 && stripos($note, 'Location manually updated:') !== false) {
            $location_rows[] = $row;
            continue;
        }

        if (stripos($note, 'Exact stock set') !== false) {
            $reset_rows[] = $row;
            continue; // skip resets entirely
        }

        $logged_non_reset_delta += $delta;
        $other_logged_rows[] = $row;
    }

    // ------------------------------------------------------------
    // 4) Recursively project order consumption
    // ------------------------------------------------------------
    $expand_recursive = function(int $product_id, int $multiplier = 1, array &$accum = [], array $visited = []) use (&$expand_recursive) {
        if ($product_id <= 0) return $accum;
        if (in_array($product_id, $visited, true)) return $accum;

        $visited[] = $product_id;

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (empty($rows)) return $accum;

        foreach ($rows as $row) {
            $row_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($row_sku === '') continue;

            $row_qty = max(1, (int) ($row['qty'] ?? 1));
            $needed  = $row_qty * $multiplier;

            if (str_ends_with($row_sku, '-SA')) {
                $sa_id = (int) wc_get_product_id_by_sku($row_sku);
                if ($sa_id > 0) {
                    $expand_recursive($sa_id, $needed, $accum, $visited);
                    continue;
                }
            }

            if (!isset($accum[$row_sku])) {
                $accum[$row_sku] = 0;
            }
            $accum[$row_sku] += $needed;
        }

        return $accum;
    };

    $projected_consumption = 0;
    $projected_rows = [];

    $orders = wc_get_orders([
        'status'       => ['wc-processing', 'wc-completed', 'wc-on-hold'],
        'limit'        => $limit,
        'type'         => 'shop_order',
        'orderby'      => 'date',
        'order'        => 'ASC',
        'return'       => 'objects',
        'date_created' => '>=' . $start_date,
    ]);

    foreach ($orders as $order) {
        if (!$order instanceof \WC_Order) continue;

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof \WC_Order_Item_Product) continue;

            $order_qty = (int) $item->get_quantity();
            if ($order_qty <= 0) continue;

            $product = $item->get_product();
            if (!$product) continue;

            $bom_product_id = (int) $product->get_id();
            $parent_id      = (int) $product->get_parent_id();

            $main = \FR\ComponentsBOMPro\BOMManager::get_main_bom($bom_product_id);
            if (empty($main) && $parent_id > 0) {
                $bom_product_id = $parent_id;
            }

            $expanded = [];
            $expand_recursive($bom_product_id, $order_qty, $expanded);

            if (empty($expanded[$sku])) {
                continue;
            }

            $needed = (int) $expanded[$sku];
            $projected_consumption += $needed;

            $projected_rows[] = [
                'order_id'    => $order->get_id(),
                'order_date'  => $order->get_date_created()
                    ? $order->get_date_created()->date('Y-m-d H:i:s')
                    : '',
                'status'      => $order->get_status(),
                'ordered_sku' => $product->get_sku(),
                'ordered_qty' => $order_qty,
                'needed'      => $needed,
            ];
        }
    }

    // ------------------------------------------------------------
    // 5) Compute reset-blind shadow qty
    // starting qty
    // + non-reset logged deltas
    // - projected order consumption
    // ------------------------------------------------------------
    $shadow_qty = $starting_qty + $logged_non_reset_delta - $projected_consumption;
    $difference_vs_current = $shadow_qty - $current_loose_qty;

    $result = [
        'sku'                    => $sku,
        'start_date'             => $start_date,
        'starting_qty'           => $starting_qty,
        'current_loose_qty'      => $current_loose_qty,
        'logged_non_reset_delta' => $logged_non_reset_delta,
        'projected_consumption'  => $projected_consumption,
        'shadow_qty'             => $shadow_qty,
        'difference_vs_current'  => $difference_vs_current,
        'location_rows'          => $location_rows,
        'reset_rows'             => $reset_rows,
        'other_logged_rows'      => $other_logged_rows,
        'projected_rows'         => $projected_rows,
    ];

    echo '<pre>';
    echo "SHADOW REPLAY ABSOLUTE DRY RUN\n\n";
    print_r($result);
    echo '</pre>';
    exit;
});

/**
 * Replay missed BOM consumption from MANUAL parent stock increases
 * logged through the inventory shortcode page.
 *
 * Safe by design:
 * - uses a unique GET key
 * - uses a unique option key
 * - skips audit/reset rows containing "Exact stock set"
 * - skips components you already corrected manually
 * - dry run first, live run second
 *
 * DRY RUN:
 * /wp-admin/?fr_bom_manual_build_replay=1&start=2026-03-10%2000:00:00
 *
 * LIVE RUN:
 * /wp-admin/?fr_bom_manual_build_replay=1&live=1&start=2026-03-10%2000:00:00
 *
 * RESET processed IDs:
 * /wp-admin/?fr_bom_manual_build_replay=1&reset_processed=1
 */

if (!function_exists('fr_bom_manual_build_replay_collect_flat_bom')) {
    function fr_bom_manual_build_replay_collect_flat_bom(int $product_id): array {
        if ($product_id <= 0) {
            return [];
        }

        if (!class_exists('\FR\ComponentsBOMPro\BOMManager')) {
            return [];
        }

        $rows = \FR\ComponentsBOMPro\BOMManager::flatten_bom($product_id);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            $qty = (int) ($row['qty'] ?? 0);

            if ($sku === '' || $qty <= 0) {
                continue;
            }

            if (!isset($normalized[$sku])) {
                $normalized[$sku] = 0;
            }

            $normalized[$sku] += $qty;
        }

        return $normalized;
    }
}

add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (!isset($_GET['fr_bom_manual_build_replay'])) {
        return;
    }

    if (
        !function_exists('wc_get_product_id_by_sku') ||
        !function_exists('wc_get_product') ||
        !class_exists('\FR\ComponentsBOMPro\BOMManager') ||
        !class_exists('\FR\ComponentsBOMPro\FR_BOM_Inventory_API')
    ) {
        wp_die('Required WooCommerce / FR BOM classes are not available.');
    }

    global $wpdb;

    $start = isset($_GET['start'])
        ? sanitize_text_field(wp_unslash($_GET['start']))
        : '2026-03-10 00:00:00';

    $is_live = isset($_GET['live']) && $_GET['live'] === '1';
    $reset_processed = isset($_GET['reset_processed']) && $_GET['reset_processed'] === '1';

    $log_table = $wpdb->prefix . 'fr_cbompro_stock_adjust_log';

    // Components you already fixed manually and do NOT want touched here.
    $skip_components = [
        'HDW-80011',
        'HDW-80012',
    ];

    // Optional parent SKUs to skip completely, if needed later.
    $skip_parents = [
        // 'CP-40001-SA',
    ];

    $processed_option_key = 'fr_bom_manual_build_replay_processed_ids';

    if ($reset_processed) {
        delete_option($processed_option_key);
    }

    $processed_ids = get_option($processed_option_key, []);
    if (!is_array($processed_ids)) {
        $processed_ids = [];
    }

    /**
     * Pull positive stock increases from the adjustment log.
     *
     * We intentionally exclude audit/reset rows here:
     * note LIKE '%Exact stock set%'
     */
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, created_at, sku, source, delta, prev_qty, new_qty, note, user_id
             FROM {$log_table}
             WHERE created_at >= %s
               AND delta > 0
               AND (note IS NULL OR note NOT LIKE %s)
             ORDER BY id ASC",
            $start,
            '%Exact stock set%'
        ),
        ARRAY_A
    );

    $report_lines = [];
    $parent_totals = [];
    $component_totals = [];

    $applied_rows = 0;
    $skipped_rows = 0;
    $error_count = 0;

    foreach ((array) $rows as $row) {
        $log_id = (int) ($row['id'] ?? 0);
        $parent_sku = strtoupper(trim((string) ($row['sku'] ?? '')));
        $delta = (int) ($row['delta'] ?? 0);
        $note = (string) ($row['note'] ?? '');

        if ($log_id <= 0 || $parent_sku === '' || $delta <= 0) {
            $skipped_rows++;
            continue;
        }

        // Double safety: also skip in PHP if note contains Exact stock set.
        if (stripos($note, 'Exact stock set') !== false) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: audit/reset row detected by note";
            $skipped_rows++;
            continue;
        }

        if (in_array($parent_sku, $skip_parents, true)) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: parent SKU in skip list";
            $skipped_rows++;
            continue;
        }

        if ($is_live && isset($processed_ids[$log_id])) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: already processed";
            $skipped_rows++;
            continue;
        }

        $parent_product_id = (int) wc_get_product_id_by_sku($parent_sku);
        if ($parent_product_id <= 0) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: not found as Woo product";
            $skipped_rows++;
            continue;
        }

        $build_flag = (string) get_post_meta($parent_product_id, '_fr_build_from_components', true);
        if ($build_flag !== 'yes') {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: _fr_build_from_components is not yes";
            $skipped_rows++;
            continue;
        }

        $flat_bom = fr_bom_manual_build_replay_collect_flat_bom($parent_product_id);
        if (empty($flat_bom)) {
            $report_lines[] = "SKIP {$parent_sku} log #{$log_id}: no flattened BOM found";
            $skipped_rows++;
            continue;
        }

        if (!isset($parent_totals[$parent_sku])) {
            $parent_totals[$parent_sku] = 0;
        }
        $parent_totals[$parent_sku] += $delta;

        $report_lines[] = "";
        $report_lines[] = "PARENT {$parent_sku} | +{$delta} | log #{$log_id} | {$row['created_at']}";

        $row_ok = true;

        foreach ($flat_bom as $component_sku => $qty_per_unit) {
            $qty_per_unit = (int) $qty_per_unit;
            if ($qty_per_unit <= 0) {
                continue;
            }

            if ($component_sku === $parent_sku) {
                continue;
            }

            $needed = $qty_per_unit * $delta;
            if ($needed <= 0) {
                continue;
            }

            if (in_array($component_sku, $skip_components, true)) {
                $report_lines[] = "  SKIP COMPONENT {$component_sku} | would consume {$needed} | skipped by config";
                continue;
            }

            if (!isset($component_totals[$component_sku])) {
                $component_totals[$component_sku] = 0;
            }
            $component_totals[$component_sku] += $needed;

            if (!$is_live) {
                $report_lines[] = "  WOULD CONSUME {$component_sku} x {$needed}";
                continue;
            }

            $component_pid = (int) wc_get_product_id_by_sku($component_sku);
            $apply_note = sprintf(
                'Manual build replay from parent %s x %d (adjust log #%d, %s)',
                $parent_sku,
                $delta,
                $log_id,
                $row['created_at']
            );

            if ($component_pid > 0) {
                $component_product = wc_get_product($component_pid);

                if ($component_product && $component_product->managing_stock()) {
                    $current_qty = $component_product->get_stock_quantity();
                    $current_qty = ($current_qty === null) ? 0 : (int) $current_qty;

                    wc_update_product_stock($component_pid, $needed, 'decrease');

                    $report_lines[] = "  APPLIED WOO {$component_sku} | -{$needed} | {$current_qty} -> " . ($current_qty - $needed);
                    continue;
                }
            }

            $ok = \FR\ComponentsBOMPro\FR_BOM_Inventory_API::increment_loose_stock_by_sku(
                $component_sku,
                -1 * $needed,
                $apply_note
            );

            if ($ok) {
                $report_lines[] = "  APPLIED LOOSE {$component_sku} | -{$needed}";
            } else {
                $report_lines[] = "  ERROR {$component_sku} | failed applying -{$needed}";
                $row_ok = false;
                $error_count++;
            }
        }

        if ($is_live && $row_ok) {
            $processed_ids[$log_id] = current_time('mysql');
            $applied_rows++;
        }
    }

    if ($is_live) {
        update_option($processed_option_key, $processed_ids, false);
    }

    ksort($parent_totals);
    ksort($component_totals);

    echo '<div class="wrap">';
    echo '<h1>Manual Build Replay</h1>';
    echo '<p><strong>Mode:</strong> ' . ($is_live ? 'LIVE APPLY' : 'DRY RUN') . '</p>';
    echo '<p><strong>Start date:</strong> ' . esc_html($start) . '</p>';
    echo '<p><strong>Processed option key:</strong> ' . esc_html($processed_option_key) . '</p>';
    echo '<p><strong>Applied rows:</strong> ' . (int) $applied_rows . ' | <strong>Skipped rows:</strong> ' . (int) $skipped_rows . ' | <strong>Errors:</strong> ' . (int) $error_count . '</p>';

    echo '<hr>';
    echo '<h2>Parent additions found</h2>';

    if (empty($parent_totals)) {
        echo '<p>No qualifying parent additions found.</p>';
    } else {
        echo '<table class="widefat striped" style="max-width:760px;">';
        echo '<thead><tr><th>Parent SKU</th><th>Total Added</th></tr></thead><tbody>';

        foreach ($parent_totals as $sku => $qty) {
            echo '<tr><td>' . esc_html($sku) . '</td><td>' . (int) $qty . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    echo '<hr>';
    echo '<h2>Component totals ' . ($is_live ? 'applied' : 'that would be applied') . '</h2>';

    if (empty($component_totals)) {
        echo '<p>No component consumption calculated.</p>';
    } else {
        echo '<table class="widefat striped" style="max-width:760px;">';
        echo '<thead><tr><th>Component SKU</th><th>Total Consumption</th></tr></thead><tbody>';

        foreach ($component_totals as $sku => $qty) {
            echo '<tr><td>' . esc_html($sku) . '</td><td>' . (int) $qty . '</td></tr>';
        }

        echo '</tbody></table>';
    }

    echo '<hr>';
    echo '<h2>Detailed log</h2>';
    echo '<div style="font-family:monospace; white-space:pre-wrap; background:#fff; border:1px solid #ccd0d4; padding:12px;">';
    echo esc_html(implode("\n", $report_lines));
    echo '</div>';

    echo '</div>';
    exit;
});

/* When someone buys something and the email is triggered phone into GTM */
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $value = $order->get_total();
    ?>
    <script>
    gtag('event', 'conversion', {
        'send_to': 'AW-11350447981/v2x_CMLH9ZIcEO2uqKQq',
        'value': <?php echo $value; ?>,
        'currency': 'USD'
    });

    gtag('set', 'user_data', {
        email: '<?php echo esc_js($order->get_billing_email()); ?>',
        phone_number: '<?php echo esc_js($order->get_billing_phone()); ?>'
    });
    </script>
    <?php
});

/**
 * Prevent checkout with flat rate when preferred Shippo rates are available.
 *
 * Flat rate can still be the fallback/default.
 * But if UPS Ground or Ground Saver exists, customer must choose one of those.
 */
add_action('woocommerce_after_checkout_validation', 'fr_block_flat_rate_when_shippo_rates_available', 20, 2);
function fr_block_flat_rate_when_shippo_rates_available($data, $errors) {
    if (!function_exists('WC') || !WC()->shipping) {
        return;
    }

    $posted_methods = [];

    if (isset($_POST['shipping_method']) && is_array($_POST['shipping_method'])) {
        $posted_methods = array_map('wc_clean', wp_unslash($_POST['shipping_method']));
    }

    $chosen_methods = !empty($posted_methods)
        ? $posted_methods
        : (array) WC()->session->get('chosen_shipping_methods', []);

    $packages = WC()->shipping()->get_packages();

    foreach ($packages as $package_index => $package) {
        if (empty($package['rates'])) {
            continue;
        }

        $preferred_available = false;

        foreach ($package['rates'] as $rate) {
            $label = strtolower($rate->get_label());
            $label = preg_replace('/\s+/', ' ', $label);

            $is_ground = strpos($label, 'ups ground') !== false && strpos($label, 'saver') === false;
            $is_saver  = strpos($label, 'ground saver') !== false;

            if ($is_ground || $is_saver) {
                $preferred_available = true;
                break;
            }
        }

        if (!$preferred_available) {
            continue;
        }

        $selected_method = $chosen_methods[$package_index] ?? '';

        if (strpos($selected_method, 'flat_rate') === 0) {
            $errors->add(
                'fr_flat_rate_blocked',
                'Please select UPS Ground or Ground Saver before placing your order.'
            );
            return;
        }
    }
}

/*add_filter('woocommerce_package_rates', 'prioritize_shippo_over_flat_rate', 100, 2);
function prioritize_shippo_over_flat_rate($rates, $package) {
    $has_shippo = false;

    foreach ($rates as $rate_id => $rate) {
        if (strpos($rate->method_id, 'shippo') !== false) {
            $has_shippo = true;
            break;
        }
    }

    if ($has_shippo) {
        foreach ($rates as $rate_id => $rate) {
            if ($rate->method_id === 'flat_rate') {
                unset($rates[$rate_id]);
            }
        }
    }

    return $rates;
}*/

/**
 * =========================================================
 * FLEX ROCK SHIPPING - CHECKOUT ONLY
 * =========================================================
 * Goal:
 * - Cart shipping calculator is disabled
 * - Checkout is the single source of truth
 * - If "Ship to different address" is unchecked, use billing address for rates
 * - If "Ship to different address" is checked, use shipping address for rates
 * - Clear stale shipping cache during checkout refresh
 * - Optionally show only UPS Ground / Ground Saver when available
 */

/**
 * Default ship-to-different-address to unchecked.
 */
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

/**
 * Helper: determine whether checkout currently has
 * "Ship to different address" enabled.
 */
function fr_is_ship_to_different_address_enabled() {
    $enabled = false;

    if (!empty($_POST['post_data'])) {
        parse_str(wp_unslash($_POST['post_data']), $posted);

        if (isset($posted['ship_to_different_address'])) {
            $enabled = wc_string_to_bool($posted['ship_to_different_address']);
        }
    } elseif (isset($_POST['ship_to_different_address'])) {
        $enabled = wc_string_to_bool(wp_unslash($_POST['ship_to_different_address']));
    }

    return $enabled;
}

/**
 * Helper: clear cached shipping package rates.
 */
function fr_clear_shipping_cache() {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    foreach ((array) WC()->session->get_session_data() as $key => $value) {
        if (strpos($key, 'shipping_for_package_') === 0) {
            WC()->session->__unset($key);
        }
    }
}

/**
 * On checkout refresh, clear stale cached shipping rates.
 */
add_action('woocommerce_checkout_update_order_review', 'fr_clear_shipping_cache_on_checkout_refresh', 1);
function fr_clear_shipping_cache_on_checkout_refresh($posted_data) {
    fr_clear_shipping_cache();
}

/**
 * Force WooCommerce shipping packages to use the correct
 * checkout address depending on checkbox state.
 *
 * - If ship-to-different is OFF: use billing address
 * - If ship-to-different is ON: use shipping address
 */
add_filter('woocommerce_cart_shipping_packages', 'fr_use_checkout_address_for_shipping_packages', 20);
function fr_use_checkout_address_for_shipping_packages($packages) {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return $packages;
    }

    $posted = [];

    if (!empty($_POST['post_data'])) {
        parse_str(wp_unslash($_POST['post_data']), $posted);
    } else {
        $posted = array_map('wp_unslash', $_POST);
    }

    /*
     * Important:
     * Google Pay / express checkout may not send normal Woo checkout fields.
     * If no normal checkout address was posted, do NOT overwrite the package destination.
     * Let Payment Plugins / Woo / Shippo use the address already placed on the package/customer.
     */
    $has_normal_checkout_address =
        !empty($posted['billing_country']) ||
        !empty($posted['billing_postcode']) ||
        !empty($posted['shipping_country']) ||
        !empty($posted['shipping_postcode']);

    if (!$has_normal_checkout_address) {
        return $packages;
    }

    $ship_to_different = fr_is_ship_to_different_address_enabled();

    if ($ship_to_different) {
        $country   = isset($posted['shipping_country']) ? wc_clean($posted['shipping_country']) : '';
        $state     = isset($posted['shipping_state']) ? wc_clean($posted['shipping_state']) : '';
        $postcode  = isset($posted['shipping_postcode']) ? wc_clean($posted['shipping_postcode']) : '';
        $city      = isset($posted['shipping_city']) ? wc_clean($posted['shipping_city']) : '';
        $address_1 = isset($posted['shipping_address_1']) ? wc_clean($posted['shipping_address_1']) : '';
        $address_2 = isset($posted['shipping_address_2']) ? wc_clean($posted['shipping_address_2']) : '';
    } else {
        $country   = isset($posted['billing_country']) ? wc_clean($posted['billing_country']) : '';
        $state     = isset($posted['billing_state']) ? wc_clean($posted['billing_state']) : '';
        $postcode  = isset($posted['billing_postcode']) ? wc_clean($posted['billing_postcode']) : '';
        $city      = isset($posted['billing_city']) ? wc_clean($posted['billing_city']) : '';
        $address_1 = isset($posted['billing_address_1']) ? wc_clean($posted['billing_address_1']) : '';
        $address_2 = isset($posted['billing_address_2']) ? wc_clean($posted['billing_address_2']) : '';
    }

    /*
     * If the posted address is still missing core destination fields,
     * do not overwrite Shippo/Google Pay destination data.
     */
    if (empty($country) || empty($postcode)) {
        return $packages;
    }

    foreach ($packages as $i => $package) {
        $existing_destination = isset($package['destination']) && is_array($package['destination'])
            ? $package['destination']
            : [];

        $packages[$i]['destination'] = array_merge($existing_destination, [
            'country'   => $country,
            'state'     => $state,
            'postcode'  => $postcode,
            'city'      => $city,
            'address'   => $address_1,
            'address_1' => $address_1,
            'address_2' => $address_2,
        ]);
    }

    return $packages;
}

/**
 * Sync posted checkout address into Woo customer object
 * so shipping plugins that read WC()->customer get fresh data too.
 */
add_action('woocommerce_checkout_update_order_review', 'fr_sync_checkout_posted_address_to_customer', 5);
function fr_sync_checkout_posted_address_to_customer($posted_data) {
    if (!function_exists('WC') || !WC()->customer) {
        return;
    }

    parse_str($posted_data, $posted);

    $ship_to_different = false;
    if (isset($posted['ship_to_different_address'])) {
        $ship_to_different = wc_string_to_bool($posted['ship_to_different_address']);
    }

    // Always sync billing fields
    if (isset($posted['billing_country'])) {
        WC()->customer->set_billing_country(wc_clean($posted['billing_country']));
    }
    if (isset($posted['billing_state'])) {
        WC()->customer->set_billing_state(wc_clean($posted['billing_state']));
    }
    if (isset($posted['billing_postcode'])) {
        WC()->customer->set_billing_postcode(wc_clean($posted['billing_postcode']));
    }
    if (isset($posted['billing_city'])) {
        WC()->customer->set_billing_city(wc_clean($posted['billing_city']));
    }
    if (isset($posted['billing_address_1'])) {
        WC()->customer->set_billing_address(wc_clean($posted['billing_address_1']));
    }
    if (isset($posted['billing_address_2'])) {
        WC()->customer->set_billing_address_2(wc_clean($posted['billing_address_2']));
    }

    // Sync shipping fields depending on checkbox state
    if ($ship_to_different) {
        if (isset($posted['shipping_country'])) {
            WC()->customer->set_shipping_country(wc_clean($posted['shipping_country']));
        }
        if (isset($posted['shipping_state'])) {
            WC()->customer->set_shipping_state(wc_clean($posted['shipping_state']));
        }
        if (isset($posted['shipping_postcode'])) {
            WC()->customer->set_shipping_postcode(wc_clean($posted['shipping_postcode']));
        }
        if (isset($posted['shipping_city'])) {
            WC()->customer->set_shipping_city(wc_clean($posted['shipping_city']));
        }
        if (isset($posted['shipping_address_1'])) {
            WC()->customer->set_shipping_address(wc_clean($posted['shipping_address_1']));
        }
        if (isset($posted['shipping_address_2'])) {
            WC()->customer->set_shipping_address_2(wc_clean($posted['shipping_address_2']));
        }
    } else {
        // If shipping address is same as billing, mirror billing into shipping.
        if (isset($posted['billing_country'])) {
            WC()->customer->set_shipping_country(wc_clean($posted['billing_country']));
        }
        if (isset($posted['billing_state'])) {
            WC()->customer->set_shipping_state(wc_clean($posted['billing_state']));
        }
        if (isset($posted['billing_postcode'])) {
            WC()->customer->set_shipping_postcode(wc_clean($posted['billing_postcode']));
        }
        if (isset($posted['billing_city'])) {
            WC()->customer->set_shipping_city(wc_clean($posted['billing_city']));
        }
        if (isset($posted['billing_address_1'])) {
            WC()->customer->set_shipping_address(wc_clean($posted['billing_address_1']));
        }
        if (isset($posted['billing_address_2'])) {
            WC()->customer->set_shipping_address_2(wc_clean($posted['billing_address_2']));
        }
    }
}

/**
 * Refresh checkout when the checkbox changes.
 */
/**
 * Refresh checkout when the checkbox changes OR address fields change.
 * - Added processing lock to prevent overlapping AJAX (the #1 cause of stale Shippo rates).
 * - Longer debounce (shipping API calls are slow).
 * - Fewer initial triggers (the 3 setTimeouts were firing a burst of requests).
 * - Still handles autofill reliably.
 */
add_action('wp_footer', 'fr_refresh_checkout_when_address_changes', 100);
function fr_refresh_checkout_when_address_changes() {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return;
    }
    ?>
    <script>
    jQuery(function($){
        var updateTimer = null;
        var lastAddressSnapshot = '';
        var isUpdatingCheckout = false;
        var pendingUpdate = false;

        function getAddressSnapshot() {
            var useShipping = $('#ship-to-different-address-checkbox').is(':checked');

            var fields = useShipping ? [
                $('#shipping_country').val() || '',
                $('#shipping_state').val() || '',
                $('#shipping_postcode').val() || '',
                $('#shipping_city').val() || '',
                $('#shipping_address_1').val() || '',
                $('#shipping_address_2').val() || '',
                'shipping'
            ] : [
                $('#billing_country').val() || '',
                $('#billing_state').val() || '',
                $('#billing_postcode').val() || '',
                $('#billing_city').val() || '',
                $('#billing_address_1').val() || '',
                $('#billing_address_2').val() || '',
                'billing'
            ];

            return fields.join('|');
        }

        $(document.body).on('update_checkout', function() {
            isUpdatingCheckout = true;
        });

        $(document.body).on('updated_checkout checkout_error', function() {
            isUpdatingCheckout = false;

            if (pendingUpdate) {
                pendingUpdate = false;

                setTimeout(function() {
                    queueCheckoutUpdate();
                }, 150);
            }
        });

        function queueCheckoutUpdate() {
            clearTimeout(updateTimer);

            updateTimer = setTimeout(function() {
                var currentSnapshot = getAddressSnapshot();

                if (currentSnapshot === lastAddressSnapshot) {
                    return;
                }

                lastAddressSnapshot = currentSnapshot;

                if (isUpdatingCheckout || $('form.checkout').hasClass('processing')) {
                    pendingUpdate = true;
                    return;
                }

                $(document.body).trigger('update_checkout');
            }, 750);
        }

        $('form.checkout').on('change', '#ship-to-different-address-checkbox', queueCheckoutUpdate);

        $('form.checkout').on(
            'change input',
            '#billing_country, #billing_state, #billing_postcode, #billing_city, #billing_address_1, #billing_address_2, #shipping_country, #shipping_state, #shipping_postcode, #shipping_city, #shipping_address_1, #shipping_address_2',
            queueCheckoutUpdate
        );

        setTimeout(function() {
            lastAddressSnapshot = '';
            queueCheckoutUpdate();
        }, 1000);

        lastAddressSnapshot = getAddressSnapshot();
    });
    </script>
    <?php
}

/**
 * Optional:
 * Hide unwanted methods visually, keeping only UPS Ground / Ground Saver.
 */
add_action('wp_footer', 'fr_checkout_hide_unwanted_shipping_options', 100);
function fr_checkout_hide_unwanted_shipping_options() {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return;
    }
    ?>
    <script>
    jQuery(function($){
        function filterShippingOptions() {
            var $methods = $('ul#shipping_method li, ul.woocommerce-shipping-methods li');

            if (!$methods.length) {
                return;
            }

            var foundPreferred = false;

            $methods.each(function() {
                var text = $(this).text().toLowerCase().replace(/\s+/g, ' ').trim();
                var isGround = text.indexOf('ups ground') !== -1 && text.indexOf('saver') === -1;
                var isSaver  = text.indexOf('ground saver') !== -1;

                if (isGround || isSaver) {
                    foundPreferred = true;
                }
            });

            // Only hide others if at least one preferred rate exists.
            if (!foundPreferred) {
                $methods.show();
                return;
            }

            $methods.each(function() {
                var $li = $(this);
                var text = $li.text().toLowerCase().replace(/\s+/g, ' ').trim();
                var isGround = text.indexOf('ups ground') !== -1 && text.indexOf('saver') === -1;
                var isSaver  = text.indexOf('ground saver') !== -1;

                if (isGround || isSaver) {
                    $li.show();
                } else {
                    $li.hide();
                }
            });
        }

        filterShippingOptions();
        $(document.body).on('updated_checkout', filterShippingOptions);
    });
    </script>
    <?php
}

add_filter('woocommerce_cart_shipping_method_full_label', 'fr_customize_flat_rate_label', 10, 2);
function fr_customize_flat_rate_label($label, $method) {
    if (!is_cart()) {
        return $label;
    }

    $method_id = method_exists($method, 'get_method_id')
        ? $method->get_method_id()
        : ($method->method_id ?? '');

    // Keep flat rate message
    if ($method_id === 'flat_rate') {
        return 'The total is a default estimate until checkout';
    }

    // Hide everything else
    return '';
}

add_action('wp_head', 'fr_hide_non_flat_rate_shipping_rows_on_cart');
function fr_hide_non_flat_rate_shipping_rows_on_cart() {
    if (!is_cart()) {
        return;
    }
    ?>
    <style>
        /* Hide all non-flat-rate methods */
        .woocommerce-cart ul#shipping_method li:not(:has(input[value*="flat_rate"])) {
            display: none !important;
        }
        
        /* Hide the radio button for flat rate */
        .woocommerce-cart ul#shipping_method li:has(input[value*="flat_rate"]) input[type="radio"] {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * Use JavaScript to replace the shipping destination text
 * This works 100% of the time regardless of filter issues
 */
add_action('wp_footer', 'fr_replace_shipping_destination_text_js');
function fr_replace_shipping_destination_text_js() {
    if (!is_checkout() && !is_cart()) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        function replaceShippingText() {
            // Find the shipping destination paragraph
            var $shippingDest = $('.woocommerce-shipping-destination');
            
            if ($shippingDest.length) {
                // Replace the entire HTML content
                $shippingDest.html('Eligible locations include the United States, Canada, and Puerto Rico');
            }
        }
        
        // Run immediately
        replaceShippingText();
        
        // Run after checkout updates (AJAX)
        $(document.body).on('updated_checkout', function() {
            replaceShippingText();
        });
        
        // Also run after cart updates
        $(document.body).on('updated_cart_totals', function() {
            replaceShippingText();
        });
    });
    </script>
    <?php
}

add_action('woocommerce_checkout_update_order_review', function($posted_data) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        parse_str($posted_data, $posted);
        error_log(sprintf(
            '[Checkout Refresh] ship_to_diff=%s, postcode=%s',
            $posted['ship_to_different_address'] ?? 'not set',
            ($posted['ship_to_different_address'] ?? false) 
                ? ($posted['shipping_postcode'] ?? 'empty')
                : ($posted['billing_postcode'] ?? 'empty')
        ));
    }
}, 0);

/**
 * Force checkout to start with blank address data
 * for all customers, including logged-in users.
 */
add_action('template_redirect', 'fr_blank_checkout_customer_addresses', 1);
function fr_blank_checkout_customer_addresses() {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return;
    }

    if (!function_exists('WC') || !WC()->customer || !WC()->session) {
        return;
    }

    // Prevent running repeatedly in the same session.
    if (WC()->session->get('fr_checkout_blank_initialized')) {
        return;
    }

    // Blank billing address fields.
    WC()->customer->set_billing_country('');
    WC()->customer->set_billing_state('');
    WC()->customer->set_billing_postcode('');
    WC()->customer->set_billing_city('');
    WC()->customer->set_billing_address('');
    WC()->customer->set_billing_address_2('');

    // Blank shipping address fields.
    WC()->customer->set_shipping_country('');
    WC()->customer->set_shipping_state('');
    WC()->customer->set_shipping_postcode('');
    WC()->customer->set_shipping_city('');
    WC()->customer->set_shipping_address('');
    WC()->customer->set_shipping_address_2('');

    // Optional: keep name/email/phone if you want checkout less annoying.
    // If you want literally everything blank, uncomment these too:
    /*
    WC()->customer->set_billing_first_name('');
    WC()->customer->set_billing_last_name('');
    WC()->customer->set_billing_company('');
    WC()->customer->set_billing_phone('');
    WC()->customer->set_billing_email('');
    WC()->customer->set_shipping_first_name('');
    WC()->customer->set_shipping_last_name('');
    WC()->customer->set_shipping_company('');
    */

    // Clear cached shipping packages/rates.
    foreach ((array) WC()->session->get_session_data() as $key => $value) {
        if (strpos($key, 'shipping_for_package_') === 0) {
            WC()->session->__unset($key);
        }
    }

    // Clear selected shipping methods so Woo doesn't cling to old choices.
    WC()->session->set('chosen_shipping_methods', null);

    // Save customer/session data.
    WC()->customer->save();
    WC()->session->set('fr_checkout_blank_initialized', true);
}

add_action('woocommerce_checkout_update_order_review', 'fr_preserve_posted_shipping_method', 20);
function fr_preserve_posted_shipping_method($posted_data) {
    if (!function_exists('WC') || !WC()->session) {
        return;
    }

    parse_str($posted_data, $posted);

    if (!empty($posted['shipping_method']) && is_array($posted['shipping_method'])) {
        WC()->session->set('chosen_shipping_methods', array_map('wc_clean', $posted['shipping_method']));
    }
}

/*add_action('admin_init', function () {
    if (!current_user_can('manage_woocommerce') || !isset($_GET['check_order_meta'])) {
        return;
    }

    $order_id = absint($_GET['check_order_meta']);
    if (!$order_id) {
        exit('Missing order ID.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        exit('Order not found.');
    }

    echo '<pre>';

    echo "Transaction ID:\n";
    var_dump($order->get_transaction_id());

    echo "\nPayment method:\n";
    var_dump($order->get_payment_method());
    var_dump($order->get_payment_method_title());

    echo "\nMeta data containing stripe / intent / charge / txn:\n";
    $meta = $order->get_meta_data();

    foreach ($meta as $item) {
        $data = $item->get_data();
        $key = isset($data['key']) ? $data['key'] : '';
        $value = isset($data['value']) ? $data['value'] : '';

        $haystack = strtolower($key . ' ' . print_r($value, true));

        if (
            strpos($haystack, 'stripe') !== false ||
            strpos($haystack, 'intent') !== false ||
            strpos($haystack, 'charge') !== false ||
            strpos($haystack, 'txn') !== false ||
            strpos($haystack, 'payment') !== false
        ) {
            echo "KEY: " . $key . "\n";
            print_r($value);
            echo "\n-----------------------\n";
        }
    }

    echo '</pre>';
    exit;
});*/

add_filter('woocommerce_cart_item_remove_link', function($link) {
    return str_replace(
        '</a>',
        '<span style="display:block; width:70px; margin-top:4px; margin-left:-25px; font-size:12px; line-height:1.2; color:#686868; text-align:center; font-weight:400;">Remove</span></a>',
        $link
    );
}, 10, 1);

/**
 * Only allow preferred shipping methods when live Shippo rates are available.
 * This affects WooCommerce rates before they are passed to checkout / Google Pay.
 */
add_filter('woocommerce_package_rates', function ($rates, $package) {

    if (empty($rates) || !is_array($rates)) {
        return $rates;
    }

    $preferred_rates = [];

    foreach ($rates as $rate_id => $rate) {
        $label = strtolower($rate->get_label());

        if (
            strpos($label, 'ups ground') !== false ||
            strpos($label, 'ground saver') !== false
        ) {
            $preferred_rates[$rate_id] = $rate;
        }
    }

    /**
     * Only filter if preferred Shippo rates exist.
     * This prevents breaking fallback flat rate when Shippo is unavailable.
     */
    if (!empty($preferred_rates)) {
        return $preferred_rates;
    }

    return $rates;

}, 100, 2);

/*add_action('woocommerce_checkout_order_processed', function($order_id) {
    if (!empty($_POST['wc_stripe_payment_request_type'])) {
        update_post_meta(
            $order_id,
            '_wallet_type',
            sanitize_text_field($_POST['wc_stripe_payment_request_type'])
        );
    }
});*/

/**
 * Save express checkout flag to Woo session
 */
//add_action('wp_ajax_fr_set_express_checkout_flag', 'fr_set_express_checkout_flag');
//add_action('wp_ajax_nopriv_fr_set_express_checkout_flag', 'fr_set_express_checkout_flag');

/*function fr_set_express_checkout_flag() {
    if (!WC()->session) {
        wp_send_json_error('No session');
    }

    WC()->session->set('fr_express_checkout_clicked', 'yes');

    wp_send_json_success();
}*/

/**
 * Save flag to order meta at checkout
 */
/*add_action('woocommerce_checkout_create_order', function($order) {
    if (WC()->session && WC()->session->get('fr_express_checkout_clicked') === 'yes') {
        $order->update_meta_data('_fr_express_checkout_clicked', 'yes');
    }
});*/

add_action('wp_ajax_fr_gpay_clicked', 'fr_gpay_clicked_log');
add_action('wp_ajax_nopriv_fr_gpay_clicked', 'fr_gpay_clicked_log');

function fr_gpay_clicked_log() {
    $upload_dir = wp_upload_dir();
    $file = $upload_dir['basedir'] . '/gpay-clicks.txt';

    $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : 'unknown';

    $line = current_time('Y-m-d H:i:s') . " - " . $source . "\n";

    file_put_contents($file, $line, FILE_APPEND);

    wp_send_json_success();
}

/**
 * Add "Push to Shippo Order" to WooCommerce order actions dropdown.
 */
add_filter('woocommerce_order_actions', function($actions) {
    $actions['push_to_shippo_order'] = 'Push to Shippo Order';
    return $actions;
});

/**
 * Handle manual order action: create a Shippo Order.
 */
add_action('woocommerce_order_action_push_to_shippo_order', function($order) {

    if (!$order instanceof WC_Order) {
        return;
    }

    // Prevent duplicate Shippo Orders.
    $existing_shippo_order_id = $order->get_meta('_shippo_order_id');

    if (!empty($existing_shippo_order_id)) {
        $order->add_order_note('Shippo: order already exists in Shippo: ' . $existing_shippo_order_id);
        return;
    }

    // Prevent simultaneous/manual double-click/race-condition pushes.
    if ($order->get_meta('_shippo_push_in_progress')) {
        $order->add_order_note('Shippo push skipped: already in progress.');
        return;
    }

    $order->update_meta_data('_shippo_push_in_progress', 'yes');
    $order->save();

    $clear_shippo_lock = function() use ($order) {
        $order->delete_meta_data('_shippo_push_in_progress');
        $order->save();
    };

    $shippo_token = defined('SHIPPO_TOKEN') ? SHIPPO_TOKEN : '';

    if (empty($shippo_token)) {
        $order->add_order_note('Shippo error: missing SHIPPO_TOKEN.');
        $clear_shippo_lock();
        return;
    }

    $base = wc_get_base_location();

    $from_address = [
        'name'     => get_bloginfo('name'),
        'company'  => get_bloginfo('name'),
        'street1'  => get_option('woocommerce_store_address'),
        'street2'  => get_option('woocommerce_store_address_2'),
        'city'     => get_option('woocommerce_store_city'),
        'state'    => $base['state'],
        'zip'      => get_option('woocommerce_store_postcode'),
        'country'  => $base['country'],
        'phone'    => get_option('admin_phone') ?: '000-000-0000',
        'email'    => get_option('admin_email'),
    ];

    $to_name = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());

    if (empty($to_name)) {
        $to_name = $order->get_formatted_billing_full_name();
    }

    $to_address = [
        'name'     => $to_name,
        'company'  => $order->get_shipping_company() ?: $order->get_billing_company(),
        'street1'  => $order->get_shipping_address_1() ?: $order->get_billing_address_1(),
        'street2'  => $order->get_shipping_address_2() ?: $order->get_billing_address_2(),
        'city'     => $order->get_shipping_city() ?: $order->get_billing_city(),
        'state'    => $order->get_shipping_state() ?: $order->get_billing_state(),
        'zip'      => $order->get_shipping_postcode() ?: $order->get_billing_postcode(),
        'country'  => $order->get_shipping_country() ?: $order->get_billing_country(),
        'phone'    => $order->get_billing_phone(),
        'email'    => $order->get_billing_email(),
    ];

    foreach (['name', 'street1', 'city', 'state', 'zip', 'country'] as $field) {
        if (empty($to_address[$field])) {
            $order->add_order_note('Shippo error: missing customer address field: ' . $field);
            $clear_shippo_lock();
            return;
        }
    }

    $line_items = [];
    $total_weight_lb = 0.0;
    $fallback_each_lb = 1.0;
    $store_unit = get_option('woocommerce_weight_unit', 'kg');

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $qty = max(1, (int) $item->get_quantity());

        $item_weight_lb = $fallback_each_lb;

        if ($product && $product->get_weight()) {
            $item_weight = (float) $product->get_weight();

            if ($store_unit === 'kg') {
                $item_weight_lb = $item_weight * 2.20462;
            } elseif ($store_unit === 'g') {
                $item_weight_lb = $item_weight * 0.00220462;
            } elseif ($store_unit === 'oz') {
                $item_weight_lb = $item_weight * 0.0625;
            } else {
                $item_weight_lb = $item_weight;
            }
        }

        $total_weight_lb += $item_weight_lb * $qty;

        $line_items[] = [
            'title'       => $item->get_name(),
            'sku'         => $product ? $product->get_sku() : '',
            'quantity'    => $qty,
            'total_price' => number_format((float) $item->get_total(), 2, '.', ''),
            'currency'    => $order->get_currency(),
            'weight'      => number_format($item_weight_lb, 2, '.', ''),
            'weight_unit' => 'lb',
        ];
    }

    if ($total_weight_lb <= 0) {
        $total_weight_lb = $fallback_each_lb;
    }

    $parcel = [
        'length'        => '10',
        'width'         => '8',
        'height'        => '4',
        'distance_unit' => 'in',
        'weight'        => number_format($total_weight_lb, 2, '.', ''),
        'mass_unit'     => 'lb',
    ];

    $shipping_method = '';
    $shipping_cost   = 0;

    foreach ($order->get_shipping_methods() as $shipping_item) {
        $shipping_method = $shipping_item->get_name();
        $shipping_cost  += (float) $shipping_item->get_total();
    }

    $request_body = [
        'to_address'      => $to_address,
        'from_address'    => $from_address,
        'line_items'      => $line_items,
        'parcels'         => [$parcel],
        'placed_at'       => gmdate('c', strtotime($order->get_date_created()->date('Y-m-d H:i:s'))),
        'order_number'    => (string) $order->get_order_number(),
        'order_status'    => 'PAID',
        'shipping_cost'          => number_format($shipping_cost, 2, '.', ''),
        'shipping_cost_currency' => $order->get_currency(),
        'shipping_method'        => $shipping_method ?: 'Customer selected shipping',
        'subtotal_price'  => number_format((float) $order->get_subtotal(), 2, '.', ''),
        'total_price'     => number_format((float) $order->get_total(), 2, '.', ''),
        'total_tax'       => number_format((float) $order->get_total_tax(), 2, '.', ''),
        'currency'        => $order->get_currency(),
        'weight'          => (float) $total_weight_lb,
        'weight_unit'     => 'lb',
        'shop_app'        => 'WooCommerce',
    ];

    $response = wp_remote_post('https://api.goshippo.com/orders/', [
        'headers' => [
            'Authorization' => 'ShippoToken ' . $shippo_token,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode($request_body),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        $order->add_order_note('Shippo error: ' . $response->get_error_message());
        $clear_shippo_lock();
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if ($response_code < 200 || $response_code >= 300) {
        $order->add_order_note('Shippo API error. Status: ' . $response_code . '. Response: ' . $response_body);
        $clear_shippo_lock();
        return;
    }

    if (empty($data['object_id'])) {
        $order->add_order_note('Shippo: failed to create order. Response: ' . $response_body);
        $clear_shippo_lock();
        return;
    }

    $shippo_order_id = sanitize_text_field($data['object_id']);

    $order->update_meta_data('_shippo_order_id', $shippo_order_id);
    $order->delete_meta_data('_shippo_push_in_progress');
    $order->save();

    $order->add_order_note('Shippo order created: ' . $shippo_order_id . ' (check Shippo Orders).');
});

/*add_action('woocommerce_order_status_processing', function($order_id) {

    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    // Already pushed to Shippo
    if ($order->get_meta('_shippo_order_id')) {
        return;
    }

    do_action('woocommerce_order_action_push_to_shippo_order', $order);

}, 20);*/

/**
 * Automatically send paid Processing orders to Shippo.
 */
add_action(
    'woocommerce_order_status_processing',
    'frp_automatically_push_processing_order_to_shippo',
    20
);

function frp_automatically_push_processing_order_to_shippo($order_id) {
    $order = wc_get_order($order_id);

    if (!$order instanceof WC_Order) {
        return;
    }

    // Do not create a duplicate Shippo order.
    if ($order->get_meta('_shippo_order_id')) {
        return;
    }

    // Do not start another request while one is already running.
    if ($order->get_meta('_shippo_push_in_progress')) {
        return;
    }

    do_action('woocommerce_order_action_push_to_shippo_order', $order);
}

/**
 * Shippo webhook:
 * Completes WooCommerce order after Shippo creates a label / tracking number.
 *
 * Webhook URL:
 * https://flexrockperformance.com/wp-json/frp/v1/shippo-webhook
 */
add_action('rest_api_init', function () {
    register_rest_route('frp/v1', '/shippo-webhook', [
        'methods'             => 'POST',
        'callback'            => 'frp_shippo_complete_order_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function frp_shippo_complete_order_webhook(WP_REST_Request $request) {
    $payload = $request->get_json_params();

    if (empty($payload)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Empty payload'], 200);
    }

    $data = $payload['data'] ?? $payload;

    $tracking_number = $data['tracking_number'] ?? '';
    $tracking_status = $data['tracking_status']['status'] ?? '';
    $shippo_order_id = $data['order'] ?? '';
    $transaction_id  = $data['object_id'] ?? '';

    // Only continue if Shippo has created a real label/tracking number.
    if (empty($tracking_number)) {
        return new WP_REST_Response(['success' => true, 'message' => 'No tracking number yet'], 200);
    }

    // Find Woo order by saved Shippo order ID.
    $orders = wc_get_orders([
        'limit'      => 1,
        'meta_key'   => '_shippo_order_id',
        'meta_value' => $shippo_order_id,
        'status'     => ['processing', 'on-hold'],
    ]);

    if (empty($orders)) {
        return new WP_REST_Response(['success' => true, 'message' => 'No matching WooCommerce order found'], 200);
    }

    $order = $orders[0];

    $order->update_meta_data('_shippo_tracking_number', sanitize_text_field($tracking_number));
    $order->update_meta_data('_shippo_transaction_id', sanitize_text_field($transaction_id));
    $order->update_meta_data('_shippo_tracking_status', sanitize_text_field($tracking_status));
    $order->save();

    $order->update_status(
        'completed',
        'Order automatically completed after Shippo label purchase. Tracking number: ' . $tracking_number
    );

    return new WP_REST_Response(['success' => true, 'message' => 'WooCommerce order completed'], 200);
}

/**
 * Dynamically adjust Flat Rate fallback based on destination country.
 *
 * US (United States)  -> $15
 * CA (Canada)         -> $30
 * PR (Puerto Rico)    -> $15
 */
add_filter('woocommerce_package_rates', 'fr_adjust_flat_rate_by_country', 9999, 2);

function fr_adjust_flat_rate_by_country($rates, $package) {

    if (empty($rates) || !is_array($rates)) {
        return $rates;
    }

    // Determine destination country.
    $country = $package['destination']['country'] ?? '';

    foreach ($rates as $rate_id => $rate) {

        // Only modify Flat Rate methods.
        if ($rate->get_method_id() !== 'flat_rate') {
            continue;
        }

        switch ($country) {
            case 'CA': // Canada
                $rate->cost = 30;
                break;

            case 'US': // United States
            case 'PR': // Puerto Rico
                $rate->cost = 15;
                break;

            default:
                // Leave existing flat rate unchanged for other countries.
                break;
        }

        // Recalculate taxes for the modified shipping cost.
        if (!empty($rate->taxes) && is_array($rate->taxes)) {
            foreach ($rate->taxes as $tax_key => $tax_amount) {
                $rate->taxes[$tax_key] = 0;
            }
        }
    }

    return $rates;
}

// Facebook meta pixel
add_action('wp_head', 'frp_add_meta_pixel', 5);

function frp_add_meta_pixel() {
    ?>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;
    n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');

    fbq('init', '978924321646290');
    fbq('track', 'PageView');
    </script>

    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=978924321646290&ev=PageView&noscript=1" />
    </noscript>
    <!-- End Meta Pixel Code -->
    <?php
}

/*add_action('woocommerce_before_checkout_form', 'remove_default_payment_selection', 10);
function remove_default_payment_selection() {
    wc_enqueue_js("
        jQuery(document).ready(function($) {
            $('input[name=payment_method]').prop('checked', false);
        });
        
        $(document.body).on('updated_checkout', function() {
            $('input[name=payment_method]').prop('checked', false);
        });
    ");
}*/

/* Shipping disclaimer checkout */
add_action( 'woocommerce_before_checkout_form', 'fr_checkout_shipping_disclaimer', 5 );

function fr_checkout_shipping_disclaimer() {
	if ( is_checkout() && ! is_order_received_page() ) {
		wc_print_notice(
			'Please allow up to 4 business days for your order to ship. We operate Monday–Thursday.',
			'notice'
		);
	}
}

/* Sticky parts search button */
add_action('wp_footer', function(){
    // Don't show on the parts search page
    if ( is_page('parts-search') ){
        return;
    }
   ?>
   <a
        href="/parts-search"
        class="sticky-tab"
        data-clarity-feature="sticky_parts_search_click"
    >
       <span class="sticky-tab__icon">
           <i class="fas fa-search"></i>
       </span>
       
       <span class="sticky-tab__text">
           Parts
       </span>
   </a>
   <?php
});

/**
 * Always place product ID 7368 first in the ShopLentor product grid
 * on the Shop page. The coozie
 */
add_filter( 'posts_clauses', 'frp_pin_product_first_in_shop_grid', 99, 2 );

function frp_pin_product_first_in_shop_grid( $clauses, $query ) {
	global $wpdb;

	// Do not affect regular WordPress admin screens.
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $clauses;
	}

	// Only run on the Shop page.
	// Shop page ID is 2963.
	if ( ! is_page( 2963 ) && ! wp_doing_ajax() ) {
		return $clauses;
	}

	$post_type = $query->get( 'post_type' );

	// Make sure this is a product query.
	$is_product_query =
		'product' === $post_type ||
		( is_array( $post_type ) && in_array( 'product', $post_type, true ) );

	if ( ! $is_product_query ) {
		return $clauses;
	}

	$pinned_product_id = 7368;

	// Preserve ShopLentor's existing order after the pinned product.
	$existing_orderby = ! empty( $clauses['orderby'] )
		? $clauses['orderby']
		: "{$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_date DESC";

	$clauses['orderby'] = $wpdb->prepare(
		"CASE
			WHEN {$wpdb->posts}.ID = %d THEN 0
			ELSE 1
		END ASC, {$existing_orderby}",
		$pinned_product_id
	);

	return $clauses;
}

/**
 * Display an installation guide link on WooCommerce product pages.
 */
function frp_display_product_install_guide() {
    if ( ! is_product() || ! function_exists( 'get_field' ) ) {
        return;
    }

    $guide = get_field( 'guide', get_the_ID() );

    if ( empty( $guide ) ) {
        return;
    }

    /*
     * Supports all three ACF File return formats:
     * - File Array
     * - File URL
     * - Attachment ID
     */
    $guide_url  = '';
    $guide_name = 'View Installation Guide';

    if ( is_array( $guide ) ) {
        $guide_url = isset( $guide['url'] ) ? $guide['url'] : '';

        if ( ! empty( $guide['title'] ) ) {
            $guide_name = 'View Installation Guide';
            //$guide_name = 'View Product' . $guide['title'];
        }
    } elseif ( is_numeric( $guide ) ) {
        $guide_url = wp_get_attachment_url( $guide );

        $attachment_title = get_the_title( $guide );

        if ( $attachment_title ) {
            $guide_name = 'View ' . $attachment_title;
        }
    } elseif ( is_string( $guide ) ) {
        $guide_url = $guide;
    }

    if ( ! $guide_url ) {
        return;
    }

    echo '<div class="frp-product-install-guide">';
    echo '<a class="button frp-install-guide-button" href="' . esc_url( $guide_url ) . '" target="_blank" rel="noopener">';
    echo esc_html( $guide_name );
    echo '</a>';
    echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'frp_display_product_install_guide', 35 );

/**
 * Add product installation guide links to customer order emails.
 */
add_action(
    'woocommerce_email_after_order_table',
    'fr_add_install_guides_to_order_email',
    10,
    4
);

function fr_add_install_guides_to_order_email(
    $order,
    $sent_to_admin,
    $plain_text,
    $email
) {
    // Customer emails only.
    if ($sent_to_admin || !$order instanceof WC_Order) {
        return;
    }

    /*
     * Include the link in these customer emails:
     *
     * customer_processing_order = normal order confirmation
     * customer_completed_order  = order completed/shipped email
     */
    $allowed_email_ids = array(
        'customer_processing_order',
        'customer_completed_order',
    );

    if (
        !isset($email->id) ||
        !in_array($email->id, $allowed_email_ids, true)
    ) {
        return;
    }

    $guide_page_url = home_url('/product-installation-guide/');
    $guides         = array();

    foreach ($order->get_items('line_item') as $item) {
        $product = $item->get_product();

        if (!$product) {
            continue;
        }

        $product_id = $product->get_id();

        /*
         * If the ordered item is a variation, use the parent product because
         * the Install Guide page displays normal WooCommerce products.
         */
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();

            if ($parent_id) {
                $product_id = $parent_id;
                $product    = wc_get_product($parent_id);
            }
        }

        if (!$product) {
            continue;
        }

        // Check whether the product has an ACF installation guide.
        $guide_file = get_field('guide', $product_id);

        if (empty($guide_file)) {
            continue;
        }

        $product_sku = $product->get_sku();

        $guide_anchor = $product_sku
            ? sanitize_title($product_sku)
            : 'product-' . $product_id;

        $guide_url = add_query_arg(
            array(),
            $guide_page_url
        ) . '#' . $guide_anchor;

        /*
         * Store by product ID to prevent duplicate buttons when the same
         * product appears more than once in an order.
         */
        $guides[$product_id] = array(
            'name' => $product->get_name(),
            'url'  => $guide_url,
        );
    }

    if (empty($guides)) {
        return;
    }

    /*
     * Plain-text email output.
     */
    if ($plain_text) {
        echo "\n";
        echo "PRODUCT INSTALL GUIDES\n";
        echo "----------------------\n";

        foreach ($guides as $guide) {
            echo wp_strip_all_tags($guide['name']) . ': ';
            echo esc_url_raw($guide['url']) . "\n";
        }

        echo "\n";

        return;
    }

    /*
     * HTML email output.
     */
    ?>
    <div style="margin: 30px 0;">
        <h2 style="margin: 0 0 12px;">
            Product Install Guide(s)
        </h2>

        <p style="margin: 0 0 16px;">
            View the installation instructions for the product(s) in your order.
        </p>

        <?php foreach ($guides as $guide) : ?>
            <table
                role="presentation"
                cellspacing="0"
                cellpadding="0"
                border="0"
                style="margin: 0 0 12px;"
            >
                <tr>
                    <td
                        bgcolor="#00a8e8"
                        style="
                            background-color: #00a8e8;
                            border-radius: 4px;
                            padding: 12px 18px;
                            text-align: center;
                        "
                    >
                        <a
                            href="<?php echo esc_url($guide['url']); ?>"
                            style="
                                display: inline-block;
                                color: #ffffff;
                                text-decoration: none;
                                font-weight: bold;
                            "
                        >
                            <font
                                color="#ffffff"
                                style="
                                    color: #ffffff;
                                    -webkit-text-fill-color: #ffffff;
                                "
                            >
                                View Guide: <?php echo esc_html($guide['name']); ?>
                            </font>
                        </a>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>
    </div>
    <?php
}