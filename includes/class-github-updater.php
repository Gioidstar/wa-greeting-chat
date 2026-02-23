<?php
/**
 * GitHub Plugin Updater
 *
 * Handles automatic plugin updates from GitHub releases.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WA_Greeting_Chat_GitHub_Updater {

    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_response;
    private $access_token;

    /**
     * Constructor
     *
     * @param string $plugin_file Full path to the main plugin file
     */
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->slug = plugin_basename($plugin_file);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    /**
     * Set GitHub repository info
     *
     * @param string $username GitHub username
     * @param string $repo Repository name
     */
    public function set_repository($username, $repo) {
        $this->username = $username;
        $this->repo = $repo;
    }

    /**
     * Set access token for private repos
     *
     * @param string $token GitHub access token
     */
    public function set_access_token($token) {
        $this->access_token = $token;
    }

    /**
     * Get plugin data
     */
    private function get_plugin_data() {
        if (empty($this->plugin_data)) {
            $this->plugin_data = get_plugin_data($this->plugin_file);
        }
        return $this->plugin_data;
    }

    /**
     * Get GitHub release info
     */
    private function get_github_release() {
        if (!empty($this->github_response)) {
            return $this->github_response;
        }

        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";

        $args = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ]
        ];

        if (!empty($this->access_token)) {
            $args['headers']['Authorization'] = "token {$this->access_token}";
        }

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            error_log('WA Greeting Chat - GitHub API Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('WA Greeting Chat - GitHub API Response Code: ' . $response_code);
            error_log('WA Greeting Chat - GitHub API Response: ' . wp_remote_retrieve_body($response));
            return false;
        }

        $this->github_response = json_decode(wp_remote_retrieve_body($response));

        return $this->github_response;
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient Update transient
     * @return object Modified transient
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_github_release();

        if (!$release) {
            error_log('WA Greeting Chat - No GitHub release found');
            return $transient;
        }

        $plugin_data = $this->get_plugin_data();
        $current_version = $plugin_data['Version'];

        // Remove 'v' prefix from tag if present
        $latest_version = ltrim($release->tag_name, 'v');

        error_log('WA Greeting Chat - Current: ' . $current_version . ', Latest: ' . $latest_version . ', Tag: ' . $release->tag_name);

        if (version_compare($latest_version, $current_version, '>')) {
            // Find the zip asset
            $download_url = $release->zipball_url;

            // Check if there's a specific zip asset attached
            if (!empty($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_url = $asset->browser_download_url;
                        break;
                    }
                }
            }

            error_log('WA Greeting Chat - Update found! Downloading from: ' . $download_url);

            $transient->response[$this->slug] = (object) [
                'slug' => dirname($this->slug),
                'new_version' => $latest_version,
                'url' => $release->html_url,
                'package' => $download_url,
                'icons' => [],
                'banners' => [],
                'tested' => get_bloginfo('version'),
                'requires_php' => '7.4',
            ];
        } else {
            error_log('WA Greeting Chat - No update needed (current >= latest)');
        }

        return $transient;
    }

    /**
     * Plugin information for the update details popup
     *
     * @param false|object|array $result
     * @param string $action
     * @param object $args
     * @return object|false
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (dirname($this->slug) !== $args->slug) {
            return $result;
        }

        $release = $this->get_github_release();

        if (!$release) {
            return $result;
        }

        $plugin_data = $this->get_plugin_data();

        return (object) [
            'name' => $plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => ltrim($release->tag_name, 'v'),
            'author' => $plugin_data['AuthorName'],
            'homepage' => $plugin_data['PluginURI'],
            'short_description' => $plugin_data['Description'],
            'sections' => [
                'description' => $plugin_data['Description'],
                'changelog' => $this->parse_changelog($release->body),
            ],
            'download_link' => $release->zipball_url,
            'last_updated' => $release->published_at,
            'tested' => get_bloginfo('version'),
            'requires_php' => '7.4',
        ];
    }

    /**
     * Parse markdown changelog to HTML
     *
     * @param string $body Release body/notes
     * @return string HTML formatted changelog
     */
    private function parse_changelog($body) {
        if (empty($body)) {
            return '<p>No changelog provided.</p>';
        }

        // Basic markdown to HTML conversion
        $body = esc_html($body);
        $body = nl2br($body);
        $body = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body);
        $body = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $body);
        $body = preg_replace('/^- (.+)$/m', '<li>$1</li>', $body);
        $body = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $body);

        return $body;
    }

    /**
     * Rename folder after install to match expected plugin folder name
     *
     * @param bool $response
     * @param array $hook_extra
     * @param array $result
     * @return array
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        // Check if this is our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $result;
        }

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->slug);

        // Move from downloaded folder to proper plugin folder
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        // Re-activate plugin if it was active
        if (is_plugin_active($this->slug)) {
            activate_plugin($this->slug);
        }

        return $result;
    }
}
