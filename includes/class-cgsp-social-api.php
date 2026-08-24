<?php
defined('ABSPATH') || exit;

class CGSP_Social_API {
    private $settings;

    public function __construct() {
        $this->settings = wp_parse_args(get_option('cgsp_settings', array()), array(
            'graph_version' => 'v23.0',
            'page_id' => '',
            'instagram_id' => '',
            'access_token' => '',
        ));
    }

    public function publish($payload) {
        $payload = wp_parse_args($payload, array('message' => '', 'image_url' => '', 'platforms' => array()));
        $results = array();
        foreach ((array) $payload['platforms'] as $platform) {
            if ('facebook' === $platform) {
                $results['facebook'] = $this->publish_facebook($payload);
            } elseif ('instagram' === $platform) {
                $results['instagram'] = $this->publish_instagram($payload);
            }
        }
        return $results;
    }

    public function test_connection() {
        if (empty($this->settings['access_token']) || empty($this->settings['page_id'])) {
            return new WP_Error('cgsp_missing_settings', 'יש להזין מזהה עמוד ו־Access Token.');
        }
        return $this->request('GET', $this->settings['page_id'], array('fields' => 'id,name'));
    }

    private function publish_facebook($payload) {
        if (empty($this->settings['page_id'])) {
            return $this->failure('facebook', 'מזהה עמוד Facebook חסר.');
        }
        $endpoint = $this->settings['page_id'] . (empty($payload['image_url']) ? '/feed' : '/photos');
        $body = array('message' => $payload['message']);
        if (!empty($payload['image_url'])) {
            $body['url'] = esc_url_raw($payload['image_url']);
        }
        $response = $this->request('POST', $endpoint, $body);
        return $this->log_result('facebook', $response);
    }

    private function publish_instagram($payload) {
        if (empty($this->settings['instagram_id'])) {
            return $this->failure('instagram', 'מזהה Instagram Business חסר.');
        }
        if (empty($payload['image_url'])) {
            return $this->failure('instagram', 'Instagram דורשת כתובת תמונה ציבורית.');
        }
        $container = $this->request('POST', $this->settings['instagram_id'] . '/media', array(
            'image_url' => esc_url_raw($payload['image_url']),
            'caption' => $payload['message'],
        ));
        if (is_wp_error($container) || empty($container['id'])) {
            return $this->log_result('instagram', $container);
        }
        $published = $this->request('POST', $this->settings['instagram_id'] . '/media_publish', array(
            'creation_id' => $container['id'],
        ));
        return $this->log_result('instagram', $published);
    }

    private function request($method, $endpoint, $params = array()) {
        $version = preg_replace('/[^v0-9.]/', '', $this->settings['graph_version']);
        $url = 'https://graph.facebook.com/' . $version . '/' . ltrim($endpoint, '/');
        $params['access_token'] = $this->settings['access_token'];
        $args = array('timeout' => 30);
        if ('GET' === $method) {
            $response = wp_remote_get(add_query_arg($params, $url), $args);
        } else {
            $args['body'] = $params;
            $response = wp_remote_post($url, $args);
        }
        if (is_wp_error($response)) {
            return $response;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (wp_remote_retrieve_response_code($response) >= 400 || isset($data['error'])) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : 'Meta API request failed.';
            return new WP_Error('cgsp_meta_error', $message, $data);
        }
        return is_array($data) ? $data : array();
    }

    private function log_result($platform, $result) {
        if (is_wp_error($result)) {
            return $this->failure($platform, $result->get_error_message());
        }
        $id = isset($result['id']) ? $result['id'] : '';
        CGSP_Logger::add($platform, 'success', 'הפרסום הושלם בהצלחה.', $id);
        return $result;
    }

    private function failure($platform, $message) {
        CGSP_Logger::add($platform, 'error', $message);
        return new WP_Error('cgsp_publish_error', $message);
    }
}
