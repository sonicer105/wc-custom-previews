<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

/**
 * Class WC_CP_Admin_UI
 *
 * All the managed back end ui code
 */
class WC_CP_Admin_UI {

    /**
     * WC_CP_Admin_UI constructor.
     */
    function __construct() {
        /* settings pages */
        add_action('admin_menu', [$this, 'add_admin_menu'], 9);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_my_scripts']);
        /* Woocommerce Product Page */
        add_action('woocommerce_product_options_advanced', [$this, 'option_group']);
        add_action('woocommerce_process_product_meta', [$this, 'save_fields'], 10, 2 );
        /* Woocommerce Order Edit Page */
        add_action('woocommerce_after_order_itemmeta', [$this, 'insert_image_after_order_item_meta'], 20, 3 );
    }

    /* settings pages */

    /**
     * Register display hooks for the sidebar items in the admin UI
     */
    public function add_admin_menu() {
        add_menu_page(WC_CP_PLUGIN_NAME, __('Custom Previews', WC_CP_SLUG), 'administrator', WC_CP_SLUG . '-menu', [$this, 'display_admin_dashboard'], 'dashicons-images-alt2', 26);
        add_submenu_page(WC_CP_SLUG . '-menu', __('WC CP Settings', WC_CP_SLUG), 'Preview Editor', 'administrator', WC_CP_SLUG.'-previews', [$this, 'display_preview_editor']);
        add_submenu_page(WC_CP_SLUG . '-menu', __('WC CP Settings', WC_CP_SLUG), 'Grid Editor', 'administrator', WC_CP_SLUG.'-grids', [$this, 'display_grid_editor']);
    }

    /**
     * Displays the admin dashboard
     */
    public function display_admin_dashboard() {
        require_once WC_CP_PATH . 'partials/admin-display.php';
    }

    /**
     * Displays the admin settings page
     */
    public function display_preview_editor() {
        if(isset($_POST['should-save'])){
            $this->save_layers();
        }
        require_once WC_CP_PATH . 'partials/admin-preview-editor.php';
    }

    function save_layers() {
        if(isset($_POST['layer_data'])) {
            $data = json_decode(stripslashes($_POST['layer_data']), true);
            if (!empty($data) && is_array($data) && !empty($data['option_name'])){
                if($data['option_name'] == 'new') {
                    $settings = json_decode(get_option(WC_CP_SLUG . '-settings', '{"nextPreviewId":1,"nextGridId":1}'), true);
                    $data['option_name'] = WC_CP_SLUG . '-preview-' . $settings['nextPreviewId'];
                    $settings['nextPreviewId']++;
                    update_option(WC_CP_SLUG . '-settings', json_encode($settings), false);
                }
                update_option($data['option_name'], $data['option_value'], false);
                add_settings_error('success', 'success', "Preview was saved!", $type = 'success');
            } else {
                add_settings_error('preview_data', 'data_corrupt', 'unable to decode JSON data in preview_data', $type = 'error');
            }
        } else {
            add_settings_error('preview_data', 'data_missing', 'preview_data missing from POST', $type = 'error');
        }
    }

    /**
     * Displays the admin settings page
     */
    public function display_grid_editor() {
        if(isset($_POST['should-save'])){
            $this->save_grids();
        }
        require_once WC_CP_PATH . 'partials/admin-grid-editor.php';
    }

    function save_grids() {
        if(isset($_POST['grid_data'])) {
            $data = json_decode(stripslashes($_POST['grid_data']), true);
            if (!empty($data) && is_array($data) && !empty($data['option_name'])){
                if($data['option_name'] == 'new') {
                    $settings = json_decode(get_option(WC_CP_SLUG . '-settings', '{"nextPreviewId":1,"nextGridId":1}'), true);
                    $data['option_name'] = WC_CP_SLUG . '-grid-' . $settings['nextGridId'];
                    $settings['nextGridId']++;
                    update_option(WC_CP_SLUG . '-settings', json_encode($settings), false);
                }
                update_option($data['option_name'], $data['option_value'], false);
                add_settings_error('success', 'success', "Grid was saved!", $type = 'success');
            } else {
                add_settings_error('grid_data', 'data_corrupt', 'unable to decode JSON data in grid_data', $type = 'error');
            }
        } else {
            add_settings_error('grid_data', 'data_missing', 'grid_data missing from POST', $type = 'error');
        }
    }

    /**
     * Adds our scripts and styles to our settings pages.
     * @param $hook string The current admin page hook being run
     */
    function enqueue_my_scripts($hook) {
        if(str_contains($hook, WC_CP_SLUG)) {
            global $wpdb;
            $slug = WC_CP_SLUG;
            $options = $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name like '{$slug}-grid-%'", ARRAY_A);
            $grids = [];
            if(!empty($options) && is_array($options)) {
                foreach ($options as $option) {
                    $json = json_decode($option['option_value'], true);
                    $grids[] = [
                        'id' => $option['option_name'],
                        'title' => $json['title'],
                        'gridType' => $json['gridType']
                    ];
                }
            }

            wp_enqueue_script(WC_CP_SLUG . '-admin-script', WC_CP_URL . 'js/admin-script.js', ['jquery', 'wp-color-picker'], WC_CP_VER);
            wp_localize_script(WC_CP_SLUG . '-admin-script', 'WC_CP_GRIDS', $grids);
            wp_localize_script(WC_CP_SLUG . '-admin-script', 'Imagick', [
                'COMPOSITE_DEFAULT' => Imagick::COMPOSITE_DEFAULT,
                'COMPOSITE_MULTIPLY' => Imagick::COMPOSITE_MULTIPLY,
                'CHANNEL_DEFAULT' => Imagick::CHANNEL_DEFAULT,
                'CHANNEL_ALPHA' => Imagick::CHANNEL_ALPHA
            ]);

            wp_enqueue_style('wp-color-picker');
            wp_register_style(WC_CP_SLUG . '-admin-styles', WC_CP_URL . 'css/admin-style.css', false, WC_CP_VER);
            wp_enqueue_style(WC_CP_SLUG . '-admin-styles');
        }
    }

