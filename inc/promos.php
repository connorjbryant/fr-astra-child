<?php
/**
 * Flex Rock e-gift cards. Replace the OLD gift-card snippet entirely.
 * Requires WooCommerce and a simple, virtual gift-card product (ID 4803).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'FR_GIFT_CARD_PRODUCT_ID' ) ) define( 'FR_GIFT_CARD_PRODUCT_ID', 4803 );

function frgc_promo_active() {
    $today = wp_date( 'Y-m-d' );
    return $today >= '2026-10-26' && $today <= '2026-10-30'; // EDIT dates before launch.
}
function frgc_tiers() { return array( 100 => 110, 250 => 280, 500 => 550, 1000 => 1075 ); }
function frgc_money( $value ) { return html_entity_decode( wp_strip_all_tags( wc_price( $value ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ); }
function frgc_amount( $value ) { return round( (float) wc_format_decimal( $value ), wc_get_price_decimals() ); }
function frgc_bonus_tier( $paid ) {
    $paid = frgc_amount( $paid );
    foreach ( frgc_tiers() as $minimum => $value ) {
        if ( $paid >= $minimum && $paid < $value ) return array( $minimum, $value );
    }
    return null;
}
function frgc_value( $paid, $promo ) {
    $tier = $promo ? frgc_bonus_tier( $paid ) : null;
    return $tier ? $tier[1] : $paid;
}
function frgc_valid_amount( $amount, $promo ) {
    return is_finite( (float) $amount ) && $amount >= 10 && $amount <= 10000;
}

add_action( 'woocommerce_before_add_to_cart_button', function () {
    global $product;
    if ( ! $product || (int) $product->get_id() !== FR_GIFT_CARD_PRODUCT_ID ) return;
    echo '<div class="frgc-picker"><label for="gift_card_amount"><strong>Gift card purchase amount ($)</strong></label> ';
    echo '<input type="number" id="gift_card_amount" name="gift_card_amount" min="10" max="10000" step="0.01" value="100" required style="max-width:160px">';
    echo '<p id="frgc-live-value" aria-live="polite"></p>';
    if ( frgc_promo_active() ) {
        echo '<p>Christmas bonus tiers: Pay $100–$109.99, get $110; pay $250–$279.99, get $280; pay $500–$549.99, get $550; or pay $1,000–$1,074.99, get $1,075. Other amounts have no bonus.</p>';
    }
    echo '<p>Your unique gift card code is emailed after payment.</p></div>';
} );

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $quantity ) {
    if ( (int) $product_id !== FR_GIFT_CARD_PRODUCT_ID ) return $passed;
    $amount = isset( $_POST['gift_card_amount'] ) ? frgc_amount( wp_unslash( $_POST['gift_card_amount'] ) ) : 0;
    if ( ! frgc_valid_amount( $amount, frgc_promo_active() ) ) {
        wc_add_notice( 'Please choose a valid gift card amount.', 'error' );
        return false;
    }
    return $passed;
}, 10, 3 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
    if ( (int) $product_id !== FR_GIFT_CARD_PRODUCT_ID ) return $data;
    $paid = frgc_amount( wp_unslash( $_POST['gift_card_amount'] ?? 0 ) );
    $data['frgc_paid'] = $paid;
    $data['frgc_value'] = frgc_value( $paid, frgc_promo_active() );
    $data['frgc_unique'] = wp_generate_uuid4(); // separate gift cards, even at same price
    return $data;
}, 10, 2 );

add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
    if ( is_admin() && ! wp_doing_ajax() ) return;
    foreach ( $cart->get_cart() as $cart_item ) {
        if ( (int) $cart_item['product_id'] === FR_GIFT_CARD_PRODUCT_ID && isset( $cart_item['frgc_paid'] ) ) {
            $cart_item['data']->set_price( $cart_item['frgc_paid'] );
        }
    }
}, 20 );

// Allow editing the purchase amount directly in the classic WooCommerce cart.
add_filter( 'woocommerce_cart_item_price', function ( $price, $item, $key ) {
    if ( (int) ( $item['product_id'] ?? 0 ) !== FR_GIFT_CARD_PRODUCT_ID || ! isset( $item['frgc_paid'] ) || ! is_cart() ) return $price;
    return '<label>Gift card amount ($) <input type="number" min="10" max="10000" step="0.01" style="width:105px" name="cart[' . esc_attr( $key ) . '][frgc_paid]" value="' . esc_attr( $item['frgc_paid'] ) . '"></label>';
}, 10, 3 );

add_filter( 'woocommerce_update_cart_action_cart_updated', function ( $updated ) {
    if ( ! WC()->cart || ! isset( $_POST['cart'] ) || ! is_array( $_POST['cart'] ) ) return $updated;
    $rows = wp_unslash( $_POST['cart'] );
    foreach ( WC()->cart->get_cart() as $key => $item ) {
        if ( (int) ( $item['product_id'] ?? 0 ) !== FR_GIFT_CARD_PRODUCT_ID || ! isset( $rows[$key]['frgc_paid'] ) ) continue;
        $paid = frgc_amount( $rows[$key]['frgc_paid'] );
        if ( ! frgc_valid_amount( $paid, frgc_promo_active() ) ) {
            wc_add_notice( 'Gift card amounts must be between $10 and $10,000.', 'error' );
            continue;
        }
        $new_value = frgc_value( $paid, frgc_promo_active() );
        if ( (float) $item['frgc_paid'] !== $paid || (float) $item['frgc_value'] !== $new_value ) {
            WC()->cart->cart_contents[$key]['frgc_paid'] = $paid;
            WC()->cart->cart_contents[$key]['frgc_value'] = $new_value;
            $updated = true;
        }
    }
    if ( $updated ) WC()->cart->set_session();
    return $updated;
} );

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
    if ( isset( $item['frgc_paid'], $item['frgc_value'] ) ) {
        $data[] = array( 'key' => 'You pay', 'value' => frgc_money( $item['frgc_paid'] ) );
        $data[] = array( 'key' => 'Gift card value', 'value' => frgc_money( $item['frgc_value'] ) );
        if ( $item['frgc_value'] > $item['frgc_paid'] ) $data[] = array( 'key' => 'Christmas bonus', 'value' => frgc_money( $item['frgc_value'] - $item['frgc_paid'] ) );
    }
    return $data;
}, 10, 2 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_key, $values ) {
    if ( (int) ( $values['product_id'] ?? 0 ) !== FR_GIFT_CARD_PRODUCT_ID ) return;
    $item->add_meta_data( '_frgc_paid', $values['frgc_paid'], true );
    $item->add_meta_data( '_frgc_value', $values['frgc_value'], true );
    $item->add_meta_data( 'Gift card value', frgc_money( $values['frgc_value'] ), true );
}, 10, 3 );

// Do not let customers buy gift cards using gift cards.
add_filter( 'woocommerce_coupon_is_valid', function ( $valid, $coupon ) {
    if ( ! $coupon->get_meta( '_frgc_managed' ) || ! WC()->cart ) return $valid;
    foreach ( WC()->cart->get_cart() as $item ) {
        if ( (int) $item['product_id'] === FR_GIFT_CARD_PRODUCT_ID ) {
            throw new Exception( 'Gift cards cannot be used to purchase gift cards.' );
        }
    }
    return $valid;
}, 10, 2 );

/**
 * Create a managed gift-card coupon.
 *
 * Tries WC_Coupon::save() first. If the data store fails to return an ID
 * (the failure mode observed in the order notes: save_return=0,
 * object_id=0, lookup_id=0), it falls back to inserting the shop_coupon
 * post directly with wp_insert_post() and writing the standard coupon
 * meta keys, so WooCommerce still treats it as a real coupon.
 *
 * @return array{id:int,code:string,method:string,error:string}
 */
