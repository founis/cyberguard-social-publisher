<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('cgsp_settings');
wp_clear_scheduled_hook('cgsp_publish_scheduled_post');

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cgsp_logs");
