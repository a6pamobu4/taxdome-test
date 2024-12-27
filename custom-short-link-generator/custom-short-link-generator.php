<?php
/*
Plugin Name: Сокращатель ссылок
Plugin URI: https://taxdome.com/
Description: Позволяет создавать короткие ссылки и отслеживать данные переходов по ним.
Text Domain: custom-short-link-generator
Version: 1.0
Author: Ilia Antonovich
Author URI: https://taxdome.com/
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/short-links-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/click-logging.php';
require_once plugin_dir_path(__FILE__) . 'includes/short-links-table.php'; 

function cslg_activate() {
    cslg_create_click_logs_table();
    short_links_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'cslg_activate');

function cslg_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'cslg_deactivate');