function frgc_create_coupon( $code, $value, $order_id ) {
    // --- Attempt 1: standard WooCommerce data store ---
    $coupon = new WC_Coupon();
    $coupon->set_code( $code );
    $coupon->set_discount_type( 'fixed_cart' );
    $coupon->set_amount( $value );
    $coupon->set_individual_use( true );
    $coupon->set_description( 'Flex Rock e-gift card from order #' . $order_id );
    $coupon->update_meta_data( '_frgc_managed', 'yes' );
    $coupon->update_meta_data( '_frgc_initial', $value );

    $save_error = '';
    try {
        $coupon->save();
    } catch ( Throwable $e ) {
        $save_error = $e->getMessage();
    }

    $saved_id = absint( $coupon->get_id() );
    if ( $saved_id ) {
        return array(
            'id'     => $saved_id,
            'code'   => $code,
            'method' => 'wc_coupon_save',
            'error'  => $save_error,
        );
    }

    // --- Attempt 2: direct post insert fallback ---
    $post_id = wp_insert_post( array(
        'post_title'   => $code,
        'post_name'    => sanitize_title( $code ),
        'post_content' => '',
        'post_excerpt' => 'Flex Rock e-gift card from order #' . $order_id,
        'post_status'  => 'publish',
        'post_type'    => 'shop_coupon',
        'post_author'  => 1,
    ), true );

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        return array(
            'id'     => 0,
            'code'   => $code,
            'method' => 'wp_insert_post',
            'error'  => is_wp_error( $post_id ) ? $post_id->get_error_message() : 'wp_insert_post returned 0',
        );
    }

    // Write the meta keys WooCommerce expects for a fixed_cart coupon.
    $meta = array(
        'discount_type'              => 'fixed_cart',
        'coupon_amount'              => $value,
        'individual_use'             => 'yes',
        'product_ids'                => array(),
        'exclude_product_ids'        => array(),
        'usage_limit'                => '',
        'usage_limit_per_user'       => '',
        'limit_usage_to_x_items'     => '',
        'usage_count'                => 0,
        'date_expires'               => '',
        'free_shipping'              => 'no',
        'product_categories'         => array(),
        'exclude_product_categories' => array(),
        'exclude_sale_items'         => 'no',
        'minimum_amount'             => '',
        'maximum_amount'             => '',
        'customer_email'             => array(),
        '_frgc_managed'              => 'yes',
        '_frgc_initial'              => $value,
    );
    foreach ( $meta as $key => $val ) {
        update_post_meta( $post_id, $key, $val );
    }

    // Verify the coupon is readable through the normal coupon API.
    $verify = new WC_Coupon( $post_id );
    if ( ! $verify->get_id() || strcasecmp( $verify->get_code(), $code ) !== 0 ) {
        return array(
            'id'     => 0,
            'code'   => $code,
            'method' => 'wp_insert_post',
            'error'  => 'fallback post inserted (ID ' . $post_id . ') but WC_Coupon read-back failed',
        );
    }

    return array(
        'id'     => $post_id,
        'code'   => $code,
        'method' => 'wp_insert_post_fallback',
        'error'  => $save_error,
    );
}

