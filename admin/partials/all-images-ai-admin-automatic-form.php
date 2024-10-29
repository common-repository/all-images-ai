<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!current_user_can('manage_options')) {
    return;
}
?>
<div id="allimages-automatic-form" class="wrap allimages-form">


    <h1><?php esc_html_e('Automatic image generation for your posts', 'all-images-ai') ?></h1>

    <h2><?php esc_html_e('Manage the settings to automatically add images to your newly created posts.', 'all-images-ai'); ?></h2>

    <div>
        <form action="" autocomplete="off" method="post" id="automatic-generation-form">

            <button type="button" id="collapse-accordions" data-opened-text="<?php esc_attr_e('Collapse all tabs', 'all-images-ai'); ?>" class="button button-primary"><?php esc_html_e('Expand all tabs', 'all-images-ai'); ?></button>
            <div class="accordion-wrapper">
                <?php foreach($post_types as $post_type) {

                    $hasData = (isset($this->settings['all_images_automatic']) && !empty($this->settings['all_images_automatic'][$post_type->name]));
                    $values = ($hasData) ? $this->settings['all_images_automatic'][$post_type->name] : null;
                    $active_txt = (isset($values['active']) && $values['active'] == '1') ? esc_html__('Active', 'all-images-ai') : esc_html__('Inactive', 'all-images-ai');

                    ?>

                    <h3 class="accordion-title">
                        <?php echo esc_html__('Settings for ', 'all-images-ai').$post_type->label.' ('.$post_type->name.')' ?>
                        <span class="active-text"><?php echo $active_txt; ?></span>
                    </h3>
                    <div class="accordion-content">

                        <table class="form-table" role="presentation" data-post-type="<?php echo esc_attr($post_type->name); ?>">
                            <tbody>

                            <tr>
                                <th scope="row"><?php esc_html_e('Activate', 'all-images-ai'); ?></th>
                                <td>
                                    <input <?php echo (isset($values['active']) && $values['active'] == '1') ? 'checked="checked"' : ''; ?> type="checkbox" value="1" class="active-field" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][active]">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Images', 'all-images-ai'); ?></th>
                                <td>
                                    <label for="allimages_<?php echo esc_attr($post_type->name); ?>_has_featured_image"><?php esc_html_e('Featured image', 'all-images-ai'); ?></label>
                                    <select id="allimages_<?php echo esc_attr($post_type->name); ?>_has_featured_image" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][allimages_has_featured_image]">
                                        <option selected value=""><?php esc_html_e('Select an option', 'all-images-ai'); ?></option>
                                        <option value="true" <?php if(isset($values['allimages_has_featured_image']) && $values['allimages_has_featured_image'] == 'true') echo 'selected'; ?>><?php esc_html_e('With', 'all-images-ai'); ?></option>
                                        <option value="false" <?php if(isset($values['allimages_has_featured_image']) && $values['allimages_has_featured_image'] == 'false') echo 'selected'; ?>><?php esc_html_e('Without', 'all-images-ai'); ?></option>
                                    </select>

                                    <label for="allimages_<?php echo esc_attr($post_type->name); ?>_has_content_image"><?php esc_html_e('Image in content', 'all-images-ai'); ?></label>
                                    <select id="allimages_<?php echo esc_attr($post_type->name); ?>_has_content_image" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][allimages_has_content_image]">
                                        <option selected value=""><?php esc_html_e('Select an option', 'all-images-ai'); ?></option>
                                        <option value="true" <?php if(isset($values['allimages_has_content_image']) && $values['allimages_has_content_image'] == 'true') echo 'selected'; ?>><?php esc_html_e('With', 'all-images-ai'); ?></option>
                                        <option value="false" <?php if(isset($values['allimages_has_content_image']) && $values['allimages_has_content_image'] == 'false') echo 'selected'; ?>><?php esc_html_e('Without', 'all-images-ai'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <?php if($post_type->name == 'post') { ?>
                            <tr>
                                <th><?php esc_html_e('Categories', 'all-images-ai'); ?></th>
                                <td>
                                    <label></label>
                                    <?php
                                    $cats = get_categories(array(
                                            'hide_empty' => false
                                    ));
                                    foreach($cats as $c) { ?>
                                        <label><input required type="checkbox" <?php echo (isset($values['cats']) && in_array($c->term_id, $values['cats']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][cats][]" value="<?php echo esc_attr($c->term_id); ?>"> <?php echo esc_html($c->name); ?></label>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Post status', 'all-images-ai'); ?>
                                </th>
                                <td>
                                    <label><input required type="checkbox" <?php echo (isset($values['post_status']) && in_array('publish', $values['post_status']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][post_status][]" value="publish"> <?php esc_html_e('Publish', 'all-images-ai'); ?></label>
                                    <label><input required type="checkbox" <?php echo (isset($values['post_status']) && in_array('future', $values['post_status']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][post_status][]" value="future"> <?php esc_html_e('Future', 'all-images-ai'); ?></label>
                                    <label><input required type="checkbox" <?php echo (isset($values['post_status']) && in_array('draft', $values['post_status']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][post_status][]" value="draft"> <?php esc_html_e('Draft', 'all-images-ai'); ?></label>
                                    <label><input required type="checkbox" <?php echo (isset($values['post_status']) && in_array('pending', $values['post_status']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][post_status][]" value="pending"> <?php esc_html_e('Pending', 'all-images-ai'); ?></label>
                                    <label><input required type="checkbox" <?php echo (isset($values['post_status']) && in_array('private', $values['post_status']) || !isset($values)) ? 'checked="checked"' : ''; ?> name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][post_status][]" value="private"> <?php esc_html_e('Private', 'all-images-ai'); ?></label>
                                    <span class="hint-text"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('When the post changes to one of the checked statuses, the generation will be launched.', 'all-images-ai'); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <button type="button" class="button button-primary" id="allimages-add-line"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add an image', 'all-images-ai'); ?></button>
                                </th>
                            </tr>
                            <?php if($values && !empty($values['images'])) {
                                $images = json_decode($values['images'], true);
                                foreach($images as $i => $image) { ?>
                                <tr class="allimages-image-row">
                                    <th>
                                        Image #<span class="image-number"><?php echo $i+1; ?></span>
                                        <br>
                                        <a class="button-delete"><?php esc_html_e('Delete', 'all-images-ai'); ?></a>
                                    </th>

                                    <td>
                                        <label for="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-type" class="required-field"><?php esc_html_e('Image type:', 'all-images-ai'); ?></label>
                                        <select id="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-type" class="image-type-field" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][images][<?php echo $i; ?>][type]" required>
                                            <option selected disabled value=""><?php esc_html_e('Select the image type', 'all-images-ai'); ?></option>
                                            <option value="featured" <?php if(isset($image['type']) && $image['type'] == 'featured') echo 'selected'; ?>><?php esc_html_e('Featured image', 'all-images-ai'); ?></option>
                                            <option value="content" <?php if(isset($image['type']) && $image['type'] == 'content') echo 'selected'; ?>><?php esc_html_e('Content image', 'all-images-ai'); ?></option>
                                        </select>

                                        <label for="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-position" class="required-field hide-for-featured"><?php esc_html_e('Image position:', 'all-images-ai'); ?></label>
                                        <select id="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-position" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][images][<?php echo $i; ?>][position]" class="position-field hide-for-featured" required>
                                            <option selected disabled value=""><?php esc_html_e('Select the image position', 'all-images-ai'); ?></option>
                                            <?php foreach($this->positions as $v => $l) { ?>
                                                <option <?php if(isset($image['position']) && $image['position'] == $v) echo 'selected'; ?> value="<?php echo $v; ?>"><?php echo $l; ?></option>
                                            <?php } ?>
                                        </select>

                                        <label for="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-prompt" class="required-field"><?php esc_html_e('Generate image from:', 'all-images-ai'); ?></label>
                                        <select id="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-prompt" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][images][<?php echo $i; ?>][prompt]" class="prompt-field" required>
                                            <option selected disabled value=""><?php esc_html_e('Select the description used for generation', 'all-images-ai'); ?></option>
                                            <option value="post_title" <?php if(isset($image['prompt']) && $image['prompt'] == 'post_title') echo 'selected'; ?>><?php esc_html_e('Post title', 'all-images-ai'); ?></option>
                                            <option value="post_h1" <?php if(isset($image['prompt']) && $image['prompt'] == 'post_h1') echo 'selected'; ?>><?php esc_html_e('Post &lt;h1&gt; title', 'all-images-ai'); ?></option>
                                            <option value="category" <?php if(isset($image['prompt']) && $image['prompt'] == 'category') echo 'selected'; ?>><?php esc_html_e('Post category', 'all-images-ai'); ?></option>
                                            <option value="corresponding_h2" <?php if(isset($image['prompt']) && $image['prompt'] == 'corresponding_h2') echo 'selected'; ?>><?php esc_html_e('Corresponding &lt;h2&gt;', 'all-images-ai'); ?></option>
                                        </select>

                                        <label for="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-picking" class="required-field"><?php esc_html_e('Image choice:', 'all-images-ai'); ?></label>
                                        <select id="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-picking" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][images][<?php echo $i; ?>][picking]" class="picking-field" required>
                                            <option selected disabled value=""><?php esc_html_e('Select the image choice mode', 'all-images-ai'); ?></option>
                                            <option value="auto" <?php if(isset($image['picking']) && $image['picking'] == 'auto') echo 'selected'; ?>><?php esc_html_e('Automatic', 'all-images-ai'); ?></option>
                                            <option value="manual" <?php if(isset($image['picking']) && $image['picking'] == 'manual') echo 'selected'; ?>><?php esc_html_e('Manual', 'all-images-ai'); ?></option>
                                        </select>

                                        <label for="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-size" class="required-field hide-for-featured"><?php esc_html_e('Wordpress media size:', 'all-images-ai'); ?></label>
                                        <select id="allimages-auto-<?php echo esc_attr($post_type->name); ?>-<?php echo $i; ?>-size" name="allimages-auto[<?php echo esc_attr($post_type->name); ?>][images][<?php echo $i; ?>][size]" class="size-field hide-for-featured" required>
                                            <option selected disabled value=""><?php esc_html_e('Select the image size to display', 'all-images-ai'); ?></option>
                                            <option value="full" <?php if(isset($image['size']) && $image['size'] == 'full') echo 'selected'; ?>><?php esc_html_e('Full size', 'all-images-ai'); ?></option>
                                            <option value="large" <?php if(isset($image['size']) && $image['size'] == 'large') echo 'selected'; ?>><?php esc_html_e('Large', 'all-images-ai'); ?></option>
                                            <option value="medium" <?php if(isset($image['size']) && $image['size'] == 'medium') echo 'selected'; ?>><?php esc_html_e('Medium', 'all-images-ai'); ?></option>
                                            <option value="thumbnail" <?php if(isset($image['size']) && $image['size'] == 'thumbnail') echo 'selected'; ?>><?php esc_html_e('Thumbnail', 'all-images-ai'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            <?php }
                            } ?>
                            </tbody>
                        </table>

                    </div>

                <?php } ?>
            </div>


            <br>
            <?php wp_nonce_field('allimages_auto_settings'); ?>
            <input type="submit" class="button button-primary" id="save-settings" value="<?php esc_attr_e('Save settings', 'all-images-ai'); ?>">

        </form>

    </div>
</div>
<script>
    let lineTemplate = '<tr class="allimages-image-row">\n'+
'                                    <th>\n'+
'                                        Image #<span class="image-number">{n}</span>\n'+
'                                        <br>\n'+
'                                        <a class="button-delete"><?php esc_html_e('Delete', 'all-images-ai'); ?></a>\n'+
'                                    </th>\n'+
'                                    <td>\n'+
'                                        <label for="allimages-auto-{p}-{n}-type" class="required-field"><?php esc_html_e('Image type:', 'all-images-ai'); ?></label>\n'+
'                                        <select id="allimages-auto-{p}-{n}-type" class="image-type-field" name="allimages-auto[{p}][images][{n}][type]" required>\n'+
'                                            <option selected disabled value=""><?php esc_html_e('Select the image type', 'all-images-ai'); ?></option>\n'+
'                                            <option value="featured"><?php esc_html_e('Featured image', 'all-images-ai'); ?></option>\n'+
'                                            <option value="content"><?php esc_html_e('Content image', 'all-images-ai'); ?></option>\n'+
'                                        </select>\n'+
'\n'+
'                                        <label for="allimages-auto-{p}-{n}-position" class="required-field hide-for-featured"><?php esc_html_e('Image position:', 'all-images-ai'); ?></label>\n'+
'                                        <select id="allimages-auto-{p}-{n}-position" name="allimages-auto[{p}][images][{n}][position]" class="position-field hide-for-featured" required>\n'+
'                                            <option selected disabled value=""><?php esc_html_e('Select the image position', 'all-images-ai'); ?></option>\n'+
'                                            <?php foreach($this->positions as $v => $l) { ?>\n'+
'                                                <option value="<?php echo esc_attr($v); ?>"><?php echo esc_html($l); ?></option>\n'+
'                                            <?php } ?>\n'+
'                                        </select>\n'+
'\n'+
'                                        <label for="allimages-auto-{p}-{n}-prompt" class="required-field"><?php esc_html_e('Generate image from:', 'all-images-ai'); ?></label>\n'+
'                                        <select id="allimages-auto-{p}-{n}-prompt" name="allimages-auto[{p}][images][{n}][prompt]" class="prompt-field" required>\n'+
'                                            <option selected disabled value=""><?php esc_html_e('Select the description used for generation', 'all-images-ai'); ?></option>\n'+
'                                            <option value="post_title"><?php esc_html_e('Post title', 'all-images-ai'); ?></option>\n'+
'                                            <option value="post_h1"><?php esc_html_e('Post &lt;h1&gt; title', 'all-images-ai'); ?></option>\n'+
'                                            <option value="category"><?php esc_html_e('Post category', 'all-images-ai'); ?></option>\n'+
'                                            <option value="corresponding_h2" class="hide-if-start"><?php esc_html_e('Corresponding &lt;h2&gt;', 'all-images-ai'); ?></option>\n'+
'                                        </select>\n'+
'\n'+
'                                        <label for="allimages-auto-{p}-{n}-picking" class="required-field"><?php esc_html_e('Image choice:', 'all-images-ai'); ?></label>\n'+
'                                        <select id="allimages-auto-{p}-{n}-picking" name="allimages-auto[{p}][images][{n}][picking]" class="picking-field" required>\n'+
'                                            <option selected disabled value=""><?php esc_html_e('Select the image choice mode', 'all-images-ai'); ?></option>\n'+
'                                            <option value="auto"><?php esc_html_e('Automatic', 'all-images-ai'); ?></option>\n'+
'                                            <option value="manual"><?php esc_html_e('Manual', 'all-images-ai'); ?></option>\n'+
'                                        </select>\n'+
'\n'+
'                                        <label for="allimages-auto-{p}-{n}-size" class="required-field hide-for-featured"><?php esc_html_e('Wordpress media size:', 'all-images-ai'); ?></label>\n'+
'                                        <select id="allimages-auto-{p}-{n}-size" name="allimages-auto[{p}][images][{n}][size]" class="size-field hide-for-featured" required>\n'+
'                                            <option selected disabled value=""><?php esc_html_e('Select the image size to display', 'all-images-ai'); ?></option>\n'+
'                                            <option value="full"><?php esc_html_e('Full size', 'all-images-ai'); ?></option>\n'+
'                                            <option value="large"><?php esc_html_e('Large', 'all-images-ai'); ?></option>\n'+
'                                            <option value="medium"><?php esc_html_e('Medium', 'all-images-ai'); ?></option>\n'+
'                                            <option value="thumbnail"><?php esc_html_e('Thumbnail', 'all-images-ai'); ?></option>\n'+
'                                        </select>\n'+
'                                    </td>\n'+
'                                </tr>';
</script>
