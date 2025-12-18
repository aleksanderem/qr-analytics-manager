<?php
/**
 * Database handler for QR Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_Database {

    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $qr_codes_table = $wpdb->prefix . 'qr_codes';
        $qr_clicks_table = $wpdb->prefix . 'qr_clicks';

        $sql_codes = "CREATE TABLE $qr_codes_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            destination_url text NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active)
        ) $charset_collate;";

        $sql_clicks = "CREATE TABLE $qr_clicks_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            qr_code_id bigint(20) unsigned NOT NULL,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            user_agent text,
            referer text,
            country varchar(100),
            city varchar(100),
            device_type varchar(50),
            browser varchar(100),
            os varchar(100),
            PRIMARY KEY (id),
            KEY qr_code_id (qr_code_id),
            KEY clicked_at (clicked_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_codes);
        dbDelta($sql_clicks);
    }

    public static function get_qr_codes_table() {
        global $wpdb;
        return $wpdb->prefix . 'qr_codes';
    }

    public static function get_qr_clicks_table() {
        global $wpdb;
        return $wpdb->prefix . 'qr_clicks';
    }

    public static function insert_qr_code($data) {
        global $wpdb;
        $table = self::get_qr_codes_table();

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'destination_url' => esc_url_raw($data['destination_url']),
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1
        ), array('%s', '%s', '%s', '%s', '%d'));

        return $result ? $wpdb->insert_id : false;
    }

    public static function update_qr_code($id, $data) {
        global $wpdb;
        $table = self::get_qr_codes_table();

        $update_data = array();
        $format = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }
        if (isset($data['slug'])) {
            $update_data['slug'] = sanitize_title($data['slug']);
            $format[] = '%s';
        }
        if (isset($data['destination_url'])) {
            $update_data['destination_url'] = esc_url_raw($data['destination_url']);
            $format[] = '%s';
        }
        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
            $format[] = '%s';
        }
        if (isset($data['is_active'])) {
            $update_data['is_active'] = (int) $data['is_active'];
            $format[] = '%d';
        }

        return $wpdb->update($table, $update_data, array('id' => $id), $format, array('%d'));
    }

    public static function delete_qr_code($id) {
        global $wpdb;
        $codes_table = self::get_qr_codes_table();
        $clicks_table = self::get_qr_clicks_table();

        $wpdb->delete($clicks_table, array('qr_code_id' => $id), array('%d'));
        return $wpdb->delete($codes_table, array('id' => $id), array('%d'));
    }

    public static function get_qr_code($id) {
        global $wpdb;
        $table = self::get_qr_codes_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_qr_code_by_slug($slug) {
        global $wpdb;
        $table = self::get_qr_codes_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s AND is_active = 1", $slug));
    }

    public static function get_all_qr_codes($args = array()) {
        global $wpdb;
        $table = self::get_qr_codes_table();

        $defaults = array(
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY $orderby LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        ));
    }

    public static function slug_exists($slug, $exclude_id = 0) {
        global $wpdb;
        $table = self::get_qr_codes_table();

        // Normalize slug the same way it's stored
        $slug = sanitize_title($slug);

        if ($exclude_id > 0) {
            return (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE slug = %s AND id != %d",
                $slug,
                $exclude_id
            ));
        }

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE slug = %s",
            $slug
        ));
    }

    public static function record_click($qr_code_id, $data = array()) {
        global $wpdb;
        $table = self::get_qr_clicks_table();

        return $wpdb->insert($table, array(
            'qr_code_id' => (int) $qr_code_id,
            'ip_address' => isset($data['ip_address']) ? sanitize_text_field($data['ip_address']) : '',
            'user_agent' => isset($data['user_agent']) ? sanitize_text_field($data['user_agent']) : '',
            'referer' => isset($data['referer']) ? esc_url_raw($data['referer']) : '',
            'country' => isset($data['country']) ? sanitize_text_field($data['country']) : '',
            'city' => isset($data['city']) ? sanitize_text_field($data['city']) : '',
            'device_type' => isset($data['device_type']) ? sanitize_text_field($data['device_type']) : '',
            'browser' => isset($data['browser']) ? sanitize_text_field($data['browser']) : '',
            'os' => isset($data['os']) ? sanitize_text_field($data['os']) : ''
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
    }

    public static function get_click_count($qr_code_id) {
        global $wpdb;
        $table = self::get_qr_clicks_table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE qr_code_id = %d",
            $qr_code_id
        ));
    }

    public static function get_clicks_by_date($qr_code_id = null, $start_date = null, $end_date = null) {
        global $wpdb;
        $table = self::get_qr_clicks_table();

        $where = array();
        $params = array();

        if ($qr_code_id) {
            $where[] = 'qr_code_id = %d';
            $params[] = $qr_code_id;
        }

        if ($start_date) {
            $where[] = 'clicked_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where[] = 'clicked_at <= %s';
            $params[] = $end_date;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT DATE(clicked_at) as date, COUNT(*) as clicks
                  FROM $table
                  $where_clause
                  GROUP BY DATE(clicked_at)
                  ORDER BY date ASC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }

        return $wpdb->get_results($query);
    }

    public static function get_clicks_by_device($qr_code_id = null, $start_date = null, $end_date = null) {
        global $wpdb;
        $table = self::get_qr_clicks_table();

        $where = array();
        $params = array();

        if ($qr_code_id) {
            $where[] = 'qr_code_id = %d';
            $params[] = $qr_code_id;
        }

        if ($start_date) {
            $where[] = 'clicked_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where[] = 'clicked_at <= %s';
            $params[] = $end_date;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT device_type, COUNT(*) as clicks
                  FROM $table
                  $where_clause
                  GROUP BY device_type
                  ORDER BY clicks DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }

        return $wpdb->get_results($query);
    }

    public static function get_clicks_by_country($qr_code_id = null, $start_date = null, $end_date = null) {
        global $wpdb;
        $table = self::get_qr_clicks_table();

        $where = array();
        $params = array();

        if ($qr_code_id) {
            $where[] = 'qr_code_id = %d';
            $params[] = $qr_code_id;
        }

        if ($start_date) {
            $where[] = 'clicked_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where[] = 'clicked_at <= %s';
            $params[] = $end_date;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT country, COUNT(*) as clicks
                  FROM $table
                  $where_clause
                  GROUP BY country
                  ORDER BY clicks DESC";

        if (!empty($params)) {
            return $wpdb->get_results($wpdb->prepare($query, $params));
        }

        return $wpdb->get_results($query);
    }

    public static function get_total_stats($start_date = null, $end_date = null) {
        global $wpdb;
        $codes_table = self::get_qr_codes_table();
        $clicks_table = self::get_qr_clicks_table();

        $total_codes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $codes_table");
        $active_codes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $codes_table WHERE is_active = 1");

        $where = array();
        $params = array();

        if ($start_date) {
            $where[] = 'clicked_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where[] = 'clicked_at <= %s';
            $params[] = $end_date;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($params)) {
            $total_clicks = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $clicks_table $where_clause",
                $params
            ));
        } else {
            $total_clicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM $clicks_table");
        }

        return array(
            'total_codes' => $total_codes,
            'active_codes' => $active_codes,
            'total_clicks' => $total_clicks
        );
    }

    public static function get_top_qr_codes($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;
        $codes_table = self::get_qr_codes_table();
        $clicks_table = self::get_qr_clicks_table();

        $where = array();
        $params = array();

        if ($start_date) {
            $where[] = 'c.clicked_at >= %s';
            $params[] = $start_date;
        }

        if ($end_date) {
            $where[] = 'c.clicked_at <= %s';
            $params[] = $end_date;
        }

        $where_clause = !empty($where) ? 'AND ' . implode(' AND ', $where) : '';
        $params[] = $limit;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, COUNT(c.id) as click_count
             FROM $codes_table q
             LEFT JOIN $clicks_table c ON q.id = c.qr_code_id $where_clause
             GROUP BY q.id
             ORDER BY click_count DESC
             LIMIT %d",
            $params
        ));
    }
}
