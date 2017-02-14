<?php
/**
 * Shipping Calculator
 *
 * @author Ravish Pandey
 * @package woocommerce-shipping-product-page
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
global $woocommerce;

?>

<?php do_action('woocommerce_before_shipping_calculator'); ?>

    <div class="calculateshipping-button-container" style="display:block;float:<?php echo $button_align_default; ?>;">
        <div class="calculateshipping-button">
            <div class="cs-btn" data-layout="button_count">
                <button type="button" class="shipping-calculator-button button" onclick="showHideForm();">
                    <?php _e('Calculate Shipping', $this->app_name); ?>
                </button>
            </div>
        </div>
    </div>
    <div style="clear:both"></div>

    <div>
        <p>
            <?php
            if (isset($_POST['calc_shipping'], $_POST['calc_shipping_quantity']) && $_POST['calc_shipping'] == '1' && $_POST['calc_shipping_quantity'] > 0) {
                $package = array('rates' => array());
                $package['destination']['country'] = $_POST['calc_shipping_country'];
                $package['destination']['state'] = $_POST['calc_shipping_state'];
                $package['destination']['postcode'] = $_POST['calc_shipping_postcode'];
                $quantity = $_POST['calc_shipping_quantity'];
                $product_id = get_the_ID();
                $product_data = wc_get_product($product_id);

                if ($product_data->product_type === 'variable' && $data = wc_get_product($_POST['calc_shipping_variation_id'])) {
                    $product_data = $data;
                    $product_id = $product_data->variation_id;
                }

                $package['contents_cost'] = $quantity * $product_data->price;

                $package['contents'][0] = array(
                    'product_id' => $product_id,
                    'data' => $product_data,
                    'line_total' => $product_data->get_price(),
                    'quantity' => $quantity
                );

                $new_package = $woocommerce->shipping->calculate_shipping_for_package($package);

                if ($new_package) {
                    $package = $new_package;
                }

                $packages = array($package);
                $methods_id = array_keys($package['rates']);
                $chosen_method = count($methods_id) > 0 ? $methods_id[0] : '';

                wc_get_template('cart/cart-shipping.php', array(
                    'package' => $package,
                    'available_methods' => $package['rates'],
                    'show_package_details' => sizeof($packages) > 1,
                    'package_details' => '',
                    'package_name' => '',
                    'index' => 0,
                    'chosen_method' => $chosen_method
                ));
            }

            ?>
        </p>
    </div>

    <div style="clear:both"></div>
    <form class="shipping_calculator" action="" method="post">

        <input type="hidden" name="calc_shipping_variation_id" value="0">
        <input type="hidden" name="calc_shipping_quantity" value="1">

        <section class="shipping-calculator-form" id="shipping-calculator-form" style="display:none;">

            <p class="form-row form-row-wide">
                <?php
                //setting up default feilds for logged in user.
                $current_cc = $woocommerce->customer->get_shipping_country();
                $current_r = $woocommerce->customer->get_shipping_state();
                $current_ct = $woocommerce->customer->get_shipping_city();
                $shippost = $woocommerce->customer->get_shipping_postcode();

                if (is_user_logged_in()) {
                    global $current_user;
                    $usmeta = get_user_meta($current_user->ID);

                    if (isset($usmeta['shipping_country']) && $usmeta['shipping_country'][0] != '') {
                        $current_cc = $usmeta['shipping_country'][0];
                    }
                    if (isset($usmeta['shipping_state']) && $usmeta['shipping_state'][0] != '') {
                        $current_r = $usmeta['shipping_state'][0];
                    }
                    if (isset($usmeta['shipping_city']) && $usmeta['shipping_city'][0] != '') {
                        $current_ct = $usmeta['shipping_city'][0];
                    }
                    if (isset($usmeta['shipping_postcode']) && $usmeta['shipping_postcode'][0] != '') {
                        $shippost = $usmeta['shipping_postcode'][0];
                    }
                }

                if (isset($_POST['calc_shipping_postcode']) && !empty($_POST['calc_shipping_postcode'])) {
                    $shippost = wc_format_postcode($_POST['calc_shipping_postcode'], $current_cc);
                }

                $states = $woocommerce->countries->get_states($current_cc);
                ?>
                <select name="calc_shipping_country"
                        id="calc_shipping_country"
                        class="country_to_state"
                        rel="calc_shipping_state">
                    <option value=""><?php _e('Select a country&hellip;', 'woocommerce'); ?></option>
                    <?php
                    foreach ($woocommerce->countries->get_allowed_countries() as $key => $value) {
                        echo '<option value="' . esc_attr($key) . '"' . selected($current_cc, esc_attr($key), false) . '>' . esc_html($value) . '</option>';
                    }
                    ?>
                </select>
            </p>

            <p class="form-row form-row-wide">
                <?php
                // Hidden Input
                if (is_array($states) && empty($states)) {
                    ?>
                    <input type="hidden"
                           name="calc_shipping_state"
                           id="calc_shipping_state"
                           placeholder="<?php _e('State / county', 'woocommerce'); ?>"/>
                    <?php
                    // Dropdown Input
                } elseif (is_array($states)) {
                    ?>
                    <span>
                    <select name="calc_shipping_state"
                            id="calc_shipping_state"
                            placeholder="<?php _e('State / county', 'woocommerce'); ?>">
							<option value=""><?php _e('Select a state&hellip;', 'woocommerce'); ?></option>
                        <?php
                        foreach ($states as $ckey => $cvalue)
                            echo '<option value="' . esc_attr($ckey) . '" ' . selected($current_r, $ckey, false) . '>' . __(esc_html($cvalue), 'woocommerce') . '</option>';
                        ?>
						</select>
                </span>
                    <?php
                    // Standard Input
                } else {
                    ?>
                    <input type="text"
                           class="input-text"
                           value="<?php echo esc_attr($current_r); ?>"
                           placeholder="<?php _e('State / county', 'woocommerce'); ?>"
                           name="calc_shipping_state"
                           id="calc_shipping_state"/>
                <?php } ?>
            </p>

            <?php if (apply_filters('woocommerce_shipping_calculator_enable_city', false)) : ?>

                <p class="form-row form-row-wide">
                    <input type="text"
                           class="input-text"
                           value="<?php echo esc_attr($current_ct); ?>"
                           placeholder="<?php _e('City', 'woocommerce'); ?>"
                           name="calc_shipping_city"
                           id="calc_shipping_city"/>
                </p>

            <?php endif; ?>

            <?php if (apply_filters('woocommerce_shipping_calculator_enable_postcode', true)) : ?>

                <p class="form-row form-row-wide">
                    <input type="text" class="input-text" value="<?php echo esc_attr($shippost); ?>"
                           placeholder="<?php _e('Postcode / Zip', $this->app_name); ?>"
                           name="calc_shipping_postcode"
                           id="calc_shipping_postcode"/>
                </p>

            <?php endif; ?>

            <p>
                <button type="submit" name="calc_shipping" value="1" class="button">
                    <?php _e('Calculate', $this->app_name); ?>
                </button>
            </p>

            <?php wp_nonce_field('woocommerce-cart'); ?>
        </section>
    </form>

<?php do_action('woocommerce_after_shipping_calculator'); ?>