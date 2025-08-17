<?php
/**
 * Debug Script for AI Layout Plugin
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
        } else {
            echo "Extension loader class NOT found\n";
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
