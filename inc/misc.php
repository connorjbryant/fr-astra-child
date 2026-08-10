<?php

/* Prize wheel custom label at checkout */
add_filter( 'woocommerce_display_item_meta', 'rename_swr_free_meta_label', 10, 3 );
function rename_swr_free_meta_label( $html, $item, $args ) {
    // Replace 'swr_free' with 'Free Item(s)' in the HTML output
    $html = str_replace( '<strong class="wc-item-meta-label">swr_free:</strong>', '<strong class="wc-item-meta-label">Free Item(s):</strong>', $html );
    return $html;
}

/* Prize wheel email addition for customers */
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

/* Custom product recommendations */
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

// Holiday announcement bar (Dec 23 - Jan 2) on the sticky announcement bar
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