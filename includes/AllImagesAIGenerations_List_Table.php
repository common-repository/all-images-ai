<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * List image generations
 *
 * @link       https://codeben.fr
 * @since      1.0.0
 *
 * @package    All_Images_Ai
 * @subpackage All_Images_Ai/includes
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php');
}

class AllImagesAIGenerations_List_Table extends WP_List_Table
{
    private $table_data;
    private $positions;

    public function __construct($args = array())
    {
        parent::__construct($args);

        $this->positions = self::get_positions();
    }

    public static function get_positions()
    {
        return array(
            'start' => __('At the beginning', 'all-images-ai'),
            'first_h2' => __('After the first &lt;h2&gt; title', 'all-images-ai'),
            'last_h2' => __('After the last &lt;h2&gt; title', 'all-images-ai'),
            'all_h2' => __('After every &lt;h2&gt; title', 'all-images-ai'),
            'random_h2' => __('After a random &lt;h2&gt; title', 'all-images-ai'),
        );
    }

    public function get_columns()
    {
        $columns = array(
            'id' => '',
            'cb' => '<input type="checkbox" />',
            'time' => __('Date', 'all-images-ai'),
            'post' => __('Post', 'all-images-ai'),
            'type' => __('Type', 'all-images-ai'),
            'position' => __('Position', 'all-images-ai'),
            'status' => __('Status', 'all-images-ai'),
            'images' => __('Images', 'all-images-ai'),
            'actions' => '',
        );

        return $columns;
    }

    protected function get_sortable_columns()
    {
        $sortable_columns = array(
            'time'  => array('time', false),
            'type'   => array('type', false),
            'status' => array('status', false),
        );
        return $sortable_columns;
    }