    /* Woocommerce Product Page */

    /**
     * Populates a new field under the Product data > General tab when editing a product
     */
    function option_group() {
        echo '<div class="option_group">';

        woocommerce_wp_select([
            'id' => 'custom_previews',
            'value' => get_post_meta(get_the_ID(), 'custom_previews', true), // true or false
            'label' => __('Custom Previews', WC_CP_SLUG),
            'desc_tip' => true,
            'description' => __('Enables the use of custom previews for this product', WC_CP_SLUG),
            'options' => $this->getPreviewsArray()
        ]);
        echo '</div>';
    }

    function getPreviewsArray(){
        global $wpdb;
        $slug = WC_CP_SLUG;
        $options = $options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name like '{$slug}-preview-%'", ARRAY_A);
        $preview = ['none'=>'None'];
        if(!empty($options) && is_array($options)) {
            foreach ($options as $option) {
                $json = json_decode($option['option_value'], true);
                $preview[$option['option_name']] = $json['title'];
            }
        }
        return $preview;
    }

    /**
     * Saves the fields added by option_group when the product is saved
     *
     * @param $post_id int The id of the product being saved.
     * @param $post WP_Post that post data of the post being saved.
     */
    function save_fields($post_id, $post){
        if(!empty($_POST['custom_previews'])) {
            update_post_meta($post_id, 'custom_previews', $_POST['custom_previews']);
        } else {
        	delete_post_meta($post_id, 'custom_previews');
        }
    }

    static function get_config_for_product($id) {
        $preview_key = get_post_meta($id, 'custom_previews', true);
        if(!isset($preview_key) || $preview_key == 'none'){
            return new WP_Error('no-preview-configured', 'No custom preview is configured for this product', array('status' => 400));
        }
        $to_return = [
            'layers' => [],
            'grids' => []
        ];
        $layers = json_decode(get_option($preview_key), true);
        $to_return['layers'] = $layers['layers'];
        foreach ($layers['layers'] as $layer){
            if($layer['srcConfigurable'] && !empty($layer['srcList']) && $layer['srcList'] != 'none'){
                $to_return['grids'][$layer['srcList']] = [];
            }
            if($layer['colorConfigurable'] && !empty($layer['color']) && $layer['color'] != 'none'){
                $to_return['grids'][$layer['color']] = [];
            }
        }
        foreach($to_return['grids'] as $name => $value){
            $grids = json_decode(get_option($name), true);
            foreach ($grids['grids'] as $i => $grid) {
                $grids['grids'][$i]['src'] = wp_get_attachment_image_url($grid['value']);
            }
            $to_return['grids'][$name] = $grids['grids'];
            $to_return['grids'][$name . '-default'] = $grids['defaultValue'];
            $to_return['grids'][$name . '-type'] = $grids['gridType'];
        }

        return $to_return;
    }

    /**
     * @param $item_id int
     * @param $item WC_Order_Item_Product
     * @param $product WC_Product
     */
    function insert_image_after_order_item_meta($item_id, $item, $product) {
        // Only for "line item" order items
        if(!$item->is_type('line_item') || !is_admin()) return;

        // Only for backend and for preview products
        $data = $this->get_config_for_product($product->get_id());
        if($data instanceof WP_Error) return;

        //echo '<pre>' . print_r($item, true) . '</pre>';
        //echo '<pre>' . print_r($data, true) . '</pre>';

        $url_params = [
            'id' => $product->get_id()
        ];

        foreach ($data['layers'] as $layer) {
            if($layer['colorConfigurable'] == false) continue;
            $nice_value = $item->get_meta($layer['title']);

            if(strlen($nice_value) <= 0) { echo "Unable to find meta key \"{$layer['title']}\""; return; }

            $real_value_index = array_search($nice_value, array_column($data['grids'][$layer['color']],'title'));

            if($real_value_index !== 0 && empty($real_value_index)) { echo "Unable to find the hex code for layer \"{$layer['title']}\", value \"{$nice_value}\""; return; }

            $url_params[$layer['id']] = substr($data['grids'][$layer['color']][$real_value_index]['value'], 1);
        }

        echo '<strong style="display: block;">Generated Image:</strong>';
        echo '<img src="' . get_rest_url() . WC_CP_API::$namespace . '/generate?' . http_build_query($url_params) . '" style="width:300px;height:300px;">';
    }
}

$wc_cp_admin_ui = new WC_CP_Admin_UI();