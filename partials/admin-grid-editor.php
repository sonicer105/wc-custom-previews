<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

global $wpdb;
$slug = WC_CP_SLUG;
$options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name like '{$slug}-grid-%'", ARRAY_A);
$selected = $_GET['grid-id'] ?? 'none';

?>
<div class="wrap">
    <h2><?php echo WC_CP_PLUGIN_NAME; ?> Grid Editor</h2>
    <?php settings_errors(); ?>
    <h2>Grid Selection</h2>
    <form method="GET" action="admin.php">
        <input type="hidden" name="page" value="<?php echo WC_CP_SLUG ?>-grids">
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="grid-id"><?php _e('Grid', WC_CP_SLUG) ?></label></th>
                <td>
                    <select name="grid-id" id="grid-id">
                        <option <?php echo ($selected == "none") ? 'selected="selected" ' : '' ?>value="none">Choose...</option>
                        <?php if(!empty($options) && is_array($options)) { foreach ($options as $option) {
                            $json = json_decode($option['option_value'], true);
                            echo '<option ' . (($selected == $option['option_name']) ? 'selected="selected" ' : '') . 'value="' . $option['option_name'] . '">' . $json['title'] . ' (' . $json['gridType'] . ')</option>';
                        }} ?>
                        <option value="new"><?php _e('[Create New]', WC_CP_SLUG) ?></option>
                    </select>
                    <p class="description" id="new-admin-email-description"><?php _e('Select a Custom Preview to edit or select <kbd>[Create New]</kbd> to create a new one.', WC_CP_SLUG) ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php submit_button('Load Grid','primary', ''); ?>
    </form>
    <?php
    if($selected != 'none') {
        if($selected != 'new') {
            $option = $options[array_search($selected, array_column($options, 'option_name'))];
            $option['option_value'] = json_decode($option['option_value'], true);
        } else { // $selected = new
            $option = [
                'option_name' => 'new',
                'option_value' => [
                    'title' => 'New Grid',
                    'gridType' => 'color',
                    'version' => 1,
                    'defaultValue' => '',
                    'grids' => []
                ]
            ];
        }
        ?>
        <hr>
        <h2>Grid: <?php echo $option['option_value']['title']; ?></h2>
        <form id="grid-form" method="POST" action="admin.php?page=<?php echo WC_CP_SLUG ?>-grids">
            <input type="hidden" id="grid_data" name="grid_data" value="<?php echo htmlspecialchars(json_encode($option)) ?>">
            <input type="hidden" name="should-save" value="true">
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="grid-title">Title</label></th>
                    <td><input type="text" id="grid-title" value="<?php echo $option['option_value']['title'] ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="grid-default-value">Default Value</label></th>
                    <td><input type="text" id="grid-default-value" value="<?php echo $option['option_value']['defaultValue'] ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="grid-type">Grid Type</label></th>
                    <td><select id="grid-type"><option selected="selected" value="color">Color</option></select></td>
                </tr>
                </tbody>
            </table>
            <h3>Grid Options</h3>
            <table id="grid-table" class="form-table" role="presentation">
                <tbody><tr><th scope="row">LOADING GRID... (waiting for JavaScript onLoad)</th></tr></tbody>
            </table>
            <button id='new-row-button' class='button button-secondary'>Add Grid</button>
            <?php submit_button(null, 'primary', ''); ?>
        </form>
    <?php } ?>
</div>