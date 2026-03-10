<?php
/**
 * Autoloader class
 *
 * @package GML_Translate
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * GML Autoloader class
 */
class GML_Autoloader {
    
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload classes
     *
     * @param string $class Class name
     */
    public static function autoload($class) {
        // Check if class starts with GML_
        if (strpos($class, 'GML_') !== 0) {
            return;
        }
        
        // Convert class name to file name
        // GML_Output_Buffer -> class-output-buffer.php
        // GML_HTML_Parser -> class-html-parser.php
        $class_name = substr($class, 4); // Remove 'GML_'
        $class_file = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        
        // Possible directories
        $directories = [
            GML_PLUGIN_DIR . 'includes/',
            GML_PLUGIN_DIR . 'admin/',
        ];
        
        // Try to load from each directory
        foreach ($directories as $directory) {
            $file = $directory . $class_file;
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}
