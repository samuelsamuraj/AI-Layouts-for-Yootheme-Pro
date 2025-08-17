<?php
/**
 * AI Layout Settings Class
 * 
 * Handles plugin settings page and admin menu integration
 */

if (!defined('ABSPATH')) exit;

class AI_Layout_Settings {
    
    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_init', [self::class, 'settings']);
        add_action('admin_menu', [self::class, 'settingsMenu']);
        add_filter('plugin_action_links_ai-layout-for-yootheme/ai-layout-for-yootheme.php', [self::class, 'settingsLink']);
    }
    
    /**
     * Register settings
     */
    public static function settings() {
        register_setting('ai_layout_settings', 'ai_layout_openai_api_key');
        register_setting('ai_layout_settings', 'ai_layout_model');
        register_setting('ai_layout_settings', 'ai_layout_unsplash_access_key');
        register_setting('ai_layout_settings', 'ai_layout_pexels_api_key');
        
        add_settings_section('ai_layout_api', 'API Configuration', [self::class, 'apiSection'], 'ai_layout_settings');
        
        add_settings_field('ai_layout_openai_api_key', 'OpenAI API Key', [self::class, 'openaiField'], 'ai_layout_settings', 'ai_layout_api');
        add_settings_field('ai_layout_model', 'OpenAI Model', [self::class, 'modelField'], 'ai_layout_settings', 'ai_layout_api');
        add_settings_field('ai_layout_unsplash_access_key', 'Unsplash Access Key', [self::class, 'unsplashField'], 'ai_layout_settings', 'ai_layout_api');
        add_settings_field('ai_layout_pexels_api_key', 'Pexels API Key', [self::class, 'pexelsField'], 'ai_layout_settings', 'ai_layout_api');
    }
    
    /**
     * Add settings menu
     */
    public static function settingsMenu() {
        add_options_page(
            'AI Layout Settings',
            'AI Layout',
            'manage_options',
            'ai_layout_settings',
            [self::class, 'settingsPage']
        );
    }
    
    /**
     * Add settings link to plugin actions
     */
    public static function settingsLink($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=ai_layout_settings') . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        
        // Add Check for Updates link
        $update_link = '<a href="#" id="ai-layout-check-updates-link">' . __('Check for Updates') . '</a>';
        $links[] = $update_link;
        
        return $links;
    }
    
    /**
     * API Section description
     */
    public static function apiSection() {
        echo '<p>Configure API keys for AI Layout functionality. OpenAI API key is required for layout generation.</p>';
    }
    
    /**
     * OpenAI API Key field
     */
    public static function openaiField() {
        $value = get_option('ai_layout_openai_api_key', '');
        echo '<input type="password" name="ai_layout_openai_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></p>';
    }
    
    /**
     * OpenAI Model field
     */
    public static function modelField() {
        $value = get_option('ai_layout_model', 'gpt-4o-mini');
        echo '<input type="text" name="ai_layout_model" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">OpenAI model to use (e.g., gpt-4o-mini, gpt-4o)</p>';
    }
    
    /**
     * Unsplash Access Key field
     */
    public static function unsplashField() {
        $value = get_option('ai_layout_unsplash_access_key', '');
        echo '<input type="text" name="ai_layout_unsplash_access_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Optional: Get your access key from <a href="https://unsplash.com/developers" target="_blank">Unsplash Developers</a></p>';
    }
    
    /**
     * Pexels API Key field
     */
    public static function pexelsField() {
        $value = get_option('ai_layout_pexels_api_key', '');
        echo '<input type="text" name="ai_layout_pexels_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Optional: Get your API key from <a href="https://www.pexels.com/api/" target="_blank">Pexels API</a></p>';
    }
    
    /**
     * Settings page content
     */
    public static function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && isset($_POST['ai_layout_nonce'])) {
            if (wp_verify_nonce($_POST['ai_layout_nonce'], 'ai_layout_settings')) {
                // Save settings
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
        
        // Display settings form
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Plugin Status -->
            <div class="notice notice-info inline">
                <p><strong>Plugin Status:</strong> 
                <span id="ai-layout-status">Active</span> | 
                <a href="<?php echo admin_url('admin.php?page=ai-layout'); ?>">Open AI Layout Generator</a></p>
            </div>
            
            <!-- Update Check -->
            <div class="notice notice-info inline">
                <p><strong>Plugin Updates:</strong> 
                <a href="#" id="ai-layout-manual-update-check" class="button button-secondary">Check for Updates Now</a> 
                <span id="ai-layout-update-status"></span></p>
            </div>
            
            <!-- YOOtheme Version Info -->
            <div class="notice notice-info inline">
                <p><strong>YOOtheme Versions:</strong> 
                <span id="ai-layout-yootheme-versions">Loading...</span> | 
                <a href="#" id="ai-layout-refresh-versions" class="button button-small">Refresh</a></p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_layout_settings', 'ai_layout_nonce'); ?>
                <?php settings_fields('ai_layout_settings'); ?>
                <?php do_settings_sections('ai_layout_settings'); ?>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Check for updates
            $('#ai-layout-manual-update-check').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $status = $('#ai-layout-update-status');
                
                $button.prop('disabled', true).text('Checking...');
                $status.html('');
                
                $.post(ajaxurl, {
                    action: 'ai_layout_check_updates',
                    nonce: '<?php echo wp_create_nonce('ai_layout_check_updates'); ?>'
                }, function(response) {
                    if (response.success) {
                        $status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $status.html('<span style="color: red;">✗ ' + response.data + '</span>');
                    }
                }).fail(function() {
                    $status.html('<span style="color: red;">✗ Update check failed</span>');
                }).always(function() {
                    $button.prop('disabled', false).text('Check for Updates Now');
                });
            });
            
            // Refresh YOOtheme versions
            $('#ai-layout-refresh-versions').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $versions = $('#ai-layout-yootheme-versions');
                
                $button.prop('disabled', true).text('Refreshing...');
                $versions.html('Refreshing...');
                
                $.post(ajaxurl, {
                    action: 'ai_layout_refresh_versions',
                    nonce: '<?php echo wp_create_nonce('ai_layout_refresh_versions'); ?>'
                }, function(response) {
                    if (response.success) {
                        var versions = response.data;
                        $versions.html('Pro: <code>' + versions.pro + '</code> | Essentials: <code>' + versions.essentials + '</code>');
                    } else {
                        $versions.html('Failed to load versions');
                    }
                }).fail(function() {
                    $versions.html('Failed to load versions');
                }).always(function() {
                    $button.prop('disabled', false).text('Refresh');
                });
            });
            
            // Load YOOtheme versions on page load
            $('#ai-layout-refresh-versions').trigger('click');
        });
        </script>
        <?php
    }
}
