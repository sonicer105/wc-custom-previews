<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

/**
 * Class WC_CP_Front_End
 *
 * All the managed front end code
 */
class WC_CP_Front_End {

    /**
     * WC_CP_Front_End constructor.
     */
    function __construct() {
        add_action('init', [$this, 'register_resources']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_resources']);

//        add_action('woocommerce_before_single_product_summary', [$this, 'avali_preview_frame'], 10);
        add_action('woocommerce_before_add_to_cart_button', [$this, 'avali_builder_dropdowns']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_custom_field'], 10, 3);
        add_filter('woocommerce_post_class', [$this, 'add_single_product_class'], 10, 2);
        add_action('woocommerce_before_single_product_summary', [$this, 'custom_product_add_thumbnails'], 100, 0);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_field_item_data'], 10, 4);
        add_filter('woocommerce_cart_item_name', [$this, 'cart_item_name'], 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_custom_data_to_order'], 10, 4);
    }

    /**
     * Register all the plugin resources in the plugin with WordPress
     */
    function register_resources() {
        wp_register_script( 'wc_cp_script', plugins_url('/js/script.js', __FILE__), array('jquery'), WC_CP_VER );
        wp_register_style( 'wc_cp_style', plugins_url('/css/style.css', __FILE__), false, WC_CP_VER, 'all');

        wp_register_script( 'color_picker_js_script', plugins_url('/js/colorPick.min.js', __FILE__), array('jquery'), '0.4.1-11-g7bc9984' );
        wp_register_style( 'color_picker_js_style', plugins_url('/css/colorPick.min.css', __FILE__), false, '0.4.1-11-g7bc9984', 'all');
    }

    /**
     * Add all the plugin resources to the front end rendering queue
     */
    function enqueue_resources(){
        wp_enqueue_script('wc_cp_script');
        wp_enqueue_style('wc_cp_style');

        // Adds the color pallet as a varable to the JS file.
        wp_localize_script('wc_cp_script', 'colorPickerPallet', WC_Custom_Previews::layer_config['color_choices']);

        wp_enqueue_script('color_picker_js_script');
        wp_enqueue_style( 'color_picker_js_style' );
    }

//    function avali_preview_frame() {
//        echo '<div style="float: left">test</div><div style="clear: left"></div>';
//    }

    function avali_builder_dropdowns() {
        global $post;
        // Check for the custom field value
        $product = wc_get_product( $post->ID );
        $enabled = $product->get_meta('avali_designer', true) == "yes";
        if(!$enabled) return;

        // Only display our field if we've got a value for the field title
        foreach (WC_Custom_Previews::layer_config['layers'] as $item) {
            if ($item['configurable'] == false) continue; //Skip layers that should not be colored
            $id = $item['id'];
            $title = $item['title'];
            echo <<<EOF
<div class="avali-field-wrapper">
    <label for="layer-1">$title</label>
    <input type="hidden" id="$id" name="$id" value="#FFFFFF">
    <div class="color-picker" data-for="#$id" role="button"><span class="color-preview"></span><span class="color-text">#000000</span></div>
</div>
EOF;
        }
    }

    /**
     * Validate the custom fields
     *
     * @param array $passed Validation status
     * @param Integer $product_id Product ID
     * @param Boolean $quantity Quantity
     * @return array|false
     */
    function validate_custom_field($passed, $product_id, $quantity) {
        $enabled = wc_get_product($product_id)->get_meta('avali_designer', true) == "yes";
        if(!$enabled) return $passed;

        $options = array_column(WC_Custom_Previews::layer_config['color_choices'], 'value');
        foreach (WC_Custom_Previews::layer_config['layers'] as $item) {

            if(!$item['configurable']) continue;

            if(empty($_POST[$item['id']])){
                wc_add_notice(sprintf(__( 'Please enter a value for %s.', WC_CP_SLUG ), $item['title']), 'error' );
                return false;
            }

            if(!in_array($_POST[$item['id']], $options, true)){
                wc_add_notice(sprintf(__( 'The field "%s" is invalid.', WC_CP_SLUG ), $item['title']), 'error' );
                return false;
            }
        }
        return $passed;
    }

    /**
     * Add the text field as item data to the cart object
     * @param array $cart_item_data Cart item meta data.
     * @param Integer $product_id Product ID.
     * @param Integer $variation_id Variation ID.
     * @param Boolean $quantity Quantity
     * @return array
     */
    function add_custom_field_item_data($cart_item_data, $product_id, $variation_id, $quantity) {
        $enabled = wc_get_product($product_id)->get_meta('avali_designer', true) == "yes";
        if(!$enabled) return $cart_item_data;

        foreach (WC_Custom_Previews::layer_config['layers'] as $item) {
            if(!$item['configurable']) continue;

            foreach (WC_Custom_Previews::layer_config['color_choices'] as $choice) {
                if ($choice['value'] == $_POST[$item['id']]) {
                    $cart_item_data[$item['id']] = $choice['title'];
                    break;
                }
            };
        }

        return $cart_item_data;
    }

    /**
     * Display the custom field value in the cart
     * @param $name
     * @param $cart_item
     * @param $cart_item_key
     * @return string
     */
    function cart_item_name($name, $cart_item, $cart_item_key) {
        foreach (WC_Custom_Previews::layer_config['layers'] as $item) {
            if(!$item['configurable']) continue;
            if(isset($cart_item[$item['id']])) {
                $name .= sprintf('<div><b>%s:</b> %s</div>', esc_html($item['title']), esc_html($cart_item[$item['id']]));
            }
        }
        return $name;
    }

    /**
     * Add custom field to order object
     * @param $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     */
    function add_custom_data_to_order($item, $cart_item_key, $values, $order) {
        foreach($item as $cart_item_key=>$values) {
            foreach (WC_Custom_Previews::layer_config['layers'] as $layer_item) {
                if(!$layer_item['configurable']) continue;
                if(isset($values[$layer_item['id']])) {
                    $item->add_meta_data($layer_item['title'], $values[$layer_item['id']], true);
                }
            }
        }
    }

    /**
     * @param array      $classes Array of CSS classes.
     * @param WC_Product $product Product object.
     * @return mixed
     */
    function add_single_product_class($classes, $product) {

        // is_product() - Returns true on a single product page
        // NOT single product page, so return
        if (!is_product()) return $classes;

        $enabled = $product->get_meta('avali_designer', true) == "yes";
        if(!$enabled) return $classes;

        // Add new class
        $classes[] = 'avali-product';

        return $classes;
    }

    function custom_product_add_thumbnails(){
        echo <<<EOF
<div class='avali-image-preview'>
    <div class="loader-wrapper" style="display: none;"><div class="sr-only">Loading...</div><div class="loader"></div></div>
    <img src="https://dev.avali.sailextech.me/wp-json/wc-custom-previews/v1/generate?primary=ffffff&secondary=ffffff&markings=ffffff&pads=ffffff&claws=ffffff&beans=ffffff&eyes=ffffff">
    <button id="switch-to-gallery" class="button alt">Show gallery</button>
</div>
EOF;
    }
}

$wc_cp_front_end = new WC_CP_Front_End();