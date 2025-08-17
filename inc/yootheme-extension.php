<?php
/**
 * YOOtheme Extension Loader for AI Layout
 * 
 * This file hooks into YOOtheme's extension system to load our custom extension
 */

if (!defined('ABSPATH')) exit;

class AI_Layout_YOOtheme_Extension {
    
    public function __construct() {
        add_action('after_setup_theme', array($this, 'init'));
        add_filter('yootheme_extensions', array($this, 'register_extension'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_extension_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_extension_assets'));
    }
    
    /**
     * Initialize the extension
     */
    public function init() {
        // Check if YOOtheme is active
        if (!$this->is_yootheme_active()) {
            return;
        }
        
        // Add customizer support
        add_action('customize_register', array($this, 'customize_register'));
    }
    
    /**
     * Check if YOOtheme is active
     */
    private function is_yootheme_active() {
        $theme = wp_get_theme();
        return strpos($theme->get('Name'), 'YOOtheme') !== false || 
               strpos($theme->get('Template'), 'yootheme') !== false;
    }
    
    /**
     * Register extension with YOOtheme
     */
    public function register_extension($extensions) {
        $extensions['ai-layout'] = array(
            'path' => AI_LAYOUT_PLUGIN_DIR . 'extensions/ai-layout',
            'url' => AI_LAYOUT_PLUGIN_URL . 'extensions/ai-layout'
        );
        return $extensions;
    }
    
    /**
     * Enqueue extension assets
     */
    public function enqueue_extension_assets() {
        if (is_customize_preview() || is_admin()) {
            wp_enqueue_script(
                'ai-layout-extension',
                AI_LAYOUT_PLUGIN_URL . 'extensions/ai-layout/dist/index.js',
                array('jquery'),
                AI_LAYOUT_VERSION,
                true
            );
            
            wp_localize_script('ai-layout-extension', 'AI_LAYOUT_EXTENSION', array(
                'restUrl' => esc_url_raw(rest_url('ai-layout/v1')),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'ajaxNonce' => wp_create_nonce('ai_layout_check_updates')
            ));
        }
    }
    
    /**
     * Customize register hook
     */
    public function customize_register($wp_customize) {
        // Add AI Layout section
        $wp_customize->add_section('ai_layout_section', array(
            'title' => __('AI Layout Generator', 'ai-layout'),
            'priority' => 100,
            'panel' => 'yootheme_panel'
        ));
        
        // Add AI Layout setting
        $wp_customize->add_setting('ai_layout_enabled', array(
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Add AI Layout control
        $wp_customize->add_control('ai_layout_enabled', array(
            'label' => __('Enable AI Layout Generator', 'ai-layout'),
            'section' => 'ai_layout_section',
            'type' => 'checkbox'
        ));
    }
}

// Initialize the extension loader
new AI_Layout_YOOtheme_Extension();
