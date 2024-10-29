<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

?>
<div id="allimages-page-bulk-form" class="wrap allimages-form">

    <?php if(empty($posts)) { ?>
        <h1><?php esc_html_e('Add images to your posts in bulk', 'all-images-ai') ?></h1>

        <h2><?php esc_html_e('Set your preferences to select your posts', 'all-images-ai'); ?></h2>
    <?php } else { ?>
        <h1><?php esc_html_e('Add images to your selected posts', 'all-images-ai') ?></h1>
    <?php } ?>

    <div>
        <form action="" autocomplete="off" method="post" id="bulk-generation-form">

            <table class="form-table" role="presentation">
                <tbody>

                <?php if(count($posts)) { ?>

                    <h2><?php esc_html_e('Selected posts:', 'all-images-ai') ?></h2>

                    <ul>
                        <?php foreach($posts as $p_id) { ?>
                            <li>
                                <span<?php echo ( !in_array($p_id, $elligible_ids) ? ' class="strikethrough"' : '' ); ?>><?php printf('%d - %s', (int)$p_id, get_the_title((int)$p_id)); ?></span>
                                <?php if( in_array($p_id, $elligible_ids)) { ?>
                                <input type="hidden" name="posts[]" value="<?php echo esc_attr($p_id); ?>" />
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>

                <?php } else { ?>
                    <tr id="bulk-generation-form-filters">
                        <th scope="row"><?php esc_html_e('Filter posts', 'all-images-ai'); ?></th>
                        <td>
                            <label for="allimages_has_featured_image"><?php esc_html_e('Featured image', 'all-images-ai'); ?></label>
                            <select id="allimages_has_featured_image" name="allimages_has_featured_image">
                                <option selected value=""><?php esc_html_e('Select an option', 'all-images-ai'); ?></option>
                                <option value="true"><?php esc_html_e('With', 'all-images-ai'); ?></option>
                                <option value="false"><?php esc_html_e('Without', 'all-images-ai'); ?></option>
                            </select>

                            <label for="allimages_has_content_image"><?php esc_html_e('Image in content', 'all-images-ai'); ?></label>
                            <select id="allimages_has_content_image" name="allimages_has_content_image">
                                <option selected value=""><?php esc_html_e('Select an option', 'all-images-ai'); ?></option>
                                <option value="true"><?php esc_html_e('With', 'all-images-ai'); ?></option>
                                <option value="false"><?php esc_html_e('Without', 'all-images-ai'); ?></option>
                            </select>

                            <label><?php esc_html_e('Post status', 'all-images-ai'); ?></label>
                            <label><input type="checkbox" checked="checked" name="post_status[]" value="publish"> <?php esc_html_e('Publish', 'all-images-ai'); ?></label>
                            <label><input type="checkbox" checked="checked" name="post_status[]" value="future"> <?php esc_html_e('Future', 'all-images-ai'); ?></label>
                            <label><input type="checkbox" checked="checked" name="post_status[]" value="draft"> <?php esc_html_e('Draft', 'all-images-ai'); ?></label>
                            <label><input type="checkbox" checked="checked" name="post_status[]" value="pending"> <?php esc_html_e('Pending', 'all-images-ai'); ?></label>
                            <label><input type="checkbox" checked="checked" name="post_status[]" value="private"> <?php esc_html_e('Private', 'all-images-ai'); ?></label>

                            <br>
                            <label><?php esc_html_e('Categories', 'all-images-ai'); ?></label>
                            <?php
                            $cats = get_categories(array(
                                'hide_empty' => false
                            ));
                            foreach($cats as $c) { ?>
                                <label><input type="checkbox" checked="checked" name="cats[]" value="<?php echo esc_attr($c->term_id); ?>"> <?php echo esc_html($c->name); ?></label>
                            <?php } ?>

                            <br>
                            <label for="allimages_before_date"><?php esc_html_e('Before date:', 'all-images-ai'); ?></label>
                            <input id="allimages_before_date" name="allimages_before_date" type="date" placeholder="<?php esc_attr_e('dd/mm/yyyy', 'all-images-ai'); ?>">

                            <br>
                            <br>
                            <label for="allimages_after_date"><?php esc_html_e('After date:', 'all-images-ai'); ?></label>
                            <input id="allimages_after_date" name="allimages_after_date" type="date" placeholder="<?php esc_attr_e('dd/mm/yyyy', 'all-images-ai'); ?>">

                            <br>
                            <br>
                            <label for="allimages_nb_posts"><?php esc_html_e('Maximum number of posts:', 'all-images-ai'); ?></label>
                            <input id="allimages_nb_posts" name="allimages_nb_posts" type="number" min="0" max="100">

                            <div id="fields-posts-selection"></div>

                            <br><br>

                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <th colspan="2">
                        <a class="button button-primary" id="allimages-add-line"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add an image', 'all-images-ai'); ?></a>
                    </th>
                </tr>
                </tbody>
            </table>


            <?php wp_nonce_field('allimages_bulk_filter'); ?>
            <input type="submit" class="button button-primary" id="launch-generations" value="<?php esc_attr_e('Launch image generation', 'all-images-ai'); ?>">
            <p class="number-of-posts">
                <?php
                if(count($posts)) {
                    echo esc_html($label_count);
                } ?>
            </p>

            <div id="modal-window-bulk-form" class="hidden" style="max-width:800px">
                <p><?php esc_html_e('Please do not close this page while processing...', 'all-images-ai'); ?></p>

                <div class="progressbar"><div class="progress-label"><?php esc_html_e('Processing...', 'all-images-ai'); ?></div></div>

                <div id="bulk-form-result"></div>
            </div>
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
        '                                        <label for="allimages-images-{n}-type" class="required-field"><?php esc_html_e('Image type:', 'all-images-ai'); ?></label>\n'+
        '                                        <select id="allimages-images-{n}-type" class="image-type-field" name="allimages-image[{n}][type]" required>\n'+
        '                                            <option selected disabled value=""><?php esc_html_e('Select the image type', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="featured"><?php esc_html_e('Featured image', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="content"><?php esc_html_e('Content image', 'all-images-ai'); ?></option>\n'+
        '                                        </select>\n'+
        '\n'+
        '                                        <label for="allimages-images-{n}-position" class="required-field hide-for-featured"><?php esc_html_e('Image position:', 'all-images-ai'); ?></label>\n'+
        '                                        <select id="allimages-images-{n}-position" name="allimages-image[{n}][position]" class="position-field hide-for-featured" required>\n'+
        '                                            <option selected disabled value=""><?php esc_html_e('Select the image position', 'all-images-ai'); ?></option>\n'+
        '                                            <?php foreach($this->positions as $v => $l) { ?>\n'+
        '                                                <option value="<?php echo esc_attr($v); ?>"><?php echo esc_html($l); ?></option>\n'+
        '                                            <?php } ?>\n'+
        '                                        </select>\n'+
        '\n'+
        '                                        <label for="allimages-images-{n}-prompt" class="required-field"><?php esc_html_e('Generate image from:', 'all-images-ai'); ?></label>\n'+
        '                                        <select id="allimages-images-{n}-prompt" name="allimages-image[{n}][prompt]" class="prompt-field" required>\n'+
        '                                            <option selected disabled value=""><?php esc_html_e('Select the description used for generation', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="post_title"><?php esc_html_e('Post title', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="post_h1"><?php esc_html_e('Post &lt;h1&gt; title', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="category"><?php esc_html_e('Post category', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="corresponding_h2" class="hide-if-start"><?php esc_html_e('Corresponding &lt;h2&gt;', 'all-images-ai'); ?></option>\n'+
        '                                        </select>\n'+
        '\n'+
        '                                        <label for="allimages-images-{n}-picking" class="required-field"><?php esc_html_e('Image choice:', 'all-images-ai'); ?></label>\n'+
        '                                        <select id="allimages-images-{n}-picking" name="allimages-image[{n}][picking]" class="picking-field" required>\n'+
        '                                            <option selected disabled value=""><?php esc_html_e('Select the image choice mode', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="auto"><?php esc_html_e('Automatic', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="manual"><?php esc_html_e('Manual', 'all-images-ai'); ?></option>\n'+
        '                                        </select>\n'+
        '\n'+
        '                                        <label for="allimages-images-{n}-size" class="required-field hide-for-featured"><?php esc_html_e('Wordpress media size:', 'all-images-ai'); ?></label>\n'+
        '                                        <select id="allimages-images-{n}-size" name="allimages-image[{n}][size]" class="size-field hide-for-featured" required>\n'+
        '                                            <option selected disabled value=""><?php esc_html_e('Select the image size to display', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="full"><?php esc_html_e('Full size', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="large"><?php esc_html_e('Large', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="medium"><?php esc_html_e('Medium', 'all-images-ai'); ?></option>\n'+
        '                                            <option value="thumbnail"><?php esc_html_e('Thumbnail', 'all-images-ai'); ?></option>\n'+
        '                                        </select>\n'+
        '                                    </td>\n'+
        '                                </tr>';
</script>
