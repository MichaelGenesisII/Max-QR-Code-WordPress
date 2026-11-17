<?php
/**
 * Plugin Name: Max QR Code
 * Plugin URI: https://www.getmaxqr.com/docs
 * Description: Generate and embed Max QR Code images via shortcode using the Max QR Code API.
 * Version: 0.1.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Max QR Code
 * Author URI: https://www.getmaxqr.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: max-qr-code
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('MAX_QR_CODE_VERSION', '0.1.1');
define('MAX_QR_CODE_FILE', __FILE__);
define('MAX_QR_CODE_PATH', plugin_dir_path(__FILE__));
define('MAX_QR_CODE_URL', plugin_dir_url(__FILE__));

require_once MAX_QR_CODE_PATH . 'includes/class-autoloader.php';
Max_QR_Autoloader::register();

require_once MAX_QR_CODE_PATH . 'includes/class-plugin.php';

add_action('plugins_loaded', static function (): void {
    Max_QR_Plugin::instance()->boot();
});
