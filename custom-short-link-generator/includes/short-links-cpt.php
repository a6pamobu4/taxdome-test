<?php
function register_short_links_cpt() {
    $labels = [
        'name'               => 'Сокращатель ссылок',
        'singular_name'      => 'Короткая ссылка',
        'add_new'            => 'Добавить',
        'add_new_item'       => 'Добавить',
        'edit_item'          => 'Редактировать',
        'new_item'           => 'Новая ссылка',
        'view_item'          => 'Посмотреть',
        'search_items'       => 'Поиск',
        'not_found'          => 'Коротких ссылок не найдено',
        'not_found_in_trash' => 'Коротких ссылок не найдено в корзине',
        'menu_name'          => 'Short Links',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'supports'           => ['title'],
        'rewrite'            => false,
        'has_archive'        => false,
        'capability_type'    => 'post',
        'show_in_rest'       => true,
    ];

    register_post_type('short_link', $args);
}

// Add meta boxes for additional fields
function short_links_meta_boxes() {
    add_meta_box(
        'short_link_meta',
        'Short Link Details',
        'short_link_meta_box_callback',
        'short_link',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'short_links_meta_boxes');

function short_link_meta_box_callback($post) {
    $original_url = get_post_meta($post->ID, '_original_url', true);
    $custom_slug = get_post_meta($post->ID, '_custom_slug', true);

    echo '<label for="original_url">Полный URL длинной ссылки</label>';
    echo '<input type="text" id="original_url" name="original_url" value="' . esc_url($original_url) . '" style="width:100%;">';

    echo '<label for="custom_slug" style="margin-top:10px;display:block;">Короткая ссылка (slug)</label>';
    echo '<input type="text" id="custom_slug" name="custom_slug" value="' . esc_attr($custom_slug) . '" style="width:100%;">';
}

// Save meta fields
function save_short_link_meta($post_id) {
    if (array_key_exists('original_url', $_POST)) {
        update_post_meta($post_id, '_original_url', esc_url_raw($_POST['original_url']));
    }
    if (array_key_exists('custom_slug', $_POST)) {
        update_post_meta($post_id, '_custom_slug', sanitize_title($_POST['custom_slug']));
    }
}
add_action('save_post', 'save_short_link_meta');

/*if (!defined('ABSPATH')) {
    exit;
}

function cslg_register_short_links_cpt() {
    register_post_type('short_link', [
        'labels' => [
            'name'          => 'Short Links',
            'singular_name' => 'Short Link',
        ],
        'public'      => false,
        'show_ui'     => false,
        'supports'    => ['title'],
        'menu_icon'   => 'dashicons-admin-links',
    ]);
}
add_action('init', 'cslg_register_short_links_cpt');*/

