<?php
/*
Plugin Name: WooCommerce Cálculo de frete na página do produto
Plugin URI: http://gameraiderr.com/plugins/woocommerce-calculate-shipping-button.zip
Description: Adiciona um botão de cálculo de frete na página do produto.
Version: 1.0.0
Author: Ravish Pandey 'The Gameraiderr'
Author URI: http://gameraiderr.com
*/

/*  Copyright 2014 Ravish Pandey (email: ravishpandey340@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*
 * WooCommerce_Shipping_Product_page
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    if (!class_exists('WooCommerce_Shipping_Product_page')) {
        class WooCommerce_Shipping_Product_page
        {
            var $plugin_url;
            var $app_name = 'shipping-product-page';
            var $options;
            var $key;

            function __construct()
            {
                add_action( 'init', array( $this, 'load_plugin_textdomain' ), -1 );

                $this->plugin_url = trailingslashit(plugins_url(null, __FILE__));
                $this->key = 'shipping_product_page';

                // called only after woocommerce has finished loading
                add_action('woocommerce_init', array($this, 'woocommerce_loaded'));

                //Add product write panel
                add_action('woocommerce_product_write_panels', array(&$this, 'shipping_product_page_main'));
                add_action('woocommerce_product_write_panel_tabs', array(&$this, 'shipping_product_page_tab'));

                //Add product meta
                add_action('woocommerce_process_product_meta', array(&$this, 'shipping_product_page_meta'));

                //Display on product page for the calculate button
                $this->options = $this->get_options();
                $option_show_after_table = $this->options['custom_show_after_title'];

                if ($option_show_after_table == 'yes') {
                    add_action('woocommerce_single_product_summary', array(&$this, 'shipping_product_page_button'), 8);
                } else {
                    add_action('woocommerce_single_product_summary', array(&$this, 'shipping_product_page_button'), 100);
                }

                $this->options = $this->get_options();

                //Display setting menu under woocommerce
                add_action('admin_menu', array(&$this, 'add_menu_items'));

                //load stylesheet
                add_action('wp_enqueue_scripts', array(&$this, 'custom_plugin_stylesheet'));

                //Add javascript after <body> tag
                //add_action( 'init', array( &$this, 'add_afterbody_scripts' ) );
                add_action('init', array(&$this, 'add_afterbody_scripts'));

                add_shortcode('calculateshipping', array($this, 'shipping_product_page_button'));
                add_filter('widget_text', 'do_shortcode');
            }

            /**
             * Load the plugin text domain for translation.
             */
            public function load_plugin_textdomain() {
                load_plugin_textdomain( 'shipping-product-page', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
            }

            /**
             * Take care of anything that needs woocommerce to be loaded.
             * For instance, if you need access to the $woocommerce global
             */
            public function woocommerce_loaded()
            {
                // ...
            }

            /**
             * Load stylesheet for the page
             */
            function custom_plugin_stylesheet()
            {
                wp_register_style('calculateshipping-stylesheet', plugins_url('/css/calculateshipping.css', __FILE__));
                wp_enqueue_style('calculateshipping-stylesheet');
            }

            function shipping_product_page_main()
            {
                global $post;

                $enabled_option = get_post_meta($post->ID, $this->id, true);
                $label = __('Enable', 'shipping-product-page');
                $description = __('Enable Calculate Shipping Button on this product?', 'shipping-product-page');

                //if the option not set for yes or no, then default is yes
                if ('yes' != $enabled_option && 'no' != $enabled_option) {
                    $enabled_option = 'yes';
                }

                $check_id = $this->id;

                ?>
                <div id="calculateshipping" class="panel woocommerce_options_panel" style="display: none; ">
                    <fieldset>
                        <p class="form-field">
                            <?php
                            woocommerce_wp_checkbox(array(
                                'id' => $check_id,
                                'label' => __($label, $this->id_name),
                                'description' => __($description, $this->id_name),
                                'value' => $enabled_option
                            ));
                            ?>
                        </p>
                    </fieldset>
                </div>
                <?php
            }

            function shipping_product_page_tab()
            {
                ?>
                <li class="shipping_product_page_tab">
                    <a href="#calculateshipping"><?php _e('Calculate Shipping', $this->app_name); ?></a>
                </li>
                <?php
            }

            function shipping_product_page_meta($post_id)
            {
                $shipping_product_page_option = isset($_POST[$this->id]) ? 'yes' : 'no';
                update_post_meta($post_id, $this->id, $shipping_product_page_option);
            }

            function shipping_product_page_button()
            {
                global $post;
                $enabled_option = get_post_meta($post->ID, $this->id, true);

                if ($enabled_option != 'yes' && $enabled_option != 'no') {
                    $enabled_option = 'yes'; //default new products or unset value to true
                }

                $this->options = $this->get_options();
                $option_calculateshipping_enabled = $this->options['custom_calculateshipping_enabled'];

                if ($option_calculateshipping_enabled) {
                    include('shipping-calculator.php');
                }
            }

            function add_menu_items()
            {
                $wc_page = 'woocommerce';
                $comparable_settings_page = add_submenu_page($wc_page, __('Calculate Shipping Setting', 'shipping-product-page'), __('Calculate Shipping Setting', 'shipping-product-page'), 'manage_options', 'cs-settings', array(
                    &$this,
                    'options_page'
                ));
            }

            //start to include any script after <body> tag
            function add_afterbody_scripts()
            {
                wp_register_script('grcustom', plugins_url('/js/grcustom.js', __FILE__));
                wp_enqueue_script('grcustom');

                $assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
                $frontend_script_path = $assets_path . 'js/frontend/';
                $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_register_script( 'wc-country-select', $frontend_script_path . 'country-select' . $suffix . '.js', array( 'jquery' ) );
                wp_enqueue_script('wc-country-select');
            }

            function options_page()
            {
                // If form was submitted
                if (isset($_POST['submitted'])) {
                    check_admin_referer('calculate-shipping');

                    $this->options['custom_calculateshipping_enabled'] = !isset($_POST['custom_calculateshipping_enabled']) ? '' : $_POST['custom_calculateshipping_enabled'];
                    $this->options['custom_csbtn_width'] = !isset($_POST['custom_csbtn_width']) ? '450' : $_POST['custom_csbtn_width'];
                    $this->options['custom_button_align'] = !isset($_POST['custom_button_align']) ? 'left' : $_POST['custom_button_align'];

                    update_option($this->key, $this->options);

                    echo '<div id="message" class="updated fade"><p>' . __('Calculate Shipping options saved.', 'shipping-product-page') . '</p></div>';
                }

                $custom_calculateshipping_enabled = $this->options['custom_calculateshipping_enabled'];

                $checked_value2 = '';

                if ($custom_calculateshipping_enabled == 'yes') {
                    $checked_value2 = 'checked="checked"';
                }

                global $wp_version;

                $actionurl = $_SERVER['REQUEST_URI'];
                $nonce = wp_create_nonce('calculate-shipping');

                $this->options = $this->get_options();

                // Configuration Page
                ?>
                <div id="icon-options-general" class="icon32"></div>
                <h2><?php _e('Calculate Shipping Options', 'shipping-product-page'); ?></h2>

                <table width="90%" cellspacing="2">
                    <tr>
                        <td width="70%">

                            <form action="<?php echo $actionurl; ?>" method="post">
                                <table class="widefat fixed" cellspacing="0">
                                    <thead>
                                    <tr>
                                        <th width="30%"><?php _e('Option', 'shipping-product-page') ?></th>
                                        <th><?php _e('Setting', 'shipping-product-page')?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><?php _e('Enabled', 'shipping-product-page')?></td>
                                        <td>
                                            <input class="checkbox"
                                                   name="custom_calculateshipping_enabled"
                                                   id="custom_calculateshipping_enabled"
                                                   value="yes" <?php echo $checked_value2; ?>
                                                   type="checkbox">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <input class="button-primary"
                                                   type="submit"
                                                   name="Save"
                                                   value="<?php _e('Save options', 'shipping-product-page') ?>"
                                                   id="submitbutton"/>
                                            <input type="hidden" name="submitted" value="1"/>
                                            <input type="hidden"
                                                   id="_wpnonce"
                                                   name="_wpnonce"
                                                   value="<?php echo $nonce; ?>"/>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </form>

                        </td>
                    </tr>
                </table>

                <br/>

                <?php
            }

            // Handle our options
            function get_options()
            {
                $options = array(
                    'custom_show_after_title' => '',
                );
                $saved = get_option($this->key);

                if (!empty($saved)) {
                    foreach ($saved as $key => $option) {
                        $options[$key] = $option;
                    }
                }

                if ($saved != $options) {
                    update_option($this->key, $options);
                }

                return $options;
            }
        }
    }

    // finally instantiate the plugin class
    $WooCommerce_Shipping_Product_page = new WooCommerce_Shipping_Product_page();

    function calculateshipping()
    {
        $woo_calculateshipping = new WooCommerce_Shipping_Product_page();

        add_action('init', $woo_calculateshipping->shipping_product_page_button());
    }
}