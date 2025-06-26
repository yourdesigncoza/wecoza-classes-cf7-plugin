<?php
/**
 * DatabaseService.php
 *
 * Database service for WeCoza Classes Plugin
 * Extracted from WeCoza theme for standalone plugin
 */

namespace WeCozaClasses\Services\Database;

class DatabaseService {
    /**
     * PDO instance
     */
    private static $instance = null;
    private $pdo;

    /**
     * Constructor - private to prevent direct instantiation
     */
    private function __construct() {
        try {
            // Get PostgreSQL database credentials from WordPress options
            // These should be set during plugin activation or via admin settings
            $pgHost = get_option('wecoza_postgres_host', 'db-wecoza-3-do-user-17263152-0.m.db.ondigitalocean.com');
            $pgPort = get_option('wecoza_postgres_port', '25060');
            $pgName = get_option('wecoza_postgres_dbname', 'defaultdb');
            $pgUser = get_option('wecoza_postgres_user', 'doadmin');
            $pgPass = get_option('wecoza_postgres_password', '');

            // Create PDO instance for PostgreSQL
            $this->pdo = new \PDO(
                "pgsql:host=$pgHost;port=$pgPort;dbname=$pgName",
                $pgUser,
                $pgPass,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

        } catch (\PDOException $e) {
            // Log error
            error_log('WeCoza Classes Plugin: Database connection error: ' . $e->getMessage());
            throw new \Exception('Database connection failed');
        }
    }

    /**
     * Get database instance (singleton)
     *
     * @return DatabaseService
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO instance
     *
     * @return \PDO
     */
    public function getPdo() {
        return $this->pdo;
    }

    /**
     * Execute a query with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return \PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log('WeCoza Classes Plugin: Database query error: ' . $e->getMessage());
            error_log('WeCoza Classes Plugin: SQL: ' . $sql);
            error_log('WeCoza Classes Plugin: Params: ' . print_r($params, true));
            throw $e;
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Check if in transaction
     *
     * @return bool
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }

    /**
     * Get last insert ID
     *
     * @return string
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Prepare a statement
     *
     * @param string $sql SQL query
     * @return \PDOStatement
     */
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }

    /**
     * Execute a statement
     *
     * @param string $sql SQL query
     * @return int Number of affected rows
     */
    public function exec($sql) {
        return $this->pdo->exec($sql);
    }

    /**
     * Quote a string for use in a query
     *
     * @param string $string String to quote
     * @param int $parameter_type Parameter type
     * @return string Quoted string
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR) {
        return $this->pdo->quote($string, $parameter_type);
    }

    /**
     * Get database connection info (for debugging)
     *
     * @return array
     */
    public function getConnectionInfo() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return ['debug' => 'disabled'];
        }

        return [
            'host' => get_option('wecoza_postgres_host', 'not_set'),
            'port' => get_option('wecoza_postgres_port', 'not_set'),
            'database' => get_option('wecoza_postgres_dbname', 'not_set'),
            'user' => get_option('wecoza_postgres_user', 'not_set'),
            'connected' => $this->pdo ? 'yes' : 'no'
        ];
    }

    /**
     * Test database connection
     *
     * @return bool
     */
    public function testConnection() {
        try {
            $stmt = $this->pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Database connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database version
     *
     * @return string
     */
    public function getVersion() {
        try {
            $stmt = $this->pdo->query('SELECT version()');
            $result = $stmt->fetch();
            return $result['version'] ?? 'unknown';
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error getting database version: ' . $e->getMessage());
            return 'error';
        }
    }

    /**
     * Check if table exists
     *
     * @param string $tableName Table name
     * @return bool
     */
    public function tableExists($tableName) {
        try {
            $sql = "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = ?
            )";
            $stmt = $this->query($sql, [$tableName]);
            $result = $stmt->fetch();
            return $result['exists'] ?? false;
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error checking table existence: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get table columns
     *
     * @param string $tableName Table name
     * @return array
     */
    public function getTableColumns($tableName) {
        try {
            $sql = "SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns 
                    WHERE table_schema = 'public' 
                    AND table_name = ?
                    ORDER BY ordinal_position";
            $stmt = $this->query($sql, [$tableName]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log('WeCoza Classes Plugin: Error getting table columns: ' . $e->getMessage());
            return [];
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
        throw new \Exception("Cannot unserialize singleton");
    }
}
