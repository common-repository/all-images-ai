<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://codeben.fr
 * @since             1.0.0
 * @package           All_Images_Ai
 *
 * @wordpress-plugin
 * Plugin Name:       All-Images.ai
 * Plugin URI:        https://app.all-images.ai
 * Description:       Find or generate the best images for your posts
 * Version:           1.0.4
 * Author:            Weable
 * Author URI:        https://weable.fr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       all-images-ai
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 */
define('ALL_IMAGES_AI_VERSION', '1.0.4');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-all-images-ai-activator.php
 */
function activate_all_images_ai()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-all-images-ai-activator.php';
	All_Images_Ai_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-all-images-ai-deactivator.php
 */
function deactivate_all_images_ai()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-all-images-ai-deactivator.php';
	All_Images_Ai_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_all_images_ai');
register_deactivation_hook(__FILE__, 'deactivate_all_images_ai');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-all-images-ai.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_all_images_ai()
{

	$plugin = new All_Images_Ai();
	$plugin->run();
}
run_all_images_ai();
