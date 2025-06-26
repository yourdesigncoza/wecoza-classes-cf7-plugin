<?php
/**
 * Database Service for WeCoza Classes CF7 Plugin
 *
 * Handles PostgreSQL database connections and operations
 *
 * @package WeCozaClassesCF7
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Service class
 */
class WeCoza_Database_Service {
    
    /**
     * Singleton instance
     *
     * @var WeCoza_Database_Service
     */
    private static $instance = null;
    
    /**
     * PDO instance
     *
     * @var PDO
     */
    private $pdo;
    
    /**
     * Connection status
     *
     * @var bool
     */
    private $connected = false;
    
    /**
     * Get singleton instance
     *
     * @return WeCoza_Database_Service
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
        $this->connect();
    }
    
    /**
     * Connect to PostgreSQL database
     *
     * @throws Exception If connection fails
     */
    private function connect() {
        try {
            // Get PostgreSQL database credentials from WordPress options
            // These should be set during plugin activation or via admin settings
            $pg_host = get_option('wecoza_postgres_host', 'db-wecoza-3-do-user-17263152-0.m.db.ondigitalocean.com');
            $pg_port = get_option('wecoza_postgres_port', '25060');
            $pg_name = get_option('wecoza_postgres_dbname', 'defaultdb');
            $pg_user = get_option('wecoza_postgres_user', 'doadmin');
            $pg_pass = get_option('wecoza_postgres_password', '');
            
            // Validate required credentials
            if (empty($pg_pass)) {
                throw new Exception('PostgreSQL password is not configured');
            }
            
            // Create PDO instance for PostgreSQL
            $dsn = "pgsql:host={$pg_host};port={$pg_port};dbname={$pg_name}";
            
            $this->pdo = new PDO(
                $dsn,
                $pg_user,
                $pg_pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 30,
                )
            );
            
            $this->connected = true;
            
        } catch (PDOException $e) {
            $this->connected = false;
            error_log('WeCoza CF7 Plugin: Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute a query with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return PDOStatement
     * @throws Exception If query fails
     */
    public function query($sql, $params = array()) {
        try {
            if (!$this->connected) {
                throw new Exception('Database not connected');
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
            
        } catch (PDOException $e) {
            error_log('WeCoza CF7 Plugin: Database query error: ' . $e->getMessage());
            error_log('WeCoza CF7 Plugin: SQL: ' . $sql);
            error_log('WeCoza CF7 Plugin: Params: ' . print_r($params, true));
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test database connection
     *
     * @return bool True if connection is successful
     */
    public function test_connection() {
        try {
            if (!$this->connected) {
                return false;
            }
            
            $stmt = $this->pdo->query('SELECT 1');
            return $stmt !== false;
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Database connection test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database connection status
     *
     * @return bool
     */
    public function is_connected() {
        return $this->connected;
    }
    
    /**
     * Get PDO instance (for advanced operations)
     *
     * @return PDO|null
     */
    public function get_pdo() {
        return $this->connected ? $this->pdo : null;
    }
    
    /**
     * Begin transaction
     *
     * @return bool
     */
    public function begin_transaction() {
        if (!$this->connected) {
            return false;
        }
        
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit() {
        if (!$this->connected) {
            return false;
        }
        
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback() {
        if (!$this->connected) {
            return false;
        }
        
        return $this->pdo->rollback();
    }
    
    /**
     * Check if in transaction
     *
     * @return bool
     */
    public function in_transaction() {
        if (!$this->connected) {
            return false;
        }
        
        return $this->pdo->inTransaction();
    }
    
    /**
     * Get last insert ID
     *
     * @return string
     */
    public function last_insert_id() {
        if (!$this->connected) {
            return false;
        }
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Get database version
     *
     * @return string
     */
    public function get_version() {
        try {
            if (!$this->connected) {
                return 'Not connected';
            }
            
            $stmt = $this->pdo->query('SELECT version()');
            $result = $stmt->fetch();
            
            return $result['version'] ?? 'Unknown';
            
        } catch (Exception $e) {
            error_log('WeCoza CF7 Plugin: Error getting database version: ' . $e->getMessage());
            return 'Error';
        }
    }
    
    /**
     * Get connection info for debugging
     *
     * @return array
     */
    public function get_connection_info() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return array('debug' => 'disabled');
        }
        
        return array(
            'host' => get_option('wecoza_postgres_host', 'not_set'),
            'port' => get_option('wecoza_postgres_port', 'not_set'),
            'database' => get_option('wecoza_postgres_dbname', 'not_set'),
            'user' => get_option('wecoza_postgres_user', 'not_set'),
            'connected' => $this->connected ? 'yes' : 'no',
            'version' => $this->get_version()
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
