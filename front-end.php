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

        add_action('woocommerce_before_add_to_cart_button', [$this, 'custom_previews_builder_dropdowns']);
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
        wp_register_script( 'wc-cp-js', plugins_url('/js/script.js', __FILE__), array('jquery'), WC_CP_VER );
        wp_register_style( 'wc-cp-css', plugins_url('/css/style.css', __FILE__), false, WC_CP_VER, 'all');

        wp_register_script( 'select2-js', plugins_url('/js/select2.min.js', __FILE__), array('jquery'), '4.0.13' );
        wp_register_style( 'select2-css', plugins_url('/css/select2.min.css', __FILE__), false, '4.0.13', 'all');
    }

    /**
     * Add all the plugin resources to the front end rendering queue
     */
    function enqueue_resources(){
        if (is_singular('product')) {
            $data = WC_CP_Admin_UI::get_config_for_product(get_the_ID());
            if($data instanceof WP_Error) return;

            wp_enqueue_script('wc-cp-js');
            wp_enqueue_style('wc-cp-css');

            $data['grids']['id'] = get_the_ID();

            // Adds the color pallet as a varable to the JS file.
            wp_localize_script('wc-cp-js', 'colorPickerPallet', $data['grids']);

            wp_enqueue_script('select2-js');
            wp_enqueue_style( 'select2-css' );
        }
    }

    function custom_previews_builder_dropdowns() {
        global $post;

        $layers = WC_CP_Admin_UI::get_config_for_product($post->ID);
        if($layers instanceof WP_Error) return;

        // Check for the custom field value
        $product = wc_get_product( $post->ID );
        $enabled = $product->get_meta('custom_previews', true);
        if(!isset($enabled) || $enabled == 'none') return;

        // Only display our field if we've got a value for the field title
        foreach ($layers['layers'] as $item) {
            $id = $item['id'];
            $title = $item['title'];
            if ($item['colorConfigurable']) {
                $data_source = $item['color'];
                $class = "color-picker";
            } else if ($item['srcConfigurable']) {
                $data_source = $item['srcList'];
                $class = "src-picker";
            } else {
                continue;
            }
            echo <<<EOF
<div class="custom-previews-field-wrapper">
    <label for="$id">$title</label>
    <select id="$id" name="$id" class="$class" data-grid="$data_source"></select>
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
        $layers = WC_CP_Admin_UI::get_config_for_product($product_id);
        if($layers instanceof WP_Error) return $passed;

        $enabled = wc_get_product($product_id)->get_meta('custom_previews', true);
        if(!isset($enabled) || $enabled == 'none') return $passed;

        foreach ($layers['layers'] as $item) {

            if(!$item['colorConfigurable']) continue;

            if(empty($_POST[$item['id']])){
                wc_add_notice(sprintf(__( 'Please enter a value for %s.', WC_CP_SLUG ), $item['title']), 'error' );
                return false;
            }

            $options = array_column($layers['grids'][$item['color']], 'value');
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
        $layers = WC_CP_Admin_UI::get_config_for_product($product_id);
        if($layers instanceof WP_Error) return $cart_item_data;

        $enabled = wc_get_product($product_id)->get_meta('custom_previews', true);
        if(!isset($enabled) || $enabled == 'none') return $cart_item_data;

        foreach ($layers['layers'] as $item) {
            if(!$item['colorConfigurable']) continue;

            foreach ($layers['grids'][$item['color']] as $choice) {
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
        $layers = WC_CP_Admin_UI::get_config_for_product($cart_item['product_id']);
        if($layers instanceof WP_Error) return $name;

        foreach ($layers['layers'] as $item) {
            if(!$item['colorConfigurable']) continue;
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
        $layers = WC_CP_Admin_UI::get_config_for_product($item->get_product_id());
        if($layers instanceof WP_Error) return;

        foreach($item as $cart_item_key=>$values) {
            foreach ($layers['layers'] as $layer_item) {
                if(!$layer_item['colorConfigurable']) continue;
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

        $enabled = $product->get_meta('custom_previews', true);
        if(!isset($enabled) || $enabled == 'none') return $classes;

        // Add new class
        $classes[] = 'custom-previews-product';

        return $classes;
    }

    function custom_product_add_thumbnails(){
        global $post;
        $product = wc_get_product( $post->ID );
        $enabled = $product->get_meta('custom_previews', true);
        if(!isset($enabled) || $enabled == 'none') return;

        echo <<<EOF
<div class='custom-previews-image-preview'>
    <div class="loader-wrapper" style="display: none;"><div class="sr-only">Loading...</div><div class="loader"></div></div>
    <img src="" alt="">
    <button id="switch-to-gallery" class="button alt">Show gallery</button>
</div>
EOF;
    }
}

$wc_cp_front_end = new WC_CP_Front_End();