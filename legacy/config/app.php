<?php
/**
 * Application configuration for WeCoza Classes Plugin
 *
 * @package WeCozaClasses
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

return array(
    /**
     * Plugin Information
     */
    'name' => 'WeCoza Classes Plugin',
    'version' => WECOZA_CLASSES_VERSION,
    'description' => 'A comprehensive class management system for WeCoza training programs.',
    'author' => 'Your Design Co',
    'author_uri' => 'https://yourdesign.co.za',
    'text_domain' => 'wecoza-classes',

    /**
     * Plugin Settings
     */
    'settings' => array(
        'enable_debug' => defined('WP_DEBUG') && WP_DEBUG,
        'enable_logging' => true,
        'cache_duration' => 3600, // 1 hour
        'max_upload_size' => 10485760, // 10MB
        'allowed_file_types' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'),
    ),

    /**
     * Database Configuration
     */
    'database' => array(
        'use_postgresql' => true,
        // 'table_prefix' => 'wecoza_classes_',
        'charset' => 'utf8mb4',
        'collate' => 'utf8mb4_unicode_ci',
        // PostgreSQL connection settings (can be overridden via WordPress options)
        'postgresql' => array(
            'host' => get_option('wecoza_postgres_host', 'db-wecoza-3-do-user-17263152-0.m.db.ondigitalocean.com'),
            'port' => get_option('wecoza_postgres_port', '25060'),
            'dbname' => get_option('wecoza_postgres_dbname', 'defaultdb'),
            'user' => get_option('wecoza_postgres_user', 'doadmin'),
            'password' => get_option('wecoza_postgres_password', ''),
        ),
    ),

    /**
     * Controllers to initialize
     */
    'controllers' => array(
        'WeCozaClasses\\Controllers\\ClassController',
        'WeCozaClasses\\Controllers\\ClassTypesController',
        'WeCozaClasses\\Controllers\\PublicHolidaysController',
    ),

    /**
     * Shortcodes configuration
     */
    'shortcodes' => array(
        'wecoza_capture_class' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'captureClassShortcode',
            'description' => 'Display class capture form',
        ),
        'wecoza_display_classes' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'displayClassesShortcode',
            'description' => 'Display all classes in a table',
        ),
        'wecoza_display_single_class' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'displaySingleClassShortcode',
            'description' => 'Display single class details',
        ),
    ),

    /**
     * AJAX endpoints configuration
     */
    'ajax_endpoints' => array(
        'save_class' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'saveClass',
            'public' => false,
        ),
        'update_class' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'updateClass',
            'public' => false,
        ),
        'delete_class' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'deleteClass',
            'public' => false,
        ),
        'get_class_subjects' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassTypesController',
            'method' => 'getClassSubjects',
            'public' => true,
        ),
        'get_public_holidays' => array(
            'controller' => 'WeCozaClasses\\Controllers\\PublicHolidaysController',
            'method' => 'getPublicHolidays',
            'public' => true,
        ),
        'get_calendar_events' => array(
            'controller' => 'WeCozaClasses\\Controllers\\ClassController',
            'method' => 'getCalendarEvents',
            'public' => true,
        ),
    ),

    /**
     * Asset configuration
     */
    'assets' => array(
        'js' => array(
            'public' => array(
                'class-capture.js',
                'class-schedule-form.js',
                'learner-level-utils.js',
                'class-types.js',
                'wecoza-calendar.js',
                'classes-table-search.js',
            ),
            'admin' => array(
                'wecoza-classes-admin.js',
            ),
        ),
    ),

    /**
     * Capabilities configuration
     */
    'capabilities' => array(
        'manage_classes' => array(
            'administrator',
            'editor',
        ),
        'create_classes' => array(
            'administrator',
            'editor',
            'author',
        ),
        'edit_classes' => array(
            'administrator',
            'editor',
        ),
        'delete_classes' => array(
            'administrator',
        ),
        'view_reports' => array(
            'administrator',
            'editor',
            'author',
            'contributor',
        ),
    ),

    /**
     * Calendar configuration
     */
    'calendar' => array(
        'enable_fullcalendar' => true,
        'default_view' => 'dayGridMonth',
        'enable_public_holidays' => true,
        'holiday_api_enabled' => true,
        'time_format' => 'H:mm',
        'date_format' => 'Y-m-d',
    ),

    /**
     * Form validation rules
     */
    'validation' => array(
        'class_code' => array(
            'required' => true,
            'max_length' => 50,
            'pattern' => '/^[A-Z0-9\-]+$/',
        ),
        'class_duration' => array(
            'required' => true,
            'min' => 1,
            'max' => 365,
            'type' => 'integer',
        ),
        'original_start_date' => array(
            'required' => true,
            'type' => 'date',
            'min_date' => 'today',
        ),
    ),

    /**
     * Email configuration
     */
    'email' => array(
        'enable_notifications' => true,
        'from_email' => get_option('admin_email'),
        'from_name' => get_bloginfo('name'),
        'templates_path' => 'emails/',
    ),

    /**
     * File upload configuration
     */
    'uploads' => array(
        'max_file_size' => 10485760, // 10MB
        'allowed_types' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'),
        'upload_path' => 'wecoza-classes/',
        'secure_uploads' => true,
    ),

    /**
     * Cache configuration
     */
    'cache' => array(
        'enable_caching' => true,
        'default_expiration' => 3600, // 1 hour
        'cache_groups' => array(
            'classes' => 1800, // 30 minutes
            'class_types' => 7200, // 2 hours
            'public_holidays' => 86400, // 24 hours
        ),
    ),
);
