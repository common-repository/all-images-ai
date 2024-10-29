<?php
    if (!defined('ABSPATH')) exit;
    function all_images_get_provider_name($p) {
        $names = array(
            'allimages' => 'All-Images.ai',
            'unsplash' => 'Unsplash',
            'pexels' => 'Pexels',
            'pixabay' => 'Pixabay',
        );
        return $names[$p];
    }


    if(count($images) == 0) { ?>
        <p><?php esc_html_e('No image matched your search criteria', 'all-images-ai'); ?></p>
    <?php }

    foreach($images as $image) {
        $classes = [];
        $classes[] = 'provider-'.$image->provider;
        $classes[] = ($image->free) ? 'copyright-free' : 'copyright-pay';
        $locked = (!$allimages_key_set && $image->provider == 'allimages' && !$image->free);
        $classes[] = ($locked) ? 'blurred' : '';
        ?>
        <div class="all-images-image-wrapper big <?php echo implode(' ',$classes); ?>">
            <div class="image-overlay" data-image-id="<?php echo esc_attr($image->id); ?>">
                <?php if($locked) { ?>
                    <span class="dashicons dashicons-lock"></span>
                <?php } else { ?>
                    <a class="trigger-download">
                        <?php if($is_post_upload) {
                            esc_html_e('Select this image', 'all-images-ai');
                        } else {
                            esc_html_e('Upload to library', 'all-images-ai');
                        }?>
                    </a>
                <?php } ?>
            </div>
            <img src="<?php echo esc_attr($image->preview); ?>">
            <span class="pill"><?php echo esc_html(all_images_get_provider_name($image->provider)); ?></span>
            <span class="copyright"><?php echo ($image->free) ? 'Free' : '$'; ?></span>
            <a class="settings <?php echo !$locked ? esc_attr('open-modal') : esc_attr('not-allowed'); ?>" href="<?php echo ( !$locked ? esc_attr('#modal-window-'.$image->id) : '#' ); ?>"><i class="dashicons dashicons-admin-generic"></i></a>
            <div id="modal-window-<?php echo esc_attr($image->id); ?>" class="ai-modal-window">
                <a href="#" class="button-close"><i class="dashicons dashicons-no"></i></a>
                <form action="" method="post" class="modal-form" data-image-id="<?php echo esc_attr($image->id); ?>">
                    <div>
                        <label>
                            <span><?php esc_html_e('File name:', 'all-images-ai'); ?></span>
                            <input type="text" name="file_name" value="<?php echo esc_attr($image->id); ?>">
                        </label>
                    </div>
                    <div>
                        <label>
                            <span><?php esc_html_e('Title:', 'all-images-ai'); ?></span>
                            <input type="text" name="title" value="<?php echo (!empty($image->title)) ? esc_attr($image->title) : ''; ?>">
                        </label>
                    </div>
                    <div>
                        <label>
                            <span><?php esc_html_e('Alt text:', 'all-images-ai'); ?></span>
                            <input type="text" name="alt_text" value="<?php echo esc_attr($image->title); ?>">
                        </label>
                    </div>
                        <input type="hidden" name="id" value="<?php echo esc_attr($image->id); ?>">
                    <input type="hidden" name="url" value="<?php echo esc_attr($image->url); ?>">
                    <input type="hidden" name="provider" value="<?php echo esc_attr($image->provider); ?>">
                    <input type="hidden" name="free" value="<?php echo ($image->free) ? 1 : 0; ?>">
                    <input type="hidden" name="post_id" value="<?php echo (intval(get_the_ID())) ? get_the_ID() : ""; ?>">
                    <input type="hidden" name="action" value="select_image_for_library">
                    <br>
                    <input type="submit" class="button button-primary" value="<?php ($is_post_upload) ? esc_attr_e('Select this image', 'all-images-ai') : esc_attr_e('Upload to library', 'all-images-ai'); ?>">
                    <span class="button button-cancel"><?php esc_html_e('Cancel', 'all-images-ai'); ?></span>
                </form>
            </div>
        </div>
    <?php } ?>
