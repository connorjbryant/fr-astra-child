<?php
/**
 * FR Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package FR Astra Child
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * =========================================
 * Constants
 * =========================================
 */

define(
    'CHILD_THEME_FR_ASTRA_CHILD_VERSION',
    '1.0.0'
);

define(
    'FR_CHILD_DIR',
    trailingslashit(
        get_stylesheet_directory()
    )
);

define(
    'FR_CHILD_URI',
    trailingslashit(
        get_stylesheet_directory_uri()
    )
);

/*
 * =========================================
 * Specializations
 * =========================================
 */
require_once FR_CHILD_DIR . 'inc/performance.php';
require_once FR_CHILD_DIR . 'inc/rrr.php';
require_once FR_CHILD_DIR . 'inc/pricing.php';
require_once FR_CHILD_DIR . 'inc/misc.php';
require_once FR_CHILD_DIR . 'inc/inventory-tools.php';
require_once FR_CHILD_DIR . 'inc/gift-cards.php';
require_once FR_CHILD_DIR . 'inc/coupons.php';

/*
 * =========================================
 * Child Theme Styles
 * =========================================
 */

function fr_enqueue_child_theme_styles() {

    wp_enqueue_style(
        'fr-astra-child-theme-css',
        FR_CHILD_URI . 'style.css',
        array(
            'astra-theme-css',
        ),
        CHILD_THEME_FR_ASTRA_CHILD_VERSION,
        'all'
    );
}

add_action(
    'wp_enqueue_scripts',
    'fr_enqueue_child_theme_styles',
    15
);

/*
 * =========================================
 * Custom CSS
 * =========================================
 */

function fr_enqueue_custom_styles() {

    $css_path =
        FR_CHILD_DIR .
        'assets/css/custom-style.css';

    $version =
        file_exists( $css_path )
            ? filemtime( $css_path )
            : CHILD_THEME_FR_ASTRA_CHILD_VERSION;

    wp_enqueue_style(
        'fr-custom-style',
        FR_CHILD_URI .
            'assets/css/custom-style.css',
        array(),
        $version,
        'all'
    );
}

add_action(
    'wp_enqueue_scripts',
    'fr_enqueue_custom_styles',
    20
);

/*
 * =========================================
 * Custom JavaScript
 * =========================================
 */

function fr_enqueue_custom_scripts() {
    /*
     * Main custom script.
     */
    $custom_js_path =
        FR_CHILD_DIR .
        'assets/js/custom-script.js';

    $custom_js_version =
        file_exists( $custom_js_path )
            ? filemtime( $custom_js_path )
            : CHILD_THEME_FR_ASTRA_CHILD_VERSION;

    wp_enqueue_script(
        'fr-custom-script',
        FR_CHILD_URI .
            'assets/js/custom-script.js',
        array(
            'jquery',
        ),
        $custom_js_version,
        true
    );

    /*
     * WooCommerce custom script.
     */
    $woo_js_path =
        FR_CHILD_DIR .
        'assets/js/woocommerce-custom.js';

    $woo_js_version =
        file_exists( $woo_js_path )
            ? filemtime( $woo_js_path )
            : CHILD_THEME_FR_ASTRA_CHILD_VERSION;

    wp_register_script(
        'fr-woocommerce-custom',
        FR_CHILD_URI .
            'assets/js/woocommerce-custom.js',
        array(
            'jquery',
        ),
        $woo_js_version,
        true
    );

    /*
     * Pass WooCommerce values into JavaScript.
     */
    wp_localize_script(
        'fr-woocommerce-custom',
        'astra_wc_custom_params',
        array(
            'ajax_url' => admin_url(
                'admin-ajax.php'
            ),
            'cart_url' => wc_get_cart_url(),
        )
    );

    /*
     * Only load the WooCommerce script
     * where it is actually needed.
     */
    if (
        class_exists( 'WooCommerce' )
        && (
            is_woocommerce()
            || is_cart()
            || is_checkout()
            || is_product()
            || is_page( 'shop-2' )
        )
    ) {
        wp_enqueue_script(
            'fr-woocommerce-custom'
        );
    }
}

add_action(
    'wp_enqueue_scripts',
    'fr_enqueue_custom_scripts',
    20
);

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
        // Widget settings form (future add-on)
    }

    public function update( $new_instance, $old_instance ) {
        // Update widget settings (future add-on)
    }
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

// Abandon cart and website report integration (REST)
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

/* Track people who use Google Pay at checkout */
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