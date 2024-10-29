<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap all-images-wrap">

    <form id="all-images-form">
        <label for="all-image-search-input"><?php esc_html_e('Search for an image for your post:', 'all-images-ai'); ?></label>
        <br>
        <input id="all-image-search-input" type="text" size="50" required placeholder="<?php esc_attr_e('Type your search keywords...', 'all-images-ai'); ?>">
        <button class="button button-primary" type="submit"><?php esc_html_e('Search', 'all-images-ai'); ?></button>
    </form>
    
    <div id="all-images-image-results">
        <div class="all-images-results-grid" id="all-images-results-grid"></div>
    </div>

    <div class="page-load-status">
        <div class="loader-ellips infinite-scroll-request">
            <span class="loader-ellips__dot"></span>
            <span class="loader-ellips__dot"></span>
            <span class="loader-ellips__dot"></span>
            <span class="loader-ellips__dot"></span>
        </div>
    </div>

    <a href="#"><?php esc_html_e('Go to the settings page', 'all-images-ai'); ?></a>
</div>