function frgc_issue( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    if ( ! $order->is_paid() ) {
        $order->add_order_note( 'FRGC: issuance deferred; status=' . $order->get_status() . '; date_paid=' . ( $order->get_date_paid() ? $order->get_date_paid()->date('c') : 'none' ) );
        return;
    }
    $order->add_order_note( 'FRGC: issuance running; status=' . $order->get_status() );
    foreach ( $order->get_items() as $item_id => $item ) {
        if ( (int) $item->get_product_id() !== FR_GIFT_CARD_PRODUCT_ID ) continue;
        if ( $item->get_meta( '_frgc_code' ) ) {
            $order->add_order_note( 'FRGC: line ' . $item_id . ' already issued: ' . $item->get_meta( '_frgc_code' ) );
            continue;
        }
        $paid = frgc_amount( $item->get_meta( '_frgc_paid' ) );
        $value = frgc_amount( $item->get_meta( '_frgc_value' ) );
        $order->add_order_note( sprintf( 'FRGC: line %d, product %d, paid %s, value %s', $item_id, $item->get_product_id(), $paid, $value ) );
        if ( $paid < 10 || $value < $paid ) {
            $order->add_order_note( 'Gift card issue skipped: invalid stored amount for line ' . $item_id );
            continue;
        }
        $pending_coupon_id = absint( $item->get_meta( '_frgc_pending_coupon_id' ) );
        if ( $pending_coupon_id ) {
            $order->add_order_note( 'FRGC: previously saved coupon ID ' . $pending_coupon_id . ' needs lookup investigation; not creating a duplicate.' );
            continue;
        }

        $code = 'GIFT-' . $order_id . '-' . strtoupper( wp_generate_password( 12, false, false ) );
        $result = frgc_create_coupon( $code, $value, $order_id );

        $order->add_order_note( sprintf(
            'FRGC coupon creation: method=%s, id=%d, code=%s, error=%s',
            $result['method'],
            $result['id'],
            $result['code'],
            $result['error'] ?: 'none'
        ) );

        if ( ! $result['id'] ) {
            $order->add_order_note( 'FRGC ERROR: coupon could not be created by either path. No email sent.' );
            continue;
        }

        $coupon_id = $result['id'];
        $lookup_id = absint( wc_get_coupon_id_by_code( $code ) );

        if ( ! $lookup_id ) {
            // Coupon exists by ID but code lookup fails — cache or index issue.
            $item->update_meta_data( '_frgc_pending_coupon_id', $coupon_id );
            $item->save();
            $order->add_order_note( 'FRGC WARNING: coupon saved and readable by ID (' . $coupon_id . '), but code lookup returned 0. No email sent; investigate coupon lookup before retry.' );
            continue;
        }

        $item->update_meta_data( '_frgc_code', $code );
        $item->save();

        $to = $order->get_billing_email();
        $message = sprintf(
            "Hi %s,\n\nYour Flex Rock Performance e-gift card is ready!\n\nGift card code: %s\nYou paid: %s\nChristmas bonus: %s\nGift card balance: %s\n\nEnter the code in the coupon field at checkout. You can use it across multiple orders until the balance reaches zero.\n\nOrder #%s",
            $order->get_billing_first_name(),
            $code,
            frgc_money( $paid ),
            frgc_money( $value - $paid ),
            frgc_money( $value ),
            $order->get_order_number()
        );
        $sent = wp_mail( $to, 'Your Flex Rock Performance e-gift card is ready', $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
        $order->add_order_note( sprintf( 'Gift card created: %s (%s) via %s. Email %s.', $code, frgc_money( $value ), $result['method'], $sent ? 'sent' : 'failed' ) );
    }
    $order->save();
}
add_action( 'woocommerce_order_status_processing', 'frgc_issue' );
add_action( 'woocommerce_order_status_completed', 'frgc_issue' );
add_action( 'woocommerce_payment_complete', 'frgc_issue', 20 );
// Retry when payment is recorded through gateways that bypass the usual transition.
add_action( 'woocommerce_order_payment_status_changed', 'frgc_issue', 20 );

// Admin-only manual retry for a previously paid order (does not issue unpaid cards).
add_filter( 'woocommerce_order_actions', function ( $actions ) {
    $actions['frgc_retry_issue'] = 'Retry gift card issuance';
    return $actions;
} );
add_action( 'woocommerce_order_action_frgc_retry_issue', function ( $order ) {
    frgc_issue( $order->get_id() );
} );

// Auto-complete paid orders containing only virtual gift cards. Do not complete physical orders.
add_filter( 'woocommerce_payment_complete_order_status', function ( $status, $order_id, $order ) {
    if ( ! $order ) return $status;
    $has_card = false;
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        if ( (int) $item->get_product_id() !== FR_GIFT_CARD_PRODUCT_ID ) return $status;
        $has_card = true;
    }
    return $has_card ? 'completed' : $status;
}, 20, 3 );

