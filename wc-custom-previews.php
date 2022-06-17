<?php
/**
 * Plugin Name: WooCommerce Avali Custom Previews
 * Plugin URI: https://github.com/sonicer105/wc-custom-previews
 * description: Early alpha plugin to generate previews on the fly based on user input on product page.
 * Version: 0.0.1
 * Author: @LinuxPony#3888 & @Tritty#9922
 * Author URI: https://sailextech.me/
 * Requires at least: 6.0
 * Tested up to: 6.0
 * Text Domain: wc_custom_previews
 * Domain Path: /languages/
 * WC require at least: 6.4.1
 * WC tested up to: 6.4.1
 * License: GPLv3
 *
 * @package wc_dynamic_preview
 * @version 0.0.1
*/

defined( 'ABSPATH' ) or die(); // Prevents direct access to file.
define('WC_CP_SLUG', 'wc_custom_preview');
define('WC_CP_VER', '0.0.1');
define('WC_CP_PATH', ABSPATH . 'wp-content/plugins/wc-custom-previews/');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * Class WC_Custom_Previews
 *
 * Main Class
 */
class WC_Custom_Previews {

    const layer_config = [
        'layers' => [
            [
                'id' => 'avali-primary',
                'title' => 'Primary Color',
                'src' => WC_CP_PATH . 'img/0_primary.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-secondary',
                'title' => 'Secondary Color',
                'src' => WC_CP_PATH . 'img/1_secondary.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-markings',
                'title' => 'Marking Color',
                'src' => WC_CP_PATH . 'img/2_markings.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-pads',
                'title' => 'Pad Color',
                'src' => WC_CP_PATH . 'img/3_pads.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-claws',
                'title' => 'Claw Color',
                'src' => WC_CP_PATH . 'img/4_claws.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-beans',
                'title' => 'Bean Color',
                'src' => WC_CP_PATH . 'img/5_beans.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-eyes',
                'title' => 'Eye Color',
                'src' => WC_CP_PATH . 'img/6_eye.png',
                'configurable' => true
            ],
            [
                'id' => 'avali-overlay',
                'title' => 'Overlay',
                'src' => WC_CP_PATH . 'img/7_overlay.png',
                'configurable' => false
            ],
        ],
        'color_choices' => [
            [
                'id' => 'black',
                'title' => 'Black',
                'value' => '#000000'
            ],
            [
                'id' => 'red',
                'title' => 'Red',
                'value' => '#FF0000'
            ],
            [
                'id' => 'green',
                'title' => 'Green',
                'value' => '#00FF00'
            ],
            [
                'id' => 'blue',
                'title' => 'Blue',
                'value' => '#0000FF'
            ],
            [
                'id' => 'yellow',
                'title' => 'Yellow',
                'value' => '#FFFF00'
            ],
            [
                'id' => 'cyan',
                'title' => 'Cyan',
                'value' => '#00FFFF'
            ],
            [
                'id' => 'white',
                'title' => 'White',
                'value' => '#FFFFFF'
            ],
        ]
    ];

    /**
     * WC_Custom_Previews constructor.
     */
    function __construct() {
        if (!self::is_woocommerce_activated()) {
            add_action('admin_notices', [$this, 'plugin_not_available']);
            return;
        }
        require_once(WC_CP_PATH . 'front-end.php');
        require_once(WC_CP_PATH . 'admin-ui.php');
        require_once(WC_CP_PATH . 'rest-api.php');
    }

    /**
     * Sees if WooCommerce is loaded.
     * @return bool true if WooCommerce is loaded, otherwise false.
     */
    function is_woocommerce_activated() {
        return is_plugin_active('woocommerce/woocommerce.php');
    }

    /**
     * Callback that displays an error on every admin page informing the user WooCommerce is missing and is a dependency
     * of this plugin
     */
    function plugin_not_available() {
        $lang = '';
        if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
            $lang = ' lang="en_CA"';
        }

        printf(
            '<div class="error notice is-dismissible notice-info">
<p><span dir="ltr"%s>%s</span></p>
</div>',
            $lang,
            wptexturize(__(
                'WooCommerce is not active. Please install and activate WooCommerce to use WooCommerce Custom Previews.',
                WC_CP_SLUG
            ))
        );
    }
}


$wc_custom_previews = new WC_Custom_Previews();