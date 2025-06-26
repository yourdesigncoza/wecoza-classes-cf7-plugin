<?php
/**
 * Site Service for WeCoza Classes CF7 Plugin
 *
 * Handles site data retrieval from PostgreSQL database with client relationships
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site Service class
 */
class WeCoza_Site_Service {
    
    /**
     * Singleton instance
     *
     * @var WeCoza_Site_Service
     */
    private static $instance = null;
    
    /**
     * Database service instance
     *
     * @var WeCoza_Database_Service
     */
    private $db_service;
    
    /**
     * Cache duration in seconds (1 hour)
     *
     * @var int
     */
    private $cache_duration = 3600;
    
    /**
     * Get singleton instance
     *
     * @return WeCoza_Site_Service
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
        $this->db_service = WeCoza_Database_Service::get_instance();
    }
    
    /**
     * Get all sites grouped by client_id (legacy format)
     *
     * @param bool $use_cache Whether to use cached data
     * @return array Array of sites grouped by client_id: $sites[$client_id][] = site_data
     */
    public function get_sites_grouped($use_cache = true) {
        // Check cache first
        if ($use_cache) {
            $cached_sites = $this->get_cached_sites();
            if ($cached_sites !== false) {
                return $cached_sites;
            }
        }
        
        try {
            // Query sites from database with client information (same as legacy)
            $sql = "SELECT s.site_id, s.client_id, s.site_name, s.address
                    FROM public.sites s
                    ORDER BY s.client_id ASC, s.site_name ASC";
            $stmt = $this->db_service->query($sql);
            
            $sites = array();
            while ($row = $stmt->fetch()) {
                $client_id = (int) $row['client_id'];
                
                if (!isset($sites[$client_id])) {
                    $sites[$client_id] = array();
                }
                
                $sites[$client_id][] = array(
                    'id' => (int) $row['site_id'],
                    'name' => sanitize_text_field($row['site_name']),
                    'address' => sanitize_textarea_field($row['address'])
                );
            }
            
            // Cache the results
            if ($use_cache) {
                $this->cache_sites($sites);
            }
            
            return $sites;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error fetching sites: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get sites for a specific client
     *
     * @param int $client_id Client ID
     * @param bool $use_cache Whether to use cached data
     * @return array Array of sites for the client
     */
    public function get_sites_by_client($client_id, $use_cache = true) {
        if (empty($client_id) || !is_numeric($client_id)) {
            return array();
        }
        
        $client_id = (int) $client_id;
        
        // Try to get from grouped cache first
        if ($use_cache) {
            $all_sites = $this->get_sites_grouped(true);
            if (isset($all_sites[$client_id])) {
                return $all_sites[$client_id];
            }
        }
        
        try {
            // Query sites for specific client
            $sql = "SELECT s.site_id, s.client_id, s.site_name, s.address
                    FROM public.sites s
                    WHERE s.client_id = ?
                    ORDER BY s.site_name ASC";
            $stmt = $this->db_service->query($sql, array($client_id));
            
            $sites = array();
            while ($row = $stmt->fetch()) {
                $sites[] = array(
                    'id' => (int) $row['site_id'],
                    'name' => sanitize_text_field($row['site_name']),
                    'address' => sanitize_textarea_field($row['address'])
                );
            }
            
            return $sites;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error fetching sites by client: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get site by ID
     *
     * @param int $site_id Site ID
     * @return array|null Site data or null if not found
     */
    public function get_site_by_id($site_id) {
        try {
            $sql = "SELECT s.site_id, s.client_id, s.site_name, s.address
                    FROM public.sites s
                    WHERE s.site_id = ? LIMIT 1";
            $stmt = $this->db_service->query($sql, array($site_id));
            
            $row = $stmt->fetch();
            if ($row) {
                return array(
                    'id' => (int) $row['site_id'],
                    'client_id' => (int) $row['client_id'],
                    'name' => sanitize_text_field($row['site_name']),
                    'address' => sanitize_textarea_field($row['address'])
                );
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error fetching site by ID: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get sites formatted for CF7 select options by client
     *
     * @param int $client_id Client ID
     * @param bool $include_blank Whether to include blank option
     * @param string $blank_label Label for blank option
     * @return array Array formatted for CF7 select field
     */
    public function get_sites_for_cf7($client_id = null, $include_blank = true, $blank_label = 'Select Site') {
        $options = array();
        
        // Add blank option if requested
        if ($include_blank) {
            $options[''] = $blank_label;
        }
        
        if ($client_id) {
            // Get sites for specific client
            $sites = $this->get_sites_by_client($client_id);
            
            foreach ($sites as $site) {
                $options[$site['id']] = $site['name'];
            }
        }
        
        return $options;
    }
    
    /**
     * Search sites by name
     *
     * @param string $search_term Search term
     * @param int $client_id Optional client ID to limit search
     * @return array Array of matching sites
     */
    public function search_sites($search_term, $client_id = null) {
        try {
            $search_term = '%' . sanitize_text_field($search_term) . '%';
            
            $sql = "SELECT s.site_id, s.client_id, s.site_name, s.address
                    FROM public.sites s
                    WHERE s.site_name ILIKE ?";
            
            $params = array($search_term);
            
            if ($client_id) {
                $sql .= " AND s.client_id = ?";
                $params[] = (int) $client_id;
            }
            
            $sql .= " ORDER BY s.site_name ASC";
            
            $stmt = $this->db_service->query($sql, $params);
            
            $sites = array();
            while ($row = $stmt->fetch()) {
                $sites[] = array(
                    'id' => (int) $row['site_id'],
                    'client_id' => (int) $row['client_id'],
                    'name' => sanitize_text_field($row['site_name']),
                    'address' => sanitize_textarea_field($row['address'])
                );
            }
            
            return $sites;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error searching sites: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get cached sites
     *
     * @return array|false Cached sites or false if not found
     */
    private function get_cached_sites() {
        return get_transient('wecoza_cf7_sites');
    }
    
    /**
     * Cache sites data
     *
     * @param array $sites Sites data to cache
     */
    private function cache_sites($sites) {
        set_transient('wecoza_cf7_sites', $sites, $this->cache_duration);
    }
    
    /**
     * Clear sites cache
     */
    public function clear_cache() {
        delete_transient('wecoza_cf7_sites');
    }
    
    /**
     * Refresh sites cache
     *
     * @return array Fresh sites data
     */
    public function refresh_cache() {
        $this->clear_cache();
        return $this->get_sites_grouped(false);
    }
    
    /**
     * Validate site exists and belongs to client
     *
     * @param int $site_id Site ID to validate
     * @param int $client_id Client ID to validate against
     * @return bool True if valid combination
     */
    public function validate_site_client_combination($site_id, $client_id) {
        if (empty($site_id) || !is_numeric($site_id) || empty($client_id) || !is_numeric($client_id)) {
            return false;
        }
        
        $site = $this->get_site_by_id($site_id);
        return $site && $site['client_id'] === (int) $client_id;
    }
    
    /**
     * Get total sites count
     *
     * @return int Number of sites
     */
    public function get_sites_count() {
        try {
            $sql = "SELECT COUNT(*) as count FROM public.sites";
            $stmt = $this->db_service->query($sql);
            
            $row = $stmt->fetch();
            return (int) $row['count'];
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error getting sites count: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get cache status
     *
     * @return array Cache information
     */
    public function get_cache_status() {
        $cached_data = $this->get_cached_sites();
        
        return array(
            'cached' => $cached_data !== false,
            'count' => $cached_data ? count($cached_data, COUNT_RECURSIVE) - count($cached_data) : 0,
            'expires_in' => $cached_data ? get_option('_transient_timeout_wecoza_cf7_sites') - time() : 0
        );
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