// After a PAID redemption, reduce coupon value by the amount actually discounted.
// An order marker makes repeat status changes idempotent.
function frgc_consume( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || ! $order->is_paid() ) return;
    foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
        $code = $coupon_item->get_code();
        $coupon_id = wc_get_coupon_id_by_code( $code );
        if ( ! $coupon_id ) continue;
        $coupon = new WC_Coupon( $coupon_id );
        if ( ! $coupon->get_meta( '_frgc_managed' ) ) continue;
        $marker = '_frgc_consumed_' . $coupon_id;
        if ( $order->get_meta( $marker ) ) continue;
        $used = frgc_amount( $coupon_item->get_discount() + $coupon_item->get_discount_tax() );
        $balance = frgc_amount( $coupon->get_amount() );
        $coupon->set_amount( max( 0, frgc_amount( $balance - $used ) ) );
        $coupon->save();
        $order->update_meta_data( $marker, $used ?: '0' );
        $order->add_order_note( sprintf( 'Gift card %s: redeemed %s, remaining %s.', $code, frgc_money( $used ), frgc_money( $coupon->get_amount() ) ) );
    }
    $order->save();
}
add_action( 'woocommerce_order_status_processing', 'frgc_consume', 20 );
add_action( 'woocommerce_order_status_completed', 'frgc_consume', 20 );

