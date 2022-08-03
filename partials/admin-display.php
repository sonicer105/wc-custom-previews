<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

echo '<pre>' . print_r(WC_CP_Admin_UI::get_config_for_product(15), true) . '</pre>';