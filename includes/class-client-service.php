<?php
/**
 * Client Service for WeCoza Classes CF7 Plugin
 *
 * Handles client data retrieval from PostgreSQL database
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Client Service class
 */
class WeCoza_Client_Service {
    
    /**
     * Singleton instance
     *
     * @var WeCoza_Client_Service
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
     * @return WeCoza_Client_Service
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
     * Get all clients from database
     *
     * @param bool $use_cache Whether to use cached data
     * @return array Array of client data with 'id' and 'name' keys
     */
    public function get_clients($use_cache = true) {
        // Check cache first
        if ($use_cache) {
            $cached_clients = $this->get_cached_clients();
            if ($cached_clients !== false) {
                return $cached_clients;
            }
        }
        
        try {
            // Query clients from database
            $sql = "SELECT client_id, client_name FROM public.clients ORDER BY client_name ASC";
            $stmt = $this->db_service->query($sql);
            
            $clients = array();
            while ($row = $stmt->fetch()) {
                $clients[] = array(
                    'id' => (int) $row['client_id'],
                    'name' => sanitize_text_field($row['client_name'])
                );
            }
            
            // Cache the results
            if ($use_cache) {
                $this->cache_clients($clients);
            }
            
            return $clients;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error fetching clients: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get client by ID
     *
     * @param int $client_id Client ID
     * @return array|null Client data or null if not found
     */
    public function get_client_by_id($client_id) {
        try {
            $sql = "SELECT client_id, client_name FROM public.clients WHERE client_id = ? LIMIT 1";
            $stmt = $this->db_service->query($sql, array($client_id));
            
            $row = $stmt->fetch();
            if ($row) {
                return array(
                    'id' => (int) $row['client_id'],
                    'name' => sanitize_text_field($row['client_name'])
                );
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error fetching client by ID: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Search clients by name
     *
     * @param string $search_term Search term
     * @return array Array of matching clients
     */
    public function search_clients($search_term) {
        try {
            $search_term = '%' . sanitize_text_field($search_term) . '%';
            
            $sql = "SELECT client_id, client_name FROM public.clients 
                    WHERE client_name ILIKE ? 
                    ORDER BY client_name ASC";
            
            $stmt = $this->db_service->query($sql, array($search_term));
            
            $clients = array();
            while ($row = $stmt->fetch()) {
                $clients[] = array(
                    'id' => (int) $row['client_id'],
                    'name' => sanitize_text_field($row['client_name'])
                );
            }
            
            return $clients;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error searching clients: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get clients formatted for CF7 select options
     *
     * @param bool $include_blank Whether to include blank option
     * @param string $blank_label Label for blank option
     * @return array Array formatted for CF7 select field
     */
    public function get_clients_for_cf7($include_blank = true, $blank_label = '—Please choose an option—') {
        $clients = $this->get_clients();
        $options = array();
        
        // Add blank option if requested
        if ($include_blank) {
            $options[''] = $blank_label;
        }
        
        // Add client options
        foreach ($clients as $client) {
            $options[$client['id']] = $client['name'];
        }
        
        return $options;
    }
    
    /**
     * Get cached clients
     *
     * @return array|false Cached clients or false if not found
     */
    private function get_cached_clients() {
        return get_transient('wecoza_cf7_clients');
    }
    
    /**
     * Cache clients data
     *
     * @param array $clients Clients data to cache
     */
    private function cache_clients($clients) {
        set_transient('wecoza_cf7_clients', $clients, $this->cache_duration);
    }
    
    /**
     * Clear clients cache
     */
    public function clear_cache() {
        delete_transient('wecoza_cf7_clients');
    }
    
    /**
     * Refresh clients cache
     *
     * @return array Fresh clients data
     */
    public function refresh_cache() {
        $this->clear_cache();
        return $this->get_clients(false);
    }
    
    /**
     * Get cache status
     *
     * @return array Cache information
     */
    public function get_cache_status() {
        $cached_data = $this->get_cached_clients();
        
        return array(
            'cached' => $cached_data !== false,
            'count' => $cached_data ? count($cached_data) : 0,
            'expires_in' => $cached_data ? get_option('_transient_timeout_wecoza_cf7_clients') - time() : 0
        );
    }
    
    /**
     * Validate client exists
     *
     * @param int $client_id Client ID to validate
     * @return bool True if client exists
     */
    public function client_exists($client_id) {
        $client = $this->get_client_by_id($client_id);
        return $client !== null;
    }
    
    /**
     * Get total clients count
     *
     * @return int Number of clients
     */
    public function get_clients_count() {
        try {
            $sql = "SELECT COUNT(*) as count FROM public.clients";
            $stmt = $this->db_service->query($sql);
            
            $row = $stmt->fetch();
            return (int) $row['count'];
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error getting clients count: ' . $e->getMessage());
            return 0;
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