// WooCommerce rejects zero-value coupons; customers should not reuse an exhausted card.
// Coupon balances are intentionally not restored automatically on refunds/cancellations.

/**
 * Christmas gift card artwork: show an image ONLY when the amount paid is
 * exactly one of the four promotional purchase tiers. The artwork advertises
 * the resulting gift card value, not an amount-range or the card's face value.
 */
function frgc_promo_image_id( $paid ) {
    if ( ! frgc_promo_active() ) return 0;
    $tier = frgc_bonus_tier( $paid );
    $images = array( 100 => 4804, 250 => 4806, 500 => 4811, 1000 => 4810 );
    return $tier ? ( $images[$tier[0]] ?? 0 ) : 0;
}

// Update the main product image when the amount changes. For any non-tier
// amount, restore the product's original image instead of a bonus-value image.
add_action( 'wp_footer', 'frgc_promo_product_image_switcher' );
function frgc_promo_product_image_switcher() {
    if ( ! is_product() || (int) get_queried_object_id() !== FR_GIFT_CARD_PRODUCT_ID ) return;
    $images = array();
    foreach ( array( 100 => 4804, 250 => 4806, 500 => 4811, 1000 => 4810 ) as $paid => $id ) {
        $images[$paid] = wp_get_attachment_image_url( $id, 'full' );
    }
    $product = wc_get_product( FR_GIFT_CARD_PRODUCT_ID );
    $default_image = $product ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : '';
    ?>
    <script>
    jQuery(function($) {
      const input = $('#gift_card_amount');
      if (!input.length) return;

      const active = <?php echo wp_json_encode( frgc_promo_active() ); ?>;
      const images = <?php echo wp_json_encode( $images ); ?>;
      const defaultImage = <?php echo wp_json_encode( $default_image ); ?>;
      const tiers = [[100,110],[250,280],[500,550],[1000,1075]];
      const format = n => new Intl.NumberFormat('en-US',{style:'currency',currency:'USD'}).format(n);
      
      const gallery = $('.woocommerce-product-gallery');
      const img = gallery.find('.woocommerce-product-gallery__image img, img.wp-post-image').first().length ? gallery.find('.woocommerce-product-gallery__image img, img.wp-post-image').first() : $('img.wp-post-image').first();
      const price = $('.summary .price');
      const info = $('#frgc-live-value');
      
      let initialImage = null;
      if (img.length) {
        initialImage = {
          src: img.attr('src'),
          srcset: img.attr('srcset'),
          sizes: img.attr('sizes'),
          large: img.attr('data-large_image'),
          href: img.closest('a').attr('href')
        };
      }

      function setAttr(el, attr, val) {
        if (!el.length) return;
        if (val == null) el.removeAttr(attr);
        else el.attr(attr, val);
      }

      function update() {
        const raw = input.val();
        const paid = Number(raw);
        const valid = raw !== '' && Number.isFinite(paid) && paid >= 10 && paid <= 10000;
        const tier = active && valid ? tiers.find(([minimum,value]) => paid >= minimum && paid < value) : null;
        const value = tier ? tier[1] : paid;

        if (price.length) price.text(valid ? format(paid) : '');
        if (info.length) {
          info.text(valid ? ('You pay ' + format(paid) + ' and receive ' + format(value) + (tier ? ' (bonus ' + format(value-paid) + ')' : ' (no bonus)')) : 'Enter an amount from $10 to $10,000.');
        }

        if (!img.length || !initialImage) return;

        const url = tier ? images[tier[0]] : null;
        const link = img.closest('a');

        if (url) {
          setAttr(img, 'src', url);
          setAttr(img, 'srcset', null);
          setAttr(img, 'sizes', null);
          setAttr(img, 'data-large_image', url);
          setAttr(link, 'href', url);
        } else {
          setAttr(img, 'src', initialImage.src || defaultImage);
          setAttr(img, 'srcset', initialImage.srcset);
          setAttr(img, 'sizes', initialImage.sizes);
          setAttr(img, 'data-large_image', initialImage.large);
          setAttr(link, 'href', initialImage.href);
        }
      }

      input.on('input change', update);
      update();
    });
    </script>
    <?php
}

