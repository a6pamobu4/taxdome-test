<?php
/*
Plugin Name: Custom Short Link Generator
Plugin URI: https://taxdome.com/
Description: Allows users to generate short links with custom slugs, track clicks, and manage (edit/delete) links.
Text Domain: custom-short-link-generator
Version: 0.2.1
Author: Ilia Antonovich
Author URI: https://taxdome.com/
License: GPL2
*/

// Security check to ensure the file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/short-links-cpt.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/click-logging.php';
require_once plugin_dir_path(__FILE__) . 'includes/short-links-table.php'; 

// Initialize the plugin
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
