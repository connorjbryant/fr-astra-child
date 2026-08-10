(function($) {
    $(document).ready(function() {
        applyShopEnhancements();
        cleanRepeatedText();
        setupCartNoticeHandlers();

        function setRRROverrideSessionFlag(callback) {
            $.post(astra_wc_custom_params.ajax_url, {
                action: 'set_rrr_override_flag'
            }).done(callback);
        }
    });

    function applyShopEnhancements() {
        $(".columns-4").addClass("has-expand");
        $(".ht-product-image-wrap").addClass("has-image-flex");
    }

    function cleanRepeatedText() {
        const $title = $(".woocommerce-products-header__title.page-title");
        const $breadcrumb = $(".woocommerce-breadcrumb");
        if ($title.length) {
            const cleanedTitle = $title.text().replace(/\b(\w+)( \1)+\b/g, "$1");
            $title.text(cleanedTitle);
        }
        if ($breadcrumb.length) {
            const cleanedBreadcrumb = $breadcrumb.text().replace(/\b(\w+)( \1)+\b/g, "$1");
            $breadcrumb.text(cleanedBreadcrumb);
        }
    }

    function clearRRRSessionFlag() {
        $.post(astra_wc_custom_params.ajax_url, {
            action: 'clear_rrr_session_flag'
        });
    }

    function setupCartNoticeHandlers() {
        $(document).on('click', '.alt-add-to-cart', function(e) {
            e.preventDefault();

            const $button = $(this);
            const altHref = $button.attr('href');
            const altProductIdMatch = altHref.match(/add-to-cart=(\d+)/);
            const altProductId = altProductIdMatch ? altProductIdMatch[1] : null;
            const skuToRemove = $button.data('removeSku');
            const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

            if (!altProductId || !skuToRemove) {
                window.location.href = altHref;
                return;
            }

            if (isMobile) {
                window.location.href = '/?add-to-cart=' + altProductId + '&rrr_override=1';
                return;
            }

            fireGA4Event('rrr_add_alternative', {
                sku_replaced: skuToRemove,
                alt_product_id: altProductId
            });
            
            // Log the swap using RRR Tracker Lite plugin
            $.post(astra_wc_custom_params.ajax_url, {
                action: 'log_rrr_event',
                event_type: 'swap_to_alternative',
                sku: skuToRemove,
                product_id: altProductId,
                meta: {
                    source: 'alt-add-to-cart-click'
                }
            });

            $button.text('Updating...');

            $.get(astra_wc_custom_params.ajax_url, { action: 'get_cart_items_by_sku' }, function(response) {
                if (response.success && response.data[skuToRemove]) {
                    const cartItemKey = response.data[skuToRemove];

                    $.post(astra_wc_custom_params.ajax_url, {
                        action: 'woocommerce_remove_cart_item',
                        cart_item_key: cartItemKey
                    }).done(function() {
                        $.post('?add-to-cart=' + altProductId + '&rrr_override=1', function() {
                            window.location.href = astra_wc_custom_params.cart_url || '/cart/';
                        });
                    });
                } else {
                    $.post('?add-to-cart=' + altProductId + '&rrr_override=1', function() {
                        window.location.href = astra_wc_custom_params.cart_url || '/cart/';
                    });
                }
            });
        });

        $(document).on('click', '.button-remove-items', function () {
            const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

            if (isMobile) {
                window.location.href = astra_wc_custom_params.cart_url || '/cart/';
                return;
            }

            const skusToRemove = $(this).data('skus');

            fireGA4Event('rrr_remove_incompatible', {
                skus: skusToRemove
            });

            console.log("SKUs to remove:", skusToRemove);

            $.post(astra_wc_custom_params.ajax_url, {
                action: 'get_cart_items_by_sku'
            }).done(function (response) {
                if (!response.success) return;

                const skuMap = response.data;
                console.log("SKU Map from server:", skuMap);

                let removeCount = 0;
                let removedTotal = skusToRemove.length;

                skusToRemove.forEach(function (sku) {
                    const key = skuMap[sku];
                    if (key) {
                        $.post(astra_wc_custom_params.ajax_url, {
                            action: 'woocommerce_remove_cart_item',
                            cart_item_key: key
                        }).done(function () {
                            removeCount++;
                            if (removeCount === removedTotal) {
                                window.location.href = astra_wc_custom_params.cart_url || '/cart/';
                            }
                        });
                    } else {
                        removeCount++;
                        if (removeCount === removedTotal) {
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                    }
                });
            });
        });

        $(document).on('click', '.js-fitment-toggle', function() {
            const $button = $(this);
            const $hiddenWrapper = $button.siblings('.js-fitment-hidden');
            if (!$hiddenWrapper.length) return;
            const isVisible = $hiddenWrapper.is(':visible');
            $hiddenWrapper.slideToggle(200);
            $button.text(isVisible ? 'View Full List ▼' : 'Hide Full List ▲');
        });

        const skuToAltMap = {
            'RRR-10003-KIT': true,
            'RRR-10006-KIT': true,
            'RRR-10008-KIT': true,
            'RRR-10014-KIT': true,
            'RRR-10015-KIT': true
        };

        // Store notice for /cart if RRR added from archive
        $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
            const sku = $button.data('product_sku');
            if (!sku || !skuToAltMap[sku]) return;

            $.post(astra_wc_custom_params.ajax_url, {
                action: 'get_rrr_notice_for_sku',
                sku: sku
            }).done(function(response) {
                if (response.success && response.data.notice_html) {
                    sessionStorage.setItem('rrrNoticeHtml', response.data.notice_html);
                    if (!window.location.pathname.includes('/cart')) {
                        $('.woocommerce-notices-wrapper').prepend(response.data.notice_html);
                    }
                }
            });
        });

        // Original cart clearing logic
        if (window.location.pathname.includes('/cart')) {
            $('.rrr-archive-notice').remove();
            $('.woocommerce-error').remove();
        }

        // NEW: Keep RRR notice alive on cart if came from /shop-2
        if (window.location.pathname.includes('/cart')) {
            const isFromGrid = document.referrer.includes('/shop-2');
            const savedNotice = sessionStorage.getItem('rrrNoticeHtml');

            if (isFromGrid && savedNotice) {
                const target = document.querySelector('.woocommerce-notices-wrapper');

                const injectNotice = function() {
                    if (!$('.woocommerce-notices-wrapper .rrr-archive-notice').length) {
                        $('.woocommerce-notices-wrapper').prepend(savedNotice);
                        sessionStorage.removeItem('rrrNoticeHtml'); // Clear immediately after showing
                    }
                };

                injectNotice();

                const observer = new MutationObserver(function() {
                    injectNotice();
                });

                if (target) {
                    observer.observe(target, {
                        childList: true,
                        subtree: true
                    });
                }
            }
        }
        // Cart page: inject RRR notice dynamically if coming from /shop-2
        if (window.location.pathname.includes('/cart')) {
            const referrer = document.referrer;
            const isFromGrid = referrer.includes('/shop-2');

            // Don't load if user came from a single product page
            if (isFromGrid) {
                // Determine if cart has an RRR product (based on SKU prefix)
                $.post(astra_wc_custom_params.ajax_url, {
                    action: 'get_cart_items_by_sku'
                }).done(function(response) {
                    if (!response.success || !response.data) return;

                    const skuMap = response.data;
                    const rrrSkus = Object.keys(skuMap).filter(sku => sku.startsWith('RRR-'));

                    // If we have an RRR product in cart, fetch notice
                    if (rrrSkus.length > 0) {
                        // Just use the first one for mapping
                        const targetSku = rrrSkus[0];

                        $.post(astra_wc_custom_params.ajax_url, {
                            action: 'get_rrr_notice_for_sku',
                            sku: targetSku
                        }).done(function(response) {
                            if (response.success && response.data.notice_html) {
                                $('.woocommerce-notices-wrapper').prepend(response.data.notice_html);
                            }
                        });
                    }
                });
            }
        }

        function fireGA4Event(eventName, eventParams = {}) {
            if (typeof gtag === 'function') {
                gtag('event', eventName, eventParams);
            } else {
                console.warn('gtag not defined');
            }
        }

    }
})(jQuery);