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
            'original_url' => 'Original URL',
            'custom_slug'  => 'Короткий адрес',
            'date_created' => 'Date Created',
            'clicks'       => 'Clicks',
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

        // Apply search filter
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

	    // Handle pagination
	    $per_page = $this->get_items_per_page('elements_per_page', 10);
	    $current_page = $this->get_pagenum();
	    $total_items = count($data);

	    // Slice data for pagination
	    $data = array_slice($data, ($current_page - 1) * $per_page, $per_page);

	    // Set pagination arguments
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
	            return '<a href="' . esc_url(home_url($item['custom_slug'])) . '" target="_blank">' . esc_html($item['custom_slug']) . '</a>';
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

    // Adding action links to column
    /*function column_name($item)
    {
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&element=%s">' . 'Редактировать' . '</a>', $_REQUEST['page'], 'edit', $item['id']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&element=%s">' . 'Удалить' . '</a>', $_REQUEST['page'], 'delete', $item['id']),
        );

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
    }*/

    function column_name($item) {
        $actions = [
            'edit'   => sprintf('<a href="?page=short_link_generator&action=edit&element=%s">Edit</a>', $item['id']),
            'delete' => sprintf('<a href="?page=short_link_generator&action=delete&element=%s" onclick="return confirm(\'Уверены, что хотите удалить эту ссылку?\')">Delete</a>', $item['id']),
        ];

        return sprintf('%1$s %2$s', $item['name'], $this->row_actions($actions));
    }

    function get_bulk_actions() {
        return [
        	'delete'        => 'Удалить',
        ];
    }

    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['element']) ? $_REQUEST['element'] : [];
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    wp_delete_post($id, true);
                }
            } else {
                wp_delete_post($ids, true);
            }
        }
    }
}
