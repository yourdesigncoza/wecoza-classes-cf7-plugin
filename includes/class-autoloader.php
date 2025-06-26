<?php
/**
 * Autoloader for WeCoza Classes CF7 Plugin
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WeCoza CF7 Autoloader class
 */
class WeCoza_CF7_Autoloader {
    
    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }
    
    /**
     * Autoload classes
     *
     * @param string $class_name Class name to load
     */
    public static function autoload($class_name) {
        // Only handle our classes
        if (strpos($class_name, 'WeCoza_CF7_') !== 0) {
            return;
        }
        
        // Convert class name to file path
        $class_file = self::get_class_file($class_name);
        
        if ($class_file && file_exists($class_file)) {
            require_once $class_file;
        }
    }
    
    /**
     * Get class file path
     *
     * @param string $class_name Class name
     * @return string|false File path or false if not found
     */
    private static function get_class_file($class_name) {
        // Remove prefix
        $class_name = str_replace('WeCoza_CF7_', '', $class_name);
        
        // Convert to lowercase and replace underscores with hyphens
        $class_name = strtolower(str_replace('_', '-', $class_name));
        
        // Build file path
        $file_path = WECOZA_CF7_PLUGIN_DIR . 'includes/class-' . $class_name . '.php';
        
        return $file_path;
    }
}
