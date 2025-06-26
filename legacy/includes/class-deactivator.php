<?php
/**
 * Plugin deactivation handler
 *
 * @package WeCozaClasses
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WeCoza Classes Plugin Deactivator
 */
class WeCoza_Classes_Deactivator {

    /**
     * Deactivate the plugin
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();

        // Clear transients
        self::clear_transients();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Remove activation flag
        delete_option('wecoza_classes_plugin_activated');

        // Log deactivation
        error_log('WeCoza Classes Plugin deactivated');
    }

    /**
     * Clear scheduled events
     *
     * @since 1.0.0
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled cron events
        $scheduled_events = array(
            'wecoza_classes_daily_cleanup',
            'wecoza_classes_weekly_reports',
            'wecoza_classes_monthly_archive'
        );

        foreach ($scheduled_events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }

    /**
     * Clear plugin transients
     *
     * @since 1.0.0
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all plugin-related transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_wecoza_classes_%',
                '_transient_timeout_wecoza_classes_%'
            )
        );
    }
}
