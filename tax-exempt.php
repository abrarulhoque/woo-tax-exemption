// Calculate fees on the checkout page
add_action('woocommerce_cart_calculate_fees', 'shipping_method_discount', 20, 1);
function shipping_method_discount($cart_object)
{
    if (is_admin() && !defined('DOING_AJAX')) return;

    $chosen_payment_method = WC()->session->get('chosen_payment_method');

    if ($chosen_payment_method == 'cod') {
        // If the user buys with COD, don't apply tax.
        WC()->customer->set_is_vat_exempt(true);
    } else {
        // If the user buys with other payment methods, apply tax.
        WC()->customer->set_is_vat_exempt(false);
    }
}

// Add AJAX handler to update chosen payment method and form field values
add_action('wp_ajax_update_chosen_payment_method', 'update_chosen_payment_method');
add_action('wp_ajax_nopriv_update_chosen_payment_method', 'update_chosen_payment_method');
function update_chosen_payment_method()
{
    if (isset($_POST['payment_method'])) {
        WC()->session->set('chosen_payment_method', sanitize_key($_POST['payment_method']));
    }

    // Store form field values in the session
    foreach ($_POST as $key => $value) {
        $key = sanitize_text_field($key);
        $value = sanitize_text_field($value);
        if (strpos($key, 'billing_') !== false) {
            WC()->session->set($key, $value);
        }
    }

    wp_die();
}

// Refresh the checkout page when the user selects a payment method and repopulate form field values
add_action('wp_footer', 'refresh_payment_methods');
function refresh_payment_methods()
{
    // Get stored form field values
    $stored_field_values = array();
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'billing_') !== false) {
            $stored_field_values[$key] = $value;
        }
    }

    // JavaScript code
    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            // On document ready
            $(document.body).on('change', 'input[name^="payment_method"]', function () {
                var paymentMethod = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    data: {
                        action: 'update_chosen_payment_method',
                        payment_method: paymentMethod
                    },
                    success: function (response) {
                        location.reload();
                    }
                });
            });

            // Repopulate form field values
            <?php foreach ($stored_field_values as $key => $value) { ?>
            var $input = $('input[name="<?php echo $key; ?>"]');
            if ($input.length) {
                $input.val('<?php echo $value; ?>');
            } else {
                var $select = $('select[name="<?php echo $key; ?>"]');
                if ($select.length) {
                    $select.val('<?php echo $value; ?>');
                }
            }
            <?php } ?>
        });
    </script>
    <?php
}
