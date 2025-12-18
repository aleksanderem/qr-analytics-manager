<?php
/**
 * Admin Panel for QR Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_Admin {

    private static $instance = null;
    const OPTION_BASE_URL = 'qr_analytics_base_url';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_qr_save_code', array($this, 'ajax_save_code'));
        add_action('wp_ajax_qr_delete_code', array($this, 'ajax_delete_code'));
        add_action('wp_ajax_qr_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_qr_generate_preview', array($this, 'ajax_generate_preview'));
        add_action('wp_ajax_qr_download_svg', array($this, 'ajax_download_svg'));
        add_action('wp_ajax_qr_save_base_url', array($this, 'ajax_save_base_url'));
        add_action('wp_ajax_qr_get_lan_ips', array($this, 'ajax_get_lan_ips'));
        add_action('wp_ajax_qr_verify_data', array($this, 'ajax_verify_data'));
        add_action('wp_ajax_qr_delete_all_data', array($this, 'ajax_delete_all_data'));
    }

    /**
     * Check if current site URL is localhost
     */
    public static function is_localhost() {
        $host = parse_url(home_url(), PHP_URL_HOST);
        return in_array($host, array('localhost', '127.0.0.1', '::1'));
    }

    /**
     * Get local network IP addresses using PHP functions (no shell commands)
     */
    public static function get_local_ips() {
        $ips = array();

        // Method 1: Use gethostbyname with hostname
        $hostname = gethostname();
        if ($hostname) {
            $ip = gethostbyname($hostname);
            if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips[] = $ip;
            }
        }

        // Method 2: Use DNS lookup for common local hostnames
        $local_names = array($hostname, $hostname . '.local');
        foreach ($local_names as $name) {
            $records = @dns_get_record($name, DNS_A);
            if ($records) {
                foreach ($records as $record) {
                    if (isset($record['ip']) && filter_var($record['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        $ips[] = $record['ip'];
                    }
                }
            }
        }

        // Method 3: Check SERVER_ADDR if available
        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $ip = $_SERVER['SERVER_ADDR'];
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ips[] = $ip;
            }
        }

        // Filter to only private IPs (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
        $ips = array_filter($ips, function($ip) {
            return preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip);
        });

        return array_unique(array_values($ips));
    }

    /**
     * Get the base URL for QR codes (respects user setting)
     */
    public static function get_qr_base_url() {
        $saved_url = get_option(self::OPTION_BASE_URL, '');

        if (!empty($saved_url)) {
            return rtrim($saved_url, '/');
        }

        return home_url();
    }

    /**
     * Get available base URL options
     */
    public static function get_base_url_options() {
        $options = array();
        $home = home_url();
        $parsed = parse_url($home);
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';

        // Always add current home URL
        $options['home'] = array(
            'url' => $home,
            'label' => $parsed['host'] . $port,
            'type' => 'current'
        );

        // If localhost, add network IPs
        if (self::is_localhost()) {
            $local_ips = self::get_local_ips();
            foreach ($local_ips as $ip) {
                $url = $scheme . '://' . $ip . $port;
                $options['ip_' . str_replace('.', '_', $ip)] = array(
                    'url' => $url,
                    'label' => $ip . $port . ' (LAN)',
                    'type' => 'lan'
                );
            }
        }

        return $options;
    }

    /**
     * AJAX handler for saving base URL
     */
    public function ajax_save_base_url() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : '';

        update_option(self::OPTION_BASE_URL, $base_url);

        wp_send_json_success(array(
            'message' => __('Base URL saved successfully!', 'qr-analytics'),
            'base_url' => $base_url ? $base_url : home_url()
        ));
    }

    /**
     * AJAX handler for getting LAN IPs asynchronously
     */
    public function ajax_get_lan_ips() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $ips = self::get_local_ips();

        wp_send_json_success(array(
            'ips' => $ips
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('QR Analytics', 'qr-analytics'),
            'QR Analytics <span class="qr-by-alex">by Alex</span>',
            'manage_options',
            'qr-analytics',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar',
            30
        );

        add_submenu_page(
            'qr-analytics',
            __('Dashboard', 'qr-analytics'),
            __('Dashboard', 'qr-analytics'),
            'manage_options',
            'qr-analytics',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'qr-analytics',
            __('QR Codes', 'qr-analytics'),
            __('QR Codes', 'qr-analytics'),
            'manage_options',
            'qr-analytics-codes',
            array($this, 'render_codes_page')
        );

        add_submenu_page(
            'qr-analytics',
            __('Add New QR', 'qr-analytics'),
            __('Add New QR', 'qr-analytics'),
            'manage_options',
            'qr-analytics-new',
            array($this, 'render_new_code_page')
        );

        add_submenu_page(
            'qr-analytics',
            __('Reports', 'qr-analytics'),
            __('Reports', 'qr-analytics'),
            'manage_options',
            'qr-analytics-reports',
            array($this, 'render_reports_page')
        );

        add_submenu_page(
            'qr-analytics',
            __('Settings', 'qr-analytics'),
            __('Settings', 'qr-analytics'),
            'manage_options',
            'qr-analytics-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        $base_url_options = self::get_base_url_options();
        $current_base_url = get_option(self::OPTION_BASE_URL, '');
        $is_localhost = self::is_localhost();

        include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function enqueue_scripts($hook) {
        // Always load menu styles for the badge
        wp_add_inline_style('admin-menu', '
            #adminmenu .qr-by-alex {
                font-size: 9px;
                position: absolute;
                top: 0 !important;
                right: 0;
                height: 100%;
                width: 15px;
                border: none !important;
                border-radius: 0 !important;
                background: orange;
                font-weight: bold;
                color: #fff;
                text-transform: uppercase;
                padding: 0 5px;
                margin-left: 5px;
            }
        ');

        if (strpos($hook, 'qr-analytics') === false) {
            return;
        }

        wp_enqueue_style(
            'qr-analytics-admin',
            QR_ANALYTICS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            QR_ANALYTICS_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        wp_enqueue_script(
            'qr-analytics-admin',
            QR_ANALYTICS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'chart-js'),
            QR_ANALYTICS_VERSION,
            true
        );

        wp_localize_script('qr-analytics-admin', 'qrAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('qr_analytics_nonce'),
            'homeUrl' => home_url(),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this QR code? This action cannot be undone.', 'qr-analytics'),
                'saved' => __('QR code saved successfully!', 'qr-analytics'),
                'error' => __('An error occurred. Please try again.', 'qr-analytics'),
                'slugExists' => __('This slug is already in use. Please choose a different one.', 'qr-analytics')
            )
        ));
    }

    public function render_dashboard() {
        $stats = QR_Database::get_total_stats();
        $top_codes = QR_Database::get_top_qr_codes(5);
        $clicks_by_date = QR_Database::get_clicks_by_date(null, date('Y-m-d', strtotime('-30 days')));
        $clicks_by_device = QR_Database::get_clicks_by_device();

        include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function render_codes_page() {
        $qr_codes = QR_Database::get_all_qr_codes();

        foreach ($qr_codes as &$code) {
            $code->click_count = QR_Database::get_click_count($code->id);
            $code->qr_url = QR_Router::get_qr_url($code->slug);
        }

        include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/codes-list.php';
    }

    public function render_new_code_page() {
        $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $qr_code = $edit_id ? QR_Database::get_qr_code($edit_id) : null;

        include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/code-form.php';
    }

    public function render_reports_page() {
        $qr_codes = QR_Database::get_all_qr_codes();
        $selected_id = isset($_GET['qr_id']) ? (int) $_GET['qr_id'] : 0;

        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

        $clicks_by_date = QR_Database::get_clicks_by_date($selected_id ?: null, $start_date, $end_date . ' 23:59:59');
        $clicks_by_device = QR_Database::get_clicks_by_device($selected_id ?: null, $start_date, $end_date . ' 23:59:59');
        $clicks_by_country = QR_Database::get_clicks_by_country($selected_id ?: null, $start_date, $end_date . ' 23:59:59');

        include QR_ANALYTICS_PLUGIN_DIR . 'admin/views/reports.php';
    }

    public function ajax_save_code() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
        $destination_url = isset($_POST['destination_url']) ? esc_url_raw($_POST['destination_url']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;

        if (empty($name) || empty($slug) || empty($destination_url)) {
            wp_send_json_error(array('message' => __('Name, slug, and destination URL are required.', 'qr-analytics')));
        }

        if (QR_Database::slug_exists($slug, $id)) {
            wp_send_json_error(array('message' => __('This slug is already in use.', 'qr-analytics')));
        }

        $data = array(
            'name' => $name,
            'slug' => $slug,
            'destination_url' => $destination_url,
            'description' => $description,
            'is_active' => $is_active
        );

        if ($id > 0) {
            $result = QR_Database::update_qr_code($id, $data);
        } else {
            $result = QR_Database::insert_qr_code($data);
            $id = $result;
        }

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to save QR code.', 'qr-analytics')));
        }

        QR_Router::flush_rules();

        wp_send_json_success(array(
            'message' => __('QR code saved successfully!', 'qr-analytics'),
            'id' => $id,
            'qr_url' => QR_Router::get_qr_url($slug)
        ));
    }

    public function ajax_delete_code() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(array('message' => __('Invalid QR code ID.', 'qr-analytics')));
        }

        $result = QR_Database::delete_qr_code($id);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete QR code.', 'qr-analytics')));
        }

        wp_send_json_success(array('message' => __('QR code deleted successfully!', 'qr-analytics')));
    }

    public function ajax_get_analytics() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $qr_id = isset($_POST['qr_id']) ? (int) $_POST['qr_id'] : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-d', strtotime('-30 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-d');

        $data = array(
            'clicks_by_date' => QR_Database::get_clicks_by_date($qr_id ?: null, $start_date, $end_date . ' 23:59:59'),
            'clicks_by_device' => QR_Database::get_clicks_by_device($qr_id ?: null, $start_date, $end_date . ' 23:59:59'),
            'clicks_by_country' => QR_Database::get_clicks_by_country($qr_id ?: null, $start_date, $end_date . ' 23:59:59'),
            'total_clicks' => array_sum(array_column(QR_Database::get_clicks_by_date($qr_id ?: null, $start_date, $end_date . ' 23:59:59'), 'clicks'))
        );

        wp_send_json_success($data);
    }

    public function ajax_generate_preview() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';

        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Slug is required.', 'qr-analytics')));
        }

        $url = QR_Router::get_qr_url($slug);
        $svg = QR_Generator::generate($url, array('size' => 400, 'margin' => 4));

        wp_send_json_success(array(
            'svg' => $svg,
            'url' => $url
        ));
    }

    public function ajax_download_svg() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'qr-analytics'));
        }

        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 1000;

        if (empty($slug)) {
            wp_die(__('Slug is required.', 'qr-analytics'));
        }

        $url = QR_Router::get_qr_url($slug);
        $svg = QR_Generator::generate($url, array('size' => $size, 'margin' => 4));

        header('Content-Type: image/svg+xml');
        header('Content-Disposition: attachment; filename="qr-' . $slug . '.svg"');
        header('Content-Length: ' . strlen($svg));

        echo $svg;
        exit;
    }

    /**
     * AJAX handler for verifying data integrity
     */
    public function ajax_verify_data() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        global $wpdb;
        $codes_table = QR_Database::get_qr_codes_table();
        $clicks_table = QR_Database::get_qr_clicks_table();

        $issues = array();
        $stats = array(
            'total_codes' => 0,
            'total_clicks' => 0,
            'codes_checked' => 0,
            'issues_found' => 0
        );

        $qr_codes = $wpdb->get_results("SELECT * FROM $codes_table");
        $stats['total_codes'] = count($qr_codes);
        $stats['total_clicks'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $clicks_table");

        foreach ($qr_codes as $code) {
            $stats['codes_checked']++;
            $code_issues = array();

            if (empty($code->name)) {
                $code_issues[] = __('Missing name', 'qr-analytics');
            }

            if (empty($code->slug)) {
                $code_issues[] = __('Missing slug', 'qr-analytics');
            }

            if (empty($code->destination_url)) {
                $code_issues[] = __('Missing destination URL', 'qr-analytics');
            }

            if (!filter_var($code->destination_url, FILTER_VALIDATE_URL)) {
                $code_issues[] = __('Invalid destination URL format', 'qr-analytics');
            }

            if (!empty($code_issues)) {
                $issues[] = array(
                    'id' => $code->id,
                    'name' => $code->name ?: sprintf(__('QR #%d', 'qr-analytics'), $code->id),
                    'problems' => $code_issues
                );
                $stats['issues_found'] += count($code_issues);
            }
        }

        $orphaned_clicks = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $clicks_table c
             LEFT JOIN $codes_table q ON c.qr_code_id = q.id
             WHERE q.id IS NULL"
        );

        if ($orphaned_clicks > 0) {
            $issues[] = array(
                'id' => 0,
                'name' => __('Orphaned Click Records', 'qr-analytics'),
                'problems' => array(sprintf(__('%d click records with no associated QR code', 'qr-analytics'), $orphaned_clicks))
            );
            $stats['issues_found']++;
        }

        wp_send_json_success(array(
            'stats' => $stats,
            'issues' => $issues,
            'message' => empty($issues)
                ? __('All data integrity checks passed!', 'qr-analytics')
                : sprintf(__('Found %d issue(s) that need attention.', 'qr-analytics'), count($issues))
        ));
    }

    /**
     * AJAX handler for deleting all QR codes and click data
     */
    public function ajax_delete_all_data() {
        check_ajax_referer('qr_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'qr-analytics')));
        }

        $confirm = isset($_POST['confirm']) ? sanitize_text_field($_POST['confirm']) : '';

        if ($confirm !== 'DELETE') {
            wp_send_json_error(array('message' => __('Confirmation required. Type DELETE to confirm.', 'qr-analytics')));
        }

        global $wpdb;
        $codes_table = QR_Database::get_qr_codes_table();
        $clicks_table = QR_Database::get_qr_clicks_table();

        $deleted_clicks = $wpdb->query("TRUNCATE TABLE $clicks_table");
        $deleted_codes = $wpdb->query("TRUNCATE TABLE $codes_table");

        if ($deleted_clicks === false || $deleted_codes === false) {
            wp_send_json_error(array('message' => __('Failed to delete data. Database error occurred.', 'qr-analytics')));
        }

        QR_Router::flush_rules();

        wp_send_json_success(array(
            'message' => __('All QR codes and click data have been permanently deleted.', 'qr-analytics')
        ));
    }
}
