<?php
/**
 * Schedule Data Admin Utilities
 * 
 * Provides admin interface for managing schedule data migrations and integrity checks
 * 
 * @package WeCozaClasses\Admin
 */

namespace WeCozaClasses\Admin;

use WeCozaClasses\Controllers\ClassController;

class ScheduleDataAdmin {
    
    /**
     * Initialize admin hooks
     */
    public static function init() {
        // Add admin menu
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        
        // Handle AJAX requests
        add_action('wp_ajax_wecoza_check_schedule_integrity', [self::class, 'handleIntegrityCheckAjax']);
        add_action('wp_ajax_wecoza_migrate_schedule_data', [self::class, 'handleMigrationAjax']);
    }
    
    /**
     * Add admin menu for schedule data management
     */
    public static function addAdminMenu() {
        add_submenu_page(
            'wecoza-classes',
            'Schedule Data Management',
            'Schedule Data',
            'manage_options',
            'wecoza-schedule-data',
            [self::class, 'renderAdminPage']
        );
    }
    
    /**
     * Render admin page for schedule data management
     */
    public static function renderAdminPage() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        ?>
        <div class="wrap">
            <h1>Schedule Data Management</h1>
            <p>Manage schedule data format migrations and integrity checks for WeCoza Classes.</p>
            
            <div class="card">
                <h2>Schedule Data Integrity Check</h2>
                <p>Check the integrity and format of schedule data across all classes.</p>
                <button id="check-integrity-btn" class="button button-secondary">Run Integrity Check</button>
                <div id="integrity-results" style="margin-top: 15px;"></div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Schedule Data Migration</h2>
                <p>Migrate legacy schedule data (v1.0) to the new enhanced format (v2.0).</p>
                <p><strong>Warning:</strong> This will modify your database. Always backup your data first.</p>
                
                <div style="margin: 15px 0;">
                    <button id="dry-run-migration-btn" class="button button-secondary">Dry Run (Preview Only)</button>
                    <button id="run-migration-btn" class="button button-primary" style="margin-left: 10px;">Run Migration</button>
                </div>
                
                <div id="migration-results" style="margin-top: 15px;"></div>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Supported Formats</h2>
                <div id="supported-formats"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Load supported formats
            loadSupportedFormats();
            
            // Integrity check
            $('#check-integrity-btn').on('click', function() {
                const $btn = $(this);
                const $results = $('#integrity-results');
                
                $btn.prop('disabled', true).text('Checking...');
                $results.html('<div class="notice notice-info"><p>Running integrity check...</p></div>');
                
                $.post(ajaxurl, {
                    action: 'wecoza_check_schedule_integrity',
                    nonce: '<?php echo wp_create_nonce('wecoza_schedule_admin'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        displayIntegrityResults(response.data);
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $results.html('<div class="notice notice-error"><p>Request failed. Please try again.</p></div>');
                })
                .always(function() {
                    $btn.prop('disabled', false).text('Run Integrity Check');
                });
            });
            
            // Dry run migration
            $('#dry-run-migration-btn').on('click', function() {
                runMigration(true);
            });
            
            // Full migration
            $('#run-migration-btn').on('click', function() {
                if (confirm('Are you sure you want to run the migration? This will modify your database.')) {
                    runMigration(false);
                }
            });
            
            function runMigration(dryRun) {
                const $btn = dryRun ? $('#dry-run-migration-btn') : $('#run-migration-btn');
                const $results = $('#migration-results');
                
                $btn.prop('disabled', true).text(dryRun ? 'Running Preview...' : 'Migrating...');
                $results.html('<div class="notice notice-info"><p>' + (dryRun ? 'Running migration preview...' : 'Running migration...') + '</p></div>');
                
                $.post(ajaxurl, {
                    action: 'wecoza_migrate_schedule_data',
                    dry_run: dryRun ? 1 : 0,
                    nonce: '<?php echo wp_create_nonce('wecoza_schedule_admin'); ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        displayMigrationResults(response.data, dryRun);
                    } else {
                        $results.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                })
                .fail(function() {
                    $results.html('<div class="notice notice-error"><p>Request failed. Please try again.</p></div>');
                })
                .always(function() {
                    $btn.prop('disabled', false).text(dryRun ? 'Dry Run (Preview Only)' : 'Run Migration');
                });
            }
            
