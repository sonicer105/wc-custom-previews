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
        add_action('woocommerce_product_options_general_product_data', [$this, 'option_group']);
        add_action('woocommerce_process_product_meta', [$this, 'save_fields'], 10, 2 );
    }

    /**
     * Populates a new field under the Product data > General tab when editing a product
     */
    function option_group() {
        echo '<div class="option_group">';
        woocommerce_wp_checkbox([
            'id' => 'avali_designer',
            'value' => get_post_meta(get_the_ID(), 'avali_designer', true), // true or false
            'label' => 'Enable Avali Designer',
            'desc_tip' => true,
            'description' => 'Enables the designer allowing users to create their Avali'
        ]);
        echo '</div>';
    }

    /**
     * Saves the fields added by option_group when the product is saved
     *
     * @param $post_id int The id of the product being saved.
     * @param $post WP_Post that post data of the post being saved.
     */
    function save_fields($post_id, $post){
        if(!empty($_POST['avali_designer'])) {
            update_post_meta($post_id, 'avali_designer', $_POST['avali_designer']);
        } else {
        	delete_post_meta($post_id, 'avali_designer');
        }

    }
}

$wc_cp_admin_ui = new WC_CP_Admin_UI();