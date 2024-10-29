<?php
if (!defined('ABSPATH')) exit;
$path = plugin_dir_path(__DIR__) . 'includes/SmartDOMDocument.php';
include_once($path);
$path = plugin_dir_path(__DIR__) . 'includes/AllImagesAIGenerations_List_Table.php';
include_once($path);

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://codeben.fr
 * @since      1.0.0
 *
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/admin
 * @author     Weable <contact@weable.fr>
 */
class All_Images_Ai_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $wpdb;
    private $tmp_folder;
    private $api_key;
    private $settings;
    private $tablename;
    private $positions;
    private $errors;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        global $wpdb;

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->wpdb = $wpdb;
        $up_dir = wp_get_upload_dir();
        $this->tmp_folder = $up_dir['basedir'] . DIRECTORY_SEPARATOR;
        $this->api_key = get_option('all-images-api-key');
        $this->settings = get_option('all-images-ai-settings');
        $this->tablename = $this->wpdb->prefix . 'allimages_generations';
        $this->positions = AllImagesAIGenerations_List_Table::get_positions();
    }

    public function add_media_tab($tabs)
    {

        $new_tab = array('all-images' => __('All-images', 'all-images-ai'));
        return array_merge($tabs, $new_tab);
    }

    public function add_admin_menus()
    {

        add_menu_page(
            __('All-Images', 'all-images-ai'),
            __('All-Images', 'all-images-ai'),
            'manage_options',
            'all-images-ai',
            array($this, 'include_bulk_partial'),
            plugin_dir_url(__DIR__) . 'admin/images/favicon-all-images.svg',
            10
        );

        add_submenu_page(
            'all-images-ai',
            __('Generate images', 'all-images-ai'),
            __('Generate images', 'all-images-ai'),
            'manage_options',
            'all-images-ai',
            array($this, 'include_bulk_partial')
        );
        add_submenu_page(
            'all-images-ai',
            __('Image generations', 'all-images-ai'),
            __('Image generations', 'all-images-ai'),
            'manage_options',
            'all-images-ai-generations',
            array($this, 'include_generations_partial')
        );
        add_submenu_page(
            'all-images-ai',
            __('Automatic', 'all-images-ai'),
            __('Automatic', 'all-images-ai'),
            'manage_options',
            'all-images-ai-automatic',
            array($this, 'include_automatic_partial')
        );
        add_submenu_page(
            'all-images-ai',
            __('All-images settings', 'all-images-ai'),
            __('Settings', 'all-images-ai'),
            'manage_options',
            'all-images-ai-settings',
            array($this, 'include_settings_partial')
        );

        add_media_page(
            __('All-Images', 'all-images-ai'),
            __('All-Images', 'all-images-ai'),
            'manage_options',
            'all-images-ai-search',
            array($this, 'include_main_partial'),
            10
        );
    }

    public function get_screen_options()
    {
        $screen = get_current_screen();

        if (!is_object($screen) || 'all-images_page_all-images-ai-generations' !== $screen->id) {
            return;
        }

        $option_name = 'per_page';
        add_screen_option($option_name);
    }

    public function set_options($status, $option, $value)
    {
        if (isset($_POST['screenoptionnonce']) && wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['screenoptionnonce'])),
            'screen-options-nonce'
        )) {
            if ('all_images_page_all_images_ai_generations_per_page' === $option) {
                $value = max(0, (int)$value);
            }
        }

        return $value;
    }

    public function register_settings()
    {

        $page = 'all-images-ai';

        register_setting($page, 'all-images-ai-settings');
        register_setting($page, 'all-images-api-key', array($this, 'validate_api_key'));

        add_settings_section(
            'all_images_settings_general',
            __('General settings', 'all-images-ai'),
            '',
            $page
        );

        add_settings_field(
            'all_images_max_width',
            __('Maximum width for uploaded images', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_general',
            array(
                'type'  => 'number',
                'name'  => 'all_images_max_width',
                'placeholder'  => __('eg: 1600', 'all-images-ai'),
                'value' => (empty($this->settings['all_images_max_width']))
                    ? 1344 : $this->settings['all_images_max_width'],
                'hint' => 'in px (default width: 1344px)'
            )
        );
        add_settings_field(
            'all_images_max_height',
            __('Maximum height for uploaded images', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_general',
            array(
                'type'  => 'number',
                'name'  => 'all_images_max_height',
                'placeholder'  => __('eg: 1200', 'all-images-ai'),
                'value' => (empty($this->settings['all_images_max_height']))
                    ? 896 : $this->settings['all_images_max_height'],
                'hint' => 'in px (default height: 896px)'
            )
        );

        add_settings_field(
            'all_images_image_format',
            __('Image format', 'all-images-ai'),
            array($this, 'display_select_format'),
            $page,
            'all_images_settings_general',
            array(
                'name'  => 'all_images_image_format',
                'value' => (empty($this->settings['all_images_image_format']))
                    ? '3:2' : $this->settings['all_images_image_format'],
                'hint' => '(default 3:2)'
            )
        );
        add_settings_field(
            'all_images_use_post_title_as_alt',
            __('Automatically apply the title of the article to the alt attribute of the image', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_general',
            array(
                'type'  => 'checkbox',
                'name'  => 'all_images_use_post_title_as_alt',
                'checked' => (!isset($this->settings['all_images_use_post_title_as_alt']))
                    ? false : true
            )
        );
        add_settings_field(
            'all_images_max_additional_prompt',
            __('Additional prompt, added at the beginning', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_general',
            array(
                'type'  => 'textarea',
                'name'  => 'all_images_max_additional_prompt',
                'placeholder'  => '',
                'value' => (!empty($this->settings['all_images_max_additional_prompt']))
                    ? trim($this->settings['all_images_max_additional_prompt']) : ''
            )
        );

        add_settings_section(
            'all_images_settings_providers',
            __('Providers', 'all-images-ai'),
            '',
            $page
        );

        add_settings_field(
            'all_images_api_key',
            __('All Images API key', 'all-images-ai'),
            array($this, 'display_form_input_api_key'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'text',
                'name'  => 'all-images-api-key', // see display_form_input_api_key()
                'placeholder'  => '',
                'value' => (empty($this->api_key))
                    ? null : $this->api_key,
                'hint' => 'Get your API key <a href="https://app.all-images.ai/en/api-keys" target="_blank">here</a>'
            )
        );

        add_settings_field(
            'all_images_unsplash_key',
            __('Unsplash API key', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'text',
                'name'  => 'all_images_unsplash_key',
                'placeholder'  => '',
                'value' => (empty($this->settings['all_images_unsplash_key']))
                    ? null : $this->settings['all_images_unsplash_key'],
                'hint' => 'Get your API key <a href="https://unsplash.com/developers" target="_blank">here</a>'
            )
        );
        add_settings_field(
            'all_images_unsplash_use',
            __('Use Unsplash in image searches', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'checkbox',
                'name'  => 'all_images_unsplash_use',
                'placeholder'  => __('eg: 1200px', 'all-images-ai'),
                'checked' => (bool)isset($this->settings['all_images_unsplash_use'])
            )
        );

        add_settings_field(
            'all_images_pexels_key',
            __('Pexels API key', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'text',
                'name'  => 'all_images_pexels_key',
                'placeholder'  => '',
                'value' => (empty($this->settings['all_images_pexels_key']))
                    ? null : $this->settings['all_images_pexels_key'],
                'hint' => 'Get your API key <a href="https://www.pexels.com/api/" target="_blank">here</a>'
            )
        );
        add_settings_field(
            'all_images_pexels_use',
            __('Use Pexels in image searches', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'checkbox',
                'name'  => 'all_images_pexels_use',
                'placeholder'  => __('eg: 1200px', 'all-images-ai'),
                'checked' => (bool)isset($this->settings['all_images_pexels_use'])
            )
        );

        add_settings_field(
            'all_images_pixabay_key',
            __('Pixabay API key', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'text',
                'name'  => 'all_images_pixabay_key',
                'placeholder'  => '',
                'value' => (empty($this->settings['all_images_pixabay_key']))
                    ? null : $this->settings['all_images_pixabay_key'],
                'hint' => 'Get your API key <a href="https://pixabay.com/api/docs/" target="_blank">here</a>'
            )
        );
        add_settings_field(
            'all_images_pixabay_use',
            __('Use Pixabay in image searches', 'all-images-ai'),
            array($this, 'display_form_input'),
            $page,
            'all_images_settings_providers',
            array(
                'type'  => 'checkbox',
                'name'  => 'all_images_pixabay_use',
                'placeholder'  => __('eg: 1200px', 'all-images-ai'),
                'checked' => (bool)isset($this->settings['all_images_pixabay_use'])
            )
        );
    }

    public function validate_api_key($value)
    {
        if (!empty($value) && $this->api_key !== $value) {

            $fields = array(
                'url' => get_site_url() . '/?action=allimages_generation_webhook',
                'events' => array('print.created', 'print.active', 'print.failed', 'print.completed'),
            );

            $response = $this->_send_request('POST', '/api-keys/webhook/subscribe', array(
                'body' => json_encode($fields),
                'headers' => array(
                    'api-key' => $value,
                ),
            ));
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!isset($data['webhookId'])) {
                $value = $this->api_key;
                add_settings_error('all-images-ai', 'invalid_api_key', $this->_process_api_error($data));
            }
        }

        return $value;
    }

    public function display_notices()
    {

        if (!empty($this->api_key)) {
            add_action('admin_notices', array($this, 'display_credits'));
        } else {
            add_action('admin_notices', array($this, 'display_admin_notice'));
        }

        add_action('admin_notices', array($this, 'display_transient_msg'));
    }

    public function handle_webhook()
    {

        if (!isset($_GET['action']) || $_GET['action'] !== "allimages_generation_webhook") {
            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $entityBody = file_get_contents('php://input');
        $response = json_decode($entityBody);

        if (empty($response->data->id)) {
            exit;
        }

        $this->wpdb->update(
            $this->tablename,
            array(
                'status' => isset($response->type) ? sanitize_text_field($response->type) : '',
                'time' => !empty($response->created) ? date("Y-m-d H:i:s", $response->created) : '',
                'images' => (isset($response->data->images) && count($response->data->images)) ? json_encode($response->data->images) : null
            ),
            array(
                'generation_id' => sanitize_text_field($response->data->id),
            )
        );
        if (isset($response->type) && $response->type === 'print.completed') {
            $query = $this->wpdb->prepare("SELECT * FROM {$this->tablename} WHERE generation_id = %s", sanitize_text_field($response->data->id));
            $gen = $this->wpdb->get_row($query);
            if ($gen->picking == 'auto' && isset($response->data->images) && is_array($response->data->images) && count($response->data->images)) {

                $index = random_int(0, count($response->data->images) - 1);
                $random_image = $response->data->images[$index];
                if (!$this->_download_and_insert_image(sanitize_text_field($random_image->id), $gen)) {
                    $this->wpdb->update($this->tablename, array(
                        'status' => 'no_credits',
                    ), array(
                        'generation_id' => sanitize_text_field($gen->generation_id)
                    ));
                }
            }
        }

        if ($this->wpdb->rows_affected === 0) {
            wp_send_json_error(
                array('message' => sprintf(__('No line updated for query: %s', 'all-images-ai'), $this->wpdb->last_query))
            );
        } else {
            wp_send_json_success(
                array('message' => sprintf(__('%d line updated', 'all-images-ai'), $this->wpdb->rows_affected))
            );
        }

        exit;
    }

    public function display_admin_notice()
    {

        $class = 'notice notice-error';
        $message = __('You must set an API key for All Images to be able to request custom generated images', 'all-images-ai');
        $link = '<a href="' . esc_url(admin_url('/admin.php?page=all-images-ai-settings')) . '">' . __('Go to settings', 'all-images-ai') . '</a>';

        printf('<div class="%1$s"><p>%2$s %3$s</p></div>', esc_attr($class), esc_html($message), $link);
    }

    public function display_transient_msg()
    {
        if (false === ($message = get_transient('all-images-ai-tmp-msg'))) {
            return;
        }

        delete_transient('all-images-ai-tmp-msg');

        $class = 'notice notice-error';
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function display_credits()
    {
        $current_page = (!empty($_GET['page'])) ? sanitize_text_field($_GET['page']) : '';
        if (empty($this->api_key) || strpos($current_page, 'all-images-ai') !== 0) {
            return;
        }

        $response = $this->_send_request('GET', '/credit');
        $body     = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['credits']) || !is_array($data['credits'])) {
            return;
        }

        $credits = 0;
        foreach ($data['credits'] as $info) {
            if (!empty($info['type']) && in_array($info['type'], ['f_images', 'global'])) {
                if (!empty($info['unlimited'])) {
                    $credits = -1;
                    break;
                }
                $credits += !empty($info['credit']) ? (int)$info['credit'] : 0;
            }
        }

        $class = 'notice notice-info';
        if ($credits === -1) {
            $message = __('Remaining credits: unlimited', 'all-images-ai');
        } else {
            $message = sprintf(__('Remaining credits: %d', 'all-images-ai'), $credits);
        }

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function display_form_input($args)
    {
        $this->display_form_input_name($args, 'all-images-ai-settings[' . $args['name'] . ']');
    }

    public function display_form_input_api_key($args)
    {
        $this->display_form_input_name($args, 'all-images-api-key');
    }

    private function display_form_input_name($args, $name)
    {

        switch ($args['type']) {
            case 'textarea':
?>
                <textarea name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($args['placeholder']); ?>"><?php if ($args['value']) {
                                                                                                                                echo esc_attr($args['value']);
                                                                                                                            } ?></textarea>
                <?php if (isset($args['hint'])) {
                    echo wp_kses_post($args['hint']);
                } ?>
            <?php break;
            case 'text':
            case 'number':
            ?>
                <input type="<?php echo esc_attr($args['type']); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($args['placeholder']); ?>" <?php if ($args['value']) {
                                                                                                                                                                            echo 'value="' . esc_attr($args['value']) . '"';
                                                                                                                                                                        } ?>>
                <?php if (isset($args['hint'])) {
                    echo wp_kses_post($args['hint']);
                } ?>
            <?php break;
            case 'checkbox':
            ?>
                <input type="checkbox" name="<?php echo esc_attr($name); ?>" value="true" <?php if ($args['checked']) echo 'checked="checked"'; ?>>
        <?php break;
            default:
                break;
        }
    }

    public function display_select_format($args)
    {
        $options = [
            '3:2' => '3:2 (1344x896)',
            '1:1' => '1:1 (1024x1024)',
            '4:7' => '4:7 (832x1456)',
            '5:4' => '5:4 (1200x960)',
            '2:3' => '2:3 (896x1344)',
            '7:4' => '7:4 (1456x832)',
            '9:16' => '9:16 (744x1304)',
            '16:9' => '16:9 (1304x744)',
        ]
        ?>
        <select name="<?php echo esc_attr('all-images-ai-settings[' . $args['name'] . ']'); ?>">
            <option value=""><?php esc_html_e('Select an image format', 'all-images-ai'); ?></option>
            <?php foreach ($options as $value => $label) { ?>
                <option value="<?php esc_attr_e($value); ?>" <?php echo ($args['value'] === $value ? ' selected' : ''); ?>><?php echo esc_html($label); ?></option>
            <?php } ?>
        </select>
<?php if (isset($args['hint'])) {
            echo wp_kses_post($args['hint']);
        }
    }

    public function include_main_partial()
    {
        add_thickbox();
        include 'partials/all-images-ai-admin-display-main.php';
    }

    public function include_bulk_partial()
    {
        if (!empty($this->api_key)) {
            $posts = isset($_GET['posts']) ? array_map('intval', $_GET['posts']) : [];
            if (!empty($posts)) {
                $posts_with_generation_ids = $this->get_posts_with_generation($posts);
                $elligible_ids = array_diff($posts, $posts_with_generation_ids);

                $label = sprintf(
                    _n(
                        '%s post selected',
                        '%s posts selected',
                        max(count($elligible_ids), 1),
                        'all-images-ai'
                    ),
                    number_format_i18n(count($elligible_ids))
                );

                $label_count = count($posts_with_generation_ids) > 0 ? sprintf(
                    '%s (%s)',
                    $label,
                    sprintf(
                        _n(
                            '%s post already in progress',
                            '%s posts already in progress',
                            max(count($posts_with_generation_ids), 1),
                            'all-images-ai'
                        ),
                        number_format_i18n(count($posts_with_generation_ids))
                    )
                ) : $label;
            }

            add_action('admin_notices', array($this, 'display_credits'));

            include 'partials/all-images-ai-admin-bulk-form.php';
        } else {
            add_action('admin_notices', array($this, 'display_admin_notice'));
        }
    }

    public function include_generations_partial()
    {
        $table = new AllImagesAIGenerations_List_Table();

        echo '<div class="wrap"><h2>' . esc_html__('Images generations', 'all-images-ai') . '<a href="' . esc_url(admin_url('/admin.php?page=all-images-ai')) . '" class="button back-to-form button-primary">' . esc_html__('Go back to the form', 'all-images-ai') . '</a></h2>';
        echo '<form method="post" id="generations-list">';
        $table->prepare_items();
        $table->display();
        echo '</div></form>';
    }

    public function include_automatic_partial()
    {

        $post_types = get_post_types(array(
            'public' => true
        ), 'objects');
        unset($post_types['attachment']);

        include 'partials/all-images-ai-admin-automatic-form.php';
    }

    public function include_settings_partial()
    {
        include 'partials/all-images-ai-admin-settings.php';
    }

    public function check_generation_status()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax-nonce')) {
            die('Invalid nonce');
        }

        if (empty($_POST['id'])) {
            die('Missing id');
        }

        $id = sanitize_text_field($_POST['id']);

        $query = $this->wpdb->prepare("SELECT * FROM {$this->tablename} WHERE id = %s", $id);
        $gen = $this->wpdb->get_row($query);

        $pending = '<span class="gen-pending">' . esc_html__('Generating...', 'all-images-ai') . '</span>';
        $complete = '<span class="gen-complete">' . esc_html__('Complete', 'all-images-ai') . '</span>';

        if ($gen && $gen->status == 'print.completed') {

            wp_send_json_success(array(
                'ready' => true,
                'in_progress' => false,
                'status' => $complete,
                'images' => $this->_get_gen_table_images_html($gen->images, $gen->chosen)
            ));
        } else if ($gen && $gen->status == 'print.failed') {

            wp_send_json_success(array(
                'ready' => true,
                'in_progress' => false,
                'status' => '<span class="gen-failed">' . esc_html__('Failed', 'all-images-ai') . '</span>',
                'images' => $this->_get_gen_table_images_html($gen->images, $gen->chosen)
            ));
        } else if ($gen && $gen->status == 'print.progress') {

            wp_send_json_success(array(
                'ready' => false,
                'in_progress' => true,
                'status' => $pending,
                'images' => $this->_get_gen_table_images_html($gen->images, $gen->chosen, true)
            ));
        } else if ($gen && $gen->status == 'no_credits' && $gen->picking == 'auto') {

            wp_send_json_success(array(
                'ready' => true,
                'in_progress' => false,
                'status' => '<span class="gen-failed">' . esc_html__('Failed, no credits left', 'all-images-ai') . '</span>',
                'images' => $this->_get_gen_table_images_html($gen->images, $gen->chosen)
            ));
        } else {
            wp_send_json_success(array('ready' => false));
        }
    }

    public function get_main_content()
    {

        if (!check_ajax_referer('ajax-nonce', 'nonce')) {
            die('Invalid nonce');
        }

        ob_start();

        include 'partials/all-images-ai-admin-display-media-popup.php';

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    public function get_image_results()
    {

        if (!check_ajax_referer('ajax-nonce', 'nonce')) {
            die('Invalid nonce');
        }

        $page = (int)$_POST['page'];
        if (empty($page)) {
            die('Invalid page');
        }

        $images = $this->_search_api_images(sanitize_text_field($_POST['search']), $page);
        $allimages_key_set = (!empty($this->api_key));
        $is_post_upload = ((int)$_POST['post_id']);

        ob_start();

        include 'partials/all-images-ai-admin-image-results.php';

        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    public function select_posts_for_generation($params)
    {

        $date_query = array();
        $meta_query = array();

        if (!empty($params['allimages_before_date'])) {
            $before_date = new DateTime($params['allimages_before_date']);
            $date_query['before'] = array(
                'day' => $before_date->format('d'),
                'month' => $before_date->format('m'),
                'year' => $before_date->format('Y')
            );
        }
        if (!empty($params['allimages_after_date'])) {
            $after_date = new DateTime($params['allimages_after_date']);
            $date_query['after'] = array(
                'day' => $after_date->format('d'),
                'month' => $after_date->format('m'),
                'year' => $after_date->format('Y')
            );
        }

        if (isset($params['allimages_has_featured_image']) && $params['allimages_has_featured_image'] === 'true') {
            $meta_query[] = array(
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            );
        } else if (isset($params['allimages_has_featured_image']) && $params['allimages_has_featured_image'] === 'false') {
            $meta_query[] = array(
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS'
            );
        }

        $query = array(
            'post_type' => 'post',
            'post_status' => isset($params['post_status']) ? $params['post_status'] : null,
            'numberposts' => (int)$params['allimages_nb_posts'] ? min((int)$params['allimages_nb_posts'], 100) : -1,
            'date_query' => $date_query,
            'meta_query' => $meta_query
        );

        if (!empty($params['cats'])) {
            $query['category__in'] = $params['cats'];
        }
        $posts = get_posts($query);

        $ids = array();

        if ($posts) {
            foreach ($posts as $i => $post) {
                $content = $post->post_content;
                if (preg_match('/<img [^>]*>/', $content)) {
                    if (isset($params['allimages_has_content_image']) && $params['allimages_has_content_image'] === 'false') {
                        unset($posts[$i]);
                    } else $ids[] = $post->ID;
                } else {
                    if (isset($params['allimages_has_content_image']) && $params['allimages_has_content_image'] === 'true') {
                        unset($posts[$i]);
                    } else $ids[] = $post->ID;
                }
            }
        }

        return array_unique($ids);
    }

    public function get_posts_with_generation($ids)
    {
        $d = implode(
            ',',
            array_fill(0, count($ids), '%d')
        );

        $query = $this->wpdb->prepare(
            <<<SQL
SELECT DISTINCT post_id
FROM {$this->tablename}
WHERE post_id IN ($d)
  AND status NOT IN ('print.failed', 'print.completed');
SQL,
            $ids
        );

        $result = $this->wpdb->get_col($query);

        return is_array($result) ? array_map('intval', $result) : [];
    }

    public function get_selected_posts()
    {

        if (!check_ajax_referer('ajax-nonce', 'nonce')) {
            die('Invalid nonce');
        }

        if (!isset($_POST['fields'])) {
            die('Invalid form data');
        }

        $data = array();
        parse_str($_POST['fields'], $data);

        $params = $this->_sanitize_data($data);

        $ids = $this->select_posts_for_generation($params);
        $progress_ids = $this->get_posts_with_generation($ids);
        $elligible_ids = array_diff($ids, $progress_ids);

        $label = sprintf(
            _n(
                '%s post selected',
                '%s posts selected',
                max(count($elligible_ids), 1),
                'all-images-ai'
            ),
            number_format_i18n(count($elligible_ids))
        );

        wp_send_json_success(array(
            'post_ids' => $elligible_ids,
            'message' => count($progress_ids) > 0 ? sprintf(
                '%s (%s)',
                $label,
                sprintf(
                    _n(
                        '%s post already in progress',
                        '%s posts already in progress',
                        max(count($progress_ids), 1),
                        'all-images-ai'
                    ),
                    number_format_i18n(count($progress_ids))
                )
            ) : $label,
        ));
    }

    public function handle_bulk_filter()
    {

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'allimages_bulk_filter')) {
            return;
        }

        if (!isset($_POST['allimages-image']) || !is_array($_POST['allimages-image'])) {
            die('Invalid form data');
        }

        wp_redirect(admin_url('/admin.php?page=all-images-ai-generations'));
        exit;
    }

    public function launch_generation()
    {

        $this->errors = [];
        if (!check_ajax_referer('ajax-nonce', 'nonce')) {
            $this->errors[] = 'Invalid nonce';
        } else {
            parse_str($_POST['fields'], $params);

            $id = (int)$_POST['post_id'];
            $params = $this->_sanitize_data($params);

            if (empty($id) || empty($params['images'])) {
                $this->errors[] = 'Invalid parameters';
            }
        }

        if (empty($this->errors)) {
            $generation_ids = $this->launch_generations(array($id), $params['images']);

            if (!empty($generation_ids)) {
                return wp_send_json_success(array(
                    'generation_ids' => $generation_ids
                ));
            }
        }

        return wp_send_json_error(array(
            'message' => implode("\n", $this->errors),
        ), 500);
    }

    private function _sanitize_data($data)
    {

        $posts = [];
        if (isset($data['posts']) && is_array($data['posts'])) {
            $posts = array_map('intval', $data['posts']);
        }

        $categories = [];
        if (isset($data['cats']) && is_array($data['cats'])) {
            $categories = array_map('intval', $data['cats']);
        }

        $post_status = [];
        if (isset($data['post_status']) && is_array($data['post_status'])) {
            $post_status = array_map('sanitize_key', $data['post_status']);
        }

        $date_images = [];
        if (isset($data['allimages-image']) && is_array($data['allimages-image'])) {
            $date_images = $data['allimages-image'];
            unset($data['allimages-image']);
        } else if (isset($data['images']) && is_array($data['images'])) {
            $date_images = $data['images'];
            unset($data['images']);
        }

        $images = [];
        foreach ($date_images as $key => $image) {
            $keys = array_keys($image);
            $keys = array_map('sanitize_key', $keys);

            $values = array_values($image);
            $values = array_map('sanitize_text_field', $values);

            $images[sanitize_key($key)] = array_combine($keys, $values);
        }

        unset($data['cats'], $data['post_status']);

        $keys = array_keys($data);
        $keys = array_map('sanitize_key', $keys);

        $values = array_values($data);
        $values = array_map('sanitize_text_field', $values);

        $params = array_combine($keys, $values);
        $params['posts'] = $posts;
        $params['cats'] = $categories;
        $params['post_status'] = $post_status;
        $params['images'] = $images;

        return $params;
    }

    public function handle_auto_settings()
    {

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'allimages_auto_settings')) {
            return;
        }

        $post_types = get_post_types(array(
            'public' => true
        ), 'objects');
        unset($post_types['attachment']);

        $params = [];
        foreach ($post_types as $p_type) {
            if (isset($_POST['allimages-auto'][$p_type->name])) {
                $params[$p_type->name] = $this->_sanitize_data($_POST['allimages-auto'][$p_type->name]);
            }

            if (!isset($params[$p_type->name])) {
                $this->settings['all_images_automatic'][$p_type->name]['active'] = 0;
            }
        }

        if (!empty($params)) {
            foreach ($params as $post_type => $settings) {
                $this->settings['all_images_automatic'][$post_type] = array(
                    'active' => $settings['active'],
                    'allimages_has_featured_image' => (isset($settings['allimages_has_featured_image'])) ? $settings['allimages_has_featured_image'] : '',
                    'allimages_has_content_image' => (isset($settings['allimages_has_content_image'])) ? $settings['allimages_has_content_image'] : '',
                    'post_status' => (isset($settings['post_status'])) ? $settings['post_status'] : array(),
                    'cats' => (isset($settings['cats'])) ? $settings['cats'] : array(),
                    'images' => json_encode(array_values($settings['images']))
                );
            }
        }

        update_option('all-images-ai-settings', $this->settings);
    }

    public function save_post_generation($post_id, $post, $update, $post_before)
    {

        if (isset($this->settings['all_images_automatic'][$post->post_type]) && !empty($this->settings['all_images_automatic'][$post->post_type])) {

            $active = ($this->settings['all_images_automatic'][$post->post_type]['active'] == '1');
            if (isset($this->settings['all_images_automatic'][$post->post_type]) && is_array($this->settings['all_images_automatic'][$post->post_type]['post_status'])) {
                $statuses = $this->settings['all_images_automatic'][$post->post_type]['post_status'];
            } else {
                $statuses = array();
            }
            $cats = (isset($this->settings['all_images_automatic'][$post->post_type]['cats'])) ? $this->settings['all_images_automatic'][$post->post_type]['cats'] : array();
            if ($post->post_type == 'post' && !in_category($cats, $post_id)) {
                $active = false;
            }

            if (has_post_thumbnail($post_id)) {
                if (isset($this->settings['all_images_automatic'][$post->post_type]['allimages_has_featured_image']) && $this->settings['all_images_automatic'][$post->post_type]['allimages_has_featured_image'] === 'false') {
                    return;
                }
            } else {
                if (isset($this->settings['all_images_automatic'][$post->post_type]['allimages_has_featured_image']) && $this->settings['all_images_automatic'][$post->post_type]['allimages_has_featured_image'] === 'true') {
                    return;
                }
            }

            $content = $post->post_content;
            if (preg_match('/<img [^>]*>/', $content)) {
                if (isset($this->settings['all_images_automatic'][$post->post_type]['allimages_has_content_image']) && $this->settings['all_images_automatic'][$post->post_type]['allimages_has_content_image'] === 'false') {
                    return;
                }
            } else {
                if (isset($this->settings['all_images_automatic'][$post->post_type]['allimages_has_content_image']) && $this->settings['all_images_automatic'][$post->post_type]['allimages_has_content_image'] === 'true') {
                    return;
                }
            }

            if (!get_post_meta($post_id, 'allimages_auto_image') && in_array($post->post_status, $statuses) && $active) {

                $images = json_decode($this->settings['all_images_automatic'][$post->post_type]['images'], true);
                $this->launch_generations(array($post_id), $images);
                update_post_meta($post_id, 'allimages_auto_image', true);
            }
        }
    }

    public function launch_generations($ids, $images)
    {
        $generation_ids = [];
        foreach ($ids as $id) {
            $my_post = get_post($id);
            $post_h2_indexes_asked = [];

            foreach ($images as $image) {

                $params = array(
                    'name' => $my_post->ID . ' - ' . $my_post->post_title,
                    'mode' => 'simple',
                    'additionalPrompt' => (!empty($this->settings['all_images_max_additional_prompt']) ? esc_html($this->settings['all_images_max_additional_prompt']) : ''),
                    'optimizePrompt' => true,
                    'params' => array(
                        array(
                            'name' => 'format',
                            'value' => (!empty($this->settings['all_images_image_format']) ? esc_html($this->settings['all_images_image_format']) : '3:2')
                        )
                    ),
                    'prompt' => []
                );

                $params['prompt'][] = esc_html($my_post->post_title);
                switch ($image['prompt']) {
                    case 'post_title':
                        $params['prompt'][] = esc_html($my_post->post_title);
                        break;
                    case 'post_h1':
                        $title = $this->_getTextBetweenTags($my_post->post_content, 'h1');
                        if (!empty($title)) {
                            $params['prompt'][] = esc_html($title);
                        } else {
                            $params['prompt'][] = esc_html($my_post->post_title);
                        }
                        break;
                    case 'category':
                        $categories = get_the_category($my_post->ID);
                        if (isset($categories[0]) && $categories[0]->term_id !== 1) {
                            $params['prompt'][] = esc_html($categories[0]->name);
                        } else {
                            $params['prompt'][] = esc_html($my_post->post_title);
                        }
                        break;
                }

                if (isset($image['position']) && in_array($image['position'], ['first_h2', 'last_h2', 'all_h2', 'random_h2'])) {
                    // CREATE GENERATION FOR H2
                    $doc = new toolsDomDocument\SmartDOMDocument();
                    $doc->loadPartialHTML($my_post->post_content);
                    $h2s = $doc->getElementsByTagName('h2');
                    $random_index = -1;

                    if ($h2s->length === 0) {
                        $this->errors[] = sprintf(__('No h2 found for post id %d', 'all-images-ai'), $my_post->ID);
                        continue;
                    }

                    if ($image['position'] === 'random_h2') {
                        $indexes = array_diff(range(0, $h2s->length - 1), $post_h2_indexes_asked);
                        $random_index = array_rand($indexes);
                    }

                    foreach ($h2s as $i => $h2) {
                        $params_h2 = $params;
                        switch ($image['position']) {
                            case 'first_h2':
                                if ($i > 0) {
                                    break;
                                }
                                break;
                            case 'last_h2':
                                if ($i < $h2s->length - 1) {
                                    continue 2;
                                }
                                break;
                            case 'random_h2':
                                if ($i !== $random_index) {
                                    continue 2;
                                }
                                break;
                        }

                        if (in_array($i, $post_h2_indexes_asked, true)) {
                            $this->errors[] = sprintf(__('h2 index %d already processed for post id %d', 'all-images-ai'), $i, $my_post->ID);
                            continue;
                        }

                        $post_h2_indexes_asked[] = $i;

                        if ($image['prompt'] == 'corresponding_h2') {
                            $params_h2['prompt'][] = $this->_getTextBetweenTags($my_post->post_content, 'h2', $i);
                        }

                        $params_h2['prompt'] = sprintf('###%s###', implode('###', $params_h2['prompt']));

                        $return = $this->wpdb->insert(
                            $this->tablename,
                            array(
                                'time' => date('Y-m-d H:i:s'),
                                'post_id' => $my_post->ID,
                                'type' => $image['type'],
                                'position' => 'h2_' . $i,
                                'picking' => $image['picking'],
                                'size' => isset($image['size']) ? $image['size'] : null,
                                'prompt' => $params_h2['prompt'],
                            )
                        );

                        if ($return === false) {
                            $this->errors[] = sprintf(__('Cannot insert row for post id %d and h2 index %d', 'all-images-ai'), $my_post->ID, $i);
                            continue;
                        }

                        $insert_id = (int) $this->wpdb->insert_id;
                        if ($generation_id = $this->_query_generation($params_h2)) {
                            $this->wpdb->update($this->tablename, array(
                                'generation_id' => sanitize_text_field($generation_id),
                                'status' => 'created',
                            ), array(
                                'id' => $insert_id,
                            ));

                            $generation_ids[] = $generation_id;
                        }
                    }
                } else {

                    $params['prompt'] = sprintf('###%s###', implode('###', $params['prompt']));

                    $return = $this->wpdb->insert(
                        $this->tablename,
                        array(
                            'time' => date('Y-m-d H:i:s'),
                            'post_id' => $my_post->ID,
                            'type' => $image['type'],
                            'position' => isset($image['position']) ? $image['position'] : null,
                            'picking' => $image['picking'],
                            'size' => isset($image['size']) ? $image['size'] : null,
                            'prompt' => $params['prompt'],
                        )
                    );

                    if ($return === false) {
                        $this->errors[] = sprintf(__('Cannot insert row for post id %d', 'all-images-ai'), $my_post->ID);
                        continue;
                    }

                    $insert_id = (int) $this->wpdb->insert_id;
                    if ($generation_id = $this->_query_generation($params)) {
                        $this->wpdb->update($this->tablename, array(
                            'generation_id' => sanitize_text_field($generation_id),
                            'status' => 'created',
                        ), array(
                            'id' => $insert_id,
                        ));

                        $generation_ids[] = $generation_id;
                    }
                }
            }
        }

        return $generation_ids;
    }

    public function select_image_for_library()
    {

        if (!check_ajax_referer('ajax-nonce', 'nonce')) {
            die('Invalid nonce');
        }
        parse_str($_POST['fields'], $params);
        $metadata = array(
            'file_name' => (!empty($params['file_name'])) ? sanitize_file_name($params['file_name']) : null,
            'title' => (!empty($params['title'])) ? sanitize_text_field($params['title']) : null,
            'alt_text' => (!empty($params['alt_text'])) ? sanitize_text_field($params['alt_text']) : null
        );
        if ($params['provider'] == 'allimages' && $params['free'] == 0 && !empty($this->api_key)) {
            $library_image_id = $this->_get_image_buffer_by_id(sanitize_text_field($params['id']), null, $metadata);
        } else {
            $library_image_id = $this->_get_image_by_url(sanitize_text_field($params['id']), sanitize_url($params['url']), null, $metadata);
        }
        if ($library_image_id) {
            return wp_send_json_success(array(
                'attachment_id' => $library_image_id
            ));
        } else {
            return wp_send_json_error(array(
                'message' => 'upload_failed'
            ), 500);
        }
    }

    public function select_generation_image()
    {

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax-nonce')) {
            die('Invalid nonce');
        }

        if (empty($_POST['generation_id'])) {
            die('Missing generation id');
        }

        $generation_id = sanitize_text_field($_POST['generation_id']);

        if (empty($_POST['image_id'])) {
            die('Missing image id');
        }

        $image_id = sanitize_text_field($_POST['image_id']);

        $params = array();
        parse_str($_POST['fields'], $params);
        $metadata = array(
            'file_name' => (!empty($params['file_name'])) ? sanitize_file_name($params['file_name']) : null,
            'title' => (!empty($params['title'])) ? sanitize_text_field($params['title']) : null,
            'alt_text' => (!empty($params['alt_text'])) ? sanitize_text_field($params['alt_text']) : null
        );

        $query = $this->wpdb->prepare("SELECT * FROM {$this->tablename} WHERE generation_id=%s", $generation_id);
        $gen = $this->wpdb->get_row($query);

        if ($gen) {
            $this->_download_and_insert_image($image_id, $gen, $metadata);
        } else {
            wp_send_json_error(array(
                'error' => esc_html__('Generation not found', 'all-images-ai')
            ), 500);
        }
    }

    public function add_bulk_action($bulk_actions)
    {

        $bulk_actions['allimages_generate'] = esc_html__('Generate AI images', 'all-images-ai');
        return $bulk_actions;
    }

    public function delete_image()
    {
        if (isset($_GET['image-ai']) && check_admin_referer('trash-image-ai-' . $_GET['image-ai'])) {
            $this->delete_image_by_id($_GET['image-ai']);
            wp_redirect(add_query_arg('paged', isset($_GET['paged']) ? absint($_GET['paged']) : 1, admin_url('/admin.php?page=all-images-ai-generations')));
            exit;
        }

        $action = $this->current_action();

        if (empty($action)) {
            return;
        }

        $ids = isset($_REQUEST['element']) ? wp_parse_id_list(wp_unslash($_REQUEST['element'])) : array();

        if (empty($ids)) {
            return;
        }

        check_admin_referer('bulk-all-images_page_all-images-ai-generations');

        switch ($action) {
            case 'delete':
                list($count, $failures) = $this->delete_image_by_ids($ids);

                if (!function_exists('add_settings_error')) {
                    require_once ABSPATH . 'wp-admin/includes/template.php';
                }

                if ($failures) {
                    add_settings_error(
                        'all-images-ai',
                        'bulk_action',
                        sprintf(
                            _n(
                                '%d image generation failed to delete.',
                                '%d images generations failed to delete.',
                                $failures,
                                'all-images-ai'
                            ),
                            $failures
                        )
                    );
                }

                if ($count) {
                    add_settings_error(
                        'all-images-ai',
                        'bulk_action',
                        sprintf(
                            _n(
                                '%d image generation deleted successfully.',
                                '%d images generations deleted successfully.',
                                $count,
                                'all-images-ai'
                            ),
                            $count
                        ),
                        'success'
                    );
                }

                break;
        }
    }

    private function delete_image_by_id($id)
    {
        $query = $this->wpdb->prepare("SELECT generation_id FROM {$this->tablename} WHERE id = %d", (int)$id);
        $gen = $this->wpdb->get_row($query);

        if (!empty($gen->generation_id)) {
            $this->_send_request('DELETE', '/image-generations', array(
                'body' => json_encode([
                    'printIds' => [$gen->generation_id]
                ]),
            ));
        }

        return $this->wpdb->delete($this->tablename, array('id' => (int)$id));
    }

    private function delete_image_by_ids($ids)
    {
        $d = implode(
            ',',
            array_fill(0, count($ids), '%d')
        );

        $query = $this->wpdb->prepare(
            <<<SQL
SELECT DISTINCT id, generation_id
FROM {$this->tablename}
WHERE id IN ($d)
  AND generation_id IS NOT NULL;
SQL,
            $ids
        );

        $result = $this->wpdb->get_results($query, ARRAY_A);

        $generation_id_by_id = [];
        foreach ($result as $row) {
            $generation_id_by_id[$row['id']] = $row['generation_id'];
        }

        $count = $failures = 0;
        $generation_to_delete = [];
        foreach ($ids as $id) {
            if ($this->wpdb->delete($this->tablename, array('id' => (int)$id))) {
                $count++;
                if (!empty($generation_id_by_id[$id])) {
                    $generation_to_delete[] = $generation_id_by_id[$id];
                }
            } else {
                $failures++;
            }
        }

        if (!empty($generation_to_delete)) {
            $this->_send_request('DELETE', '/image-generations', array(
                'body' => json_encode([
                    'printIds' => $generation_to_delete
                ]),
            ));
        }

        return [$count, $failures];
    }

    public function current_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action'])) {
            return false;
        }

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action']) {
            return $_REQUEST['action'];
        }

        return false;
    }

    public function add_quick_action($actions)
    {
        global $post;
        $progress_ids = $this->get_posts_with_generation(array($post->ID));

        if (empty($progress_ids)) {
            $link = esc_url(add_query_arg('posts', array($post->ID), admin_url('admin.php?page=all-images-ai')));
            $actions['generate-image'] = "<a href=\"$link\">" . esc_html__('Generate image', 'all-images-ai') . "</a>";
        }

        return $actions;
    }

    public function handle_bulk_action($redirect_url, $action, $post_ids)
    {

        if ($action == 'allimages_generate') {

            $redirect_url = add_query_arg('posts', $post_ids, admin_url('admin.php?page=all-images-ai'));
        }
        return $redirect_url;
    }

    private function _download_and_insert_image($image_id, $gen, $metadata = null)
    {

        $image = $this->_get_image_buffer_by_id($image_id, $gen->post_id, $metadata);
        if ($image) {
            switch ($gen->type) {
                case 'featured':
                    set_post_thumbnail($gen->post_id, $image);
                    break;
                case 'content':
                    $this->_insert_image_in_content($image, $gen, $metadata);
                    break;
            }
            $this->wpdb->update($this->tablename, array(
                'chosen' => $image_id,
            ), array(
                'generation_id' => $gen->generation_id
            ));

            if (wp_doing_ajax()) {
                wp_send_json_success(array(
                    'images' => $this->_get_gen_table_images_html($gen->images, $image_id)
                ));
            }
            return true;
        } else {
            return false;
        }
    }

    private function _get_image_buffer_by_id($id, $post_id = null, $metadata = null)
    {

        if (isset($metadata['file_name'])) {
            $filename = $this->tmp_folder . $metadata['file_name'] . '.jpg';
        } else {
            $filename = $this->tmp_folder . $id . '.jpg';
        }
        $fp = fopen($filename, 'w+');

        $fields = array(
            'id' => $id
        );
        $response = $this->_send_request('POST', '/images/download', array(
            'body' => json_encode($fields),
            'timeout' => '30',
        ));
        $httpcode = (!is_wp_error($response) && !empty($response['response']['code']) ? (int)$response['response']['code'] : 500);

        if ($httpcode === 200) {
            $body = wp_remote_retrieve_body($response);
            fputs($fp, $body);
            $this->_resize_image($filename, 'jpg', $this->settings['all_images_max_width'], $this->settings['all_images_max_height'], false);
            return $this->_upload_to_library($id, 'jpg', $post_id, $metadata);
        } else if ($httpcode === 402) {
            unlink($filename);
            if (wp_doing_ajax()) {
                wp_send_json_error(array(
                    'error' => esc_html__('No more credits on your account', 'all-images-ai'),
                    'action' => 'get_image_buffer_by_id'
                ), 500);
            }
            return false;
        } else {
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $return = $this->_process_api_error(json_decode($body, true));
            } else {
                $return = sprintf(
                    'WP_Error code %s - %s',
                    $response->get_error_code(),
                    $response->get_error_message()
                );
            }

            if (wp_doing_ajax()) {
                wp_send_json_error(array(
                    'error' => $return,
                    'action' => 'get_image_buffer_by_id'
                ), 500);
            } else {
                delete_transient('all-images-ai-tmp-msg');
                set_transient('all-images-ai-tmp-msg', $return, 60 * 2);
            }
            return false;
        }
    }

    private function _get_image_by_url($id, $url, $post_id = null, $metadata = null)
    {
        $response = wp_remote_get($url);
        $body = wp_remote_retrieve_body($response);
        $mime = wp_remote_retrieve_header($response, 'content-type');
        $extension = substr($mime, 6);
        if (isset($metadata['file_name'])) {
            $filename = $this->tmp_folder . $metadata['file_name'] . '.' . $extension;
        } else {
            $filename = $this->tmp_folder . $id . '.' . $extension;
        }
        file_put_contents($filename, $body);

        if (
            isset($this->settings['all_images_max_width'], $this->settings['all_images_max_height'])
            && (int)$this->settings['all_images_max_width'] && (int)$this->settings['all_images_max_height']
        ) {
            $this->_resize_image($filename, $extension, $this->settings['all_images_max_width'], $this->settings['all_images_max_height'], false, $metadata);
        }
        return $this->_upload_to_library($id, $extension, $post_id, $metadata);
    }

    private function _upload_to_library($id, $extension, $post_id = null, $metadata = null)
    {
        try {

            if ($metadata && isset($metadata['file_name'])) {
                $pretty_name = $metadata['file_name'];
                $file_path = $this->tmp_folder . $metadata['file_name'] . '.' . $extension;
            } else {
                $pretty_name = ($post_id) ? sanitize_title(get_the_title($post_id)) : $id;
                $file_path = $this->tmp_folder . $id  . '.' . $extension;
            }
            if ($metadata && isset($metadata['title'])) {
                $pretty_title = $metadata['title'];
            } else {
                $pretty_title = ($post_id) ? get_the_title($post_id) : $id;
            }

            $file = array(
                'name'     => $pretty_name . '.' . $extension, // ex: wp-header-logo.png
                'type'     => 'image/jpeg',
                'tmp_name' => $file_path,
                'error'    => 0,
                'size'     => filesize($file_path),
            );
            $overrides = array(
                'test_form' => false,
                'test_size' => true,
                'test_upload' => false,
            );

            // COPYING FILE TO UPLOAD DIR
            $library_data = wp_handle_sideload($file, $overrides);

            $wp_filetype = wp_check_filetype($library_data['file'], null);
            $attachment = array(
                'guid' => $library_data['url'],
                'post_mime_type' => $wp_filetype['type'],
                'parent_post_id' => $post_id,
                'post_title'     => $pretty_title,
                'post_status'    => 'inherit',
                'post_date'      => date('Y-m-d H:i:s')
            );
            $attachment_id = wp_insert_attachment($attachment, $library_data['file'], $post_id);
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $library_data['file']);

            wp_update_attachment_metadata($attachment_id, $attachment_data);

            if ($post_id && isset($this->settings['all_images_use_post_title_as_alt'])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', get_the_title($post_id));
            } elseif (isset($metadata['alt_text']) && !empty($metadata['alt_text'])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $metadata['alt_text']);
            }

            return $attachment_id;
        } catch (Exception $e) {
            wp_send_json_error(array(
                'error' => $e->getMessage(),
                'action' => 'upload_to_library'
            ), 500);
        }
    }

    private function _search_api_images($search, $page)
    {

        try {
            $options = $this->settings;
            $api_params = array(
                "providers" => [],
                "search" => array(
                    "value" => $search
                ),
                "page" => $page
            );

            $all_images_api_key = (!empty($this->api_key)) ? $this->api_key : null;
            $api_params['providers'][] = array('name' => 'allimages', 'apiKey' => $all_images_api_key);

            $unsplash_api_key = (isset($options['all_images_unsplash_key']) && !empty($options['all_images_unsplash_key'])) ? $options['all_images_unsplash_key'] : null;
            $unsplash_api_use = isset($options['all_images_unsplash_use']);
            if ($unsplash_api_use) $api_params['providers'][] = array('name' => 'unsplash', 'apiKey' => $unsplash_api_key);

            $pexels_api_key = (isset($options['all_images_pexels_key']) && !empty($options['all_images_pexels_key'])) ? $options['all_images_pexels_key'] : null;
            $pexels_api_use = isset($options['all_images_pexels_use']);
            if ($pexels_api_use && !empty($search)) $api_params['providers'][] = array('name' => 'pexels', 'apiKey' => $pexels_api_key);

            $pixabay_api_key = (isset($options['all_images_pixabay_key']) && !empty($options['all_images_pixabay_key'])) ? $options['all_images_pixabay_key'] : null;;
            $pixabay_api_use = isset($options['all_images_pixabay_use']);
            if ($pixabay_api_use) $api_params['providers'][] = array('name' => 'pixabay', 'apiKey' => $pixabay_api_key);

            $response = $this->_send_request('POST', '/plugin-wp/searchProviders', array(
                'body' => json_encode($api_params),
            ), false);
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body);

            if (!isset($json->images)) {
                throw new Exception($this->_process_api_error(json_decode($body, true)));
            }
            $imgArray = $json->images;

            shuffle($imgArray);

            return $imgArray;
        } catch (Exception $e) {
            wp_send_json_error(array(
                'error' => $e->getMessage(),
                'action' => 'search_api'
            ), 500);
        }
    }

    private function _resize_image($file, $extension, $w, $h, $crop = false, $metadata = null)
    {
        try {
            list($width, $height) = getimagesize($file);
            if ($height == 0) {
                throw new Exception(esc_html__('You may have used all your credits', 'all-images-ai'));
            }
            $r = $width / $height;
            if ($crop) {
                if ($width > $height) {
                    $width = ceil($width - ($width * abs($r - $w / $h)));
                } else {
                    $height = ceil($height - ($height * abs($r - $w / $h)));
                }
                $newwidth = $w;
                $newheight = $h;
            } else {
                if ($w / $h > $r) {
                    $newwidth = $h * $r;
                    $newheight = $h;
                } else {
                    $newheight = $w / $r;
                    $newwidth = $w;
                }
            }
            switch ($extension) {
                case 'png':
                    $src = imagecreatefrompng($file);
                    break;
                case 'jpg':
                case 'jpeg':
                default:
                    $src = imagecreatefromjpeg($file);
                    break;
            }
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagejpeg($dst, $file);
            return $dst;
        } catch (Exception $e) {
            wp_send_json_error(array(
                'error' => $e->getMessage(),
                'action' => 'resize_image'
            ), 500);
        }
    }

    private function _query_generation($api_params)
    {

        $response = $this->_send_request('POST', '/image-generations', array(
            'body' => json_encode($api_params),
            'timeout'     => '30',
            'redirection' => '10',
            'httpversion' => '1.1',
            'sslverify'   => false,
        ));
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['id'])) {
            $post_name = !empty($api_params['name']) ? $api_params['name'] : __('Unknown post', 'all-images-ai');
            $message = $this->_process_api_error($data);

            $this->errors[] = sprintf('%s / %s', $post_name, $message);
        }

        return (isset($data['id'])) ? $data['id'] : false;
    }

    private function _getTextBetweenTags($string, $tagname, $index = 0)
    {
        $d = new DOMDocument();
        $d->loadHTML('<?xml encoding="utf-8" ?>' . $string);
        $tags = $d->getElementsByTagName($tagname);
        if (count($tags)) {
            foreach ($tags as $i => $item) {
                if ($index == $i) {
                    return $item->textContent;
                }
            }
        }
        return '';
    }

    private function _get_gen_table_images_html($json, $chosen, $in_progress = false)
    {
        return AllImagesAIGenerations_List_Table::get_gen_table_images_html($json, $chosen, $in_progress);
    }

    private function _insert_image_in_content($image_id, $gen, $metadata = array())
    {

        $img_src = wp_get_attachment_image_src($image_id, $gen->size)[0];
        $my_post = get_post($gen->post_id);
        $img_html = '<img src="' . $img_src . '" data-generation-id="' . $gen->id . '" />';
        if (isset($metadata['alt_text'])) {
            $img_html = '<img src="' . $img_src . '" data-generation-id="' . $gen->id . '" alt="' . $metadata['alt_text'] . '" />';
        }

        $has_image = false;
        $doc = new toolsDomDocument\SmartDOMDocument();
        $doc->loadPartialHTML($my_post->post_content);
        $images = $doc->getElementsByTagName('img');
        foreach ($images as $image) {
            if ($image->getAttribute('data-generation-id') == $gen->id) {
                $has_image = true;
                $image->setAttribute('src', $img_src);
                if (isset($metadata['alt_text'])) {
                    $image->setAttribute('alt', $metadata['alt_text']);
                } else {
                    $image->setAttribute('alt', "");
                }
                break;
            }
        }
        if ($has_image) {
            $content = $doc->saveHTMLExact();
            wp_update_post(array(
                'ID' => $gen->post_id,
                'post_content' => $content
            ));
        } else {
            switch ($gen->position) {
                case 'start':
                    $content = $img_html . $my_post->post_content;
                    break;
                case strpos($gen->position, 'h2_') > -1:
                    $arr = explode('_', $gen->position);
                    $h2 = $doc->getElementsByTagName('h2')->item($arr[1]);
                    $img = $doc->createElement("img", "");
                    $img->setAttribute('src', $img_src);
                    if (isset($metadata['alt_text'])) {
                        $img->setAttribute('alt', $metadata['alt_text']);
                    }
                    $img->setAttribute('class', 'alignnone size-' . $gen->size . ' wp-image-' . $image_id);
                    $img->setAttribute('data-generation-id', $gen->id);
                    $h2->parentNode->insertBefore($img, $h2->nextSibling);
                    $content = $doc->saveHTMLExact();
                    break;
            }
            wp_update_post(array(
                'ID' => $gen->post_id,
                'post_content' => $content
            ));
        }
    }

    private function _send_request($method, $uri, $options = array(), $need_api_key = true)
    {
        $default_headers = array(
            'Content-Type' => 'application/json',
        );
        if ($need_api_key) {
            $default_headers['api-key'] = $this->api_key;
        }

        $options['headers'] = wp_parse_args(
            isset($options['headers']) ? $options['headers'] : array(),
            $default_headers
        );

        $args = wp_parse_args(
            $options,
            array(
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'cookies' => array(),
            )
        );

        $url = 'https://api.all-images.ai/v1' . $uri;
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            if ($method !== 'POST') {
                $args['method'] = $method;
            }
            $response = wp_remote_post($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }

        if (defined('WP_CONTENT_DIR') && defined('WP_DEBUG') && WP_DEBUG) {
            $log_path = WP_CONTENT_DIR . '/all-images-ai-logs/';
            if (!is_dir($log_path)) {
                @mkdir($log_path, 0755);
                @chmod($log_path, 0755);
            }
            if (is_dir($log_path)) {
                $data = '';
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    if (json_decode($body, true) !== null) {
                        $data = $body;
                    }
                } else {
                    $data = sprintf(
                        'WP_Error code %s - %s',
                        $response->get_error_code(),
                        $response->get_error_message()
                    );
                }
                $date = new DateTime();
                $log = sprintf('%s - %s - %s - %s - %s', $date->format('Y-m-d H:i:s'), $method, $url, json_encode($args), $data);
                @file_put_contents($log_path . 'all-images-ai.log', $log . "\n", FILE_APPEND);
            }
        }

        return $response;
    }

    public function api_call_setopt($handle, $parsed_args, $url)
    {
        if ($url === 'https://api.all-images.ai/v1/image-generations') {
            curl_setopt($handle, CURLOPT_ENCODING, '');
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        }
    }

    private function _process_api_error($data)
    {
        $error = !empty($data['error']) ? $data['error'] : __('All images API error', 'all-images-ai');
        $message = __('No message', 'all-images-ai');
        if (!empty($data['message'])) {
            if (is_array($data['message'])) {
                $message = implode(' / ', $data['message']);
            } else {
                $message = $data['message'];
            }
        }

        return sprintf('%s - %s', $error, $message);
    }

    public function enqueue_styles()
    {

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/all-images-ai-admin.css', array('wp-jquery-ui-dialog'), $this->version, 'all');
        wp_enqueue_style('jquery-ui-css', plugin_dir_url(__FILE__) . 'css/jquery-ui.css', array(), '1.13.2', 'all');
    }

    public function enqueue_scripts()
    {

        wp_enqueue_script('jquery-validate', plugin_dir_url(__FILE__) . 'js/jquery.validate.min.js', array('jquery'), '1.20.0', false);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/all-images-ai-admin.js', array('jquery', 'jquery-ui-accordion', 'jquery-ui-dialog', 'jquery-ui-progressbar', 'imagesloaded', 'masonry', 'jquery-validate'), $this->version, false);

        wp_localize_script($this->plugin_name, 'wp_data', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'post_id' => (intval(get_the_ID())) ? get_the_ID() : null,
            'loader_url' => plugin_dir_url(__FILE__) . 'images/loader.gif',
            'nonce' => wp_create_nonce('ajax-nonce'),
            'form_url' => (intval(get_the_ID())) ? add_query_arg(array(
                'posts' => array(get_the_ID()),
                'featured' => true
            ), admin_url('admin.php?page=all-images-ai'))  : null,
            'featured_label' => esc_html__('Generate a featured image', 'all-images-ai'),
            'processing_label' => esc_html__('Processing...', 'all-images-ai'),
            'complete_label' => esc_html__('Complete!', 'all-images-ai'),
            'complete_with_error_label' => esc_html__('Complete with errors', 'all-images-ai'),
            'link_label' => esc_html__('See image generations page', 'all-images-ai'),
            'redirect_url' => admin_url('admin.php?page=all-images-ai-generations'),
            'ajax_error' => esc_html__('An error has occurred, please try again or contact the support', 'all-images-ai')
        ));
    }
}
