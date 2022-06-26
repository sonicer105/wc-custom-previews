<?php
/**
 * Plugin Name: WooCommerce Avali Custom Previews
 * Plugin URI: https://github.com/sonicer105/wc-custom-previews
 * description: Early alpha plugin to generate previews on the fly based on user input on product page.
 * Version: 0.0.3
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
 * @version 0.0.3
*/

defined( 'ABSPATH' ) or die(); // Prevents direct access to file.
define('WC_CP_SLUG', 'wc_custom_preview');
define('WC_CP_VER', '0.0.3');
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
                'id' => 'avali-base',
                'title' => 'Base',
                'src' => WC_CP_PATH . 'img/a0_base.png',
                'configurable' => false
            ],
            [
                'id' => 'avali-eye',
                'title' => 'Eyes',
                'src' => WC_CP_PATH . 'img/a1_eye.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-primary',
                'title' => 'Primary',
                'src' => WC_CP_PATH . 'img/a2_primary.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-secondary',
                'title' => 'Secondary',
                'src' => WC_CP_PATH . 'img/a3_secondary.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-dots',
                'title' => 'Dots',
                'src' => WC_CP_PATH . 'img/a4_dots.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-markings',
                'title' => 'Markings',
                'src' => WC_CP_PATH . 'img/a5_markings.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-claws',
                'title' => 'Claws',
                'src' => WC_CP_PATH . 'img/a6_claws.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-talon-pads',
                'title' => 'Talon Pads',
                'src' => WC_CP_PATH . 'img/a7_talon_pads.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => true
            ],
            [
                'id' => 'avali-shading',
                'title' => 'Shading',
                'src' => WC_CP_PATH . 'img/a8_shading.png',
                'blend_mode' => Imagick::COMPOSITE_MULTIPLY,
                'configurable' => false
            ],
            [
                'id' => 'avali-store-text',
                'title' => 'Store Text',
                'src' => WC_CP_PATH . 'img/a9_store.png',
                'blend_channel' => Imagick::CHANNEL_ALPHA,
                'configurable' => false
            ]
        ],
        'color_choices' => [
            [
                'id' => 'black',
                'title' => 'Black',
                'description' => 'Row 1, Column 1',
                'value' => '#000000'
            ],
            [
                'id' => 'red',
                'title' => 'Red',
                'description' => 'Row 1, Column 2',
                'value' => '#FF0000'
            ],
            [
                'id' => 'green',
                'title' => 'Green',
                'description' => 'Row 1, Column 3',
                'value' => '#00FF00'
            ],
            [
                'id' => 'blue',
                'title' => 'Blue',
                'description' => 'Row 1, Column 4',
                'value' => '#0000FF'
            ],
            [
                'id' => 'yellow',
                'title' => 'Yellow',
                'description' => 'Row 2, Column 1',
                'value' => '#FFFF00'
            ],
            [
                'id' => 'cyan',
                'title' => 'Cyan',
                'description' => 'Row 2, Column 2',
                'value' => '#00FFFF'
            ],
            [
                'id' => 'white',
                'title' => 'White',
                'description' => 'Row 2, Column 3',
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