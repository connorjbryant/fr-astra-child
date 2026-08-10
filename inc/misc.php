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