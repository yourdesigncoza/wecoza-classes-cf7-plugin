<?php
/**
 * Plugin Name: WeCoza Classes CF7 Plugin
 * Plugin URI: https://yourdesign.co.za
 * Description: A WordPress plugin that dynamically populates Contact Form 7 select fields with client data from PostgreSQL database.
 * Version: 1.0.0
 * Author: Your Design Co
 * Author URI: https://yourdesign.co.za
 * Text Domain: wecoza-classes-cf7
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WECOZA_CF7_VERSION', '1.0.0');
define('WECOZA_CF7_PLUGIN_FILE', __FILE__);
define('WECOZA_CF7_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WECOZA_CF7_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WECOZA_CF7_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WeCoza_Classes_CF7_Plugin {
    
    /**
     * Plugin instance
     *
     * @var WeCoza_Classes_CF7_Plugin
     */
    private static $instance = null;
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = WECOZA_CF7_VERSION;
    
    /**
     * Get plugin instance
     *
     * @return WeCoza_Classes_CF7_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Initialize plugin features
        add_action('init', array($this, 'init_features'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Autoloader
        require_once WECOZA_CF7_PLUGIN_DIR . 'includes/class-autoloader.php';
        WeCoza_CF7_Autoloader::register();
        
        // Core classes
        require_once WECOZA_CF7_PLUGIN_DIR . 'includes/class-database-service.php';
        require_once WECOZA_CF7_PLUGIN_DIR . 'includes/class-client-service.php';
        require_once WECOZA_CF7_PLUGIN_DIR . 'includes/class-site-service.php';
        require_once WECOZA_CF7_PLUGIN_DIR . 'includes/class-cf7-integration.php';

        // Load test file in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            require_once WECOZA_CF7_PLUGIN_DIR . 'test-cf7-integration.php';
        }
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Plugin loaded hook
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
    }
    
    /**
     * Initialize plugin features
     */
    public function init_features() {
        // Check if Contact Form 7 is active
        if (!$this->is_cf7_active()) {
            add_action('admin_notices', array($this, 'cf7_missing_notice'));
            return;
        }
        
        // Initialize CF7 integration
        WeCoza_CF7_Integration::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Test database connection
        try {
            $db_service = WeCoza_Database_Service::get_instance();
            if (!$db_service->test_connection()) {
                wp_die(
                    __('Database connection failed. Please check your PostgreSQL settings.', 'wecoza-classes-cf7'),
                    __('Plugin Activation Error', 'wecoza-classes-cf7'),
                    array('back_link' => true)
                );
            }
        } catch (Exception $e) {
            wp_die(
                sprintf(__('Database connection error: %s', 'wecoza-classes-cf7'), $e->getMessage()),
                __('Plugin Activation Error', 'wecoza-classes-cf7'),
                array('back_link' => true)
            );
        }
        
        // Set activation flag
        update_option('wecoza_cf7_plugin_activated', true);
        
        // Log activation
        error_log('WeCoza Classes CF7 Plugin activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any cached data
        wp_cache_flush();
        
        // Remove activation flag
        delete_option('wecoza_cf7_plugin_activated');
        
        // Log deactivation
        error_log('WeCoza Classes CF7 Plugin deactivated');
    }
    
    /**
     * Plugins loaded hook
     */
    public function plugins_loaded() {
        // Load text domain
        load_plugin_textdomain(
            'wecoza-classes-cf7',
            false,
            dirname(WECOZA_CF7_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Check if Contact Form 7 is active
     *
     * @return bool
     */
    private function is_cf7_active() {
        return class_exists('WPCF7');
    }
    
    /**
     * Display notice when CF7 is missing
     */
    public function cf7_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    __('WeCoza Classes CF7 Plugin requires %s to be installed and activated.', 'wecoza-classes-cf7'),
                    '<strong>Contact Form 7</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}

/**
 * Initialize the plugin
 */
function wecoza_cf7_plugin() {
    return WeCoza_Classes_CF7_Plugin::get_instance();
}

// Start the plugin
wecoza_cf7_plugin();
