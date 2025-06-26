<?php
/**
 * Plugin uninstall handler
 *
 * @package WeCozaClasses
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WeCoza Classes Plugin Uninstaller
 */
class WeCoza_Classes_Uninstaller {

    /**
     * Uninstall the plugin
     *
     * @since 1.0.0
     */
    public static function uninstall() {
        // Check if user has permission to uninstall
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Check if this is a multisite and if so, run for each site
        if (is_multisite()) {
            self::uninstall_multisite();
        } else {
            self::uninstall_single_site();
        }

        // Log uninstall
        error_log('WeCoza Classes Plugin uninstalled');
    }

    /**
     * Uninstall for multisite
     *
     * @since 1.0.0
     */
    private static function uninstall_multisite() {
        global $wpdb;

        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::uninstall_single_site();
            restore_current_blog();
        }
    }

    /**
     * Uninstall for single site
     *
     * @since 1.0.0
     */
    private static function uninstall_single_site() {
        // Remove plugin options
        self::remove_plugin_options();

        // Remove plugin tables (WordPress-specific only, not PostgreSQL data)
        self::remove_plugin_tables();

        // Remove upload directories
        self::remove_upload_directories();

        // Clear scheduled events
        self::clear_scheduled_events();

        // Clear transients
        self::clear_transients();
    }

    /**
     * Remove plugin options
     *
     * @since 1.0.0
     */
    private static function remove_plugin_options() {
        global $wpdb;

        // Remove all plugin options
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'wecoza_classes_%'
            )
        );
    }

    /**
     * Remove plugin tables
     *
     * @since 1.0.0
     */
    private static function remove_plugin_tables() {
        global $wpdb;

        // Only remove WordPress-specific tables, not the main PostgreSQL data
        $tables_to_remove = array(
            $wpdb->prefix . 'wecoza_classes_settings'
        );

        foreach ($tables_to_remove as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Remove upload directories
     *
     * @since 1.0.0
     */
    private static function remove_upload_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/wecoza-classes';

        if (file_exists($plugin_upload_dir)) {
            self::remove_directory_recursive($plugin_upload_dir);
        }
    }

    /**
     * Recursively remove directory
     *
     * @param string $dir Directory path
     * @since 1.0.0
     */
    private static function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Clear scheduled events
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_events() {
        $scheduled_events = array(
            'wecoza_classes_daily_cleanup',
            'wecoza_classes_weekly_reports',
            'wecoza_classes_monthly_archive'
        );

        foreach ($scheduled_events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }

    /**
     * Clear plugin transients
     *
     * @since 1.0.0
     */
    private static function clear_transients() {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_wecoza_classes_%',
                '_transient_timeout_wecoza_classes_%'
            )
        );
    }
}
