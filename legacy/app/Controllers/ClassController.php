<?php
/**
 * ClassController.php
 *
 * Controller for handling class-related operations
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Controllers;

use WeCozaClasses\Models\ClassModel;
use WeCozaClasses\Controllers\ClassTypesController;
use WeCozaClasses\Controllers\PublicHolidaysController;

// WordPress functions are in global namespace
// We'll access them directly with the global namespace prefix
// Example: \add_action() instead of add_action()

class ClassController {

    /**
     * Constructor
     */
    public function __construct() {
        // Register WordPress hooks
        \add_action('init', [$this, 'registerShortcodes']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // Ensure required pages exist
        \add_action('init', [$this, 'ensureRequiredPages']);
        
        // Register AJAX handlers
        \add_action('wp_ajax_save_class', [__CLASS__, 'saveClassAjax']);
        \add_action('wp_ajax_nopriv_save_class', [__CLASS__, 'saveClassAjax']);
        \add_action('wp_ajax_delete_class', [__CLASS__, 'deleteClassAjax']);
        \add_action('wp_ajax_nopriv_delete_class', [__CLASS__, 'deleteClassAjax']);
        \add_action('wp_ajax_get_calendar_events', [__CLASS__, 'getCalendarEventsAjax']);
        \add_action('wp_ajax_nopriv_get_calendar_events', [__CLASS__, 'getCalendarEventsAjax']);
        \add_action('wp_ajax_get_class_subjects', [__CLASS__, 'getClassSubjectsAjax']);
        \add_action('wp_ajax_nopriv_get_class_subjects', [__CLASS__, 'getClassSubjectsAjax']);
    }

    /**
     * Register all class-related shortcodes
     */
    public function registerShortcodes() {
        \add_shortcode('wecoza_capture_class', [$this, 'captureClassShortcode']);
        \add_shortcode('wecoza_display_classes', [$this, 'displayClassesShortcode']);
        \add_shortcode('wecoza_display_single_class', [$this, 'displaySingleClassShortcode']);
    }

    /**
     * Ensure required pages exist for the plugin functionality
     */
    public function ensureRequiredPages() {
        // Only run this once per request and only for admin users
        if (!\current_user_can('manage_options') || \get_transient('wecoza_pages_checked')) {
            return;
        }

        // Set transient to prevent multiple checks
        \set_transient('wecoza_pages_checked', true, HOUR_IN_SECONDS);

        // Check if display-single-class page exists
        $class_details_page = \get_page_by_path('app/display-single-class');

        if (!$class_details_page) {
            // Get or create app parent page
            $app_page = \get_page_by_path('app');
            $app_page_id = 0;

            if (!$app_page) {
                $app_page_id = \wp_insert_post([
                    'post_title' => 'App',
                    'post_content' => '<h2>WeCoza Application</h2><p>Welcome to the WeCoza training management system.</p>',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => 'app',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ]);
            } else {
                $app_page_id = $app_page->ID;
            }

            // Create display-single-class page
            if ($app_page_id && !\is_wp_error($app_page_id)) {
                $page_id = \wp_insert_post([
                    'post_title' => 'Display Single Class',
                    'post_content' => '<h2>Class Details</h2>
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
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => 'display-single-class',
                    'post_parent' => $app_page_id,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ]);

                if ($page_id && !\is_wp_error($page_id)) {
                    \error_log("WeCoza Classes Plugin: Created display-single-class page with ID {$page_id}");
                }
            }
        }
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueueAssets() {
        // Only enqueue on pages that use our shortcodes
        if (!$this->shouldEnqueueAssets()) {
            return;
        }

        // FullCalendar CDN - Latest version from Context7
        // \wp_enqueue_style(
        //     'fullcalendar-css',
        //     'https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.css',
        //     [],
        //     '6.1.15'
        // );

        \wp_enqueue_script(
            'fullcalendar',
            'https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js',
            [],
            '6.1.15', // Latest stable version
            true
        );

        \wp_enqueue_script(
            'wecoza-calendar-js',
            WECOZA_CLASSES_JS_URL . 'wecoza-calendar.js',
            ['jquery', 'fullcalendar'],
            WECOZA_CLASSES_VERSION,
            true
        );

        // Plugin JavaScript files
        \wp_enqueue_script(
            'wecoza-class-js',
            WECOZA_CLASSES_JS_URL . 'class-capture.js',
            ['jquery'],
            WECOZA_CLASSES_VERSION,
            true
        );

        \wp_enqueue_script(
            'wecoza-class-schedule-form-js',
            WECOZA_CLASSES_JS_URL . 'class-schedule-form.js',
            ['jquery', 'wecoza-learner-level-utils-js'],
            WECOZA_CLASSES_VERSION,
            true
        );

        \wp_enqueue_script(
            'wecoza-learner-level-utils-js',
            WECOZA_CLASSES_JS_URL . 'learner-level-utils.js',
            ['jquery'],
            WECOZA_CLASSES_VERSION,
            true
        );

        \wp_enqueue_script(
            'wecoza-class-types-js',
            WECOZA_CLASSES_JS_URL . 'class-types.js',
            ['jquery', 'wecoza-class-js', 'wecoza-learner-level-utils-js'],
            WECOZA_CLASSES_VERSION,
            true
        );

        \wp_enqueue_script(
            'wecoza-classes-table-search-js',
            WECOZA_CLASSES_JS_URL . 'classes-table-search.js',
            ['jquery'],
            WECOZA_CLASSES_VERSION,
            true
        );

        // Localize script with AJAX URL and nonce
        \wp_localize_script('wecoza-class-js', 'wecozaClass', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('wecoza_class_nonce'),
            'siteAddresses' => $this->getSiteAddresses(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'conflictCheckEnabled' => true
        ]);

        // Get public holidays data for the class schedule form
        try {
            $publicHolidaysController = PublicHolidaysController::getInstance();
            $currentYear = date('Y');
            $nextYear = $currentYear + 1;

            // Get holidays for current and next year to cover class schedules
            $currentYearHolidays = $publicHolidaysController->getHolidaysForCalendar($currentYear);
            $nextYearHolidays = $publicHolidaysController->getHolidaysForCalendar($nextYear);
            $allHolidays = array_merge($currentYearHolidays, $nextYearHolidays);

            // Localize public holidays data for class-schedule-form.js
            \wp_localize_script('wecoza-class-schedule-form-js', 'wecozaPublicHolidays', [
                'events' => $allHolidays
            ]);
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error loading public holidays: ' . $e->getMessage());
        }

        // Localize calendar data for wecoza-calendar.js
        \wp_localize_script('wecoza-calendar-js', 'wecozaCalendar', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('wecoza_calendar_nonce'),
            'fallbackCdn' => 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.17/index.global.min.js',
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }

    /**
     * Check if we should enqueue assets on current page
     *
     * @return bool
     */
    private function shouldEnqueueAssets() {
        global $post;
        
        if (!$post) {
            return false;
        }

        // Check if any of our shortcodes are present in the content
        $shortcodes = ['wecoza_capture_class', 'wecoza_display_classes', 'wecoza_display_single_class'];
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get site addresses based on client and site selection
     *
     * @return array
     */
    private function getSiteAddresses() {
        try {
            // Get database service
            $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();

            // Query site addresses from database
            $sql = "SELECT site_id, address FROM public.sites WHERE address IS NOT NULL AND address != ''";
            $stmt = $db->query($sql);

            $addresses = [];
            while ($row = $stmt->fetch()) {
                $site_id = (int)$row['site_id'];
                $address = sanitize_textarea_field($row['address']);

                if (!empty($address)) {
                    $addresses[$site_id] = $address;
                }
            }

            return $addresses;

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error fetching site addresses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Handle class capture shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function captureClassShortcode($atts) {
        // Process shortcode attributes
        $atts = \shortcode_atts([
            'redirect_url' => '',
        ], $atts);

        // Check for URL parameters to determine mode
        $mode = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : 'create';
        $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

        // Handle different modes
        if ($mode === 'update') {
            // For testing: allow update mode without class_id to see all fields
            if ($class_id <= 0) {
                return $this->handleUpdateMode($atts, null); // Pass null for testing
            }
            return $this->handleUpdateMode($atts, $class_id);
        } else {
            return $this->handleCreateMode($atts);
        }
    }

    /**
     * Handle create mode logic
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    private function handleCreateMode($atts) {
        // Get data for the view
        $viewData = [
            'mode' => 'create',
            'class_data' => null,
            'clients' => $this->getClients(),
            'sites' => $this->getSites(),
            'agents' => $this->getAgents(),
            'supervisors' => $this->getSupervisors(),
            'learners' => $this->getLearnersExam(),
            'setas' => $this->getSeta(),
            'class_types' => $this->getClassType(),
            'yes_no_options' => $this->getYesNoOptions(),
            'class_notes_options' => $this->getClassNotesOptions(),
            'redirect_url' => $atts['redirect_url']
        ];

        // Render the view
        return \WeCozaClasses\view('components/class-capture-form', $viewData);
    }

    /**
     * Handle update mode logic
     *
     * @param array $atts Shortcode attributes
     * @param int|null $class_id Class ID to update
     * @return string HTML output
     */
    private function handleUpdateMode($atts, $class_id) {
        $class = null;
        
        if ($class_id) {
            // Get existing class data
            $class = $this->getSingleClass($class_id);
            
            if (empty($class)) {
                return '<div class="alert alert-danger">Class not found.</div>';
            }
        }

        // Get data for the view
        $viewData = [
            'mode' => 'update',
            'class_data' => $class,
            'class_id' => $class_id,
            'clients' => $this->getClients(),
            'sites' => $this->getSites(),
            'agents' => $this->getAgents(),
            'supervisors' => $this->getSupervisors(),
            'learners' => $this->getLearnersExam(),
            'setas' => $this->getSeta(),
            'class_types' => $this->getClassType(),
            'yes_no_options' => $this->getYesNoOptions(),
            'class_notes_options' => $this->getClassNotesOptions(),
            'redirect_url' => $atts['redirect_url']
        ];

        // Render the view
        return \WeCozaClasses\view('components/class-capture-form', $viewData);
    }

    // Placeholder methods for data retrieval - these will need to be implemented
    // based on how the plugin will access the data (from theme integration or plugin-specific)
    
    private function getClients() {
        try {
            // Get database service
            $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();

            // Query clients from database
            $sql = "SELECT client_id, client_name FROM public.clients ORDER BY client_name ASC";
            $stmt = $db->query($sql);

            $clients = [];
            while ($row = $stmt->fetch()) {
                $clients[] = [
                    'id' => (int)$row['client_id'],
                    'name' => sanitize_text_field($row['client_name'])
                ];
            }

            return $clients;

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error fetching clients: ' . $e->getMessage());
            return [];
        }
    }

    private function getSites() {
        try {
            // Get database service
            $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();

            // Query sites from database with client information
            $sql = "SELECT s.site_id, s.client_id, s.site_name, s.address
                    FROM public.sites s
                    ORDER BY s.client_id ASC, s.site_name ASC";
            $stmt = $db->query($sql);

            $sites = [];
            while ($row = $stmt->fetch()) {
                $client_id = (int)$row['client_id'];

                if (!isset($sites[$client_id])) {
                    $sites[$client_id] = [];
                }

                $sites[$client_id][] = [
                    'id' => (int)$row['site_id'],
                    'name' => sanitize_text_field($row['site_name']),
                    'address' => sanitize_textarea_field($row['address'])
                ];
            }

            return $sites;

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error fetching sites: ' . $e->getMessage());
            return [];
        }
    }

    private function getAgents() {
        // Static agent data - in production this would come from database
        return [
            ['id' => 1, 'name' => 'Michael M. van der Berg'],
            ['id' => 2, 'name' => 'Thandi T. Nkosi'],
            ['id' => 3, 'name' => 'Rajesh R. Patel'],
            ['id' => 4, 'name' => 'Lerato L. Moloi'],
            ['id' => 5, 'name' => 'Johannes J. Pretorius'],
            ['id' => 6, 'name' => 'Nomvula N. Dlamini'],
            ['id' => 7, 'name' => 'David D. O\'Connor'],
            ['id' => 8, 'name' => 'Zanele Z. Mthembu'],
            ['id' => 9, 'name' => 'Pieter P. van Zyl'],
            ['id' => 10, 'name' => 'Fatima F. Ismail'],
            ['id' => 11, 'name' => 'Sipho S. Ndlovu'],
            ['id' => 12, 'name' => 'Anita A. van Rensburg'],
            ['id' => 13, 'name' => 'Kobus K. Steyn'],
            ['id' => 14, 'name' => 'Nalini N. Reddy'],
            ['id' => 15, 'name' => 'Willem W. Botha']
        ];
    }

    private function getSupervisors() {
        // Static supervisor data - in production this would come from database
        return [
            ['id' => 1, 'name' => 'Dr. Sarah Johnson'],
            ['id' => 2, 'name' => 'Prof. Michael Smith'],
            ['id' => 3, 'name' => 'Ms. Jennifer Brown'],
            ['id' => 4, 'name' => 'Mr. David Wilson'],
            ['id' => 5, 'name' => 'Dr. Lisa Anderson']
        ];
    }

    private function getLearnersExam() {
        // Static learner data for exam classes - in production this would come from database
        return [
            ['id' => 1, 'name' => 'John Doe', 'id_number' => '8001015009088'],
            ['id' => 2, 'name' => 'Jane Smith', 'id_number' => '8505123456789'],
            ['id' => 3, 'name' => 'Mike Johnson', 'id_number' => '9002087654321'],
            ['id' => 4, 'name' => 'Sarah Wilson', 'id_number' => '8712034567890'],
            ['id' => 5, 'name' => 'David Brown', 'id_number' => '9105156789012']
        ];
    }

    private function getSeta() {
        // Static SETA data - in production this would come from database
        return [
            ['id' => 'BANKSETA', 'name' => 'Banking Sector Education and Training Authority'],
            ['id' => 'CHIETA', 'name' => 'Chemical Industries Education and Training Authority'],
            ['id' => 'CETA', 'name' => 'Construction Education and Training Authority'],
            ['id' => 'ETDP', 'name' => 'Education, Training and Development Practices SETA'],
            ['id' => 'FASSET', 'name' => 'Finance and Accounting Services SETA'],
            ['id' => 'FOODBEV', 'name' => 'Food and Beverages Manufacturing Industry SETA'],
            ['id' => 'HWSETA', 'name' => 'Health and Welfare SETA'],
            ['id' => 'INSETA', 'name' => 'Insurance Sector Education and Training Authority'],
            ['id' => 'LGSETA', 'name' => 'Local Government Sector Education and Training Authority'],
            ['id' => 'MERSETA', 'name' => 'Manufacturing, Engineering and Related Services SETA']
        ];
    }

    private function getClassType() {
        // Use the ClassTypesController to get class types
        return ClassTypesController::getClassTypes();
    }

    private function getYesNoOptions() {
        return array(
            array('id' => 1, 'name' => 'Yes'),
            array('id' => 0, 'name' => 'No')
        );
    }

    private function getClassNotesOptions() {
        // Static class notes options - in production this could come from database
        return [
            ['id' => 'Agent Absent', 'name' => 'Agent Absent'],
            ['id' => 'Client Cancelled', 'name' => 'Client Cancelled'],
            ['id' => 'Poor attendance', 'name' => 'Poor attendance'],
            ['id' => 'Learners behind schedule', 'name' => 'Learners behind schedule'],
            ['id' => 'Learners unhappy', 'name' => 'Learners unhappy'],
            ['id' => 'Client unhappy', 'name' => 'Client unhappy'],
            ['id' => 'Learners too fast', 'name' => 'Learners too fast'],
            ['id' => 'Class on track', 'name' => 'Class on track'],
            ['id' => 'Bad QA report', 'name' => 'Bad QA report'],
            ['id' => 'Good QA report', 'name' => 'Good QA report'],
            ['id' => 'Venue issues', 'name' => 'Venue issues'],
            ['id' => 'Equipment problems', 'name' => 'Equipment problems'],
            ['id' => 'Material shortage', 'name' => 'Material shortage'],
            ['id' => 'Weather delay', 'name' => 'Weather delay'],
            ['id' => 'Holiday adjustment', 'name' => 'Holiday adjustment']
        ];
    }

    /**
     * Handle AJAX request to save class data
     */
    public static function saveClassAjax() {
        error_log('=== CLASS SAVE AJAX START ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));

        // Create instance
        $instance = new self();

        // Check nonce for security
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], 'wecoza_class_nonce')) {
            error_log('Nonce verification failed');
            $instance->sendJsonError('Security check failed.');
            return;
        }

        error_log('Nonce verification passed');

        // Process form data (including file uploads)
        $formData = self::processFormData($_POST, $_FILES);
        error_log('Processed form data: ' . print_r($formData, true));

        // Determine if this is create or update operation
        $isUpdate = isset($formData['id']) && !empty($formData['id']);
        $classId = $isUpdate ? intval($formData['id']) : null;

        error_log($isUpdate ? "Updating existing class with ID: {$classId}" : 'Creating new class');

        // Use direct model access for create or update
        try {
            if ($isUpdate) {
                // Load existing class and update it
                $class = ClassModel::getById($classId);
                if (!$class) {
                    error_log('Class not found for update: ' . $classId);
                    $instance->sendJsonError('Class not found for update.');
                    return;
                }

                // Update the class with new data
                $class = self::populateClassModel($class, $formData);
                $result = $class->update();
            } else {
                // Create new class instance and save it
                $class = new ClassModel();
                $class = self::populateClassModel($class, $formData);
                $result = $class->save();
            }

            if ($result) {
                error_log('Class saved successfully with ID: ' . $class->getId());
                $instance->sendJsonSuccess([
                    'message' => $isUpdate ? 'Class updated successfully.' : 'Class created successfully.',
                    'class_id' => $class->getId()
                ]);
            } else {
                error_log('Model operation failed');
                $instance->sendJsonError(
                    $isUpdate ? 'Failed to update class.' : 'Failed to create class.'
                );
            }
        } catch (\Exception $e) {
            error_log('Exception during class save: ' . $e->getMessage());
            $instance->sendJsonError('An error occurred while saving the class: ' . $e->getMessage());
        }
    }

    /**
     * Populate ClassModel with form data
     *
     * @param ClassModel $class Class model instance
     * @param array $formData Processed form data
     * @return ClassModel
     */
    private static function populateClassModel($class, $formData) {
        // Set all the properties from form data
        if (isset($formData['client_id'])) $class->setClientId($formData['client_id']);
        if (isset($formData['site_id'])) $class->setSiteId($formData['site_id']);
        if (isset($formData['site_address'])) $class->setClassAddressLine($formData['site_address']);
        if (isset($formData['class_type'])) $class->setClassType($formData['class_type']);
        if (isset($formData['class_subject'])) $class->setClassSubject($formData['class_subject']);
        if (isset($formData['class_code'])) $class->setClassCode($formData['class_code']);
        if (isset($formData['class_duration'])) $class->setClassDuration($formData['class_duration']);
        if (isset($formData['original_start_date'])) $class->setOriginalStartDate($formData['original_start_date']);
        if (isset($formData['seta_funded'])) $class->setSetaFunded($formData['seta_funded']);
        if (isset($formData['seta'])) $class->setSeta($formData['seta']);
        if (isset($formData['exam_class'])) $class->setExamClass($formData['exam_class']);
        if (isset($formData['exam_type'])) $class->setExamType($formData['exam_type']);
        if (isset($formData['qa_visit_dates'])) $class->setQaVisitDates($formData['qa_visit_dates']);
        if (isset($formData['qa_reports'])) $class->setQaReports($formData['qa_reports']);
        if (isset($formData['class_agent'])) $class->setClassAgent($formData['class_agent']);
        if (isset($formData['initial_class_agent'])) $class->setInitialClassAgent($formData['initial_class_agent']);
        if (isset($formData['initial_agent_start_date'])) $class->setInitialAgentStartDate($formData['initial_agent_start_date']);
        if (isset($formData['project_supervisor'])) $class->setProjectSupervisorId($formData['project_supervisor']);
        if (isset($formData['delivery_date'])) $class->setDeliveryDate($formData['delivery_date']);
        if (isset($formData['learner_ids'])) $class->setLearnerIds($formData['learner_ids']);
        if (isset($formData['backup_agent_ids'])) $class->setBackupAgentIds($formData['backup_agent_ids']);
        if (isset($formData['schedule_data'])) $class->setScheduleData($formData['schedule_data']);
        if (isset($formData['stop_restart_dates'])) $class->setStopRestartDates($formData['stop_restart_dates']);
        if (isset($formData['class_notes'])) $class->setClassNotesData($formData['class_notes']);

        return $class;
    }

    /**
     * Send JSON success response
     *
     * @param array $data Response data
     */
    private function sendJsonSuccess($data) {
        \wp_send_json_success($data);
    }

    /**
     * Send JSON error response
     *
     * @param string $message Error message
     */
    private function sendJsonError($message) {
        \wp_send_json_error($message);
    }

    /**
     * Process form data from POST and FILES
     *
     * @param array $data POST data
     * @param array $files FILES data
     * @return array Processed form data
     */
    private static function processFormData($data, $files = []) {
        $processed = [];

        // Basic fields - using snake_case field names that the model expects
        $processed['id'] = isset($data['class_id']) && $data['class_id'] !== 'auto-generated' ? intval($data['class_id']) : null;
        $processed['client_id'] = isset($data['client_id']) && !empty($data['client_id']) ? intval($data['client_id']) : null;
        $processed['site_id'] = isset($data['site_id']) && !is_array($data['site_id']) ? $data['site_id'] : null;
        $processed['site_address'] = isset($data['site_address']) && !is_array($data['site_address']) ? self::sanitizeText($data['site_address']) : null;
        $processed['class_type'] = isset($data['class_type']) && !is_array($data['class_type']) ? self::sanitizeText($data['class_type']) : null;
        $processed['class_subject'] = isset($data['class_subject']) && !is_array($data['class_subject']) ? self::sanitizeText($data['class_subject']) : null;
        $processed['class_code'] = isset($data['class_code']) && !is_array($data['class_code']) ? self::sanitizeText($data['class_code']) : null;
        $processed['class_duration'] = isset($data['class_duration']) && !empty($data['class_duration']) ? intval($data['class_duration']) : null;
        $processed['original_start_date'] = isset($data['original_start_date']) && !is_array($data['original_start_date']) ? self::sanitizeText($data['original_start_date']) : null;
        $processed['seta_funded'] = isset($data['seta_funded']) ? (bool)$data['seta_funded'] : false;
        $processed['seta'] = isset($data['seta']) && !is_array($data['seta']) ? self::sanitizeText($data['seta']) : null;
        $processed['exam_class'] = isset($data['exam_class']) ? (bool)$data['exam_class'] : false;
        $processed['exam_type'] = isset($data['exam_type']) && !is_array($data['exam_type']) ? self::sanitizeText($data['exam_type']) : null;
        $processed['qa_visit_dates'] = isset($data['qa_visit_dates']) && !is_array($data['qa_visit_dates']) ? self::sanitizeText($data['qa_visit_dates']) : null;
        $processed['class_agent'] = isset($data['class_agent']) && !empty($data['class_agent']) ? intval($data['class_agent']) : null;
        $processed['initial_class_agent'] = isset($data['initial_class_agent']) && !empty($data['initial_class_agent']) ? intval($data['initial_class_agent']) : null;
        $processed['initial_agent_start_date'] = isset($data['initial_agent_start_date']) && !is_array($data['initial_agent_start_date']) ? self::sanitizeText($data['initial_agent_start_date']) : null;
        $processed['project_supervisor'] = isset($data['project_supervisor']) && !empty($data['project_supervisor']) ? intval($data['project_supervisor']) : null;
        $processed['delivery_date'] = isset($data['delivery_date']) && !is_array($data['delivery_date']) ? self::sanitizeText($data['delivery_date']) : null;

        // Array fields
        $processed['class_notes'] = isset($data['class_notes']) && is_array($data['class_notes']) ? array_map([self::class, 'sanitizeText'], $data['class_notes']) : [];
        $processed['qa_reports'] = isset($data['qa_reports']) && is_array($data['qa_reports']) ? array_map([self::class, 'sanitizeText'], $data['qa_reports']) : [];

        // JSON fields that need special handling
        $processed['learner_ids'] = self::processJsonField($data, 'class_learners_data');
        $processed['backup_agent_ids'] = self::processJsonField($data, 'backup_agents_data');
        $processed['schedule_data'] = self::processJsonField($data, 'schedule_data');
        $processed['stop_restart_dates'] = self::processJsonField($data, 'stop_restart_dates');

        return $processed;
    }

    /**
     * Process JSON field from form data with enhanced schedule data handling
     *
     * @param array $data Form data
     * @param string $field Field name
     * @return array Processed JSON data
     */
    private static function processJsonField($data, $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [];
        }

        $value = $data[$field];

        // Handle WordPress addslashes and HTML encoding
        if (is_string($value)) {
            $value = stripslashes($value);
            $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        }

        // Decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error for field {$field}: " . json_last_error_msg());
            return [];
        }

        // Special handling for schedule_data field
        if ($field === 'schedule_data' && !empty($decoded)) {
            $decoded = self::processScheduleData($decoded);
        }

        return $decoded ?: [];
    }

    /**
     * Process schedule data with format detection, validation, and conversion
     *
     * @param array $scheduleData Raw schedule data from form
     * @return array Processed schedule data in v2.0 format
     */
    private static function processScheduleData($scheduleData) {
        // Validate the raw data first
        $validator = new \WeCozaClasses\Services\ScheduleDataValidator();
        $validationResult = $validator->validate($scheduleData);

        // Log validation warnings
        if (!empty($validationResult['warnings'])) {
            error_log('Schedule data validation warnings: ' . implode(', ', $validationResult['warnings']));
        }

        // Handle validation errors
        if (!$validationResult['isValid']) {
            error_log('Schedule data validation failed: ' . implode(', ', $validationResult['errors']));
            // For now, continue processing but log the errors
            // In production, you might want to throw an exception or return an error
        }

        // Expect V2.0 format only
        return self::validateScheduleDataV2($scheduleData);
    }

    // V1 format detection functions removed - V2.0 format only

    // V1 to V2 conversion function removed - V2.0 format only

    // Legacy helper functions removed - V2.0 format only

    /**
     * Calculate duration in hours from start and end time
     *
     * @param string $startTime Start time (HH:MM)
     * @param string $endTime End time (HH:MM)
     * @return float Duration in hours
     */
    private static function calculateDuration($startTime, $endTime) {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($start === false || $end === false || $end <= $start) {
            return 0;
        }

        return ($end - $start) / 3600; // Convert seconds to hours
    }

    /**
     * Sanitize text input
     *
     * @param string $text Input text
     * @return string Sanitized text
     */
    private static function sanitizeText($text) {
        return sanitize_text_field($text);
    }

    /**
     * Validate and sanitize v2.0 schedule data format
     *
     * @param array $data v2.0 schedule data
     * @return array Validated and sanitized data
     */
    private static function validateScheduleDataV2($data) {
        $validated = [
            'version' => '2.0',
            'pattern' => 'weekly',
            'startDate' => '',
            'endDate' => '',
            'timeData' => [
                'mode' => 'single'
            ],
            'selectedDays' => [],
            'dayOfMonth' => null,
            'exceptionDates' => [],
            'holidayOverrides' => [],
            'metadata' => [
                'lastUpdated' => date('c'),
                'validatedAt' => date('c')
            ]
        ];

        // Validate version
        if (isset($data['version'])) {
            $validated['version'] = sanitize_text_field($data['version']);
        }

        // Validate pattern
        $allowedPatterns = ['weekly', 'biweekly', 'monthly', 'custom'];
        if (isset($data['pattern']) && in_array($data['pattern'], $allowedPatterns)) {
            $validated['pattern'] = $data['pattern'];
        }

        // Validate dates
        if (isset($data['startDate']) && self::isValidDate($data['startDate'])) {
            $validated['startDate'] = sanitize_text_field($data['startDate']);
        }

        if (isset($data['endDate']) && self::isValidDate($data['endDate'])) {
            $validated['endDate'] = sanitize_text_field($data['endDate']);
        }

        // Validate day of month for monthly pattern
        if (isset($data['dayOfMonth']) && is_numeric($data['dayOfMonth'])) {
            $dayOfMonth = intval($data['dayOfMonth']);
            if ($dayOfMonth >= 1 && $dayOfMonth <= 31) {
                $validated['dayOfMonth'] = $dayOfMonth;
            }
        }

        // Validate time data
        if (isset($data['timeData']) && is_array($data['timeData'])) {
            $validated['timeData'] = self::validateTimeData($data['timeData']);
        }

        // Validate selected days
        if (isset($data['selectedDays']) && is_array($data['selectedDays'])) {
            $allowedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $validated['selectedDays'] = array_intersect($data['selectedDays'], $allowedDays);
        }

        // Validate exception dates
        if (isset($data['exceptionDates']) && is_array($data['exceptionDates'])) {
            $validated['exceptionDates'] = self::validateExceptionDates($data['exceptionDates']);
        }

        // Validate holiday overrides
        if (isset($data['holidayOverrides']) && is_array($data['holidayOverrides'])) {
            $validated['holidayOverrides'] = self::validateHolidayOverrides($data['holidayOverrides']);
        }

        // Preserve metadata
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $validated['metadata'] = array_merge($validated['metadata'], $data['metadata']);
        }

        // Preserve generated schedule if present (for backward compatibility)
        if (isset($data['generatedSchedule']) && is_array($data['generatedSchedule'])) {
            $validated['generatedSchedule'] = $data['generatedSchedule'];
        }

        return $validated;
    }

    /**
     * Validate time data structure
     *
     * @param array $timeData Time data to validate
     * @return array Validated time data
     */
    private static function validateTimeData($timeData) {
        $validated = ['mode' => 'single'];

        // Validate mode
        $allowedModes = ['single', 'per-day'];
        if (isset($timeData['mode']) && in_array($timeData['mode'], $allowedModes)) {
            $validated['mode'] = $timeData['mode'];
        }

        // Validate single time data
        if ($validated['mode'] === 'single' && isset($timeData['single'])) {
            $validated['single'] = self::validateSingleTimeData($timeData['single']);
        }

        // Validate per-day time data
        if ($validated['mode'] === 'per-day' && isset($timeData['perDay'])) {
            $validated['perDay'] = self::validatePerDayTimeData($timeData['perDay']);
        }

        return $validated;
    }

    /**
     * Validate single time data
     *
     * @param array $singleData Single time data
     * @return array Validated single time data
     */
    private static function validateSingleTimeData($singleData) {
        $validated = [
            'startTime' => '',
            'endTime' => '',
            'duration' => 0
        ];

        if (isset($singleData['startTime']) && self::isValidTime($singleData['startTime'])) {
            $validated['startTime'] = sanitize_text_field($singleData['startTime']);
        }

        if (isset($singleData['endTime']) && self::isValidTime($singleData['endTime'])) {
            $validated['endTime'] = sanitize_text_field($singleData['endTime']);
        }

        if (isset($singleData['duration']) && is_numeric($singleData['duration'])) {
            $validated['duration'] = floatval($singleData['duration']);
        } else if ($validated['startTime'] && $validated['endTime']) {
            $validated['duration'] = self::calculateDuration($validated['startTime'], $validated['endTime']);
        }

        return $validated;
    }

    /**
     * Validate per-day time data
     *
     * @param array $perDayData Per-day time data
     * @return array Validated per-day time data
     */
    private static function validatePerDayTimeData($perDayData) {
        $validated = [];
        $allowedDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($perDayData as $day => $dayData) {
            if (in_array($day, $allowedDays) && is_array($dayData)) {
                $validated[$day] = self::validateSingleTimeData($dayData);
            }
        }

        return $validated;
    }

    /**
     * Validate exception dates array
     *
     * @param array $exceptionDates Exception dates to validate
     * @return array Validated exception dates
     */
    private static function validateExceptionDates($exceptionDates) {
        $validated = [];

        foreach ($exceptionDates as $exception) {
            if (is_array($exception) && isset($exception['date']) && self::isValidDate($exception['date'])) {
                $validException = [
                    'date' => sanitize_text_field($exception['date']),
                    'reason' => isset($exception['reason']) ? sanitize_text_field($exception['reason']) : 'No reason specified'
                ];
                $validated[] = $validException;
            }
        }

        return $validated;
    }

    /**
     * Validate holiday overrides
     *
     * @param array $holidayOverrides Holiday overrides to validate
     * @return array Validated holiday overrides
     */
    private static function validateHolidayOverrides($holidayOverrides) {
        $validated = [];

        foreach ($holidayOverrides as $date => $override) {
            if (self::isValidDate($date)) {
                $validated[sanitize_text_field($date)] = (bool) $override;
            }
        }

        return $validated;
    }

    /**
     * Check if a date string is valid
     *
     * @param string $date Date string to validate
     * @return bool True if valid date
     */
    private static function isValidDate($date) {
        if (!is_string($date)) {
            return false;
        }

        $timestamp = strtotime($date);
        return $timestamp !== false && date('Y-m-d', $timestamp) === $date;
    }

    /**
     * Check if a time string is valid (HH:MM format)
     *
     * @param string $time Time string to validate
     * @return bool True if valid time
     */
    private static function isValidTime($time) {
        if (!is_string($time)) {
            return false;
        }

        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * Convert v2.0 schedule data back to legacy format for calendar compatibility
     *
     * @param array $v2Data v2.0 schedule data
     * @return array Legacy format schedule entries
     */
    public static function convertV2ToLegacy($v2Data) {
        // If we have a cached generated schedule, use it
        if (isset($v2Data['generatedSchedule']) && is_array($v2Data['generatedSchedule'])) {
            return $v2Data['generatedSchedule'];
        }

        // Generate schedule entries from v2.0 pattern data
        return self::generateScheduleEntries($v2Data);
    }

    /**
     * Generate schedule entries from v2.0 pattern data
     *
     * @param array $v2Data v2.0 schedule data
     * @return array Generated schedule entries in legacy format
     */
    private static function generateScheduleEntries($v2Data) {
        $entries = [];

        if (!isset($v2Data['startDate']) || !isset($v2Data['endDate'])) {
            return $entries;
        }

        $startDate = new \DateTime($v2Data['startDate']);
        $endDate = new \DateTime($v2Data['endDate']);
        $pattern = $v2Data['pattern'] ?? 'weekly';
        $timeData = $v2Data['timeData'] ?? [];
        $selectedDays = $v2Data['selectedDays'] ?? [];

        // Generate entries based on pattern
        switch ($pattern) {
            case 'weekly':
                $entries = self::generateWeeklyEntries($startDate, $endDate, $timeData, $selectedDays);
                break;
            case 'biweekly':
                $entries = self::generateBiweeklyEntries($startDate, $endDate, $timeData, $selectedDays);
                break;
            case 'monthly':
                $entries = self::generateMonthlyEntries($startDate, $endDate, $timeData, $v2Data['dayOfMonth'] ?? 1);
                break;
            case 'custom':
            default:
                // For custom pattern, return minimal entry if we have time data
                if (isset($timeData['single'])) {
                    $entries[] = [
                        'date' => $v2Data['startDate'],
                        'start_time' => $timeData['single']['startTime'] ?? '09:00',
                        'end_time' => $timeData['single']['endTime'] ?? '17:00'
                    ];
                }
                break;
        }

        // Apply exception dates (remove entries on exception dates)
        if (isset($v2Data['exceptionDates']) && is_array($v2Data['exceptionDates'])) {
            $exceptionDates = array_column($v2Data['exceptionDates'], 'date');
            $entries = array_filter($entries, function($entry) use ($exceptionDates) {
                return !in_array($entry['date'], $exceptionDates);
            });
        }

        return array_values($entries); // Re-index array
    }

    /**
     * Generate weekly schedule entries
     *
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @param array $timeData Time data
     * @param array $selectedDays Selected days of week
     * @return array Schedule entries
     */
    private static function generateWeeklyEntries($startDate, $endDate, $timeData, $selectedDays) {
        $entries = [];
        $current = clone $startDate;

        while ($current <= $endDate) {
            $dayName = $current->format('l');

            if (in_array($dayName, $selectedDays)) {
                $times = self::getTimesForDay($timeData, $dayName);
                if ($times) {
                    $entries[] = [
                        'date' => $current->format('Y-m-d'),
                        'start_time' => $times['startTime'],
                        'end_time' => $times['endTime']
                    ];
                }
            }

            $current->add(new \DateInterval('P1D'));
        }

        return $entries;
    }

    /**
     * Generate biweekly schedule entries
     *
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @param array $timeData Time data
     * @param array $selectedDays Selected days of week
     * @return array Schedule entries
     */
    private static function generateBiweeklyEntries($startDate, $endDate, $timeData, $selectedDays) {
        $entries = [];
        $current = clone $startDate;
        $weekCount = 0;

        while ($current <= $endDate) {
            $dayName = $current->format('l');

            // Only add entries on even weeks (0, 2, 4, etc.)
            if ($weekCount % 2 === 0 && in_array($dayName, $selectedDays)) {
                $times = self::getTimesForDay($timeData, $dayName);
                if ($times) {
                    $entries[] = [
                        'date' => $current->format('Y-m-d'),
                        'start_time' => $times['startTime'],
                        'end_time' => $times['endTime']
                    ];
                }
            }

            // Increment week count on Sundays
            if ($current->format('N') == 7) {
                $weekCount++;
            }

            $current->add(new \DateInterval('P1D'));
        }

        return $entries;
    }

    /**
     * Generate monthly schedule entries
     *
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End date
     * @param array $timeData Time data
     * @param int $dayOfMonth Day of month for schedule
     * @return array Schedule entries
     */
    private static function generateMonthlyEntries($startDate, $endDate, $timeData, $dayOfMonth) {
        $entries = [];
        $current = clone $startDate;

        // Set to the specified day of month
        $current->setDate($current->format('Y'), $current->format('n'), $dayOfMonth);

        // If the day is before start date, move to next month
        if ($current < $startDate) {
            $current->add(new \DateInterval('P1M'));
            $current->setDate($current->format('Y'), $current->format('n'), $dayOfMonth);
        }

        while ($current <= $endDate) {
            $times = self::getTimesForDay($timeData, null); // Use single time for monthly
            if ($times) {
                $entries[] = [
                    'date' => $current->format('Y-m-d'),
                    'start_time' => $times['startTime'],
                    'end_time' => $times['endTime']
                ];
            }

            // Move to next month
            $current->add(new \DateInterval('P1M'));

            // Handle month-end edge cases
            $targetDay = min($dayOfMonth, $current->format('t')); // Last day of month if dayOfMonth > days in month
            $current->setDate($current->format('Y'), $current->format('n'), $targetDay);
        }

        return $entries;
    }

    /**
     * Get times for a specific day from time data
     *
     * @param array $timeData Time data structure
     * @param string|null $dayName Day name (for per-day mode) or null (for single mode)
     * @return array|null Times array with startTime and endTime, or null if not found
     */
    private static function getTimesForDay($timeData, $dayName = null) {
        $mode = $timeData['mode'] ?? 'single';

        if ($mode === 'per-day' && $dayName && isset($timeData['perDay'][$dayName])) {
            $dayData = $timeData['perDay'][$dayName];
            return [
                'startTime' => $dayData['startTime'] ?? '09:00',
                'endTime' => $dayData['endTime'] ?? '17:00'
            ];
        } else if ($mode === 'single' && isset($timeData['single'])) {
            return [
                'startTime' => $timeData['single']['startTime'] ?? '09:00',
                'endTime' => $timeData['single']['endTime'] ?? '17:00'
            ];
        }

        return null;
    }

    /**
     * Handle display classes shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function displayClassesShortcode($atts) {
        // Process shortcode attributes
        $atts = \shortcode_atts([
            'limit' => 50,
            'order_by' => 'created_at',
            'order' => 'DESC',
            'show_loading' => true,
        ], $atts);

        try {
            // Get all classes from database
            $classes = $this->getAllClasses($atts);

            // Enrich classes with agent names
            $classes = $this->enrichClassesWithAgentNames($classes);

            // Calculate active classes count (excluding currently stopped classes)
            $activeClassesCount = 0;
            foreach ($classes as $class) {
                if (!$this->isClassCurrentlyStopped($class)) {
                    $activeClassesCount++;
                }
            }

            // Prepare view data
            $viewData = [
                'classes' => $classes,
                'show_loading' => $atts['show_loading'],
                'total_count' => count($classes),
                'active_count' => $activeClassesCount,
                'controller' => $this
            ];

            // Render the view
            return \WeCozaClasses\view('components/classes-display', $viewData);

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error in displayClassesShortcode: ' . $e->getMessage());
            return '<div class="alert alert-danger">Error loading classes: ' . esc_html($e->getMessage()) . '</div>';
        }
    }

    /**
     * Handle display single class shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function displaySingleClassShortcode($atts) {
        // Process shortcode attributes
        $atts = \shortcode_atts([
            'class_id' => 0,
            'show_loading' => true,
        ], $atts);

        // Get class_id from URL parameter if not provided in shortcode
        $class_id = $atts['class_id'] ?: (isset($_GET['class_id']) ? intval($_GET['class_id']) : 0);

        if (empty($class_id) || $class_id <= 0) {
            return '<div class="alert alert-warning">No valid class ID provided.</div>';
        }

        try {
            // Get single class from database
            $class = $this->getSingleClass($class_id);

            // Prepare view data
            $viewData = [
                'class' => $class,
                'show_loading' => $atts['show_loading'],
                'error_message' => ''
            ];

            // If class not found, set error message
            if (empty($class)) {
                $viewData['error_message'] = "Class with ID {$class_id} was not found in the database.";
            }

            // Render the view
            return \WeCozaClasses\view('components/single-class-display', $viewData);

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error in displaySingleClassShortcode: ' . $e->getMessage());
            return '<div class="alert alert-danger">Error loading class: ' . esc_html($e->getMessage()) . '</div>';
        }
    }

    /**
     * Check if a class is currently stopped based on stop_restart_dates
     *
     * @param array $class Class data
     * @return bool True if class is currently stopped, false otherwise
     */
    public function isClassCurrentlyStopped($class) {
        // Check if stop_restart_dates field exists and has data
        if (empty($class['stop_restart_dates'])) {
            return false;
        }

        // Parse JSON if it's a string
        $stopRestartDates = is_string($class['stop_restart_dates'])
            ? json_decode($class['stop_restart_dates'], true)
            : $class['stop_restart_dates'];

        // If parsing failed or no data, class is not stopped
        if (!is_array($stopRestartDates) || empty($stopRestartDates)) {
            return false;
        }

        $currentDate = date('Y-m-d');

        // Check each stop/restart period
        foreach ($stopRestartDates as $period) {
            if (!isset($period['stop_date']) || !isset($period['restart_date'])) {
                continue;
            }

            $stopDate = $period['stop_date'];
            $restartDate = $period['restart_date'];

            // Check if current date falls between stop and restart dates (inclusive)
            if ($currentDate >= $stopDate && $currentDate <= $restartDate) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enrich classes array with agent names
     *
     * @param array $classes Array of class data
     * @return array Array of class data with agent names added
     */
    private function enrichClassesWithAgentNames($classes) {
        $agents = $this->getAgents();
        $agentLookup = [];

        // Create lookup array for faster access
        foreach ($agents as $agent) {
            $agentLookup[$agent['id']] = $agent['name'];
        }

        // Enrich each class with agent names
        foreach ($classes as &$class) {
            // Add current agent name
            if (!empty($class['class_agent'])) {
                $class['agent_name'] = $agentLookup[$class['class_agent']] ?? 'Unknown Agent';
            }

            // Add initial agent name
            if (!empty($class['initial_class_agent'])) {
                $class['initial_agent_name'] = $agentLookup[$class['initial_class_agent']] ?? 'Unknown Agent';
            }
        }

        return $classes;
    }

    /**
     * Get all classes from database with optional filtering
     *
     * @param array $options Query options (limit, order_by, order)
     * @return array Array of class data
     */
    private function getAllClasses($options = []) {
        $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();

        // Set defaults
        $limit = isset($options['limit']) ? intval($options['limit']) : 50;
        $order_by = isset($options['order_by']) ? $options['order_by'] : 'created_at';
        $order = isset($options['order']) ? strtoupper($options['order']) : 'DESC';

        // Validate order_by to prevent SQL injection
        $allowed_columns = [
            'class_id', 'client_id', 'class_type', 'class_subject',
            'original_start_date', 'delivery_date', 'created_at', 'updated_at'
        ];

        if (!in_array($order_by, $allowed_columns)) {
            $order_by = 'created_at';
        }

        // Validate order direction
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        // Build the query with client name JOIN
        $sql = "
            SELECT
                c.class_id,
                c.client_id,
                cl.client_name,
                c.class_type,
                c.class_subject,
                c.class_code,
                c.class_duration,
                c.original_start_date,
                c.delivery_date,
                c.seta_funded,
                c.seta,
                c.exam_class,
                c.exam_type,
                c.class_agent,
                c.initial_class_agent,
                c.project_supervisor_id,
                c.stop_restart_dates,
                c.created_at,
                c.updated_at
            FROM public.classes c
            LEFT JOIN public.clients cl ON c.client_id = cl.client_id
            ORDER BY c." . $order_by . " " . $order . "
            LIMIT " . $limit;

        try {
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error in getAllClasses: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single class from database
     *
     * @param int $class_id Class ID
     * @return array|null Class data or null if not found
     */
    private function getSingleClass($class_id) {
        $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();

        $sql = "
            SELECT
                c.*,
                cl.client_name
            FROM public.classes c
            LEFT JOIN public.clients cl ON c.client_id = cl.client_id
            WHERE c.class_id = :class_id
            LIMIT 1";

        try {
            $stmt = $db->getPdo()->prepare($sql);
            $stmt->execute(['class_id' => $class_id]);
            $result = $stmt->fetch();

            if (!$result) {
                // No class found - return sample data for testing
                error_log('WeCoza Classes Plugin: No class found with ID ' . $class_id . ', returning sample data');
                return [
                    'class_id' => $class_id,
                    'class_code' => 'SAMPLE-CLASS-' . $class_id,
                    'class_subject' => 'Sample Class Subject',
                    'original_start_date' => date('Y-m-d'),
                    'delivery_date' => date('Y-m-d', strtotime('+30 days')),
                    'schedule_data' => null,
                    'exception_dates' => null,
                    'stop_restart_dates' => [
                        [
                            'stop_date' => date('Y-m-d', strtotime('+10 days')),
                            'restart_date' => date('Y-m-d', strtotime('+15 days'))
                        ]
                    ]
                ];
            }

            // Handle JSONB fields that come as strings from PostgreSQL
            $jsonbFields = ['learner_ids', 'backup_agent_ids', 'schedule_data', 'stop_restart_dates', 'class_notes_data', 'qa_reports'];

            foreach ($jsonbFields as $field) {
                if (isset($result[$field]) && is_string($result[$field])) {
                    $decoded = json_decode($result[$field], true);
                    if ($decoded !== null) {
                        $result[$field] = $decoded;
                    }
                }
            }

            // Schedule data expected to be in V2.0 format only

            return $result;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error in getSingleClass: ' . $e->getMessage());
            // Return sample data for testing when database fails
            return [
                'class_id' => $class_id,
                'class_code' => 'SAMPLE-CLASS-' . $class_id,
                'class_subject' => 'Sample Class Subject',
                'original_start_date' => date('Y-m-d'),
                'delivery_date' => date('Y-m-d', strtotime('+30 days')),
                'schedule_data' => null,
                'exception_dates' => null,
                'stop_restart_dates' => [
                    [
                        'stop_date' => date('Y-m-d', strtotime('+10 days')),
                        'restart_date' => date('Y-m-d', strtotime('+15 days'))
                    ]
                ]
            ];
        }
    }

    /**
     * Handle AJAX request to delete class
     */
    public static function deleteClassAjax() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], 'wecoza_class_nonce')) {
            \wp_send_json_error('Security check failed.');
            return;
        }

        // Check user permissions - only administrators can delete classes
        if (!current_user_can('manage_options')) {
            \wp_send_json_error('Only administrators can delete classes.');
            return;
        }

        // Validate class_id
        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        if (empty($class_id) || $class_id <= 0) {
            \wp_send_json_error('Invalid class ID provided.');
            return;
        }

        try {
            $db = \WeCozaClasses\Services\Database\DatabaseService::getInstance();
            $db->beginTransaction();

            try {
                // Delete the main class record using RETURNING to check if it existed
                $stmt = $db->getPdo()->prepare(
                    "DELETE FROM public.classes WHERE class_id = :class_id RETURNING class_id"
                );
                $stmt->execute(['class_id' => $class_id]);
                $deletedClass = $stmt->fetch();

                if (!$deletedClass) {
                    $db->rollback();
                    \wp_send_json_error('Class not found or already deleted.');
                    return;
                }

                $db->commit();
                \wp_send_json_success([
                    'message' => 'Class deleted successfully.',
                    'class_id' => $class_id
                ]);

            } catch (\Exception $e) {
                $db->rollback();
                error_log('WeCoza Classes Plugin: Error during class deletion: ' . $e->getMessage());
                \wp_send_json_error('Failed to delete class: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Database error during class deletion: ' . $e->getMessage());
            \wp_send_json_error('Database error occurred while deleting class.');
        }
    }

    /**
     * Handle AJAX request to get calendar events
     */
    public static function getCalendarEventsAjax() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], 'wecoza_calendar_nonce')) {
            \wp_send_json_error('Security check failed.');
            return;
        }

        $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
        if (empty($class_id) || $class_id <= 0) {
            \wp_send_json_error('Invalid class ID provided.');
            return;
        }

        try {
            $instance = new self();
            $class = $instance->getSingleClass($class_id);

            if (!$class) {
                \wp_send_json_error('Class not found.');
                return;
            }

            // Generate calendar events from schedule data
            $events = $instance->generateCalendarEvents($class);

            // FullCalendar expects a direct array of events, not wrapped in success response
            \wp_send_json($events);

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error getting calendar events: ' . $e->getMessage());
            \wp_send_json_error('Error loading calendar events.');
        }
    }

    /**
     * Handle AJAX request to get class subjects
     */
    public static function getClassSubjectsAjax() {
        // Check if class type is provided
        if (!isset($_GET['class_type']) || empty($_GET['class_type'])) {
            \wp_send_json_error('Class type is required.');
            return;
        }

        $classType = sanitize_text_field($_GET['class_type']);

        try {
            // Get subjects for the selected class type
            $subjects = ClassTypesController::getClassSubjects($classType);

            if (empty($subjects)) {
                \wp_send_json_error('No subjects found for the selected class type.');
                return;
            }

            \wp_send_json_success($subjects);

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error getting class subjects: ' . $e->getMessage());
            \wp_send_json_error('Error loading class subjects.');
        }
    }

    /**
     * Generate calendar events from class schedule data
     * Enhanced to handle both legacy v1.0 and new v2.0 schedule formats
     *
     * @param array $class Class data
     * @return array Calendar events
     */
    private function generateCalendarEvents($class) {
        $events = [];

        // Get basic class information
        $classCode = $class['class_code'] ?? 'Unknown';
        $classSubject = $class['class_subject'] ?? 'Unknown Subject';
        $startDate = $class['original_start_date'] ?? null;
        $deliveryDate = $class['delivery_date'] ?? null;

        // Parse schedule data if available
        $scheduleData = null;
        if (!empty($class['schedule_data'])) {
            $scheduleData = is_string($class['schedule_data'])
                ? json_decode($class['schedule_data'], true)
                : $class['schedule_data'];
        }

        // Generate events from schedule data
        if ($scheduleData && is_array($scheduleData)) {
            $events = $this->generateEventsFromScheduleData($scheduleData, $class);
        } else {
            // Generate sample events based on start and delivery dates
            if ($startDate && $deliveryDate) {
                $start = new \DateTime($startDate);
                $end = new \DateTime($deliveryDate);
                $interval = new \DateInterval('P1D'); // 1 day interval
                $period = new \DatePeriod($start, $interval, $end);

                $eventCount = 0;
                foreach ($period as $date) {
                    // Skip weekends for sample events
                    if ($date->format('N') >= 6) {
                        continue;
                    }

                    // Limit to first 10 events for demo
                    if ($eventCount >= 10) {
                        break;
                    }

                    $events[] = [
                        'id' => 'class_' . $class['class_id'] . '_' . $date->format('Y-m-d'),
                        'title' => '09:00 - 17:00',
                        'start' => $date->format('Y-m-d') . 'T09:00:00',
                        'end' => $date->format('Y-m-d') . 'T17:00:00',
                        'classNames' => ['wecoza-class-event', 'text-primary'],
                        'extendedProps' => [
                            'type' => 'class_session',
                            'classCode' => $classCode,
                            'classSubject' => $classSubject,
                            'notes' => 'Sample class session'
                        ]
                    ];

                    $eventCount++;
                }
            }
        }

        // Handle exception dates if available
        if (!empty($class['exception_dates'])) {
            $exceptionDates = is_string($class['exception_dates'])
                ? json_decode($class['exception_dates'], true)
                : $class['exception_dates'];

            if (is_array($exceptionDates)) {
                foreach ($exceptionDates as $exception) {
                    if (isset($exception['date']) && isset($exception['reason'])) {
                        $events[] = [
                            'id' => 'exception_' . $class['class_id'] . '_' . $exception['date'],
                            'title' => 'Exception - ' . $exception['reason'],
                            'start' => $exception['date'],
                            'allDay' => true,
                            'display' => 'background',
                            'classNames' => ['wecoza-exception-event'],
                            'extendedProps' => [
                                'type' => 'exception',
                                'reason' => $exception['reason']
                            ]
                        ];
                    }
                }
            }
        }

        // Handle stop/restart dates if available
        if (!empty($class['stop_restart_dates'])) {
            $stopRestartDates = is_string($class['stop_restart_dates'])
                ? json_decode($class['stop_restart_dates'], true)
                : $class['stop_restart_dates'];

            if (is_array($stopRestartDates)) {
                foreach ($stopRestartDates as $index => $stopRestart) {
                    if (isset($stopRestart['stop_date']) && isset($stopRestart['restart_date'])) {
                        $stopDate = $stopRestart['stop_date'];
                        $restartDate = $stopRestart['restart_date'];

                        // Create stop date event
                        $events[] = [
                            'id' => 'class_stop_' . $class['class_id'] . '_' . $index,
                            'title' => 'Class Stopped',
                            'start' => $stopDate,
                            'allDay' => true,
                            'display' => 'block',
                            'classNames' => ['text-danger', 'wecoza-stop-restart'],
                            'extendedProps' => [
                                'type' => 'stop_date',
                                'class_id' => $class['class_id'],
                                'description' => sprintf(
                                    'Class Stopped: %s\nClass: %s',
                                    $stopDate,
                                    $classSubject
                                ),
                                'interactive' => false
                            ]
                        ];

                        // Create restart date event
                        $events[] = [
                            'id' => 'class_restart_' . $class['class_id'] . '_' . $index,
                            'title' => 'Restart',
                            'start' => $restartDate,
                            'allDay' => true,
                            'display' => 'block',
                            'classNames' => ['text-danger', 'wecoza-stop-restart'],
                            'extendedProps' => [
                                'type' => 'restart_date',
                                'class_id' => $class['class_id'],
                                'description' => sprintf(
                                    'Class Restart: %s\nClass: %s',
                                    $restartDate,
                                    $classSubject
                                ),
                                'interactive' => false
                            ]
                        ];

                        // Create events for days between stop and restart (red circles only)
                        try {
                            $currentDate = new \DateTime($stopDate);
                            $endDate = new \DateTime($restartDate);

                            // Move to the day after stop date
                            $currentDate->add(new \DateInterval('P1D'));

                            while ($currentDate < $endDate) {
                                $dateStr = $currentDate->format('Y-m-d');

                                $events[] = [
                                    'id' => 'stop_period_' . $class['class_id'] . '_' . $index . '_' . $dateStr,
                                    'title' => '', // No text, just red circle
                                    'start' => $dateStr,
                                    'allDay' => true,
                                    'display' => 'block',
                                    'classNames' => ['text-danger', 'wecoza-stop-period'],
                                    'extendedProps' => [
                                        'type' => 'stop_period',
                                        'class_id' => $class['class_id'],
                                        'description' => sprintf(
                                            'Class Stopped Period: %s\nClass: %s\nStopped from %s to %s',
                                            $dateStr,
                                            $classSubject,
                                            $stopDate,
                                            $restartDate
                                        ),
                                        'interactive' => false
                                    ]
                                ];

                                $currentDate->add(new \DateInterval('P1D'));
                            }
                        } catch (\Exception $e) {
                            error_log('WeCoza Classes Plugin: Error generating stop period events: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Generate events from V2.0 schedule data only
     *
     * @param array $scheduleData V2.0 schedule data
     * @param array $class Class information
     * @return array Calendar events
     */
    private function generateEventsFromScheduleData($scheduleData, $class) {
        // Only handle V2.0 format
        return $this->generateEventsFromV2Data($scheduleData, $class);
    }

    /**
     * Generate events from v2.0 schedule data with proper per-day time support
     *
     * @param array $scheduleData v2.0 schedule data
     * @param array $class Class information
     * @return array Calendar events
     */
    private function generateEventsFromV2Data($scheduleData, $class) {
        $events = [];
        $classCode = $class['class_code'] ?? 'Unknown';
        $classSubject = $class['class_subject'] ?? 'Unknown Subject';

        // Check if we have per-day time data that should be preserved
        $timeData = $scheduleData['timeData'] ?? [];
        $hasPerDayTimes = isset($timeData['mode']) && $timeData['mode'] === 'per-day' && !empty($timeData['perDay']);

        // Generate events directly from v2.0 pattern data
        $events = $this->generateEventsFromV2Pattern($scheduleData, $class);

        // Add exception date events
        if (isset($scheduleData['exceptionDates'])) {
            $events = array_merge($events, $this->generateExceptionEvents($scheduleData['exceptionDates'], $class));
        }

        return $events;
    }

    // Legacy event generation function removed - V2.0 format only

    /**
     * Format event title based on schedule format
     *
     * @param array $schedule Schedule entry
     * @param string $format Schedule format version
     * @return string Formatted title
     */
    private function formatEventTitle($schedule, $format) {
        $startTime = $schedule['start_time'];
        $endTime = $schedule['end_time'];

        if ($format === 'v2.0') {
            // Enhanced title for v2.0 format
            $duration = $this->calculateEventDuration($startTime, $endTime);
            return sprintf('%s - %s (%.1fh)', $startTime, $endTime, $duration);
        } else {
            // Simple title for legacy format
            return $startTime . ' - ' . $endTime;
        }
    }

    /**
     * Generate events from V2.0 schedule data (handles both pattern-based and direct entries)
     *
     * @param array $scheduleData V2.0 schedule data
     * @param array $class Class information
     * @return array Calendar events
     */
    private function generateEventsFromV2Pattern($scheduleData, $class) {
        $events = [];
        $classCode = $class['class_code'] ?? 'Unknown';
        $classSubject = $class['class_subject'] ?? 'Unknown Subject';

        // Check if we have direct schedule entries (numbered keys format)
        $hasDirectEntries = false;
        foreach ($scheduleData as $key => $value) {
            if (is_numeric($key) && is_array($value) && isset($value['date']) && isset($value['start_time']) && isset($value['end_time'])) {
                $hasDirectEntries = true;
                break;
            }
        }

        if ($hasDirectEntries) {
            // Handle direct schedule entries (numbered keys format)
            foreach ($scheduleData as $key => $schedule) {
                if (is_numeric($key) && is_array($schedule) && isset($schedule['date']) && isset($schedule['start_time']) && isset($schedule['end_time'])) {
                    // Calculate duration for display
                    $duration = $this->calculateEventDuration($schedule['start_time'], $schedule['end_time']);
                    $dayName = $schedule['day'] ?? date('l', strtotime($schedule['date']));

                    $events[] = [
                        'id' => 'class_' . $class['class_id'] . '_' . $schedule['date'],
                        'title' => $dayName . ': ' . $schedule['start_time'] . ' - ' . $schedule['end_time'] . ' (' . $duration . 'h)',
                        'start' => $schedule['date'] . 'T' . $schedule['start_time'],
                        'end' => $schedule['date'] . 'T' . $schedule['end_time'],
                        'classNames' => ['wecoza-class-event', 'text-primary'],
                        'extendedProps' => [
                            'type' => 'class_session',
                            'classCode' => $classCode,
                            'classSubject' => $classSubject,
                            'notes' => $schedule['notes'] ?? '',
                            'scheduleFormat' => 'v2.0',
                            'dayOfWeek' => $dayName,
                            'duration' => $duration
                        ]
                    ];
                }
            }
        } else {
            // Handle pattern-based generation
            $pattern = $scheduleData['pattern'] ?? 'weekly';
            $startDate = isset($scheduleData['startDate']) ? new \DateTime($scheduleData['startDate']) : null;
            $endDate = isset($scheduleData['endDate']) ? new \DateTime($scheduleData['endDate']) : null;
            $timeData = $scheduleData['timeData'] ?? [];
            $selectedDays = $scheduleData['selectedDays'] ?? [];

            if ($startDate && $endDate) {
                // Generate schedule entries using existing pattern generation methods
                $scheduleEntries = [];
                switch ($pattern) {
                    case 'weekly':
                        $scheduleEntries = self::generateWeeklyEntries($startDate, $endDate, $timeData, $selectedDays);
                        break;
                    case 'biweekly':
                        $scheduleEntries = self::generateBiweeklyEntries($startDate, $endDate, $timeData, $selectedDays);
                        break;
                    case 'monthly':
                        $scheduleEntries = self::generateMonthlyEntries($startDate, $endDate, $timeData, $scheduleData['dayOfMonth'] ?? 1);
                        break;
                    case 'custom':
                    default:
                        // For custom pattern, create a single entry if we have time data
                        if (isset($timeData['single'])) {
                            $scheduleEntries[] = [
                                'date' => $scheduleData['startDate'],
                                'start_time' => $timeData['single']['startTime'] ?? '09:00',
                                'end_time' => $timeData['single']['endTime'] ?? '17:00'
                            ];
                        }
                        break;
                }

                // Convert schedule entries to calendar events
                foreach ($scheduleEntries as $schedule) {
                    if (isset($schedule['date']) && isset($schedule['start_time']) && isset($schedule['end_time'])) {
                        // Get day of week for enhanced title
                        $date = new \DateTime($schedule['date']);
                        $dayName = $date->format('l');

                        $events[] = [
                            'id' => 'class_' . $class['class_id'] . '_' . $schedule['date'],
                            'title' => $this->formatV2EventTitle($schedule, $dayName, $timeData),
                            'start' => $schedule['date'] . 'T' . $schedule['start_time'],
                            'end' => $schedule['date'] . 'T' . $schedule['end_time'],
                            'classNames' => ['wecoza-class-event', 'text-primary'],
                            'extendedProps' => [
                                'type' => 'class_session',
                                'classCode' => $classCode,
                                'classSubject' => $classSubject,
                                'notes' => $schedule['notes'] ?? '',
                                'scheduleFormat' => 'v2.0',
                                'dayOfWeek' => $dayName,
                                'pattern' => $pattern,
                                'timeMode' => $timeData['mode'] ?? 'single',
                                'duration' => $this->calculateEventDuration($schedule['start_time'], $schedule['end_time'])
                            ]
                        ];
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Format event title for v2.0 events with enhanced per-day information
     *
     * @param array $schedule Schedule entry
     * @param string $dayName Day of week
     * @param array $timeData Time data from v2.0 format
     * @return string Formatted title
     */
    private function formatV2EventTitle($schedule, $dayName, $timeData) {
        $startTime = $schedule['start_time'];
        $endTime = $schedule['end_time'];
        $duration = $this->calculateEventDuration($startTime, $endTime);

        // Check if this is per-day mode for enhanced title
        $mode = $timeData['mode'] ?? 'single';
        if ($mode === 'per-day') {
            // Show day name for per-day schedules to highlight different times
            return sprintf('%s: %s - %s (%.1fh)', $dayName, $startTime, $endTime, $duration);
        } else {
            // Standard title for single-time mode
            return sprintf('%s - %s (%.1fh)', $startTime, $endTime, $duration);
        }
    }

    /**
     * Calculate event duration in hours
     *
     * @param string $startTime Start time (HH:MM)
     * @param string $endTime End time (HH:MM)
     * @return float Duration in hours
     */
    private function calculateEventDuration($startTime, $endTime) {
        $start = strtotime($startTime);
        $end = strtotime($endTime);

        if ($start === false || $end === false || $end <= $start) {
            return 0;
        }

        return ($end - $start) / 3600;
    }

    /**
     * Generate exception date events
     *
     * @param array $exceptionDates Exception dates from v2.0 format
     * @param array $class Class information
     * @return array Exception events
     */
    private function generateExceptionEvents($exceptionDates, $class) {
        $events = [];

        foreach ($exceptionDates as $exception) {
            if (isset($exception['date']) && isset($exception['reason'])) {
                $events[] = [
                    'id' => 'exception_' . $class['class_id'] . '_' . $exception['date'],
                    'title' => 'Exception - ' . $exception['reason'],
                    'start' => $exception['date'],
                    'allDay' => true,
                    'display' => 'background',
                    'classNames' => ['wecoza-exception-event'],
                    'extendedProps' => [
                        'type' => 'exception',
                        'reason' => $exception['reason'],
                        'scheduleFormat' => 'v2.0'
                    ]
                ];
            }
        }

        return $events;
    }

    /**
     * Validate schedule data (public method for external use)
     *
     * @param array $scheduleData Schedule data to validate
     * @return array Validation result
     */
    public static function validateScheduleData($scheduleData) {
        $validator = new \WeCozaClasses\Services\ScheduleDataValidator();
        return $validator->validate($scheduleData);
    }

    /**
     * Get supported schedule data formats
     *
     * @return array Supported formats with descriptions
     */
    public static function getSupportedScheduleFormats() {
        return [
            'v2.0' => [
                'name' => 'Enhanced Format v2.0',
                'description' => 'Structured format with patterns, per-day times, and metadata',
                'example' => [
                    'version' => '2.0',
                    'pattern' => 'weekly',
                    'timeData' => [
                        'mode' => 'per-day',
                        'perDay' => [
                            'Monday' => ['startTime' => '09:00', 'endTime' => '12:00', 'duration' => 3],
                            'Wednesday' => ['startTime' => '13:00', 'endTime' => '17:00', 'duration' => 4]
                        ]
                    ],
                    'selectedDays' => ['Monday', 'Wednesday'],
                    'exceptionDates' => [],
                    'holidayOverrides' => []
                ]
            ]
        ];
    }

    // Backward compatibility function removed - V2.0 format only

    // Migration and integrity check functions removed - V2.0 format only
}
