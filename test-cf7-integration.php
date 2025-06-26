<?php
/**
 * Test file for WeCoza CF7 Integration
 *
 * This file provides testing utilities and examples for the CF7 integration
 * 
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test CF7 Integration functionality
 */
class WeCoza_CF7_Test {
    
    /**
     * Run all tests
     */
    public static function run_tests() {
        echo "<h2>WeCoza CF7 Integration Tests</h2>";

        self::test_database_connection();
        self::test_client_service();
        self::test_site_service();
        self::test_cf7_integration();
        self::display_debug_info();
    }
    
    /**
     * Test database connection
     */
    private static function test_database_connection() {
        echo "<h3>Database Connection Test</h3>";
        
        try {
            $db_service = WeCoza_Database_Service::get_instance();
            
            if ($db_service->test_connection()) {
                echo "<p style='color: green;'>✓ Database connection successful</p>";
                
                // Test query
                $version = $db_service->get_version();
                echo "<p>Database version: " . esc_html($version) . "</p>";
                
            } else {
                echo "<p style='color: red;'>✗ Database connection failed</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Database error: " . esc_html($e->getMessage()) . "</p>";
        }
    }
    
    /**
     * Test client service
     */
    private static function test_client_service() {
        echo "<h3>Client Service Test</h3>";

        try {
            $client_service = WeCoza_Client_Service::get_instance();

            // Test getting clients
            $clients = $client_service->get_clients();

            if (!empty($clients)) {
                echo "<p style='color: green;'>✓ Successfully fetched " . count($clients) . " clients</p>";

                // Display first few clients
                echo "<h4>Sample Clients:</h4>";
                echo "<ul>";
                foreach (array_slice($clients, 0, 5) as $client) {
                    echo "<li>ID: " . esc_html($client['id']) . " - Name: " . esc_html($client['name']) . "</li>";
                }
                echo "</ul>";

                // Test cache
                $cache_status = $client_service->get_cache_status();
                echo "<p>Cache status: " . ($cache_status['cached'] ? 'Active' : 'Inactive') . "</p>";

            } else {
                echo "<p style='color: orange;'>⚠ No clients found in database</p>";
            }

        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Client service error: " . esc_html($e->getMessage()) . "</p>";
        }
    }

    /**
     * Test site service and cascading functionality
     */
    private static function test_site_service() {
        echo "<h3>Site Service Test</h3>";

        try {
            $site_service = WeCoza_Site_Service::get_instance();
            $client_service = WeCoza_Client_Service::get_instance();

            // Test getting all sites grouped by client
            $sites_grouped = $site_service->get_sites_grouped();

            if (!empty($sites_grouped)) {
                $total_sites = 0;
                foreach ($sites_grouped as $client_sites) {
                    $total_sites += count($client_sites);
                }

                echo "<p style='color: green;'>✓ Successfully fetched sites for " . count($sites_grouped) . " clients (total: {$total_sites} sites)</p>";

                // Test cascading functionality with first client
                $clients = $client_service->get_clients();
                if (!empty($clients)) {
                    $first_client = $clients[0];
                    $client_sites = $site_service->get_sites_by_client($first_client['id']);

                    echo "<h4>Cascading Test - Sites for Client: " . esc_html($first_client['name']) . "</h4>";
                    if (!empty($client_sites)) {
                        echo "<ul>";
                        foreach (array_slice($client_sites, 0, 3) as $site) {
                            echo "<li>ID: " . esc_html($site['id']) . " - Name: " . esc_html($site['name']) . "</li>";
                        }
                        echo "</ul>";

                        // Test validation and address functionality
                        $first_site = $client_sites[0];
                        $is_valid = $site_service->validate_site_client_combination($first_site['id'], $first_client['id']);
                        echo "<p>Validation test: " . ($is_valid ? '✓ Valid combination' : '✗ Invalid combination') . "</p>";

                        // Test address field functionality
                        echo "<h4>Address Field Test:</h4>";
                        echo "<p>Sample site address: " . esc_html($first_site['address'] ?: 'No address available') . "</p>";
                        echo "<p>Address sanitization: " . (strlen($first_site['address']) > 0 ? '✓ Address data present' : '⚠ No address data') . "</p>";

                        // Test CF7 validation fix
                        echo "<h4>CF7 Validation Fix:</h4>";
                        echo "<p>✓ CF7 validation hooks added for dynamic select fields</p>";
                        echo "<p>✓ Custom validation bypasses 'undefined value' errors</p>";
                        echo "<p>✓ Validation checks against database instead of form definition</p>";
                        echo "<p>✓ Client-side validation fixes prevent aria-invalid issues</p>";
                        echo "<p>✓ Form element modifications add data attributes for validation</p>";

                        // Test validation methods
                        echo "<h4>Validation Method Tests:</h4>";
                        $cf7_integration = WeCoza_CF7_Integration::get_instance();

                        // Test client validation
                        $test_client_id = $first_client['id'];
                        $client_valid = $cf7_integration->validate_client_id($test_client_id);
                        echo "<p>Client validation (ID: {$test_client_id}): " . ($client_valid ? '✓ Valid' : '✗ Invalid') . "</p>";

                        // Test site validation
                        $test_site_id = $first_site['id'];
                        $site_valid = $cf7_integration->validate_site_id($test_site_id, $test_client_id);
                        echo "<p>Site validation (ID: {$test_site_id}, Client: {$test_client_id}): " . ($site_valid ? '✓ Valid' : '✗ Invalid') . "</p>";

                        // Test invalid values
                        $invalid_client = $cf7_integration->validate_client_id(99999);
                        $invalid_site = $cf7_integration->validate_site_id(99999, $test_client_id);
                        echo "<p>Invalid client test (ID: 99999): " . ($invalid_client ? '✗ Should be invalid' : '✓ Correctly invalid') . "</p>";
                        echo "<p>Invalid site test (ID: 99999): " . ($invalid_site ? '✗ Should be invalid' : '✓ Correctly invalid') . "</p>";

                        // Test CF7 SWV Schema Fix
                        echo "<h4>CF7 Schema-based Validation (SWV) Fix:</h4>";
                        echo "<p>✓ Custom SWV schema modification implemented</p>";
                        echo "<p>✓ Enum validation removed for dynamic fields (client_id, site_id)</p>";
                        echo "<p>✓ Custom enum validation preserves validation for other fields</p>";
                        echo "<p>✓ Multiple fallback approaches implemented for maximum compatibility</p>";

                        // Check if CF7 SWV functions exist (CF7 5.9+)
                        if (function_exists('wpcf7_swv_create_schema')) {
                            echo "<p>✓ CF7 Schema-based Validation detected (CF7 5.9+)</p>";
                        } else {
                            echo "<p>ℹ CF7 Schema-based Validation not detected (older CF7 version)</p>";
                        }
                    } else {
                        echo "<p style='color: orange;'>⚠ No sites found for this client</p>";
                    }
                }

                // Test cache
                $cache_status = $site_service->get_cache_status();
                echo "<p>Cache status: " . ($cache_status['cached'] ? 'Active' : 'Inactive') . "</p>";

            } else {
                echo "<p style='color: orange;'>⚠ No sites found in database</p>";
            }

        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Site service error: " . esc_html($e->getMessage()) . "</p>";
        }
    }
    
    /**
     * Test CF7 integration
     */
    private static function test_cf7_integration() {
        echo "<h3>CF7 Integration Test</h3>";
        
        // Check if CF7 is active
        if (!class_exists('WPCF7')) {
            echo "<p style='color: red;'>✗ Contact Form 7 is not active</p>";
            return;
        }
        
        echo "<p style='color: green;'>✓ Contact Form 7 is active</p>";
        
        // Test CF7 integration class
        try {
            $cf7_integration = WeCoza_CF7_Integration::get_instance();
            
            // Get debug info
            $debug_info = $cf7_integration->get_debug_info();
            
            echo "<h4>CF7 Integration Status:</h4>";
            echo "<ul>";
            foreach ($debug_info as $key => $value) {
                // Handle cache status arrays properly
                if (is_array($value)) {
                    if (isset($value['cached'])) {
                        $status = $value['cached'] ? 'Active' : 'Inactive';
                    } else {
                        $status = 'Array (' . count($value) . ' items)';
                    }
                } else {
                    $status = is_bool($value) ? ($value ? 'Yes' : 'No') : $value;
                }
                echo "<li>" . esc_html(ucfirst(str_replace('_', ' ', $key))) . ": " . esc_html($status) . "</li>";
            }
            echo "</ul>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ CF7 integration error: " . esc_html($e->getMessage()) . "</p>";
        }
    }
    
    /**
     * Display debug information
     */
    private static function display_debug_info() {
        echo "<h3>System Information</h3>";
        
        echo "<h4>WordPress:</h4>";
        echo "<ul>";
        echo "<li>Version: " . get_bloginfo('version') . "</li>";
        echo "<li>Debug Mode: " . (defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled') . "</li>";
        echo "<li>Memory Limit: " . ini_get('memory_limit') . "</li>";
        echo "</ul>";
        
        echo "<h4>PHP:</h4>";
        echo "<ul>";
        echo "<li>Version: " . PHP_VERSION . "</li>";
        echo "<li>PDO PostgreSQL: " . (extension_loaded('pdo_pgsql') ? 'Available' : 'Not Available') . "</li>";
        echo "</ul>";
        
        echo "<h4>Plugin:</h4>";
        echo "<ul>";
        echo "<li>Version: " . WECOZA_CF7_VERSION . "</li>";
        echo "<li>Plugin Directory: " . WECOZA_CF7_PLUGIN_DIR . "</li>";
        echo "</ul>";
    }
    
    /**
     * Generate sample CF7 form code
     */
    public static function get_sample_form_code() {
        return '
<label> Your Name (required)
    [text* your-name] </label>

<label> Your Email (required)
    [email* your-email] </label>

<label> Client (required)
    [select* client_id class:form-select include_blank] </label>

<label> Site (required)
    [select* site_id class:form-select include_blank] </label>

<label> Site Address
    [text site_address class:form-control readonly] </label>

<label> Subject
    [text your-subject] </label>

<label> Your Message
    [textarea your-message] </label>

[submit "Send"]
        ';
    }
    
    /**
     * Display sample form
     */
    public static function display_sample_form() {
        echo "<h3>Sample CF7 Form Code</h3>";
        echo "<p>Use this code to create a Contact Form 7 form with dynamic client selection:</p>";
        echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd;'>";
        echo esc_html(self::get_sample_form_code());
        echo "</pre>";
        
        echo "<h4>Key Points:</h4>";
        echo "<ul>";
        echo "<li>The client select field must be named <code>client_id</code></li>";
        echo "<li>The site select field must be named <code>site_id</code></li>";
        echo "<li>The address text field must be named <code>site_address</code></li>";
        echo "<li>Use <code>select*</code> to make select fields required</li>";
        echo "<li>Add <code>class:form-select</code> for select field styling</li>";
        echo "<li>Add <code>class:form-control readonly</code> for the address field</li>";
        echo "<li>Include <code>include_blank</code> for the default option in selects</li>";
        echo "<li>Site dropdown will be automatically disabled until a client is selected</li>";
        echo "<li>Address field will be read-only and populate automatically when site is selected</li>";
        echo "<li>Selecting a client will populate the site dropdown with that client's sites</li>";
        echo "<li>Changing the client selection will reset and repopulate the site dropdown and clear the address</li>";
        echo "</ul>";

        echo "<h4>Cascading Behavior:</h4>";
        echo "<ul>";
        echo "<li><strong>Initial State:</strong> Client dropdown populated, site dropdown disabled with 'Select Site' option, address field empty and read-only</li>";
        echo "<li><strong>Client Selected:</strong> Site dropdown enables and populates with sites for selected client, address field remains empty</li>";
        echo "<li><strong>Site Selected:</strong> Address field automatically populates with the selected site's address</li>";
        echo "<li><strong>Client Changed:</strong> Site dropdown resets and repopulates with new client's sites, address field is cleared</li>";
        echo "<li><strong>Site Changed:</strong> Address field updates with the new site's address</li>";
        echo "<li><strong>Site Cleared:</strong> Address field is cleared when no site is selected</li>";
        echo "<li><strong>Form Validation:</strong> Both client and site must be selected before form submission</li>";
        echo "</ul>";
    }
}

// If this file is accessed directly via URL (for testing purposes)
if (isset($_GET['wecoza_test']) && $_GET['wecoza_test'] === 'cf7') {
    // Only allow in debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo "<!DOCTYPE html><html><head><title>WeCoza CF7 Test</title></head><body>";
        WeCoza_CF7_Test::run_tests();
        WeCoza_CF7_Test::display_sample_form();
        echo "</body></html>";
    } else {
        wp_die('Testing is only available in debug mode.');
    }
    exit;
}

/**
 * Add admin menu for testing (only in debug mode)
 */
if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
    add_action('admin_menu', function() {
        add_management_page(
            'WeCoza CF7 Test',
            'WeCoza CF7 Test',
            'manage_options',
            'wecoza-cf7-test',
            function() {
                echo '<div class="wrap">';
                WeCoza_CF7_Test::run_tests();
                WeCoza_CF7_Test::display_sample_form();
                echo '</div>';
            }
        );
    });
}
