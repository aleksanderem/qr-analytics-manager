<?php
/**
 * QR Code Router - Handles slug-based redirects and tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_Router {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_redirect'));
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^qr/([^/]+)/?$',
            'index.php?qr_slug=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'qr_slug';
        return $vars;
    }

    public function handle_redirect() {
        $slug = get_query_var('qr_slug');

        if (empty($slug)) {
            return;
        }

        $qr_code = QR_Database::get_qr_code_by_slug($slug);

        if (!$qr_code) {
            wp_redirect(home_url(), 301);
            exit;
        }

        $this->track_click($qr_code->id);

        wp_redirect($qr_code->destination_url, 302);
        exit;
    }

    private function track_click($qr_code_id) {
        $tracker = new QR_Analytics_Tracker();

        $data = array(
            'ip_address' => $tracker->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'device_type' => $tracker->get_device_type(),
            'browser' => $tracker->get_browser(),
            'os' => $tracker->get_os()
        );

        QR_Database::record_click($qr_code_id, $data);
    }

    public static function get_qr_url($slug, $base_url = null) {
        if ($base_url === null) {
            $base_url = QR_Admin::get_qr_base_url();
        }
        return rtrim($base_url, '/') . '/qr/' . $slug . '/';
    }

    public static function flush_rules() {
        $router = self::get_instance();
        $router->add_rewrite_rules();
        flush_rewrite_rules();
    }
}
