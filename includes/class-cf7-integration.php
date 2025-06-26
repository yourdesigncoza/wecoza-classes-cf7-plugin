<?php
/**
 * Contact Form 7 Integration for WeCoza Classes CF7 Plugin
 *
 * Handles dynamic population of CF7 select fields with client data
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CF7 Integration class
 */
class WeCoza_CF7_Integration {
    
    /**
     * Singleton instance
     *
     * @var WeCoza_CF7_Integration
     */
    private static $instance = null;
    
    /**
     * Client service instance
     *
     * @var WeCoza_Client_Service
     */
    private $client_service;

    /**
     * Site service instance
     *
     * @var WeCoza_Site_Service
     */
    private $site_service;
    
    /**
     * Get singleton instance
     *
     * @return WeCoza_CF7_Integration
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - private to enforce singleton
     */
    private function __construct() {
        $this->client_service = WeCoza_Client_Service::get_instance();
        $this->site_service = WeCoza_Site_Service::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into CF7 form elements to modify select fields
        add_filter('wpcf7_form_elements', array($this, 'populate_client_select'), 10, 1);
        
        // Hook into CF7 form tag to handle custom attributes
        add_filter('wpcf7_form_tag', array($this, 'handle_client_select_tag'), 10, 2);
        
        // Add AJAX handlers for refreshing data and cascading selects
        add_action('wp_ajax_wecoza_refresh_clients', array($this, 'ajax_refresh_clients'));
        add_action('wp_ajax_nopriv_wecoza_refresh_clients', array($this, 'ajax_refresh_clients'));
        add_action('wp_ajax_wecoza_get_sites_by_client', array($this, 'ajax_get_sites_by_client'));
        add_action('wp_ajax_nopriv_wecoza_get_sites_by_client', array($this, 'ajax_get_sites_by_client'));

        // Add CF7 validation hooks for dynamic select fields
        add_filter('wpcf7_validate_select', array($this, 'validate_dynamic_select'), 10, 2);
        add_filter('wpcf7_validate_select*', array($this, 'validate_dynamic_select'), 10, 2);

        // Add additional validation hooks to catch all scenarios
        add_filter('wpcf7_validate', array($this, 'validate_form_submission'), 10, 2);
        add_filter('wpcf7_form_elements', array($this, 'modify_form_elements'), 10, 1);

        // Fix for CF7 5.9+ Schema-based Validation (SWV) enum validation
        // This is the key fix for the "undefined value" error with dynamic options
        add_action('wpcf7_swv_create_schema', array($this, 'modify_swv_schema'), 10, 2);

        // Alternative approach: Remove enum validation entirely for forms with dynamic fields
        add_action('wpcf7_contact_form', array($this, 'disable_enum_validation_for_dynamic_forms'), 10, 1);

        // Most targeted approach: Replace enum validation with custom validation
        add_action('init', array($this, 'replace_enum_validation_with_custom'), 20);
        
        // Enqueue scripts for frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Populate select fields with dynamic data (client_id and site_id)
     *
     * @param string $form_elements Form HTML elements
     * @return string Modified form HTML
     */
    public function populate_client_select($form_elements) {
        // Handle client_id select field
        if (strpos($form_elements, 'name="client_id"') !== false) {
            $form_elements = $this->populate_client_field($form_elements);
        }

        // Handle site_id select field
        if (strpos($form_elements, 'name="site_id"') !== false) {
            $form_elements = $this->populate_site_field($form_elements);
        }

        // Handle site_address text field
        if (strpos($form_elements, 'name="site_address"') !== false) {
            $form_elements = $this->populate_address_field($form_elements);
        }

        return $form_elements;
    }

    /**
     * Populate client_id select field with dynamic data
     *
     * @param string $form_elements Form HTML elements
     * @return string Modified form HTML
     */
    private function populate_client_field($form_elements) {
        
        // Get client data
        $clients = $this->client_service->get_clients();
        
        if (empty($clients)) {
            // If no clients found, add a notice option
            $options_html = '<option value="">No clients available</option>';
        } else {
            // Build options HTML
            $options_html = '<option value="">—Please choose an option—</option>';
            
            foreach ($clients as $client) {
                $client_id = esc_attr($client['id']);
                $client_name = esc_html($client['name']);
                $options_html .= "<option value=\"{$client_id}\">{$client_name}</option>";
            }
        }
        
        // Use regex to find and replace the client_id select field
        $pattern = '/<select[^>]*name="client_id"[^>]*>(.*?)<\/select>/s';
        
        $replacement = function($matches) use ($options_html) {
            $select_tag = $matches[0];
            
            // Extract attributes from the original select tag
            preg_match('/<select([^>]*)>/', $select_tag, $attr_matches);
            $attributes = isset($attr_matches[1]) ? $attr_matches[1] : '';
            
            // Ensure required classes and attributes are present
            if (strpos($attributes, 'class=') === false) {
                $attributes .= ' class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-select"';
            }
            
            if (strpos($attributes, 'aria-required=') === false) {
                $attributes .= ' aria-required="true"';
            }
            
            if (strpos($attributes, 'aria-invalid=') === false) {
                $attributes .= ' aria-invalid="false"';
            }
            
            // Add data attribute to identify this as a dynamic field
            $attributes .= ' data-wecoza-dynamic="client_id"';
            
            return "<select{$attributes}>{$options_html}</select>";
        };
        
        $form_elements = preg_replace_callback($pattern, $replacement, $form_elements);
        
        return $form_elements;
    }

    /**
     * Populate site_id select field with initial disabled state
     *
     * @param string $form_elements Form HTML elements
     * @return string Modified form HTML
     */
    private function populate_site_field($form_elements) {
        // For site_id, we start with a disabled field that will be populated via AJAX
        $options_html = '<option value="">Select Site</option>';

        // Use regex to find and replace the site_id select field
        $pattern = '/<select[^>]*name="site_id"[^>]*>(.*?)<\/select>/s';

        $replacement = function($matches) use ($options_html) {
            $select_tag = $matches[0];

            // Extract attributes from the original select tag
            preg_match('/<select([^>]*)>/', $select_tag, $attr_matches);
            $attributes = isset($attr_matches[1]) ? $attr_matches[1] : '';

            // Ensure required classes and attributes are present
            if (strpos($attributes, 'class=') === false) {
                $attributes .= ' class="wpcf7-form-control wpcf7-select wpcf7-validates-as-required form-select"';
            }

            if (strpos($attributes, 'aria-required=') === false) {
                $attributes .= ' aria-required="true"';
            }

            if (strpos($attributes, 'aria-invalid=') === false) {
                $attributes .= ' aria-invalid="false"';
            }

            // Add disabled state and data attributes for cascading functionality
            $attributes .= ' disabled="disabled" data-wecoza-dynamic="site_id" data-depends-on="client_id"';

            return "<select{$attributes}>{$options_html}</select>";
        };

        $form_elements = preg_replace_callback($pattern, $replacement, $form_elements);

        return $form_elements;
    }

    /**
     * Populate site_address text field with read-only state
     *
     * @param string $form_elements Form HTML elements
     * @return string Modified form HTML
     */
    private function populate_address_field($form_elements) {
        // Use regex to find and replace the site_address text field
        $pattern = '/<input[^>]*name="site_address"[^>]*>/';

        $replacement = function($matches) {
            $input_tag = $matches[0];

            // Extract attributes from the original input tag
            preg_match('/<input([^>]*)>/', $input_tag, $attr_matches);
            $attributes = isset($attr_matches[1]) ? $attr_matches[1] : '';

            // Ensure required classes and attributes are present
            if (strpos($attributes, 'class=') === false) {
                $attributes .= ' class="wpcf7-form-control form-control"';
            }

            // Ensure readonly attribute is present
            if (strpos($attributes, 'readonly') === false) {
                $attributes .= ' readonly="readonly"';
            }

            // Add data attributes for cascading functionality
            $attributes .= ' data-wecoza-dynamic="site_address" data-depends-on="site_id"';

            // Set placeholder and initial empty value
            if (strpos($attributes, 'placeholder=') === false) {
                $attributes .= ' placeholder="Address will appear when site is selected"';
            }

            if (strpos($attributes, 'value=') === false) {
                $attributes .= ' value=""';
            }

            return "<input{$attributes}>";
        };

        $form_elements = preg_replace_callback($pattern, $replacement, $form_elements);

        return $form_elements;
    }

    /**
     * Handle form tag attributes for client_id, site_id, and site_address
     *
     * @param array $tag CF7 form tag
     * @param string $unused Unused parameter
     * @return array Modified tag
     */
    public function handle_client_select_tag($tag, $unused = '') {
        // Handle select tags
        if ($tag['type'] === 'select' || $tag['type'] === 'select*') {
            // Handle client_id select
            if ($tag['name'] === 'client_id') {
                // Add custom class if not present
                if (!in_array('form-select', $tag['options'])) {
                    $tag['options'][] = 'form-select';
                }

                // Mark as dynamic field
                $tag['options'][] = 'wecoza-dynamic-client';
            }

            // Handle site_id select
            if ($tag['name'] === 'site_id') {
                // Add custom class if not present
                if (!in_array('form-select', $tag['options'])) {
                    $tag['options'][] = 'form-select';
                }

                // Mark as dynamic field
                $tag['options'][] = 'wecoza-dynamic-site';
            }
        }

        // Handle text input tags
        if ($tag['type'] === 'text' || $tag['type'] === 'text*') {
            // Handle site_address text field
            if ($tag['name'] === 'site_address') {
                // Add custom class if not present
                if (!in_array('form-control', $tag['options'])) {
                    $tag['options'][] = 'form-control';
                }

                // Ensure readonly attribute
                if (!in_array('readonly', $tag['options'])) {
                    $tag['options'][] = 'readonly';
                }

                // Mark as dynamic field
                $tag['options'][] = 'wecoza-dynamic-address';
            }
        }

        return $tag;
    }
    
    /**
     * AJAX handler to refresh client data
     */
    public function ajax_refresh_clients() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'wecoza_cf7_nonce')) {
            wp_die('Security check failed');
        }

        // Refresh client cache
        $clients = $this->client_service->refresh_cache();

        // Return JSON response
        wp_send_json_success(array(
            'clients' => $clients,
            'count' => count($clients),
            'message' => sprintf(__('Refreshed %d clients', 'wecoza-classes-cf7'), count($clients))
        ));
    }

    /**
     * AJAX handler to get sites by client ID
     */
    public function ajax_get_sites_by_client() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'wecoza_cf7_nonce')) {
            wp_die('Security check failed');
        }

        // Get client ID from request
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;

        if (empty($client_id)) {
            wp_send_json_error('Client ID is required');
        }

        // Get sites for the client
        $sites = $this->site_service->get_sites_by_client($client_id);

        // Return JSON response
        wp_send_json_success(array(
            'sites' => $sites,
            'client_id' => $client_id,
            'count' => count($sites),
            'message' => sprintf(__('Found %d sites for client', 'wecoza-classes-cf7'), count($sites))
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with CF7 forms
        if (!$this->has_cf7_form()) {
            return;
        }
        
        wp_enqueue_script(
            'wecoza-cf7-integration',
            WECOZA_CF7_PLUGIN_URL . 'assets/js/cf7-integration.js',
            array('jquery'),
            WECOZA_CF7_VERSION,
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('wecoza-cf7-integration', 'wecozaCF7', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wecoza_cf7_nonce'),
            'strings' => array(
                'refreshing' => __('Refreshing clients...', 'wecoza-classes-cf7'),
                'error' => __('Error refreshing clients', 'wecoza-classes-cf7'),
                'success' => __('Clients refreshed successfully', 'wecoza-classes-cf7')
            )
        ));
    }
    
    /**
     * Check if current page has CF7 forms
     *
     * @return bool
     */
    private function has_cf7_form() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if post content contains CF7 shortcode
        return has_shortcode($post->post_content, 'contact-form-7');
    }
    
    /**
     * Get client options for a specific form
     *
     * @param int $form_id CF7 form ID
     * @return array Client options
     */
    public function get_client_options_for_form($form_id = null) {
        return $this->client_service->get_clients_for_cf7();
    }
    
    /**
     * Validate client ID from form submission
     *
     * @param int $client_id Client ID to validate
     * @return bool True if valid
     */
    public function validate_client_id($client_id) {
        if (empty($client_id) || !is_numeric($client_id)) {
            return false;
        }

        return $this->client_service->client_exists((int) $client_id);
    }

    /**
     * Validate site ID from form submission
     *
     * @param int $site_id Site ID to validate
     * @param int $client_id Client ID to validate against
     * @return bool True if valid
     */
    public function validate_site_id($site_id, $client_id = null) {
        if (empty($site_id) || !is_numeric($site_id)) {
            return false;
        }

        if ($client_id) {
            // Validate that site belongs to the specified client
            return $this->site_service->validate_site_client_combination($site_id, $client_id);
        } else {
            // Just validate that site exists
            $site = $this->site_service->get_site_by_id($site_id);
            return $site !== null;
        }
    }
    
    /**
     * Get debug information
     *
     * @return array Debug info
     */
    public function get_debug_info() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return array('debug' => 'disabled');
        }
        
        return array(
            'cf7_active' => class_exists('WPCF7'),
            'clients_count' => $this->client_service->get_clients_count(),
            'sites_count' => $this->site_service->get_sites_count(),
            'clients_cache_status' => $this->client_service->get_cache_status(),
            'sites_cache_status' => $this->site_service->get_cache_status(),
            'database_connected' => WeCoza_Database_Service::get_instance()->is_connected()
        );
    }

    /**
     * Validate dynamic select fields (client_id and site_id)
     *
     * This method overrides CF7's default validation for our dynamic fields
     * since CF7 validates against the original form definition, but our
     * options are populated dynamically via AJAX.
     *
     * @param WPCF7_Validation $result CF7 validation result object
     * @param WPCF7_FormTag $tag CF7 form tag object
     * @return WPCF7_Validation Modified validation result
     */
    public function validate_dynamic_select($result, $tag) {
        // Debug logging
        error_log('WeCoza CF7: validate_dynamic_select called for field: ' . $tag->name);

        // Only validate our dynamic fields
        if (!in_array($tag->name, array('client_id', 'site_id'))) {
            error_log('WeCoza CF7: Skipping validation for field: ' . $tag->name);
            return $result;
        }

        // Get submitted value
        $value = isset($_POST[$tag->name]) ? sanitize_text_field($_POST[$tag->name]) : '';
        error_log('WeCoza CF7: Validating field ' . $tag->name . ' with value: ' . $value);

        // Handle required field validation
        if ($tag->is_required() && empty($value)) {
            error_log('WeCoza CF7: Required field ' . $tag->name . ' is empty');
            $result->invalidate($tag, wpcf7_get_message('invalid_required'));
            return $result;
        }

        // Skip validation if field is empty and not required
        if (empty($value)) {
            error_log('WeCoza CF7: Field ' . $tag->name . ' is empty but not required');
            return $result;
        }

        // Validate client_id field
        if ($tag->name === 'client_id') {
            // Convert to integer for existing validation method
            $client_id_int = (int) $value;
            error_log('WeCoza CF7: Validating client_id: ' . $client_id_int);

            if (!$this->validate_client_id($client_id_int)) {
                error_log('WeCoza CF7: Client validation failed for: ' . $client_id_int);
                $result->invalidate($tag, 'Please select a valid client.');
                return $result;
            }
            error_log('WeCoza CF7: Client validation passed for: ' . $client_id_int);
        }

        // Validate site_id field
        if ($tag->name === 'site_id') {
            $client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';
            // Convert both to integers for existing validation method
            $site_id_int = (int) $value;
            $client_id_int = (int) $client_id;

            error_log('WeCoza CF7: Validating site_id: ' . $site_id_int . ' for client: ' . $client_id_int);

            if (!$this->validate_site_id($site_id_int, $client_id_int)) {
                error_log('WeCoza CF7: Site validation failed for site: ' . $site_id_int . ', client: ' . $client_id_int);
                $result->invalidate($tag, 'Please select a valid site for the chosen client.');
                return $result;
            }
            error_log('WeCoza CF7: Site validation passed for site: ' . $site_id_int . ', client: ' . $client_id_int);
        }

        error_log('WeCoza CF7: Validation passed for field: ' . $tag->name);
        return $result;
    }



    /**
     * Validate entire form submission for dynamic fields
     *
     * This is a backup validation that runs on the entire form
     * to catch any validation issues that the individual field
     * validation might have missed.
     *
     * @param WPCF7_Validation $result CF7 validation result object
     * @param WPCF7_FormTag[] $tags Array of all form tags
     * @return WPCF7_Validation Modified validation result
     */
    public function validate_form_submission($result, $tags) {
        error_log('WeCoza CF7: validate_form_submission called');

        // Check if this form has our dynamic fields
        $has_dynamic_fields = false;
        foreach ($tags as $tag) {
            if (in_array($tag->name, array('client_id', 'site_id'))) {
                $has_dynamic_fields = true;
                break;
            }
        }

        if (!$has_dynamic_fields) {
            return $result;
        }

        // Re-validate our dynamic fields at form level
        foreach ($tags as $tag) {
            if (in_array($tag->name, array('client_id', 'site_id'))) {
                $this->validate_dynamic_select($result, $tag);
            }
        }

        return $result;
    }

    /**
     * Modify form elements to add data attributes for validation
     *
     * This adds data attributes to our dynamic select fields
     * to help with client-side validation fixes.
     *
     * @param string $form_elements The form HTML
     * @return string Modified form HTML
     */
    public function modify_form_elements($form_elements) {
        // Add data attributes to help with client-side validation
        $form_elements = str_replace(
            'name="client_id"',
            'name="client_id" data-wecoza-dynamic="true"',
            $form_elements
        );

        $form_elements = str_replace(
            'name="site_id"',
            'name="site_id" data-wecoza-dynamic="true"',
            $form_elements
        );

        return $form_elements;
    }

    /**
     * Modify CF7 Schema-based Validation (SWV) for dynamic fields
     *
     * CF7 5.9+ introduced enum validation that validates select values against
     * a schema created at form render time. Since our options are populated
     * dynamically, they're not in the original schema and cause validation errors.
     *
     * This method removes enum validation for our dynamic fields and replaces
     * it with custom validation that can handle dynamic values.
     *
     * @param array $schema The validation schema
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function modify_swv_schema($schema, $contact_form) {
        error_log('WeCoza CF7: modify_swv_schema called for form: ' . $contact_form->id());

        // Get form tags to check if this form has our dynamic fields
        $form_tags = $contact_form->scan_form_tags();
        $has_dynamic_fields = false;
        $dynamic_field_names = array();

        foreach ($form_tags as $tag) {
            if (in_array($tag->name, array('client_id', 'site_id'))) {
                $has_dynamic_fields = true;
                $dynamic_field_names[] = $tag->name;
                error_log('WeCoza CF7: Found dynamic field: ' . $tag->name);
            }
        }

        if (!$has_dynamic_fields) {
            error_log('WeCoza CF7: No dynamic fields found in form');
            return $schema;
        }

        // Note: $schema is a WPCF7_SWV_Schema object, not an array
        // We cannot directly modify the schema object's internal properties
        // Instead, we'll rely on our custom enum validation replacement
        // which removes the default enum rules and adds our own

        error_log('WeCoza CF7: Schema object detected, relying on custom enum validation replacement for dynamic fields: ' . implode(', ', $dynamic_field_names));

        // Return the schema object unchanged - our custom enum validation handles the fix
    }

    /**
     * Disable enum validation for forms containing dynamic fields
     *
     * This is an alternative/backup approach that completely removes
     * enum validation for forms that contain our dynamic fields.
     *
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function disable_enum_validation_for_dynamic_forms($contact_form) {
        // Check if this form has our dynamic fields
        $form_tags = $contact_form->scan_form_tags();
        $has_dynamic_fields = false;

        foreach ($form_tags as $tag) {
            if (in_array($tag->name, array('client_id', 'site_id'))) {
                $has_dynamic_fields = true;
                break;
            }
        }

        if ($has_dynamic_fields) {
            // Remove the enum validation action for this form
            remove_action('wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20);
            error_log('WeCoza CF7: Disabled enum validation for form with dynamic fields: ' . $contact_form->id());
        }
    }

    /**
     * Replace CF7's enum validation with our custom validation
     *
     * This method removes the default enum validation and replaces it
     * with our own validation that can handle dynamic values.
     */
    public function replace_enum_validation_with_custom() {
        // Remove the default enum validation
        remove_action('wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20);

        // Add our custom enum validation
        add_action('wpcf7_swv_create_schema', array($this, 'add_custom_select_enum_rules'), 20, 2);

        error_log('WeCoza CF7: Replaced default enum validation with custom validation');
    }

    /**
     * Add custom enum validation rules for select fields
     *
     * This replaces CF7's default enum validation with our own that
     * can handle dynamic values for client_id and site_id fields.
     *
     * Based on CF7's original implementation but with dynamic field support.
     *
     * @param array $schema The validation schema
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function add_custom_select_enum_rules($schema, $contact_form) {
        // Use CF7's exact tag scanning approach from the original commit
        $tags = $contact_form->scan_form_tags(array(
            'basetype' => array('select')
        ));

        // Use CF7's exact value collection approach
        $values = array_reduce(
            $tags,
            function ($values, $tag) {
                if (!isset($values[$tag->name])) {
                    $values[$tag->name] = array();
                }

                // Skip our dynamic fields - they'll be validated by database validation
                if (in_array($tag->name, array('client_id', 'site_id'))) {
                    error_log('WeCoza CF7: Skipping enum validation for dynamic field: ' . $tag->name);
                    return $values;
                }

                // Use CF7's exact value collection logic for non-dynamic fields
                $tag_values = array_merge(
                    (array) $tag->values,
                    (array) $tag->get_data_option()
                );

                // Handle first_as_label option exactly like CF7
                if ($tag->has_option('first_as_label')) {
                    $tag_values = array_slice($tag_values, 1);
                }

                $values[$tag->name] = array_merge(
                    $values[$tag->name],
                    $tag_values
                );

                return $values;
            },
            array()
        );

        // Apply CF7's exact validation rule creation for non-dynamic fields
        foreach ($values as $field => $field_values) {
            $field_values = array_filter(
                array_unique($field_values),
                function ($value) {
                    return is_string($value) && '' !== $value;
                }
            );

            if (!empty($field_values)) {
                $schema->add_rule(
                    wpcf7_swv_create_rule('enum', array(
                        'field' => $field,
                        'accept' => array_values($field_values),
                        'error' => $contact_form->filter_message(
                            __("Undefined value was submitted through this field.", 'contact-form-7')
                        ),
                    ))
                );

                error_log('WeCoza CF7: Added enum validation for static field: ' . $field . ' with values: ' . implode(', ', $field_values));
            }
        }
    }



    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
