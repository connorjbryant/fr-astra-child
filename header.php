<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-container" id="top">

    <header class="masthead">

        <div class="masthead-top">

            <!-- Mobile hamburger -->
            <button type="button"
                class="menu-toggle"
                aria-label="Open menu"
                aria-expanded="false"
                aria-controls="primary-navigation">

                <span></span>
                <span></span>
                <span></span>

            </button>

            <!-- Logo -->
            <div class="logo-panel">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img
                        alt="Flex Rock UTV Performance"
                        src="/wp-content/uploads/2026/11/blue-FlexRockUTVPerformance-logo-no-mountain-scaled.png">
                </a>
            </div>

            <div class="masthead-right">

                <!-- Review ribbon -->
                <div class="promo-row">
                    <a href="https://g.page/r/CcY0XDmyagI7EBM/review"
                        target="_blank"
                        rel="noopener noreferrer">

                        Happy with our parts? Leave us a review

                    </a>
                </div>

                <div class="search-row">

                    <!-- FiboSearch -->
                    <div class="product-search">

                        <button type="button"
                            class="mobile-search-toggle"
                            aria-label="Search products"
                            aria-expanded="false">

                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>

                        </button>

                        <div class="header-search-panel">
                            <?php echo do_shortcode('[fibosearch layout="classic" mobile_overlay="0"]'); ?>
                        </div>

                    </div>

                    <!-- My Account -->
                    <a class="header-tool account-tool"
                        href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M5 21c.6-4 3-6 7-6s6.4 2 7 6"></path>
                        </svg>

                        <span>My Account</span>

                    </a>

                    <!-- WooCommerce cart -->
                    <a class="header-tool cart-tool"
                        href="<?php echo esc_url(wc_get_cart_url()); ?>"
                        aria-label="Shopping cart">

                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M3 5h2l2.2 9.5h9.8l2-7H7"></path>
                            <circle cx="9" cy="19" r="1.4"></circle>
                            <circle cx="17" cy="19" r="1.4"></circle>
                        </svg>

                        <?php frp_header_cart_count(); ?>

                        <span>Cart</span>

                    </a>

                </div>
            </div>
        </div>

        <!-- Announcement bar -->
        <?php echo do_shortcode('[frp_announcement_bar]'); ?>

        <!-- WordPress navigation -->
        <nav id="primary-navigation"
            class="main-nav"
            aria-label="Primary navigation">

            <div class="nav-inner page-width">

                <?php
                wp_nav_menu([
                    'theme_location' => 'frp_primary',
                    'container'      => false,
                    'menu_class'     => 'frp-nav-menu',
                    'menu_id'        => 'frp-nav-menu',
                    'fallback_cb'    => false,
                    'depth'          => 0,
                ]);
                ?>

                <a class="mobile-account-link"
                    href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">

                    My Account
                    <span aria-hidden="true">↗</span>

                </a>

            </div>
        </nav>

    </header>