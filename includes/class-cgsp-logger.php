<?php
defined('ABSPATH') || exit;

class CGSP_Logger {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'cgsp_logs';
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL,
            platform varchar(30) NOT NULL,
            status varchar(20) NOT NULL,
            message text NOT NULL,
            remote_id varchar(191) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};";
        dbDelta($sql);
    }

    public static function add($platform, $status, $message, $remote_id = '') {
        global $wpdb;
        $wpdb->insert(self::table_name(), array(
            'created_at' => current_time('mysql'),
            'platform' => sanitize_key($platform),
            'status' => sanitize_key($status),
            'message' => wp_strip_all_tags((string) $message),
            'remote_id' => sanitize_text_field((string) $remote_id),
        ), array('%s', '%s', '%s', '%s', '%s'));
    }

    public static function recent($limit = 25) {
        global $wpdb;
        $limit = max(1, min(100, absint($limit)));
        return $wpdb->get_results("SELECT * FROM " . self::table_name() . " ORDER BY id DESC LIMIT {$limit}");
    }
}
