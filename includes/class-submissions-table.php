<?php
/**
 * Custom WP_List_Table for WA Submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WA_Submissions_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'wa_submission',
            'plural'   => 'wa_submissions',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'row_number'    => 'No',
            'name'          => 'Name',
            'email'         => 'Email',
            'company'       => 'Company',
            'service_group' => 'Service Group',
            'sub_date'      => 'Date',
            'actions'       => '',
        ];
    }

    public function get_sortable_columns() {
        return [
            'name'          => ['name', false],
            'email'         => ['email', false],
            'company'       => ['company', false],
            'service_group' => ['service_group', false],
            'sub_date'      => ['date', true],
        ];
    }

    public function prepare_items() {
        $per_page     = 20;
        $current_page = $this->get_pagenum();

        $args = [
            'post_type'      => 'wa_submission',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Date range filtering
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to   = isset($_GET['date_to'])   ? sanitize_text_field($_GET['date_to'])   : '';

        if ($date_from || $date_to) {
            $date_query = ['inclusive' => true];
            if ($date_from) {
                $date_query['after'] = $date_from;
            }
            if ($date_to) {
                $date_query['before'] = date('Y-m-d', strtotime($date_to . ' +1 day'));
            }
            $args['date_query'] = [$date_query];
        }

        // Search across meta fields
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        if (!empty($search)) {
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => 'name',          'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'email',         'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'company',       'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'message',       'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'number',        'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'service_group', 'value' => $search, 'compare' => 'LIKE'],
                ['key' => 'plugin',        'value' => $search, 'compare' => 'LIKE'],
            ];
        }

        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order   = isset($_GET['order'])   ? sanitize_text_field($_GET['order'])   : 'DESC';

        if ($orderby === 'date') {
            $args['orderby'] = 'date';
        } else {
            $args['meta_key'] = $orderby;
            $args['orderby']  = 'meta_value';
        }
        $args['order'] = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $query = new WP_Query($args);

        $this->items = $query->posts;

        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages,
        ]);

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    }

    public function column_default($item, $column_name) {
        $meta_fields = ['name', 'email', 'company', 'service_group'];
        if (in_array($column_name, $meta_fields)) {
            $value = get_post_meta($item->ID, $column_name, true);
            $detail_url = admin_url('post.php?post=' . $item->ID . '&action=edit');

            if ($column_name === 'name') {
                return '<a href="' . esc_url($detail_url) . '"><strong>' . esc_html($value ?: '—') . '</strong></a>';
            }
            if ($column_name === 'email') {
                return '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            }
            return esc_html($value ?: '—');
        }
        return '';
    }

    public function column_row_number($item) {
        static $counter = 0;
        $page     = $this->get_pagenum();
        $per_page = $this->get_pagination_arg('per_page') ?: 20;
        $counter++;
        return (($page - 1) * $per_page) + $counter;
    }

    public function column_sub_date($item) {
        return get_the_date('d M Y, H:i', $item);
    }

    public function column_actions($item) {
        $detail_url = admin_url('post.php?post=' . $item->ID . '&action=edit');
        return '<a href="' . esc_url($detail_url) . '" class="button button-small">View Detail</a>';
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="submission_ids[]" value="%d" />', $item->ID);
    }

    public function get_bulk_actions() {
        return [
            'delete' => 'Delete',
        ];
    }

    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        $date_from = isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : '';
        $date_to   = isset($_GET['date_to'])   ? esc_attr($_GET['date_to'])   : '';
        $search    = isset($_GET['s'])          ? esc_attr($_GET['s'])         : '';
        ?>
        <div class="alignleft actions wa-date-filters">
            <label for="date_from">From:</label>
            <input type="date" id="date_from" name="date_from" value="<?= $date_from ?>" />
            <label for="date_to">To:</label>
            <input type="date" id="date_to" name="date_to" value="<?= $date_to ?>" />
            <?php submit_button('Filter', 'secondary', 'filter_action', false); ?>
            <?php if ($date_from || $date_to) : ?>
                <a href="<?= esc_url(admin_url('edit.php?post_type=wa_submission&page=wa-submissions')) ?>" class="button">Clear</a>
            <?php endif; ?>
        </div>
        <div class="alignleft actions">
            <a href="<?= esc_url(add_query_arg([
                'action'    => 'wa_export_excel',
                'date_from' => $date_from,
                'date_to'   => $date_to,
                's'         => $search,
            ], admin_url('admin-post.php'))) ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align:middle;margin-right:4px;font-size:16px;line-height:1.4;"></span>
                Export CSV
            </a>
        </div>
        <?php
    }

    public function no_items() {
        echo 'No submissions found.';
    }
}
