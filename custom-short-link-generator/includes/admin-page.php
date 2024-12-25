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

function cslg_admin_page_init() {
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

    // Display the form and the list table
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

        <form method="post">
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