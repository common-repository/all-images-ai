<?php
if (!defined('ABSPATH')) exit;
// check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

$current_user = wp_get_current_user();

if (isset($_GET['settings-updated'])) {
    $errors = get_settings_errors('all-images-ai');
    if (empty($errors)) {
        add_settings_error('all-images-ai', 'update_successfully', esc_html__('Settings saved', 'all-images-ai'), 'updated');
    }
}

settings_errors('all-images-ai');
?>
<div id="allimages-page-settings" class="wrap">

    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div style="display:flex;flex-wrap: wrap;">
        <form action="options.php" method="post" style="flex:1;min-width:700px;">
            <?php

            settings_fields('all-images-ai');

            do_settings_sections('all-images-ai');

            submit_button(esc_html__('Save settings', 'all-images-ai'));
            ?>
        </form>
    </div>
</div>
