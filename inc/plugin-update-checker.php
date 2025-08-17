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
    
    public function __construct($plugin_file, $github_url) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = basename($plugin_file, '.php');
        $this->github_url = $github_url;
        $this->github_token = '';
        $this->is_private = false;
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
        
        // Add manual update check action
        add_action('wp_ajax_ai_layout_check_updates', array($this, 'manual_update_check'));
        add_action('admin_notices', array($this, 'admin_notices'));
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
        
        // Public repository - no token required
        
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
    
    /**
     * Manual update check via AJAX
     */
    public function manual_update_check() {
        // Check permissions
        if (!current_user_can('update_plugins')) {
            wp_die('Insufficient permissions');
        }
        
        // Clear cache to force fresh check
        delete_transient('ai_layout_github_release');
        
        // Force update check
        $transient = get_site_transient('update_plugins');
        if ($transient) {
            unset($transient->response[$this->plugin_slug]);
            set_site_transient('update_plugins', $transient);
        }
        
        // Trigger update check
        $this->check_for_updates($transient);
        
        // Return result
        wp_send_json_success(array(
            'message' => 'Update check completed',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Admin notices for manual update check
     */
    public function admin_notices() {
        if (isset($_GET['page']) && $_GET['page'] === 'ai-layout') {
            echo '<div class="notice notice-info">';
            echo '<p><strong>AI Layout:</strong> <a href="#" id="ai-layout-check-updates">Check for updates now</a> | ';
            echo '<a href="' . admin_url('plugins.php') . '">View all plugins</a></p>';
            echo '</div>';
            
            // Add JavaScript for manual update check
            echo '<script>
            jQuery(document).ready(function($) {
                $("#ai-layout-check-updates").on("click", function(e) {
                    e.preventDefault();
                    var $link = $(this);
                    $link.text("Checking...").prop("href", "#");
                    
                    $.post(ajaxurl, {
                        action: "ai_layout_check_updates",
                        _ajax_nonce: "' . wp_create_nonce('ai_layout_check_updates') . '"
                    }, function(response) {
                        if (response.success) {
                            $link.text("Update check completed").css("color", "green");
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $link.text("Update check failed").css("color", "red");
                        }
                    }).fail(function() {
                        $link.text("Update check failed").css("color", "red");
                    });
                });
            });
            </script>';
        }
    }
}
