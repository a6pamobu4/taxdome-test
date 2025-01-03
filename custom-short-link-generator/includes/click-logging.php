<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rewrite rules для переходов 
 * */
function short_links_rewrite_rules() {
    global $wpdb;
    $slugs = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '_custom_slug'");
    foreach ($slugs as $slug) {
        add_rewrite_rule('^' . preg_quote($slug) . '/?$', 'index.php?short_link_redirect=' . $slug, 'top');
    }
}
add_action('init', 'short_links_rewrite_rules');


function short_links_query_vars($vars) {
    $vars[] = 'short_link_redirect';
    return $vars;
}
add_filter('query_vars', 'short_links_query_vars');

/**
 * Обработка переходов
 * */
function short_links_template_redirect() {
    $slug = get_query_var('short_link_redirect');

    if ($slug) {
        $args = [
            'post_type'  => 'short_link',
            'meta_key'   => '_custom_slug',
            'meta_value' => $slug,
        ];
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $original_url = get_post_meta(get_the_ID(), '_original_url', true);
            cslg_log_click($post_id);
            wp_redirect(esc_url_raw($original_url), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'short_links_template_redirect');

/**
 * Создание таблицы БД для переходов
 * */
function cslg_create_click_logs_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cslg_click_logs';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) NOT NULL,
        click_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        referrer TEXT DEFAULT NULL,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'cslg_create_click_logs_table');

/**
 * Сохранение данных перехода
 * */
function cslg_log_click($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cslg_click_logs';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;

    $wpdb->insert($table_name, [
        'post_id'     => $post_id,
        'ip_address'  => $ip_address,
        'referrer'    => $referrer,
    ]);
}

