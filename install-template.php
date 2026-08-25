<?php
/**
 * Template Name: Install Guide
 */

get_header();

// Grab the search term from the query param. Defaults to empty string
$search_query = isset($_GET['search_guide']) ? sanitize_text_field($_GET['search_guide']) : '';

?>

<style>
    .product-install {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1rem;
        font-family: 'Oxanium', sans-serif;
        font-size: 1.25rem;
        margin-top: 6rem !important;
    }
    
    .product-install__header {
        grid-column: 1 / -1;
    }
    
    .product-install__controls {
        display: grid !important;
        grid-column: 1 / -1;
    }
    
    .product-install__controls form {
        display: grid;
        align-items: center;
        background-color: #E5E7EB;
        border: 1px solid #D1D5DC;
        padding: 0.5rem;
        margin-bottom: 2rem;
        gap: 1rem;
    }
    
    .product-install__controls div:first-of-type {
        grid-column: 1 / 6;
        display: flex;
        flex-direction: row;
        gap: 1rem;
    }
    
    .product-install__controls div:first-of-type section {
        display: flex;
        flex-direction: column;
    }
    
    .product-install__controls div:last-of-type {
        display: flex;
        grid-column: 6 / 12;
        flex-direction: column;
    }
    
    .install-btn {
        border: 1px solid black;
    }
    
    .is-row {
        display: flex !important;
        flex-direction: row !important;
        gap: 0.25rem;
    }
    
    .product-install__controls div:last-of-type input {
        flex: 1;
    }
    
    .product-install__controls button {
        flex: 0 0 auto;
    }
    
    .product-install__render {
        grid-column: 1 / -1;
    }
    
    .product-install__render a {
        color: black !important;
        text-decoration: underline;
    }
    
    .product-item:not(:last-of-type) {
        border-bottom: 1px solid #D1D5DC;
        margin-bottom: 2rem;
        padding-bottom: 0 !important;
    }
    
    .install-guide-preview {
        margin-bottom: 2rem;
    }
    
    #clear-search-btn {
        display: flex;
        align-items: center;
        color: black;
        text-decoration: underline;
        font-size: 1.25rem;
    }
    
    @media (max-width: 1024px) {
        .install-btn {
            margin: 0;
        }
        /* Main layout */
        .product-install {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    
        .product-install__header,
        .product-install__controls,
        .product-install__render {
            grid-column: 1 / -1;
            min-width: 0;
        }
    
        /* Form */
        .product-install__controls form {
            grid-template-columns: 1fr;
            gap: 1rem;
            width: 100%;
            box-sizing: border-box;
        }
    
        /* Filter groups */
        .product-install__controls div:first-of-type,
        .product-install__controls div:last-of-type {
            grid-column: 1 / -1;
            width: 100%;
            min-width: 0;
        }
    
        .product-install__controls div:first-of-type {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
    
        .product-install__controls section {
            width: 100%;
            min-width: 0;
        }
    
        /* Inputs */
        .product-install__controls label {
            display: block;
            margin-bottom: .25rem;
        }
    
        .product-install__controls select,
        .product-install__controls input {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
    
        /* Search row */
        .is-row {
            display: flex !important;
            flex-direction: column !important;
            gap: .5rem;
            width: 100%;
        }
    
        .is-row input,
        .is-row button,
        #clear-search-btn {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
    
        #clear-search-btn {
            justify-content: center;
        }
    
        /* Product list */
        .products {
            margin: 0;
            padding: 0;
        }
    
        .product-item {
            width: 100%;
            box-sizing: border-box;
        }
    
        .install-guide-preview {
            width: 100%;
        }
    
        .install-guide-preview iframe {
            width: 100%;
            max-width: 100%;
            height: 350px;
            display: block;
        }
    }
    
    @media (min-width: 1025px){
        .product-install button, .product-install select {
            font-size: 1.25rem;
        }
    }
</style>

<main id="primary" class="site-main product-install">
    
<?php

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        's'              => $search_query,
        'meta_query'     => array(
            array(
                'key'     => 'guide',
                'value'   => '',
                'compare' => '!=',
            ),
        ),
    );
    
    $loop = new WP_Query($args);
    
    if ($loop->have_posts()) {
    
        $products_list = array();
        $categories_list = array();
    
        while ($loop->have_posts()) {
            $loop->the_post();
    
            $product_id = get_the_ID();
            $file       = get_field('guide', $product_id);
    
            // Extra protection against empty ACF values.
            if (empty($file)) {
                continue;
            }
    
            $products_list[$product_id] = get_the_title();
            
            $product_categories = get_the_terms($product_id, 'product_cat');

            if (!empty($product_categories) && !is_wp_error($product_categories)) {
                foreach ($product_categories as $category) {

                    // Skip parent categories
                    if ($category->parent == 0) {
                        continue;
                    }
                
                    $categories_list[$category->term_id] = $category->name;
                }
            }
        }
        
        // Sort product dropdown naturally by title.
        natcasesort($products_list);
        
        // Sort categories alphabetically.
        asort($categories_list);
        
        // Build product IDs sorted naturally by SKU.
        $sorted_product_ids = array();
        
        $loop->rewind_posts();
        
        while ($loop->have_posts()) {
            $loop->the_post();
        
            $product_id = get_the_ID();
            $product    = wc_get_product($product_id);
        
            if (!$product) {
                continue;
            }
        
            $sku = $product->get_sku();
        
            $sorted_product_ids[$product_id] = $sku ?: get_the_title();
        }
        
        // Natural sorting handles 10002, 10003, 10010 correctly.
        uasort($sorted_product_ids, function ($a, $b) {
            return strnatcasecmp($a, $b);
        });
        
        $loop->rewind_posts();
    
        ?>
    
    <!-- Form which includes found products -->
    <div class="product-install__header">
        <h1>Product Install Guides</h1>
    </div>
    <div class="product-install__controls">
        <form method="get" action="" id="guide-filter-form">
            <div>
                <section>
                    <label for="guide-category">Filter by category:</label>
                    <select name="guide_category" id="guide-category">
                        <option value="all">All categories</option>
                
                        <?php foreach ($categories_list as $category_id => $category_name) : ?>
                            <option value="cat-<?php echo esc_attr($category_id); ?>">
                                <?php echo esc_html($category_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </section>
                <section>
                    <label for="guide">Filter by product:</label>
                    <select name="guide" id="guide">
                        <option value="all">All products</option>
                        <?php foreach ($products_list as $id => $title) : ?>
                            <option value="prod-<?php echo esc_attr($id); ?>"><?php echo esc_html($title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </section>
            </div>
            <div>
                <label for="guide">Search:</label>
                <div class="is-row">
                    <input type="text" name="search_guide" id="search_guide" value="<?php echo esc_attr($search_query); ?>" placeholder="Type to search" />
                    <button class="install-btn" type="submit">Submit</button>
                    <?php if (!empty($search_query)) : ?>
                        <a href="#" id="clear-search-btn">Clear Search</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <div class="product-install__render">
            <?php
    
            echo '<ul class="products">';

            foreach ($sorted_product_ids as $product_id => $sku) :

                $product = wc_get_product($product_id);
                $file    = get_field('guide', $product_id);
            
                if (!$product || empty($file)) {
                    continue;
                }
            
                if (!$product || empty($file)) {
                    continue;
                }
            
                $file_url = is_array($file)
                    ? ($file['url'] ?? '')
                    : $file;
            
                if (empty($file_url)) {
                    continue;
                }
                ?>
            
                <?php
                $product_categories = get_the_terms($product_id, 'product_cat');
                $category_classes   = array();
                
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    foreach ($product_categories as $category) {
                        $category_classes[] = 'cat-' . $category->term_id;
                    }
                }
                
                $category_class_string = implode(' ', $category_classes);
                ?>
                
                <?php
                    $product_sku = $product->get_sku();
                    
                    $guide_anchor = $product_sku
                        ? sanitize_title($product_sku)
                        : 'product-' . $product_id;
                    ?>
                    
                    <li
                        id="<?php echo esc_attr($guide_anchor); ?>"
                        class="product-item prod-<?php echo esc_attr($product_id); ?> <?php echo esc_attr($category_class_string); ?>"
                    >
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                        <?php echo esc_html(get_the_title($product_id)); ?>
                    </a>
            
                    <div class="install-guide-preview">
                        <iframe
                            src="<?php echo esc_url($file_url); ?>#toolbar=1&navpanes=0"
                            width="100%"
                            height="500"
                            style="border:0;"
                            title="<?php echo esc_attr(get_the_title($product_id) . ' Install Guide'); ?>"
                        ></iframe>
            
                        <a href="<?php echo esc_url($file_url); ?>" download>
                            Download Install Guide
                        </a>
                    </div>
                </li>
            
            <?php
            endforeach;
            
            echo '</ul>';
            
            wp_reset_postdata();
            
            } else {
                echo '<p>No results</p>';
            }
            
            ?>
    </div>
</main>

<script>
jQuery(function ($) {
    function filterGuides() {
        var selectedProduct  = $('#guide').val();
        var selectedCategory = $('#guide-category').val();

        $('.product-item').each(function () {
            var $product = $(this);

            var matchesProduct =
                selectedProduct === 'all' ||
                $product.hasClass(selectedProduct);

            var matchesCategory =
                selectedCategory === 'all' ||
                $product.hasClass(selectedCategory);

            if (matchesProduct && matchesCategory) {
                $product.show();
            } else {
                $product.hide();
            }
        });

        var visibleProducts = $('.product-item:visible').length;

        $('#no-filter-results').toggle(visibleProducts === 0);
    }

    $('#guide, #guide-category').on('change', filterGuides);

    $('#clear-search-btn').on('click', function (e) {
        e.preventDefault();

        $('#search_guide').val('');
        $('#guide').val('all');
        $('#guide-category').val('all');

        $('#guide-filter-form').submit();
    });
});
</script>

<?php get_footer();