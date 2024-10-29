<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap all-images-wrap">

    <div class="title-wrapper">
        <h1><?php esc_html_e('All-images.ai', 'all-images-ai'); ?></h1>
        <a href="<?php echo esc_url(admin_url('/admin.php?page=all-images-ai-settings')); ?>"><?php esc_html_e('Go to the settings page', 'all-images-ai'); ?></a>
    </div>

    <h2><?php esc_html_e('Search images to import in your library', 'all-images-ai'); ?></h2>
    <form id="all-images-form">
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
</div>
