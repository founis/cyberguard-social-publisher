<?php
/**
 * Plugin Name: CyberGuard Social Publisher
 * Description: Publish and schedule CyberGuard posts to Facebook Pages and Instagram Business.
 * Version: 0.1.0
 * Author: CyberGuard
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: cyberguard-social-publisher
 */

defined('ABSPATH') || exit;

define('CGSP_VERSION', '0.1.0');
define('CGSP_FILE', __FILE__);
define('CGSP_DIR', plugin_dir_path(__FILE__));
define('CGSP_URL', plugin_dir_url(__FILE__));

require_once CGSP_DIR . 'includes/class-cgsp-plugin.php';

register_activation_hook(__FILE__, array('CGSP_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('CGSP_Plugin', 'deactivate'));

CGSP_Plugin::instance();
