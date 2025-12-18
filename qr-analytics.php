<?php
/**
 * Plugin Name: QR Analytics
 * Plugin URI: https://github.com/aleksanderem/qr-analytics-manager
 * Description: Generate trackable QR codes with custom slugs and comprehensive analytics dashboard for marketing campaigns.
 * Version: 1.0.2
 * Author: Alex M.
 * Author URI: https://github.com/aleksanderem
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: qr-analytics
 * Domain Path: /languages
 * GitHub Plugin URI: aleksanderem/qr-analytics-manager
 * Primary Branch: main
 */

if (!defined('ABSPATH')) {
    exit;
}

define('QR_ANALYTICS_VERSION', '1.0.2');
define('QR_ANALYTICS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QR_ANALYTICS_PLUGIN_URL', plugin_dir_url(__FILE__));

class QR_Analytics {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-database.php';
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-generator.php';
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-router.php';
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-admin.php';
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-analytics-tracker.php';
        require_once QR_ANALYTICS_PLUGIN_DIR . 'includes/class-qr-github-updater.php';
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    public function init() {
        QR_Router::get_instance();

        if (is_admin()) {
            QR_Admin::get_instance();

            // Initialize GitHub updater
            new QR_GitHub_Updater(
                __FILE__,
                'aleksanderem/qr-analytics-manager'
            );
        }
    }

    public function activate() {
        QR_Database::create_tables();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain('qr-analytics', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

QR_Analytics::get_instance();
