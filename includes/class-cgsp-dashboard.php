<?php
defined('ABSPATH') || exit;

class CGSP_Dashboard {
    const QUERY_VAR = 'cgsp_dashboard';
    const ROUTE_VERSION = '1';

    public function __construct() {
        add_action('init', array($this, 'rewrite'));
        add_action('init', array($this, 'maybe_flush_rewrites'), 99);
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'render'));
        add_action('admin_post_cgsp_dashboard_publish', array($this, 'handle_publish'));
        add_action('admin_post_cgsp_dashboard_cancel', array($this, 'handle_cancel'));
    }

    public static function activate() {
        add_rewrite_rule('^publisher/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
        flush_rewrite_rules();
        update_option('cgsp_dashboard_route_version', self::ROUTE_VERSION, false);
    }

    public static function deactivate() {
        delete_option('cgsp_dashboard_route_version');
        flush_rewrite_rules();
    }

    public function rewrite() {
        add_rewrite_rule('^publisher/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
    }

    public function maybe_flush_rewrites() {
        if (get_option('cgsp_dashboard_route_version') !== self::ROUTE_VERSION) {
            flush_rewrite_rules(false);
            update_option('cgsp_dashboard_route_version', self::ROUTE_VERSION, false);
        }
    }

    public function query_vars($vars) {
        $vars[] = self::QUERY_VAR;
        return $vars;
    }

    public function render() {
        if (!get_query_var(self::QUERY_VAR)) {
            return;
        }
        if (!is_user_logged_in()) {
            auth_redirect();
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('אין לך הרשאה להיכנס למערכת הפרסום.', 'cyberguard-social-publisher'), '', array('response' => 403));
        }

        show_admin_bar(false);
        wp_enqueue_media();
        wp_enqueue_style('cgsp-dashboard', CGSP_URL . 'assets/dashboard.css', array(), CGSP_VERSION);
        wp_enqueue_script('cgsp-dashboard', CGSP_URL . 'assets/dashboard.js', array('jquery'), CGSP_VERSION, true);

        $notice = isset($_GET['cgsp_notice']) ? sanitize_key(wp_unslash($_GET['cgsp_notice'])) : '';
        $content_library = $this->load_content_library();
        $scheduled_posts = $this->get_scheduled_posts();
        $logs = CGSP_Logger::recent();
        $settings = wp_parse_args(get_option('cgsp_settings', array()), array('page_id' => '', 'instagram_id' => '', 'access_token' => ''));

        status_header(200);
        nocache_headers();
        include CGSP_DIR . 'dashboard/dashboard-page.php';
        exit;
    }

    public function handle_publish() {
        $this->guard();
        check_admin_referer('cgsp_dashboard_publish');
        $platforms = isset($_POST['platforms']) ? array_intersect(array('website', 'facebook', 'instagram'), array_map('sanitize_key', (array) wp_unslash($_POST['platforms']))) : array();
        $payload = array(
            'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'image_url' => isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '',
            'platforms' => $platforms,
        );
        if (empty($payload['message']) || empty($platforms)) {
            $this->redirect('invalid');
        }
        if (in_array('website', $platforms, true) && empty($payload['title'])) {
            $this->redirect('missing_title');
        }
        if (in_array('instagram', $platforms, true) && empty($payload['image_url'])) {
            $this->redirect('instagram_image');
        }

        $edit_timestamp = isset($_POST['edit_timestamp']) ? absint($_POST['edit_timestamp']) : 0;
        $edit_key = isset($_POST['edit_key']) ? sanitize_text_field(wp_unslash($_POST['edit_key'])) : '';
        if ($edit_timestamp && $edit_key) {
            $event = $this->find_event($edit_timestamp, $edit_key);
            if ($event) {
                wp_unschedule_event($edit_timestamp, 'cgsp_publish_scheduled_post', $event['args']);
            }
        }

        $schedule = isset($_POST['schedule_at']) ? sanitize_text_field(wp_unslash($_POST['schedule_at'])) : '';
        if ($schedule) {
            $timestamp = strtotime($schedule . ' ' . wp_timezone_string());
            if ($timestamp && $timestamp > time() + 60) {
                wp_schedule_single_event($timestamp, 'cgsp_publish_scheduled_post', array($payload));
                CGSP_Logger::add('scheduled', 'pending', 'הפוסט תוזמן ל־' . wp_date('d/m/Y H:i', $timestamp));
                $this->redirect('scheduled');
            }
        }

        (new CGSP_Social_API())->publish($payload);
        $this->redirect('published');
    }

    public function handle_cancel() {
        $this->guard();
        check_admin_referer('cgsp_dashboard_cancel');
        $timestamp = isset($_POST['timestamp']) ? absint($_POST['timestamp']) : 0;
        $key = isset($_POST['event_key']) ? sanitize_text_field(wp_unslash($_POST['event_key'])) : '';
        $event = $this->find_event($timestamp, $key);
        if (!$event) {
            $this->redirect('not_found');
        }
        wp_unschedule_event($timestamp, 'cgsp_publish_scheduled_post', $event['args']);
        CGSP_Logger::add('scheduled', 'success', 'פוסט מתוזמן בוטל.');
        $this->redirect('cancelled');
    }

    private function get_scheduled_posts() {
        $items = array();
        if (!function_exists('_get_cron_array')) {
            return $items;
        }
        $cron = _get_cron_array();
        foreach ((array) $cron as $timestamp => $hooks) {
            if (empty($hooks['cgsp_publish_scheduled_post'])) {
                continue;
            }
            foreach ($hooks['cgsp_publish_scheduled_post'] as $key => $event) {
                $payload = isset($event['args'][0]) && is_array($event['args'][0]) ? $event['args'][0] : array();
                $items[] = array('timestamp' => (int) $timestamp,'key' => (string) $key,'args' => isset($event['args']) ? $event['args'] : array(),'payload' => $payload);
            }
        }
        usort($items, function ($a, $b) { return $a['timestamp'] <=> $b['timestamp']; });
        return $items;
    }

    private function find_event($timestamp, $key) {
        foreach ($this->get_scheduled_posts() as $event) {
            if ((int) $event['timestamp'] === (int) $timestamp && hash_equals((string) $event['key'], (string) $key)) {
                return $event;
            }
        }
        return null;
    }

    private function load_content_library() {
        $path = CGSP_DIR . 'content/cyberguard-posts.json';
        if (!is_readable($path)) {
            return array();
        }
        $posts = json_decode(file_get_contents($path), true);
        if (!is_array($posts)) {
            return array();
        }
        return array_values(array_filter($posts, function ($post) {
            return is_array($post) && !empty($post['message']) && (!isset($post['status']) || 'ready' === $post['status']);
        }));
    }

    private function guard() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_die(esc_html__('אין לך הרשאה לבצע פעולה זו.', 'cyberguard-social-publisher'), '', array('response' => 403));
        }
    }

    private function redirect($notice) {
        wp_safe_redirect(add_query_arg('cgsp_notice', $notice, home_url('/publisher/')));
        exit;
    }
}
