
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Custom product reviews based on gallery submissions */
function woocommerce_template_single_rating() {
    global $product;

    if ( ! $product instanceof WC_Product ) {
        return;
    }

    $product_id = $product->get_id();

    // Image Upload and Vote stores product IDs in serialized arrays.
    // Support both integer and string IDs.
    $reviews = get_posts([
        'post_type'      => 'uploaded_image',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_iuv_product_ids',
                'value'   => 'i:' . $product_id . ';',
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_iuv_product_ids',
                'value'   => '"' . $product_id . '"',
                'compare' => 'LIKE',
            ],
        ],
    ]);

    // Verify associations using the actual stored IDs.
    $reviews = array_values(array_filter(
        $reviews,
        function ($review) use ($product_id) {
            $ids = get_post_meta(
                $review->ID,
                '_iuv_product_ids',
                true
            );

            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }

            return is_array($ids)
                && in_array($product_id, array_map('absint', $ids), true);
        }
    ));

    $count = count($reviews);

    // Hide the entire review section if no reviews exist.
    if ($count === 0) {
        return;
    }

    echo '<div class="fr-product-reviews">';

    if ($count === 1) {

        echo '<a href="' . esc_url(get_permalink($reviews[0]->ID)) . '">';
        echo 'Product review(s): 1';
        echo '</a>';

    } elseif ($count > 1) {

        echo '<details class="fr-product-reviews__dropdown">';
        echo '<summary>Product review(s): ' . esc_html($count) . '</summary>';
        echo '<ul>';

        foreach ($reviews as $review) {
            echo '<li>';
            echo '<a href="' . esc_url(get_permalink($review->ID)) . '">';
            echo esc_html(get_the_title($review->ID));
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</details>';

    } else {

        echo '<span>Product review(s): 0</span>';

    }

    echo '<a class="fr-product-reviews__submit" href="' .
        esc_url(home_url('/upload/')) .
        '">Submit a review</a>';

    echo '</div>';
}