            function displayIntegrityResults(data) {
                let html = '<div class="notice notice-success"><p><strong>Integrity Check Complete</strong></p></div>';
                
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Metric</th><th>Count</th></tr></thead><tbody>';
                html += '<tr><td>Total Classes</td><td>' + data.total_classes + '</td></tr>';
                html += '<tr><td>Legacy Format (v1.0)</td><td>' + data.v1_format + '</td></tr>';
                html += '<tr><td>Enhanced Format (v2.0)</td><td>' + data.v2_format + '</td></tr>';
                html += '<tr><td>Invalid Format</td><td>' + data.invalid_format + '</td></tr>';
                html += '<tr><td>Empty Data</td><td>' + data.empty_data + '</td></tr>';
                html += '</tbody></table>';
                
                if (data.validation_errors.length > 0) {
                    html += '<h4>Validation Errors:</h4><ul>';
                    data.validation_errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul>';
                }
                
                if (data.recommendations.length > 0) {
                    html += '<h4>Recommendations:</h4><ul>';
                    data.recommendations.forEach(function(rec) {
                        html += '<li>' + rec + '</li>';
                    });
                    html += '</ul>';
                }
                
                $('#integrity-results').html(html);
            }
            
            function displayMigrationResults(data, dryRun) {
                const title = dryRun ? 'Migration Preview Results' : 'Migration Results';
                let noticeClass = data.success ? 'notice-success' : 'notice-warning';
                
                let html = '<div class="notice ' + noticeClass + '"><p><strong>' + title + '</strong></p></div>';
                html += '<p>' + data.summary + '</p>';
                
                html += '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Metric</th><th>Count</th></tr></thead><tbody>';
                html += '<tr><td>Total Classes</td><td>' + data.statistics.total_classes + '</td></tr>';
                html += '<tr><td>Migrated</td><td>' + data.statistics.migrated_classes + '</td></tr>';
                html += '<tr><td>Skipped</td><td>' + data.statistics.skipped_classes + '</td></tr>';
                html += '<tr><td>Errors</td><td>' + data.statistics.error_classes + '</td></tr>';
                html += '</tbody></table>';
                
                if (data.statistics.errors.length > 0) {
                    html += '<h4>Errors:</h4><ul>';
                    data.statistics.errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul>';
                }
                
                $('#migration-results').html(html);
            }
            
            function loadSupportedFormats() {
                // This would typically be loaded via AJAX, but for now we'll show static content
                let html = '<h4>Legacy Format (v1.0)</h4>';
                html += '<p>Array of individual schedule entries with date, start_time, end_time fields.</p>';
                html += '<pre><code>[{"date": "2024-01-15", "start_time": "09:00", "end_time": "17:00"}]</code></pre>';
                
                html += '<h4>Enhanced Format (v2.0)</h4>';
                html += '<p>Structured format with patterns, per-day times, and metadata.</p>';
                html += '<pre><code>{"version": "2.0", "pattern": "weekly", "timeData": {...}}</code></pre>';
                
                $('#supported-formats').html(html);
            }
        });
        </script>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
        }
        pre {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
        </style>
        <?php
    }
    
    /**
     * Handle integrity check AJAX request
     */
    public static function handleIntegrityCheckAjax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wecoza_schedule_admin')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        try {
            $results = ClassController::checkScheduleDataIntegrity();
            wp_send_json_success($results);
        } catch (\Exception $e) {
            wp_send_json_error('Integrity check failed: ' . $e->getMessage());
        }
    }
    
    // Migration functions removed - V2.0 format only
}
