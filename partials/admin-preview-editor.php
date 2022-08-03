<?php
defined( 'ABSPATH' ) or die(); // Prevents direct access to file.

wp_enqueue_media();

global $wpdb;
$slug = WC_CP_SLUG;
$options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name like '{$slug}-preview-%'", ARRAY_A);
$selected = $_GET['preview-id'] ?? 'none';

?>
<div class="wrap">
    <h2><?php echo WC_CP_PLUGIN_NAME; ?> Preview Editor</h2>
    <?php settings_errors(); ?>
    <h2>Preview Selection</h2>
    <form method="GET" action="admin.php">
        <input type="hidden" name="page" value="<?php echo WC_CP_SLUG ?>-previews">
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="preview-id"><?php _e('Previews', WC_CP_SLUG) ?></label></th>
                <td>
                    <select name="preview-id" id="preview-id">
                        <option <?php echo ($selected == "none") ? 'selected="selected" ' : '' ?>value="none">Choose...</option>
                        <?php if(!empty($options) && is_array($options)) { foreach ($options as $option) {
                            $json = json_decode($option['option_value'], true);
                            echo '<option ' . (($selected == $option['option_name']) ? 'selected="selected" ' : '') . 'value="' . $option['option_name'] . '">' . $json['title'] . '</option>';
                        }} ?>
                        <option value="new"><?php _e('[Create New]', WC_CP_SLUG) ?></option>
                    </select>
                    <p class="description" id="new-admin-email-description"><?php _e('Select a Custom Preview to edit or select <kbd>[Create New]</kbd> to create a new one.', WC_CP_SLUG) ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php submit_button('Load Preview','primary', ''); ?>
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
                    'title' => 'New Preview',
                    'version' => 1,
                    'layers' => []
                ]
            ];
        }
        ?>
        <hr>
        <h2>Preview: <?php echo $option['option_value']['title']; ?></h2>
        <form id="layer-form" method="POST" action="admin.php?page=<?php echo WC_CP_SLUG ?>-previews">
            <input type="hidden" id="layer_data" name="layer_data" value="<?php echo htmlspecialchars(json_encode($option)) ?>">
            <input type="hidden" name="should-save" value="true">
            <table class="form-table" role="presentation">
                <tbody>
                <tr>
                    <th scope="row"><label for="preview-title">Title</label></th>
                    <td><input type="text" id="preview-title" value="<?php echo $option['option_value']['title'] ?>"></td>
                </tr>
                </tbody>
            </table>
            <h3>Preview Options</h3>
            <table id="layer-table" class="form-table" role="presentation">
                <tbody><tr><th scope="row">LOADING LAYERS... (waiting for JavaScript onLoad)</th></tr></tbody>
            </table>
            <button id='new-row-button' class='button button-secondary'>Add Layer</button>
            <?php submit_button(null, 'primary', ''); ?>
        </form>
        <pre><?php print_r($option) ?></pre>
    <?php } ?>
</div>