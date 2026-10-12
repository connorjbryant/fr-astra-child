
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default product category sorting by SKU.
 */
add_filter( 'woocommerce_get_catalog_ordering_args', function( $args ) {

    if ( ! is_product_category() ) {
        return $args;
    }

    // Respect customer-selected sorting.
    if ( isset( $_GET['orderby'] ) && '' !== $_GET['orderby'] ) {
        return $args;
    }

    $args['orderby']  = 'meta_value';
    $args['order']    = 'ASC';
    $args['meta_key'] = '_sku';

    return $args;
}, 20 );