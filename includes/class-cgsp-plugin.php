<?php
defined('ABSPATH') || exit;

require_once CGSP_DIR . 'includes/class-cgsp-logger.php';
require_once CGSP_DIR . 'includes/class-cgsp-social-api.php';
require_once CGSP_DIR . 'includes/class-cgsp-admin.php';
require_once CGSP_DIR . 'includes/class-cgsp-dashboard.php';

final class CGSP_Plugin {
    private static $instance;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('cgsp_publish_scheduled_post', array($this, 'publish_scheduled'), 10, 1);
        new CGSP_Dashboard();
        if (is_admin()) {
            new CGSP_Admin();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('cyberguard-social-publisher', false, dirname(plugin_basename(CGSP_FILE)) . '/languages');
    }

    public static function activate() {
        CGSP_Logger::create_table();
        add_option('cgsp_settings', array(
            'graph_version' => 'v23.0',
            'page_id' => '',
            'instagram_id' => '',
            'access_token' => '',
        ));
        CGSP_Dashboard::activate();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook('cgsp_publish_scheduled_post');
        CGSP_Dashboard::deactivate();
    }

    public function publish_scheduled($payload) {
        $api = new CGSP_Social_API();
        $api->publish($payload);
    }
}
