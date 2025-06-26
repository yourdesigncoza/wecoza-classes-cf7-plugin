<?php
/**
 * Plugin activation handler
 *
 * @package WeCozaClasses
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WeCoza Classes Plugin Activator
 */
class WeCoza_Classes_Activator {

    /**
     * Activate the plugin
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('WeCoza Classes Plugin requires WordPress 5.0 or higher.', 'wecoza-classes'));
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('WeCoza Classes Plugin requires PHP 7.4 or higher.', 'wecoza-classes'));
        }

        // Create database tables
        self::create_database_tables();

        // Set default options
        self::set_default_options();

        // Create upload directories
        self::create_upload_directories();

        // Create required pages
        self::create_required_pages();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set activation flag
        update_option('wecoza_classes_plugin_activated', true);
        update_option('wecoza_classes_plugin_version', WECOZA_CLASSES_VERSION);

        // Log activation
        error_log('WeCoza Classes Plugin activated successfully');
    }

    /**
     * Create database tables
     *
     * @since 1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;

        // Note: In the actual implementation, we'll use the existing PostgreSQL database
        // This is a placeholder for any WordPress-specific tables we might need
        
        $charset_collate = $wpdb->get_charset_collate();

        // Example: Plugin-specific settings table (if needed)
        $table_name = $wpdb->prefix . 'wecoza_classes_settings';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(100) NOT NULL,
            setting_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Run any database migrations
        self::run_database_migrations();
    }

    /**
     * Run database migrations
     *
     * @since 1.0.0
     */
    private static function run_database_migrations() {
        // Check current database version
        $current_version = get_option('wecoza_classes_db_version', '0');
        
        // Define migrations
        $migrations = array(
            '1.0.0' => 'migration_1_0_0',
        );

        foreach ($migrations as $version => $migration_method) {
            if (version_compare($current_version, $version, '<')) {
                if (method_exists(__CLASS__, $migration_method)) {
                    call_user_func(array(__CLASS__, $migration_method));
                    update_option('wecoza_classes_db_version', $version);
                }
            }
        }
    }

    /**
     * Migration for version 1.0.0
     *
     * @since 1.0.0
     */
    private static function migration_1_0_0() {
        error_log('WeCoza Classes Plugin: Running migration 1.0.0');

        // Load the migration file
        require_once WECOZA_CLASSES_INCLUDES_DIR . 'migrations/create-classes-table.php';

        // Run the classes table creation
        $result = wecoza_classes_create_classes_table();

        if ($result) {
            error_log('WeCoza Classes Plugin: Migration 1.0.0 completed successfully');
        } else {
            error_log('WeCoza Classes Plugin: Migration 1.0.0 failed');
        }
    }

    /**
     * Set default plugin options
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        // Set default plugin options
        $default_options = array(
            'wecoza_classes_enable_calendar' => true,
            'wecoza_classes_enable_notifications' => true,
            'wecoza_classes_default_class_duration' => 8,
            'wecoza_classes_date_format' => 'Y-m-d',
            'wecoza_classes_time_format' => 'H:i',
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }

    /**
     * Create upload directories
     *
     * @since 1.0.0
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/wecoza-classes';

        // Create main plugin upload directory
        if (!file_exists($plugin_upload_dir)) {
            wp_mkdir_p($plugin_upload_dir);
        }

        // Create subdirectories
        $subdirs = array(
            'class-documents',
            'qa-reports',
            'certificates',
            'temp'
        );

        foreach ($subdirs as $subdir) {
            $subdir_path = $plugin_upload_dir . '/' . $subdir;
            if (!file_exists($subdir_path)) {
                wp_mkdir_p($subdir_path);
                
                // Add .htaccess for security
                $htaccess_content = "Options -Indexes\nDeny from all\n";
                file_put_contents($subdir_path . '/.htaccess', $htaccess_content);
            }
        }
    }

    /**
     * Create required WordPress pages for the plugin
     *
     * @since 1.0.0
     */
    private static function create_required_pages() {
        // First, ensure we have an "app" parent page
        $app_page = get_page_by_path('app');
        $app_page_id = 0;

        if (!$app_page) {
            // Create the app parent page
            $app_page_id = wp_insert_post(array(
                'post_title' => 'App',
                'post_content' => '<h2>WeCoza Application</h2><p>Welcome to the WeCoza training management system.</p>',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'app',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ));

            if ($app_page_id && !is_wp_error($app_page_id)) {
                error_log("WeCoza Classes Plugin: Created app parent page with ID {$app_page_id}");
            }
        } else {
            $app_page_id = $app_page->ID;
            error_log("WeCoza Classes Plugin: App parent page already exists with ID {$app_page_id}");
        }
        $pages = array(
            'class-details' => array(
                'title' => 'Class Details',
                'content' => '<h2>Class Details</h2>
<p>View detailed information about this training class.</p>

[wecoza_display_single_class]

<hr>

<div class="row mt-4">
    <div class="col-md-6">
        <a href="/app/all-classes/" class="btn btn-secondary">← Back to All Classes</a>
    </div>
    <div class="col-md-6 text-end">
        <a href="/app/update-class/?mode=update" class="btn btn-primary">Edit Class</a>
    </div>
</div>',
                'slug' => 'class-details'
            ),
            'all-classes' => array(
                'title' => 'All Classes',
                'content' => '<h2>All Training Classes</h2>
<p>View and manage all training classes in the system.</p>

[wecoza_display_classes limit="20" order_by="created_at" order="DESC"]

<hr>

<h3>Actions Available:</h3>
<ul>
<li><strong>View:</strong> Click on any class to view details</li>
<li><strong>Edit:</strong> Use the edit button to modify class information</li>
<li><strong>Delete:</strong> Remove classes that are no longer needed</li>
</ul>

<p><a href="/app/create-class/" class="btn btn-primary">+ Create New Class</a></p>',
                'slug' => 'all-classes'
            ),
            'create-class' => array(
                'title' => 'Create New Class',
                'content' => '<h2>Create New Class</h2>
<p>Use the form below to create a new training class.</p>

[wecoza_capture_class]

<hr>

<h3>Instructions:</h3>
<ul>
<li>Fill in all required fields marked with *</li>
<li>Select the appropriate client and site</li>
<li>Choose the class type and subject</li>
<li>Set up the class schedule using the calendar</li>
<li>Add learners and assign agents</li>
</ul>',
                'slug' => 'create-class'
            ),
            'update-class' => array(
                'title' => 'Update Class',
                'content' => '<h2>Update Class</h2>
<p>Use the form below to update an existing training class.</p>

[wecoza_capture_class]

<p><a href="/app/all-classes/" class="btn btn-secondary">← Back to All Classes</a></p>',
                'slug' => 'update-class'
            )
        );

        foreach ($pages as $page_key => $page_data) {
            // Check if page already exists (check both standalone and under app)
            $existing_page = get_page_by_path('app/' . $page_data['slug']);
            if (!$existing_page) {
                $existing_page = get_page_by_path($page_data['slug']);
            }

            if (!$existing_page) {
                // Create the page as a child of the app page
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $page_data['slug'],
                    'post_parent' => $app_page_id,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));

                if ($page_id && !is_wp_error($page_id)) {
                    error_log("WeCoza Classes Plugin: Created page '{$page_data['title']}' with ID {$page_id} under app parent");
                } else {
                    error_log("WeCoza Classes Plugin: Failed to create page '{$page_data['title']}'");
                }
            } else {
                error_log("WeCoza Classes Plugin: Page '{$page_data['title']}' already exists");
            }
        }
    }
}