// Keep the promotional thumbnail consistent in the cart, and preserve the
// regular product thumbnail for any amount that is not an exact tier.
add_filter( 'woocommerce_cart_item_thumbnail', 'frgc_cart_promo_thumbnail', 10, 3 );
function frgc_cart_promo_thumbnail( $thumbnail, $cart_item, $cart_item_key ) {
    if ( (int) ( $cart_item['product_id'] ?? 0 ) !== FR_GIFT_CARD_PRODUCT_ID ) return $thumbnail;
    if ( ! isset( $cart_item['frgc_paid'] ) ) return $thumbnail;
    $image_id = frgc_promo_image_id( $cart_item['frgc_paid'] );
    return $image_id ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail' ) : $thumbnail;
}

/**
 * Add a custom read-only gift card table to the /my-account/gift-cards/ endpoint.
 * Hooks into the standard WooCommerce endpoint action.
 */
add_action( 'woocommerce_account_gift-cards_endpoint', 'frgc_render_custom_gift_cards_table', 20 );

function frgc_render_custom_gift_cards_table() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $user_id = get_current_user_id();
    $customer_orders = wc_get_orders( array(
        'customer' => $user_id,
        'limit'    => -1,
        'status'   => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
    ) );

    $custom_cards = array();

    // 1. Find all custom gift cards issued to this user
    foreach ( $customer_orders as $order ) {
        foreach ( $order->get_items() as $item ) {
            if ( (int) $item->get_product_id() !== FR_GIFT_CARD_PRODUCT_ID ) {
                continue;
            }

            $code = $item->get_meta( '_frgc_code' );
            if ( ! $code ) {
                continue;
            }

            $coupon_id = wc_get_coupon_id_by_code( $code );
            if ( ! $coupon_id ) {
                continue;
            }

            $coupon = new WC_Coupon( $coupon_id );
            if ( ! $coupon->get_id() ) {
                continue;
            }

            $custom_cards[] = array(
                'code'    => $code,
                'balance' => $coupon->get_amount(),
                'order'   => $order->get_id(),
            );
        }
    }

    // 2. Only output the section if this user has custom gift cards
    if ( empty( $custom_cards ) ) {
        return;
    }

    // 3. Render the read-only table
    ?>
    <h3 style="margin-top: 2em;">Flex Rock Performance e-Gift Cards</h3>
    <table class="woocommerce-table shop_table shop_table_responsive gift-cards-table" cellspacing="0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Order</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $custom_cards as $card ) : ?>
                <tr>
                    <td data-title="Code"><code><?php echo esc_html( $card['code'] ); ?></code></td>
                    <td data-title="Balance"><?php echo wc_price( $card['balance'] ); ?></td>
                    <td data-title="Status">
                        <?php echo $card['balance'] > 0 ? '<span style="color:green;font-weight:bold;">Active</span>' : 'Depleted'; ?>
                    </td>
                    <td data-title="Order">
                        <a href="<?php echo esc_url( wc_get_endpoint_url( 'view-order', $card['order'], wc_get_page_permalink( 'myaccount' ) ) ); ?>">
                            #<?php echo esc_html( $card['order'] ); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
        To use a gift card, enter the code in the "Coupon code" field during checkout. The balance will decrease as you use it.
    </p>
    <?php
}