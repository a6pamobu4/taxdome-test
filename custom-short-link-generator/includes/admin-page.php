<?php
function cslg_admin_menu() {
    add_menu_page(
        'Сокращатель ссылок',
        'Сокращатель ссылок',
        'manage_options',
        'short_link_generator',
        'cslg_admin_page_init'
    );
}
add_action('admin_menu', 'cslg_admin_menu');

/**
 * С добавлением screen options
 * не сохраняются настройки столбцов, количество постов работает - доделать
 * */

/*function cslg_admin_menu() {
 
    global $cslg_admin_page;

    $cslg_admin_page = add_menu_page(
        'Short Link Generator', 
        'Short Links', 
        'manage_options', 
        'short_link_generator', 
        'cslg_admin_page_init'
    );
 
    add_action("load-$cslg_admin_page", "cslg_admin_page_screen_options");
}
add_action('admin_menu', 'cslg_admin_menu');

function cslg_admin_page_screen_options() {
 
    global $cslg_admin_page;
    global $short_links_table;
 
    $screen = get_current_screen();
 
    // get out of here if we are not on our settings page
    if(!is_object($screen) || $screen->id != $cslg_admin_page)
        return;
 
    $args = array(
        'label' => 'Показывать элементов',
        'default' => 10,
        'option' => 'elements_per_page'
    );
    add_screen_option( 'per_page', $args );

    $short_links_table = new CSLG_Short_Links_Table();
}

add_filter('set-screen-option', 'test_table_set_option', 10, 3);
function test_table_set_option($status, $option, $value) {
  return $value;
}*/

function cslg_handle_delete_action() {
    // Удаление ссылки
    if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete' && !empty($_REQUEST['element'])) {
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'delete_short_link')) {
            wp_die(__('Security check failed.'));
        }

        $element_id = intval($_REQUEST['element']);

        // Проверка прав пользователя
        if (!current_user_can('delete_post', $element_id)) {
            wp_die(__('У Вас нет прав на удаление.'));
        }

        // Удаление короткой ссылки
        if (wp_delete_post($element_id, true)) {
            flush_rewrite_rules();
            error_log('Redirecting to: ' . admin_url('admin.php?page=short_link_generator&message=deleted'));
            wp_redirect(admin_url('admin.php?page=short_link_generator&message=deleted'));
            exit;
        } else {
            wp_die(__('Не удалось удалить короткую ссылку.'));
        }
    }
}
add_action('admin_init', 'cslg_handle_delete_action');


function cslg_admin_page_init() {    

    if (isset($_REQUEST['message']) && $_REQUEST['message'] === 'deleted') {
        echo '<div class="updated"><p>Короткая ссылка успешно удалена.</p></div>';
    }

    // Форма создания новой ссылки
    if (isset($_POST['cslg_submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $original_url = esc_url_raw($_POST['original_url']);
        $custom_slug = sanitize_title($_POST['custom_slug']);

        if (!empty($name) && !empty($original_url) && !empty($custom_slug)) {
            $post_id = wp_insert_post([
                'post_title'  => $name,
                'post_type'   => 'short_link',
                'post_status' => 'publish',
            ]);

            if ($post_id) {
                update_post_meta($post_id, '_original_url', $original_url);
                update_post_meta($post_id, '_custom_slug', $custom_slug);
                echo '<div class="updated"><p>Короткая ссылка успешно создана!</p></div>';
            } else {
                echo '<div class="error"><p>Не удалось создать короткую сссылку. Попробуйте еще раз.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Все поля обязательны к заполнению</p></div>';
        }
    }

    // Отображение таблицы ссылок
    ?>
    <div class="wrap">
        <h1>Сокращатель ссылок</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="name">Название</label></th>
                    <td><input type="text" id="name" name="name" required></td>
                </tr>
                <tr>
                    <th><label for="original_url">Полный URL длинной ссылки</label></th>
                    <td><input type="url" id="original_url" name="original_url" required></td>
                </tr>
                <tr>
                    <th><label for="custom_slug">Адрес короткой ссылки</label></th>
                    <td><input type="text" id="custom_slug" name="custom_slug" required></td>
                </tr>
            </table>
            <?php submit_button('Сократить ссылку', 'primary', 'cslg_submit'); ?>
        </form>

        <form method="get">
            <input type="hidden" name="page" value="short_link_generator">
            <?php
            $short_links_table = new CSLG_Short_Links_Table();
            $short_links_table->prepare_items();
            $short_links_table->search_box('Найти ссылки', 'search_id');
            $short_links_table->display();
            ?>
        </form>
    </div>
    <?php
}
