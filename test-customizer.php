<?php
/**
 * Test Script for AI Layout Customizer Integration
 * Place this in your WordPress root directory temporarily
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate WordPress environment
define('ABSPATH', dirname(__FILE__) . '/');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/');
define('WP_PLUGIN_URL', 'http://localhost/wp-content/plugins/');

// Include plugin file to test
$plugin_file = WP_PLUGIN_DIR . 'ai-layout-for-yootheme/ai-layout-for-yootheme.php';

if (file_exists($plugin_file)) {
    echo "Plugin file exists: $plugin_file\n";
    
    try {
        // Test including the main plugin file
        include_once $plugin_file;
        echo "Plugin file included successfully\n";
        
        // Test if main class exists
        if (class_exists('AI_Layout_Plugin')) {
            echo "Main plugin class exists\n";
        } else {
            echo "Main plugin class NOT found\n";
        }
        
        // Test if extension loader exists
        if (class_exists('AI_Layout_YOOtheme_Extension')) {
            echo "Extension loader class exists\n";
            
            // Test theme detection
            $theme = wp_get_theme();
            echo "Current theme: " . $theme->get('Name') . "\n";
            echo "Theme template: " . $theme->get('Template') . "\n";
            
            // Test YOOtheme detection
            $is_yoo = strpos($theme->get('Name'), 'YOOtheme') !== false || 
                      strpos($theme->get('Template'), 'yootheme') !== false ||
                      strpos($theme->get('Name'), 'YOO') !== false ||
                      strpos($theme->get('Template'), 'yoo') !== false;
            
            echo "YOOtheme detected: " . ($is_yoo ? 'Yes' : 'No') . "\n";
            
        } else {
            echo "Extension loader class NOT found\n";
        }
        
        // Test if constants are defined
        if (defined('AI_LAYOUT_PLUGIN_DIR')) {
            echo "AI_LAYOUT_PLUGIN_DIR: " . AI_LAYOUT_PLUGIN_DIR . "\n";
        }
        
        if (defined('AI_LAYOUT_PLUGIN_URL')) {
            echo "AI_LAYOUT_PLUGIN_URL: " . AI_LAYOUT_PLUGIN_URL . "\n";
        }
        
        // Test extension directory
        $extension_dir = AI_LAYOUT_PLUGIN_DIR . 'extensions/ai-layout';
        if (is_dir($extension_dir)) {
            echo "Extension directory exists: $extension_dir\n";
            
            // Test built extension
            $built_file = $extension_dir . '/dist/index.js';
            if (file_exists($built_file)) {
                echo "Built extension exists: $built_file\n";
                echo "File size: " . filesize($built_file) . " bytes\n";
            } else {
                echo "Built extension NOT found: $built_file\n";
            }
        } else {
            echo "Extension directory NOT found: $extension_dir\n";
        }
        
    } catch (Exception $e) {
        echo "Error including plugin: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "Fatal error including plugin: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
    
} else {
    echo "Plugin file NOT found: $plugin_file\n";
}

echo "\nDebug complete.\n";
