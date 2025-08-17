<?php
/**
 * Plugin Name: AI Layout for YOOtheme
 * Plugin URI: https://github.com/samuelsamuraj/AI-Layouts-for-Yootheme-Pro
 * Description: Generate, review, and compile AI-driven layouts to YOOtheme Pro JSON inside WordPress.
 * Version: 0.3.3
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: <a href="https://samuraj.dk">Samuraj ApS</a>
 * Author URI: https://samuraj.dk
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: ai-layout
 * Domain Path: /languages
 * Network: false
 */

if (!defined('ABSPATH')) exit;

define('AI_LAYOUT_PLUGIN_FILE', __FILE__);
define('AI_LAYOUT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_LAYOUT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin constants
define('AI_LAYOUT_VERSION', '0.3.3');
define('AI_LAYOUT_RATE_LIMIT', 10); // Max requests per hour
define('AI_LAYOUT_CACHE_DURATION', 12 * HOUR_IN_SECONDS); // 12 hours
define('AI_LAYOUT_API_TIMEOUT', 60); // API timeout in seconds

// YOOtheme version constants
define('AI_LAYOUT_YOOTHEME_VERSION', '4.5.24');
define('AI_LAYOUT_YOOESSENTIALS_VERSION', '2.4.4');

require_once AI_LAYOUT_PLUGIN_DIR . 'inc/rest.php';
require_once AI_LAYOUT_PLUGIN_DIR . 'inc/plugin-update-checker.php';
require_once AI_LAYOUT_PLUGIN_DIR . 'inc/yootheme-extension.php';
require_once AI_LAYOUT_PLUGIN_DIR . 'inc/settings.php';

// Initialize update checker
new AI_Layout_Update_Checker(
    __FILE__,
    'https://api.github.com/repos/samuelsamuraj/AI-Layouts-for-Yootheme-Pro'
);

// Initialize settings
AI_Layout_Settings::init();

// Add security headers
add_action('admin_init', function() {
  if (isset($_GET['page']) && $_GET['page'] === 'ai-layout') {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
  }
});

// Disable direct file access
add_action('init', function() {
  if (isset($_GET['ai_layout_direct_access'])) {
    wp_die('Direct access not allowed');
  }
});

add_action('admin_menu', function() {
  add_menu_page(
    'AI Layout', 'AI Layout', 'edit_theme_options', 'ai-layout', 'ai_layout_admin_page', 'dashicons-layout', 61
  );
});

function ai_layout_admin_page() {
  // Check user capabilities
  if (!current_user_can('edit_theme_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  echo '<div class="wrap"><h1>AI Layout Generator</h1>';
  echo '<p>Generate, review, and compile AI-driven layouts to YOOtheme Pro JSON.</p>';
  
  // Quick actions
  echo '<div class="notice notice-info inline">';
  echo '<p><strong>Quick Actions:</strong> ';
  echo '<a href="' . admin_url('options-general.php?page=ai_layout_settings') . '" class="button button-primary">Configure API Keys</a> ';
  echo '<a href="#" id="ai-layout-manual-update-check" class="button button-secondary">Check for Updates</a></p>';
  echo '</div>';
  
  // YOOtheme version info
  $versions = ai_layout_get_cached_yootheme_versions();
  echo '<div class="notice notice-info inline">';
  echo '<p><strong>YOOtheme Versions:</strong> ';
  echo 'Pro: <code>' . esc_html($versions['pro']) . '</code> | ';
  echo 'Essentials: <code>' . esc_html($versions['essentials']) . '</code> | ';
  echo '<a href="#" id="ai-layout-refresh-versions" class="button button-small">Refresh</a>';
  echo '<small style="margin-left: 10px;">Detected: ' . esc_html($versions['detected_at']) . '</small>';
  echo '</p>';
  echo '</div>';
  
  // Main app container
  echo '<div id="ai-layout-app"></div>';
  
  // Instructions
  echo '<div class="notice notice-info">';
  echo '<h3>How to Use:</h3>';
  echo '<ol>';
  echo '<li><strong>Configure API Keys:</strong> Go to <a href="' . admin_url('options-general.php?page=ai_layout_settings') . '">Settings → AI Layout</a> to add your OpenAI API key</li>';
  echo '<li><strong>Open YOOtheme Customizer:</strong> Go to any page and click "Customize" to access the AI Layout panel</li>';
  echo '<li><strong>Generate Layouts:</strong> Use the AI Layout panel in the customizer to create layouts with natural language</li>';
  echo '</ol>';
  echo '</div>';
  
  echo '</div>';
}

add_action('admin_enqueue_scripts', function($hook){
  if ($hook !== 'toplevel_page_ai-layout') return;
  wp_enqueue_style('ai-layout-admin', AI_LAYOUT_PLUGIN_URL . 'assets/admin.css', [], AI_LAYOUT_VERSION);
  wp_enqueue_script('ai-layout-admin', AI_LAYOUT_PLUGIN_URL . 'assets/admin.js', ['wp-api'], AI_LAYOUT_VERSION, true);
  wp_localize_script('ai-layout-admin', 'AI_LAYOUT', [
    'restUrl' => esc_url_raw(rest_url('ai-layout/v1')),
    'nonce'   => wp_create_nonce('wp_rest'),
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'ajaxNonce' => wp_create_nonce('ai_layout_check_updates')
  ]);
});

// AJAX handler for refreshing YOOtheme versions
add_action('wp_ajax_ai_layout_refresh_versions', function() {
  if (!current_user_can('edit_theme_options')) {
    wp_die('Insufficient permissions');
  }
  
  $versions = ai_layout_refresh_yootheme_versions();
  wp_send_json_success($versions);
});



// Cleanup on uninstall
register_uninstall_hook(__FILE__, 'ai_layout_uninstall');

function ai_layout_uninstall() {
  // Remove all plugin options
  delete_option('ai_layout_openai_api_key');
  delete_option('ai_layout_model');
  delete_option('ai_layout_unsplash_access_key');
  delete_option('ai_layout_pexels_api_key');
  delete_option('ai_layout_library');
  
  // Remove uploaded files
  $upload_dir = wp_upload_dir();
  $ai_layout_dir = trailingslashit($upload_dir['basedir']) . 'ai-layout';
  if (is_dir($ai_layout_dir)) {
    array_map('unlink', glob("$ai_layout_dir/*.*"));
    rmdir($ai_layout_dir);
  }
  
  // Clear rate limiting transients
  global $wpdb;
  $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_layout_rate_limit_%'");
  $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_layout_rate_limit_%'");
  
  // Clear version cache
  delete_transient('ai_layout_yootheme_versions');
}
