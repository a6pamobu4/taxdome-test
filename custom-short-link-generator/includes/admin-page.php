<?php
function cslg_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Сокращатель ссылок',
        'Сокращатель ссылок',
        'manage_options',
        'short_link_generator',
        'cslg_admin_page_init'
    );
}
add_action('admin_menu', 'cslg_admin_menu');

/**
 * Удаление ссылки
 * */
function cslg_handle_delete_action() {
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
            error_log('Redirecting to: ' . admin_url('tools.php?page=short_link_generator&message=deleted'));
            wp_redirect(admin_url('tools.php?page=short_link_generator&message=deleted'));
            exit;
        } else {
            wp_die(__('Не удалось удалить короткую ссылку.'));
        }
    }
}
add_action('admin_init', 'cslg_handle_delete_action');

/**
 * Просмотр деталей ссылки с данными всех переходов
 * */
function cslg_display_click_details($short_link_id) {
    global $wpdb;

    $short_link = get_post($short_link_id);
    if (!$short_link || $short_link->post_type !== 'short_link') {
        echo '<div class="error"><p>Такой ссылки не существует.</p></div>';
        return;
    }

    $table_name = $wpdb->prefix . 'cslg_click_logs';
    $clicks = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name WHERE post_id = %d ORDER BY click_time DESC
    ", $short_link_id));

    ?>
    <div class="wrap">
        <h1>Детали переходов для ссылки: <?php echo esc_html($short_link->post_title); ?></h1>
        <p><strong>Исходный URL:</strong> <a href="<?php echo esc_url(get_post_meta($short_link_id, '_original_url', true)); ?>" target="_blank"><?php echo esc_url(get_post_meta($short_link_id, '_original_url', true)); ?></a></p>
        <p><strong>Короткая ссылка:</strong> <a href="<?php echo esc_url(home_url(get_post_meta($short_link_id, '_custom_slug', true))); ?>" target="_blank"><?php echo esc_url(home_url(get_post_meta($short_link_id, '_custom_slug', true))); ?></a></p>

        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>IP</th>
                    <th>Referrer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clicks)): ?>
                    <?php foreach ($clicks as $click): ?>
                        <tr>
                            <td><?php echo esc_html($click->click_time); ?></td>
                            <td><?php echo esc_html($click->ip_address); ?></td>
                            <td><?php echo esc_html($click->referrer ?: 'Прямой переход'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Пока нет переходов.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p><a href="<?php echo esc_url(admin_url('tools.php?page=short_link_generator')); ?>" class="button">Назад к коротким ссылкам</a></p>
    </div>
    <?php
}

/**
 * Создание главной страницы с созданием ссылок и просмотром существующих 
 * */
function cslg_admin_page_init() {    

    if (isset($_GET['view']) && $_GET['view'] === 'details' && !empty($_GET['short_link_id'])) {
        cslg_display_click_details(intval($_GET['short_link_id']));
        return;
    }

    if (isset($_REQUEST['message']) && $_REQUEST['message'] === 'deleted') {
        echo '<div class="updated"><p>Короткая ссылка успешно удалена.</p></div>';
    }
    
    if (isset($_POST['cslg_submit'])) {
        $name = sanitize_text_field($_POST['name']);
        $original_url = esc_url_raw($_POST['original_url']);
        $custom_slug = sanitize_title($_POST['custom_slug']);

        // Генерируем короткую ссылку, если поле оставили пустым
        if (empty($custom_slug)) {
            $custom_slug = cslg_generate_default_slug();
        }

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
                    <td><input type="text" id="custom_slug" name="custom_slug" value="<?php echo cslg_generate_default_slug(); ?>"></td>
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

/**
 * Генерация дефолтной короткой ссылки, 6 цифробукв
 * */
function cslg_generate_default_slug() {
    global $wpdb;

    do {
        $slug = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);

        // Проверка на дубли
        $exists = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->prefix}postmeta
            WHERE meta_key = '_custom_slug' AND meta_value = %s
        ", $slug));
    } while ($exists);

    return $slug;
}
