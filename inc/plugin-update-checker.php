<?php
/**
 * Plugin Update Checker Library
 * 
 * This is a simplified version of the Plugin Update Checker library
 * For the full version, visit: https://github.com/YahnisElsts/plugin-update-checker
 */

if (!defined('ABSPATH')) exit;

class AI_Layout_Update_Checker {
    private $plugin_file;
    private $plugin_slug;
    private $github_url;
    private $github_token;
    private $check_period = 12; // hours
    private $is_private = false;
    
    public function __construct($plugin_file, $github_url, $github_token = '', $is_private = false) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = basename($plugin_file, '.php');
        $this->github_url = $github_url;
        $this->github_token = $github_token;
        $this->is_private = $is_private;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
    }
    
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        $plugin_data = get_plugin_data($this->plugin_file);
        $current_version = $plugin_data['Version'];
        
        $latest_release = $this->get_latest_release();
        if (!$latest_release) {
            return $transient;
        }
        
        if (version_compare($latest_release['version'], $current_version, '>')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'new_version' => $latest_release['version'],
                'url' => $latest_release['url'],
                'package' => $latest_release['download_url'],
                'requires' => '6.0',
                'requires_php' => '7.4',
                'tested' => '6.4',
                'last_updated' => $latest_release['published_at'],
                'sections' => array(
                    'description' => $latest_release['description'],
                    'changelog' => $latest_release['changelog']
                )
            );
        }
        
        return $transient;
    }
    
    private function get_latest_release() {
        $cache_key = 'ai_layout_github_release';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $api_url = $this->github_url . '/releases/latest';
        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/AI-Layout-Plugin'
            ),
            'timeout' => 15
        );
        
        // For private repos, token is required
        if ($this->is_private && empty($this->github_token)) {
            error_log('AI Layout: GitHub token required for private repository access');
            return false;
        }
        
        if (!empty($this->github_token)) {
            $args['headers']['Authorization'] = 'Bearer ' . $this->github_token;
        }
        
        $response = wp_remote_get($api_url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || !isset($data['tag_name'])) {
            return false;
        }
        
        $release = array(
            'version' => ltrim($data['tag_name'], 'v'),
            'url' => $data['html_url'],
            'download_url' => $data['zipball_url'],
            'published_at' => $data['published_at'],
            'description' => $data['body'] ?? '',
            'changelog' => $data['body'] ?? ''
        );
        
        // Cache for 12 hours
        set_transient($cache_key, $release, $this->check_period * HOUR_IN_SECONDS);
        
        return $release;
    }
    
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $latest_release = $this->get_latest_release();
        if (!$latest_release) {
            return $result;
        }
        
        $result = new stdClass();
        $result->name = 'AI Layout for YOOtheme';
        $result->slug = $this->plugin_slug;
        $result->version = $latest_release['version'];
        $result->last_updated = $latest_release['published_at'];
        $result->requires = '6.0';
        $result->requires_php = '7.4';
        $result->tested = '6.4';
        $result->download_link = $latest_release['download_url'];
        $result->sections = array(
            'description' => $latest_release['description'],
            'changelog' => $latest_release['changelog']
        );
        
        return $result;
    }
    
    public function post_install($response, $hook_extra, $result) {
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_slug) {
            // Clear cache after update
            delete_transient('ai_layout_github_release');
        }
        return $response;
    }
}
