<?php
/**
 * Plugin Name: AI Layout for YOOtheme
 * Description: Generate, review, and compile AI-driven layouts to YOOtheme Pro JSON inside WordPress.
 * Version: 0.3.0
 * Author: <a href="https://samuraj.dk">Samuraj ApS</a>
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('AI_LAYOUT_PLUGIN_FILE', __FILE__);
define('AI_LAYOUT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_LAYOUT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Plugin constants
define('AI_LAYOUT_VERSION', '0.3.0');
define('AI_LAYOUT_RATE_LIMIT', 10); // Max requests per hour
define('AI_LAYOUT_CACHE_DURATION', 12 * HOUR_IN_SECONDS); // 12 hours
define('AI_LAYOUT_API_TIMEOUT', 60); // API timeout in seconds

// YOOtheme version constants
define('AI_LAYOUT_YOOTHEME_VERSION', '4.5.24');
define('AI_LAYOUT_YOOESSENTIALS_VERSION', '2.4.4');

require_once AI_LAYOUT_PLUGIN_DIR . 'inc/rest.php';
require_once AI_LAYOUT_PLUGIN_DIR . 'inc/plugin-update-checker.php';
require_once AI_LAYOUT_PLUGIN_DIR . 'inc/yootheme-extension.php';

// Initialize update checker
new AI_Layout_Update_Checker(
    __FILE__,
    'https://api.github.com/repos/samuelsamuraj/AI-Layouts-for-Yootheme-Pro'
);

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

  // Handle form submission with nonce validation
  if (isset($_POST['submit']) && isset($_POST['ai_layout_nonce'])) {
    if (wp_verify_nonce($_POST['ai_layout_nonce'], 'ai_layout_settings')) {
      // Process form submission
      if (isset($_POST['ai_layout_openai_api_key'])) {
        update_option('ai_layout_openai_api_key', sanitize_text_field($_POST['ai_layout_openai_api_key']));
      }
      if (isset($_POST['ai_layout_model'])) {
        update_option('ai_layout_model', sanitize_text_field($_POST['ai_layout_model']));
      }
      if (isset($_POST['ai_layout_unsplash_access_key'])) {
        update_option('ai_layout_unsplash_access_key', sanitize_text_field($_POST['ai_layout_unsplash_access_key']));
      }
      if (isset($_POST['ai_layout_pexels_api_key'])) {
        update_option('ai_layout_pexels_api_key', sanitize_text_field($_POST['ai_layout_pexels_api_key']));
      }

      echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    } else {
      echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
    }
  }

  echo '<div class="wrap"><h1>AI Layout</h1><p>Analyse → Wireframe → Compile (YOOtheme JSON)</p>';
  
  // Manual update check section
  echo '<div class="notice notice-info inline">';
  echo '<p><strong>Plugin Updates:</strong> ';
  echo '<a href="#" id="ai-layout-manual-update-check" class="button button-secondary">Check for Updates Now</a> ';
  echo '<span id="ai-layout-update-status"></span></p>';
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
  
  echo '<form method="post" action="">';
  wp_nonce_field('ai_layout_settings', 'ai_layout_nonce');
  settings_fields('ai_layout');
  do_settings_sections('ai_layout');
  submit_button('Save API Settings');
  echo '</form><hr/><div id="ai-layout-app"></div></div>';
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

add_action('admin_init', function(){
  add_settings_section('ai_layout_api', 'API', function(){ echo '<p>Configure API keys (OpenAI required for ML; Unsplash/Pexels optional for images).</p>'; }, 'ai_layout');

  add_settings_field('ai_layout_openai_api_key', 'OpenAI API key', function(){
    $v = esc_attr(get_option('ai_layout_openai_api_key', ''));
    echo '<input type="password" name="ai_layout_openai_api_key" value="'.$v.'" class="regular-text" />';
  }, 'ai_layout','ai_layout_api');

  add_settings_field('ai_layout_model', 'Model', function(){
    $v = esc_attr(get_option('ai_layout_model', 'gpt-4.1-mini'));
    echo '<input type="text" name="ai_layout_model" value="'.$v.'" class="regular-text" />';
  }, 'ai_layout','ai_layout_api');

  add_settings_field('ai_layout_unsplash_access_key', 'Unsplash Access Key', function(){
    $v = esc_attr(get_option('ai_layout_unsplash_access_key', ''));
    echo '<input type="text" name="ai_layout_unsplash_access_key" value="'.$v.'" class="regular-text" />';
  }, 'ai_layout','ai_layout_api');

  add_settings_field('ai_layout_pexels_api_key', 'Pexels API Key', function(){
    $v = esc_attr(get_option('ai_layout_pexels_api_key', ''));
    echo '<input type="text" name="ai_layout_pexels_api_key" value="'.$v.'" class="regular-text" />';
  }, 'ai_layout','ai_layout_api');


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
