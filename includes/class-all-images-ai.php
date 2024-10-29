<?php
if (!defined('ABSPATH')) exit;
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://codeben.fr
 * @since      1.0.0
 *
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/includes
 * @author     Weable <contact@weable.fr>
 */
class All_Images_Ai
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      All_Images_Ai_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('ALL_IMAGES_AI_VERSION')) {
			$this->version = ALL_IMAGES_AI_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'all-images-ai';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - All_Images_Ai_Loader. Orchestrates the hooks of the plugin.
	 * - All_Images_Ai_i18n. Defines internationalization functionality.
	 * - All_Images_Ai_Admin. Defines all hooks for the admin area.
	 * - All_Images_Ai_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-all-images-ai-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-all-images-ai-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-all-images-ai-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		//require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-all-images-ai-public.php';

		$this->loader = new All_Images_Ai_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the All_Images_Ai_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new All_Images_Ai_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new All_Images_Ai_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menus');
		$this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
		$this->loader->add_action('load-all-images_page_all-images-ai-generations', $plugin_admin, 'get_screen_options');
		$this->loader->add_filter('set-screen-option', $plugin_admin, 'set_options', 10, 3);

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		// WEBHOOK
        $this->loader->add_action('init', $plugin_admin, 'display_notices');
        $this->loader->add_action('init', $plugin_admin, 'handle_webhook');
		// BULK
        $this->loader->add_action('init', $plugin_admin, 'handle_bulk_filter');
        // AUTOMATIC
        $this->loader->add_action('init', $plugin_admin, 'handle_auto_settings');
        $this->loader->add_action('wp_after_insert_post', $plugin_admin, 'save_post_generation', 10, 4);
        // API CALL
        $this->loader->add_action('http_api_curl', $plugin_admin, 'api_call_setopt', 10, 3);

        // BULK ACTION
        $this->loader->add_filter('bulk_actions-edit-post', $plugin_admin, 'add_bulk_action');
        $this->loader->add_filter('handle_bulk_actions-edit-post', $plugin_admin, 'handle_bulk_action', 10, 3);
        // QUICK ACTION
        $this->loader->add_action('init', $plugin_admin, 'delete_image');
        $this->loader->add_filter('post_row_actions', $plugin_admin, 'add_quick_action');

		// ---- AJAX ----
		// POPUP CONTENT
		$this->loader->add_action('wp_ajax_get_main_content', $plugin_admin, 'get_main_content');
		// IMAGE RESULTS AJAX
		$this->loader->add_action('wp_ajax_get_image_results', $plugin_admin, 'get_image_results');
		// SELECT POST IMAGE
		$this->loader->add_action('wp_ajax_select_image_for_post', $plugin_admin, 'select_image_for_post');
		// SELECT IMAGE FOR LIBRARY
        $this->loader->add_action('wp_ajax_select_image_for_library', $plugin_admin, 'select_image_for_library');
        // CHECK NUMBER OF POSTS
        $this->loader->add_action('wp_ajax_get_selected_posts', $plugin_admin, 'get_selected_posts');
        // LAUNCH GENERATION
        $this->loader->add_action('wp_ajax_launch_generation', $plugin_admin, 'launch_generation');
        // CHECK GENERATION
        $this->loader->add_action('wp_ajax_check_generation', $plugin_admin, 'check_generation_status');
        // SELECT GENERATION IMAGE
        $this->loader->add_action('wp_ajax_select_generation_image', $plugin_admin, 'select_generation_image');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    All_Images_Ai_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
