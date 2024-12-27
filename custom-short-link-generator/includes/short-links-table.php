<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class CSLG_Short_Links_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'Short Link',
            'plural'   => 'Short Links',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
        	'cb'           => '<input type="checkbox" />',
            'name'         => 'Название',
            'original_url' => 'Исходный URL',
            'custom_slug'  => 'Короткий адрес',
            'date_created' => 'Дата',
            'clicks'       => 'Переходы',
        ];
    }

    public function get_sortable_columns() {
        return [
            'name'         => ['name', false],
            'date_created' => ['date_created', false],
            'clicks'       => ['clicks', false],
        ];
    }

    public function prepare_items() {
        $this->process_bulk_action();
	    $columns = $this->get_columns();
	    $hidden = [];
	    /*$hidden = ( is_array(get_user_meta( get_current_user_id(), 'agetoplevel_page_supporthost_list_tablecolumnshidden', true)) ) ? get_user_meta( get_current_user_id(), 'managetoplevel_page_supporthost_list_tablecolumnshidden', true) : array();*/
	    $sortable = $this->get_sortable_columns();

	    $this->_column_headers = [$columns, $hidden, $sortable];

	    global $wpdb;
        $table_name = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'date_created';
        $order = !empty($_REQUEST['order']) && $_REQUEST['order'] === 'asc' ? 'ASC' : 'DESC';

	    $data = "
            SELECT p.ID as id, p.post_title AS name, p.post_date AS date_created, 
                (SELECT meta_value FROM $meta_table WHERE post_id = p.ID AND meta_key = '_original_url') AS original_url,
                (SELECT meta_value FROM $meta_table WHERE post_id = p.ID AND meta_key = '_custom_slug') AS custom_slug,
                (SELECT COUNT(*) FROM {$wpdb->prefix}cslg_click_logs WHERE short_slug = 
                    (SELECT meta_value FROM $meta_table WHERE post_id = p.ID AND meta_key = '_custom_slug')) AS clicks
            FROM $table_name p
            WHERE p.post_type = 'short_link'";

        // Обработка поиска
        if (!empty($search)) {
            $data .= $wpdb->prepare(" AND (p.post_title LIKE %s OR 
                EXISTS (
                    SELECT 1 FROM $meta_table 
                    WHERE post_id = p.ID AND meta_key IN ('_original_url', '_custom_slug') AND meta_value LIKE %s
                ))", "%$search%", "%$search%");
        }

        // Apply ordering
        $data .= " ORDER BY $orderby $order";

        $data = $wpdb->get_results($data, ARRAY_A);

	    // Пагинация
	    $per_page = $this->get_items_per_page('elements_per_page', 10);
	    $current_page = $this->get_pagenum();
	    $total_items = count($data);

	    $data = array_slice($data, ($current_page - 1) * $per_page, $per_page);

	    $this->set_pagination_args([
	        'total_items' => $total_items,
	        'per_page'    => $per_page,
	        'total_pages' => ceil($total_items / $per_page),
	    ]);

	    $this->items = $data;
	}


    public function column_default($item, $column_name) {
	    switch ($column_name) {
	    	case 'id':
	        case 'name':
	            return esc_html($item['name']);
	        case 'original_url':
	            return '<a href="' . esc_url($item['original_url']) . '" target="_blank">' . esc_html($item['original_url']) . '</a>';
	        case 'custom_slug':
	            return '<a href="' . esc_url(home_url($item['custom_slug'])) . '" target="_blank">' . esc_url(home_url($item['custom_slug'])) . '</a>';
	        case 'date_created':
	            return esc_html($item['date_created']);
	        case 'clicks':
	            return intval($item['clicks']);
	        default:
	            return print_r($item, true); // Debugging
	    }
	}

	function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    // Редактирование и удаление ссылок
    public function column_name($item) {
        // Link to the click details page
        $details_link = admin_url('tools.php?page=short_link_generator&view=details&short_link_id=' . $item['id']);

        // Link to the edit post page
        $edit_link = admin_url('post.php?post=' . $item['id'] . '&action=edit');

        // Link to delete the short link with nonce
        $delete_link = admin_url('tools.php?page=short_link_generator&action=delete&element=' . $item['id'] . '&_wpnonce=' . wp_create_nonce('delete_short_link'));

        // Format the actions
        $actions = [
            'details' => sprintf('<a href="%s">View Clicks</a>', esc_url($details_link)),
            'edit'    => sprintf('<a href="%s">Edit</a>', esc_url($edit_link)),
            'delete'  => sprintf(
                '<a href="%s" onclick="return confirm(\'Are you sure you want to delete this short link?\')">Delete</a>',
                esc_url($delete_link)
            ),
        ];

        // Make the name clickable and append row actions
        return sprintf(
            '<a href="%s">%s</a> %s',
            esc_url($details_link),
            esc_html($item['name']),
            $this->row_actions($actions)
        );
    }

    function get_bulk_actions() {
        return [
        	'delete' => 'Удалить',
        ];
    }

    public function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['element']) ? $_REQUEST['element'] : [];
            if (!empty($ids) && is_array($ids)) {
                foreach ($ids as $id) {
                    if (current_user_can('delete_post', $id)) {
                        wp_delete_post($id, true);
                    }
                }
            }
        }
    }
}