    protected function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'all-images-ai'),
        );
    }

    public function prepare_items()
    {
        $this->table_data = $this->get_table_data();

        $columns = $this->get_columns();
        $hidden = array('id');
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable, 'id');

        usort($this->table_data, array(&$this, 'usort_reorder'));

        $per_page = get_user_meta( get_current_user_id(), 'all_images_page_all_images_ai_generations_per_page', true );

        /* pagination */
        $per_page = ( !empty($per_page) ? $per_page : 20 );
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
        ));

        $this->items = $this->table_data;
    }

    private function usort_reorder($a, $b)
    {
        $orderby = (!empty($_GET['orderby'])) ? sanitize_text_field($_GET['orderby']) : 'time';
        $order = (!empty($_GET['order'])) ? sanitize_text_field($_GET['order']) : 'desc';

        if (is_callable(array($this, 'column_'.$orderby))) {
            $valA = $this->{'column_'.$orderby}($a);
            $valB = $this->{'column_'.$orderby}($b);
        } else {
            $valA = $a[$orderby];
            $valB = $b[$orderby];
        }

        $result = strcmp($a[$orderby], $b[$orderby]);

        return ($order === 'asc') ? $result : -$result;
    }

    private function get_table_data()
    {
        global $wpdb;

        $table = $wpdb->prefix.'allimages_generations';

        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY `time` ASC",
            ARRAY_A
        );
    }

    public function single_row( $item ) {
        echo '<tr data-id="'.esc_attr($item['id']).'">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    public function column_id($item)
    {
        return '<span class="generation-id" data-id="'.esc_attr($item['id']).'" data-generation-id="'.esc_attr($item['generation_id']).'"></span>';
    }

    public function column_time($item)
    {
        return date_i18n(esc_html__('Y-m-d H:i:s', 'all-images-ai'), strtotime($item['time']));
    }

    public function column_post($item)
    {
        $post_id = $item['post_id'];
        if (current_user_can('edit_post', $post_id)) {
            $post_link = "<a href='".esc_url(get_edit_post_link($post_id))."'>";
            $post_link .= esc_html(get_the_title($post_id)).'</a>';
        } else {
            $post_link = esc_html(get_the_title($post_id));
        }

        return $post_link;
    }

    public function column_type($item)
    {
        if ($item['type'] == 'featured') {
            return esc_html__('Featured image', 'all-images-ai');
        } else {
            return esc_html__('Content image', 'all-images-ai');
        }
    }

    public function column_position($item)
    {
        if ($item['position'] !== null && strpos($item['position'], 'h2_') > -1) {
            $arr = explode('_', $item['position']);

            return esc_html('H2 #'.$arr[1] + 1);
        } else {
            return ($item['type'] == 'content' && isset($this->positions[$item['position']])) ? esc_html(
                $this->positions[$item['position']]
            ) : esc_html__('Featured image', 'all-images-ai');
        }
    }

    public function column_status($item)
    {
        switch ($item['status']) {
            case 'initializing':
                return '<span class="gen-pending">'.esc_html__('Initializing...', 'all-images-ai').'</span>';
            case 'created':
            case 'print.created':
                return '<span class="gen-pending">'.esc_html__('Created, waiting...', 'all-images-ai').'</span>';
            case 'print.active':
            case 'print.progress':
                return '<span class="gen-pending">'.esc_html__('Generating...', 'all-images-ai').'</span>';
            case 'print.failed':
                return '<span class="gen-failed">'.esc_html__('Failed', 'all-images-ai').'</span>';
            case 'no_credits':
                return '<span class="gen-failed">'.esc_html__(
                        'Failed, no credits left',
                        'all-images-ai'
                    ).'</span>';
            case 'print.completed':
                return '<span class="gen-complete">'.esc_html__('Complete', 'all-images-ai').'</span>';
        }
    }

    public function column_images($item)
    {
        return (!empty($item['images']) ? self::get_gen_table_images_html(
            $item['images'],
            $item['chosen'],
            ($item['status'] === 'print.progress')
        ) : '');
    }

    public static function get_gen_table_images_html($json, $chosen, $in_progress = false)
    {
        $images_html = '';
        $images = json_decode($json);
        foreach ($images as $image) {
            $class = ($image->id == $chosen) ? "pinned-overlay chosen" : "";
            $class .= ($in_progress) ? "in-progress" : "";
            $image->url = str_replace('.aiv1/', '.ai/v1/', $image->url);
            $im_title =  (!empty($image->title)) ? esc_attr($image->title) : '';
            $free = (isset($image->free) && $image->free) ? 1 : 0;

            $images_html .=  '<div class="all-images-image-wrapper small-image">  
                                <div class="image-overlay ' . esc_attr($class) . '">
                                    <a href="#" data-image-id="' . esc_attr($image->id) . '">' . esc_html__('Select this image', 'all-images-ai') . '</a>
                                    <span class="dashicons dashicons-yes"></span>
                                </div>
                                <img loading="lazy" decoding="async" src="' . esc_attr($image->url) . '">
                                <a class="settings open-modal" href="#modal-window-' . esc_attr($image->id) . '"><i class="dashicons dashicons-admin-generic"></i></a>
                                <div id="modal-window-' . esc_attr($image->id) . '" class="ai-modal-window">
                                    <a href="#" class="button-close"><i class="dashicons dashicons-no"></i></a>
                                        <form action="" method="post" class="modal-form" data-image-id="' . esc_attr($image->id) . '">
                                            <div>
                                                <label>
                                                    <span>'.esc_html__('File name:', 'all-images-ai') . '</span>
                                                    <input type="text" name="file_name" value="'.esc_attr($image->id).'">
                                                </label>
                                            </div>
                                            <div>
                                                <label>
                                                    <span>' . esc_html__('Title:', 'all-images-ai') . '</span>
                                                    <input type="text" name="title" value="'.$im_title.'">
                                                </label>
                                            </div>
                                            <div>
                                                <label>
                                                    <span>'.esc_html__('Alt text:', 'all-images-ai') . '</span>
                                                    <input type="text" name="alt_text" value="'.$im_title.'">
                                                </label>
                                            </div>
                                            <input type="hidden" name="id" value="' . esc_attr($image->id) . '">
                                            <input type="hidden" name="url" value="' . esc_attr($image->url) . '">
                                            <input type="hidden" name="free" value="'.esc_attr($free).'">
                                            <br>
                                            <input type="submit" class="button button-primary" value="'.esc_attr__('Select this image', 'all-images-ai').'">
                                            <span class="button button-cancel">'.esc_attr__('Cancel', 'all-images-ai').'</span>
                                        </form>
                                    </div>
                              </div>';
        }
        return $images_html;
    }

    public function column_actions($item)
    {
        $pagenum = $this->get_pagenum();
        return '<a class="button-delete no-margin" href="'.esc_url(
                wp_nonce_url(
                    add_query_arg(array('image-ai' => $item['id'], 'paged' => $pagenum)),
                    'trash-image-ai-'.$item['id']
                )
            ).'">'.esc_html__('Delete', 'all-images-ai').'</a>';
    }

    protected function get_table_classes() {
        $classes = parent::get_table_classes();
        if (($key = array_search('fixed', $classes)) !== false) {
            unset($classes[$key]);
        }
        return $classes;
    }
}
