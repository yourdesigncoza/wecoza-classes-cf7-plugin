<?php
/**
 * PublicHolidaysController.php
 *
 * Controller for managing public holidays
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Controllers;

class PublicHolidaysController {
    /**
     * @var PublicHolidaysController Singleton instance
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     *
     * @return PublicHolidaysController
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->initialize();
        }
        return self::$instance;
    }

    /**
     * Constructor - initializes the controller
     */
    public function __construct() {
        // Initialize the controller when instantiated
        $this->initialize();
    }

    /**
     * Initialize the controller
     */
    public function initialize() {
        $this->registerHooks();
    }

    /**
     * Get public holidays for a specific year
     * 
     * For now, this returns a static list of South African public holidays
     * TODO: Integrate with database or external API
     *
     * @param int $year The year to get holidays for
     * @return array Array of holiday data
     */
    public function getHolidaysByYear($year) {
        // Static South African public holidays for the given year
        return [
            ['date' => $year . '-01-01', 'name' => 'New Year\'s Day'],
            ['date' => $year . '-03-21', 'name' => 'Human Rights Day'],
            ['date' => $year . '-04-27', 'name' => 'Freedom Day'],
            ['date' => $year . '-05-01', 'name' => 'Workers\' Day'],
            ['date' => $year . '-06-16', 'name' => 'Youth Day'],
            ['date' => $year . '-08-09', 'name' => 'National Women\'s Day'],
            ['date' => $year . '-09-24', 'name' => 'Heritage Day'],
            ['date' => $year . '-12-16', 'name' => 'Day of Reconciliation'],
            ['date' => $year . '-12-25', 'name' => 'Christmas Day'],
            ['date' => $year . '-12-26', 'name' => 'Day of Goodwill'],
        ];
    }

    /**
     * Get all public holidays as an array of dates
     *
     * @return array Array of dates in Y-m-d format
     */
    public function getAllHolidayDates() {
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        
        $currentYearHolidays = $this->getHolidaysByYear($currentYear);
        $nextYearHolidays = $this->getHolidaysByYear($nextYear);
        
        $allHolidays = array_merge($currentYearHolidays, $nextYearHolidays);
        
        return array_map(function($holiday) {
            return $holiday['date'];
        }, $allHolidays);
    }

    /**
     * Check if a date is a public holiday
     *
     * @param string $date Date in Y-m-d format
     * @return bool True if the date is a public holiday
     */
    public function isPublicHoliday($date) {
        $holidayDates = $this->getAllHolidayDates();
        return in_array($date, $holidayDates);
    }

    /**
     * Get public holidays within a date range
     *
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array Array of holiday data
     */
    public function getHolidaysInRange($startDate, $endDate) {
        $startYear = date('Y', strtotime($startDate));
        $endYear = date('Y', strtotime($endDate));
        
        $holidays = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearHolidays = $this->getHolidaysByYear($year);
            $holidays = array_merge($holidays, $yearHolidays);
        }
        
        // Filter holidays within the date range
        return array_filter($holidays, function($holiday) use ($startDate, $endDate) {
            return $holiday['date'] >= $startDate && $holiday['date'] <= $endDate;
        });
    }

    /**
     * Get public holidays formatted for FullCalendar
     *
     * @param int $year The year to get holidays for
     * @return array Array of FullCalendar-compatible event objects
     */
    public function getHolidaysForCalendar($year) {
        $holidays = $this->getHolidaysByYear($year);
        $calendarEvents = array();

        foreach ($holidays as $holiday) {
            $calendarEvents[] = array(
                'id' => 'holiday_' . $holiday['date'],
                'title' => $holiday['name'],
                'start' => $holiday['date'],
                'allDay' => true,
                'display' => 'background', // Shows as background event
                'classNames' => array('wecoza-public-holiday'),
                'extendedProps' => array(
                    'type' => 'public_holiday',
                    'interactive' => false // Mark as non-interactive
                )
            );
        }

        return $calendarEvents;
    }

    /**
     * AJAX handler for getting public holidays
     * Following WordPress AJAX best practices
     */
    public static function handlePublicHolidaysAjax() {
        // Verify nonce for security
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'wecoza_calendar_nonce')) {
            \wp_send_json_error('Security check failed');
            return;
        }

        // Get and validate year parameter
        $year = intval($_POST['year'] ?? date('Y'));

        // Validate year range (reasonable bounds)
        if ($year < 2020 || $year > 2030) {
            $year = date('Y');
        }

        try {
            // Get controller instance
            $controller = self::getInstance();

            // Get holidays formatted for FullCalendar
            $holidays = $controller->getHolidaysForCalendar($year);

            // Return holidays in FullCalendar format
            \wp_send_json($holidays);

        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin Public Holidays Error: ' . $e->getMessage());
            \wp_send_json_error('Failed to load public holidays: ' . $e->getMessage());
        }
    }

    /**
     * Register hooks for the controller
     *
     * Registers WordPress AJAX handlers for public holidays
     */
    public function registerHooks() {
        // Register AJAX handlers for public holidays
        \add_action('wp_ajax_get_public_holidays', array(__CLASS__, 'handlePublicHolidaysAjax'));
        \add_action('wp_ajax_nopriv_get_public_holidays', array(__CLASS__, 'handlePublicHolidaysAjax'));
    }
}
