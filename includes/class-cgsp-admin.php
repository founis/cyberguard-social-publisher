<?php
defined('ABSPATH') || exit;

class CGSP_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_post_cgsp_save_settings', array($this, 'save_settings'));
        add_action('admin_post_cgsp_publish', array($this, 'handle_publish'));
        add_action('admin_post_cgsp_test_connection', array($this, 'test_connection'));
    }

    public function menu() {
        add_menu_page('CyberGuard Publisher', 'Social Publisher', 'manage_options', 'cgsp-publisher', array($this, 'render_publisher'), 'dashicons-share', 58);
        add_submenu_page('cgsp-publisher', 'הגדרות', 'הגדרות', 'manage_options', 'cgsp-settings', array($this, 'render_settings'));
    }

    public function assets($hook) {
        if (false === strpos($hook, 'cgsp')) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_style('cgsp-admin', CGSP_URL . 'assets/admin.css', array(), CGSP_VERSION);
        wp_enqueue_script('cgsp-admin', CGSP_URL . 'assets/admin.js', array('jquery'), CGSP_VERSION, true);
    }

    public function render_publisher() {
        $this->guard();
        $notice = isset($_GET['cgsp_notice']) ? sanitize_key(wp_unslash($_GET['cgsp_notice'])) : '';
        $logs = CGSP_Logger::recent();
        $content_library = $this->load_content_library();
        include CGSP_DIR . 'admin/publisher-page.php';
    }

    private function load_content_library() {
        $path = CGSP_DIR . 'content/cyberguard-posts.json';
        if (!is_readable($path)) {
            return array();
        }
        $json = file_get_contents($path);
        $posts = json_decode($json, true);
        if (!is_array($posts)) {
            return array();
        }
        return array_values(array_filter($posts, function ($post) {
            return is_array($post) && !empty($post['message']) && (!isset($post['status']) || 'ready' === $post['status']);
        }));
    }

    public function render_settings() {
        $this->guard();
        $settings = wp_parse_args(get_option('cgsp_settings', array()), array('graph_version' => 'v23.0', 'page_id' => '', 'instagram_id' => '', 'access_token' => ''));
        $notice = isset($_GET['cgsp_notice']) ? sanitize_key(wp_unslash($_GET['cgsp_notice'])) : '';
        include CGSP_DIR . 'admin/settings-page.php';
    }

    public function save_settings() {
        $this->guard();
        check_admin_referer('cgsp_save_settings');
        $old = get_option('cgsp_settings', array());
        $token = isset($_POST['access_token']) ? trim(sanitize_textarea_field(wp_unslash($_POST['access_token']))) : '';
        update_option('cgsp_settings', array(
            'graph_version' => isset($_POST['graph_version']) ? sanitize_text_field(wp_unslash($_POST['graph_version'])) : 'v23.0',
            'page_id' => isset($_POST['page_id']) ? sanitize_text_field(wp_unslash($_POST['page_id'])) : '',
            'instagram_id' => isset($_POST['instagram_id']) ? sanitize_text_field(wp_unslash($_POST['instagram_id'])) : '',
            'access_token' => '' !== $token ? $token : (isset($old['access_token']) ? $old['access_token'] : ''),
        ), false);
        $this->redirect('cgsp-settings', 'saved');
    }

    public function test_connection() {
        $this->guard();
        check_admin_referer('cgsp_test_connection');
        $result = (new CGSP_Social_API())->test_connection();
        $this->redirect('cgsp-settings', is_wp_error($result) ? 'test_error' : 'test_ok');
    }

    public function handle_publish() {
        $this->guard();
        check_admin_referer('cgsp_publish');
        $platforms = isset($_POST['platforms']) ? array_intersect(array('website', 'facebook', 'instagram'), array_map('sanitize_key', (array) wp_unslash($_POST['platforms']))) : array();
        $payload = array(
            'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'image_url' => isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '',
            'platforms' => $platforms,
        );
        if (empty($payload['message']) || empty($platforms)) {
            $this->redirect('cgsp-publisher', 'invalid');
        }
        if (in_array('website', $platforms, true) && empty($payload['title'])) {
            $this->redirect('cgsp-publisher', 'missing_title');
        }

        $schedule = isset($_POST['schedule_at']) ? sanitize_text_field(wp_unslash($_POST['schedule_at'])) : '';
        if ($schedule) {
            $timestamp = strtotime($schedule . ' ' . wp_timezone_string());
            if ($timestamp && $timestamp > time() + 60) {
                wp_schedule_single_event($timestamp, 'cgsp_publish_scheduled_post', array($payload));
                CGSP_Logger::add('scheduled', 'pending', 'הפוסט תוזמן ל־' . wp_date('d/m/Y H:i', $timestamp));
                $this->redirect('cgsp-publisher', 'scheduled');
            }
        }

        (new CGSP_Social_API())->publish($payload);
        $this->redirect('cgsp-publisher', 'published');
    }

    private function guard() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('אין לך הרשאה לבצע פעולה זו.', 'cyberguard-social-publisher'));
        }
    }

    private function redirect($page, $notice) {
        wp_safe_redirect(add_query_arg(array('page' => $page, 'cgsp_notice' => $notice), admin_url('admin.php')));
        exit;
    }
}
