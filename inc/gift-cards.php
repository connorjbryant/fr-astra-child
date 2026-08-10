<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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