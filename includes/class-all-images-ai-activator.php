<?php
if (!defined('ABSPATH')) exit;
/**
 * Fired during plugin activation
 *
 * @link       https://codeben.fr
 * @since      1.0.0
 *
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/includes
 * @author     Weable <contact@weable.fr>
 */
class All_Images_Ai_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

	    global $wpdb;

	    // SETTINGS
	    $settings = get_option('all-images-ai-settings');
        if ($settings === false) {
            $settings = array();
        }
	    $settings['all_images_unsplash_use'] = true;
	    $settings['all_images_pexels_use'] = true;
	    $settings['all_images_pixabay_use'] = true;
	    update_option('all-images-ai-settings', $settings);

	    // DB
        $table_name = $wpdb->prefix . "allimages_generations";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `generation_id` VARCHAR(50),
                  `time` DATETIME NOT NULL,
                  `post_id` BIGINT(20) UNSIGNED NOT NULL default '0',
                  `type` VARCHAR(15) NOT NULL,
                  `position` VARCHAR(15),
                  `picking` VARCHAR(25),
                  `size` VARCHAR(25),
                  `prompt` VARCHAR(255) NOT NULL,
                  `images` LONGTEXT,
                  `chosen` VARCHAR(100),
                  `status` VARCHAR(25) DEFAULT 'initializing' NOT NULL,
                  PRIMARY KEY (`id`),
                  INDEX `generation_id` (`generation_id`),
                  INDEX `post_id` (`post_id`)
                ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );


    }

}
