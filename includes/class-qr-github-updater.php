<?php
/**
 * GitHub Updater for QR Analytics
 *
 * Enables automatic plugin updates from a GitHub repository.
 * Checks GitHub releases for new versions and integrates with WordPress update system.
 */

if (!defined('ABSPATH')) {
    exit;
}

class QR_GitHub_Updater {

    private $slug;
    private $plugin_data;
    private $plugin_file;
    private $github_repo;
    private $github_response;
    private $access_token;

    /**
     * Initialize the updater
     *
     * @param string $plugin_file Full path to the main plugin file
     * @param string $github_repo GitHub repository in format "username/repo-name"
     * @param string $access_token Optional GitHub access token for private repos
     */
    public function __construct($plugin_file, $github_repo, $access_token = null) {
        $this->plugin_file = $plugin_file;
        $this->github_repo = $github_repo;
        $this->access_token = $access_token;

        add_action('admin_init', array($this, 'set_plugin_properties'));
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);

        // Clear update cache when plugin is activated
        add_action('activated_plugin', array($this, 'clear_update_cache'));
    }

    /**
     * Set plugin properties from plugin header
     */
    public function set_plugin_properties() {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $this->plugin_data = get_plugin_data($this->plugin_file);
        $this->slug = plugin_basename($this->plugin_file);
    }

    /**
     * Get repository info from GitHub API
     */
    private function get_repository_info() {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->github_repo}/releases/latest";

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
            )
        );

        if ($this->access_token) {
            $args['headers']['Authorization'] = 'token ' . $this->access_token;
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $this->github_response = json_decode($body);

        return $this->github_response;
    }

    /**
     * Check if there's a new version available
     *
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->set_plugin_properties();
        $github_info = $this->get_repository_info();

        if (!$github_info) {
            return $transient;
        }

        // Get version from tag (remove 'v' prefix if present)
        $github_version = ltrim($github_info->tag_name, 'v');
        $current_version = $this->plugin_data['Version'];

        if (version_compare($github_version, $current_version, '>')) {
            $download_url = $this->get_download_url($github_info);

            $transient->response[$this->slug] = (object) array(
                'slug' => dirname($this->slug),
                'plugin' => $this->slug,
                'new_version' => $github_version,
                'url' => $this->plugin_data['PluginURI'],
                'package' => $download_url,
                'icons' => array(),
                'banners' => array(),
                'banners_rtl' => array(),
                'tested' => '',
                'requires_php' => '',
                'compatibility' => new stdClass()
            );
        }

        return $transient;
    }

    /**
     * Get the download URL from release assets or zipball
     */
    private function get_download_url($github_info) {
        // First, check for a zip asset in release assets
        if (!empty($github_info->assets)) {
            foreach ($github_info->assets as $asset) {
                if (strpos($asset->name, '.zip') !== false) {
                    $url = $asset->browser_download_url;
                    if ($this->access_token) {
                        $url = add_query_arg('access_token', $this->access_token, $url);
                    }
                    return $url;
                }
            }
        }

        // Fallback to zipball URL
        $url = $github_info->zipball_url;
        if ($this->access_token) {
            $url = add_query_arg('access_token', $this->access_token, $url);
        }
        return $url;
    }

    /**
     * Provide plugin information for the WordPress plugin info popup
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return false|object
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== dirname($this->slug)) {
            return $result;
        }

        $this->set_plugin_properties();
        $github_info = $this->get_repository_info();

        if (!$github_info) {
            return $result;
        }

        $github_version = ltrim($github_info->tag_name, 'v');

        $plugin_info = (object) array(
            'name' => $this->plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => $github_version,
            'author' => $this->plugin_data['AuthorName'],
            'author_profile' => $this->plugin_data['AuthorURI'],
            'homepage' => $this->plugin_data['PluginURI'],
            'requires' => '5.0',
            'tested' => get_bloginfo('version'),
            'downloaded' => 0,
            'last_updated' => $github_info->published_at,
            'sections' => array(
                'description' => $this->plugin_data['Description'],
                'changelog' => $this->parse_changelog($github_info->body)
            ),
            'download_link' => $this->get_download_url($github_info)
        );

        return $plugin_info;
    }

    /**
     * Parse release notes as changelog
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return __('No changelog available.', 'qr-analytics');
        }

        // Convert markdown to basic HTML
        $changelog = nl2br(esc_html($body));

        // Convert markdown headers
        $changelog = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $changelog);
        $changelog = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^# (.+)$/m', '<h2>$1</h2>', $changelog);

        // Convert markdown lists
        $changelog = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $changelog);
        $changelog = preg_replace('/^- (.+)$/m', '<li>$1</li>', $changelog);

        return $changelog;
    }

    /**
     * Rename the plugin folder after installation
     *
     * GitHub zip files have a folder like "repo-name-version" which needs
     * to be renamed to match the expected plugin folder name.
     *
     * @param bool $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Only run for this plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $result;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->slug);

        // Move from temporary location to correct folder
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        // Reactivate plugin if it was active
        if (is_plugin_active($this->slug)) {
            activate_plugin($this->slug);
        }

        return $result;
    }

    /**
     * Clear the update cache
     */
    public function clear_update_cache() {
        delete_site_transient('update_plugins');
        $this->github_response = null;
    }

    /**
     * Force check for updates (useful for debugging)
     */
    public static function force_check() {
        delete_site_transient('update_plugins');
        wp_update_plugins();
    }
}
