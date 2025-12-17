<?php
/**
 * Analytics Tracker - Device detection and click tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_Analytics_Tracker {

    public function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    public function get_device_type() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';

        if (empty($user_agent)) {
            return 'unknown';
        }

        $mobile_keywords = array(
            'mobile', 'android', 'iphone', 'ipod', 'blackberry',
            'windows phone', 'opera mini', 'opera mobi', 'iemobile'
        );

        foreach ($mobile_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return 'mobile';
            }
        }

        $tablet_keywords = array('ipad', 'tablet', 'kindle', 'playbook');

        foreach ($tablet_keywords as $keyword) {
            if (strpos($user_agent, $keyword) !== false) {
                return 'tablet';
            }
        }

        return 'desktop';
    }

    public function get_browser() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        if (empty($user_agent)) {
            return 'unknown';
        }

        $browsers = array(
            'Edge' => '/edge|edg/i',
            'Opera' => '/opera|opr/i',
            'Chrome' => '/chrome/i',
            'Safari' => '/safari/i',
            'Firefox' => '/firefox/i',
            'IE' => '/msie|trident/i'
        );

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $browser;
            }
        }

        return 'other';
    }

    public function get_os() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        if (empty($user_agent)) {
            return 'unknown';
        }

        $os_array = array(
            'Windows 11' => '/windows nt 10.*build.*(22|23)/i',
            'Windows 10' => '/windows nt 10/i',
            'Windows 8.1' => '/windows nt 6\.3/i',
            'Windows 8' => '/windows nt 6\.2/i',
            'Windows 7' => '/windows nt 6\.1/i',
            'Windows Vista' => '/windows nt 6\.0/i',
            'Windows XP' => '/windows nt 5\.1/i',
            'macOS' => '/macintosh|mac os x/i',
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Linux' => '/linux/i',
            'Chrome OS' => '/cros/i'
        );

        foreach ($os_array as $os => $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return $os;
            }
        }

        return 'other';
    }

    public function parse_user_agent($user_agent = null) {
        if ($user_agent === null) {
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }

        return array(
            'device_type' => $this->get_device_type(),
            'browser' => $this->get_browser(),
            'os' => $this->get_os(),
            'raw' => $user_agent
        );
    }
}
