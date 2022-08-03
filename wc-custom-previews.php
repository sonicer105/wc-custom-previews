<?php
/**
 * Plugin Name: WooCommerce Advanced Custom Previews
 * Plugin URI: https://github.com/sonicer105/wc-custom-previews
 * description: Early alpha plugin to generate previews on the fly based on user input on product page.
 * Version: 0.1.0
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
 * @version 0.1.0
*/

defined( 'ABSPATH' ) or die(); // Prevents direct access to file.
define('WC_CP_SLUG', 'wc_custom_preview');
define('WC_CP_VER', '0.1.0');
define('WC_CP_PATH', plugin_dir_path(__FILE__));
define('WC_CP_URL', plugin_dir_url(__FILE__));
define('WC_CP_PLUGIN_NAME', 'WooCommerce Custom Previews');

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

/**
 * Class WC_Custom_Previews
 *
 * Main Class
 */
class WC_Custom_Previews {
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