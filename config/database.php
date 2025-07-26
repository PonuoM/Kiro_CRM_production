<?php
// Production Database Configuration for DirectAdmin
// This file should be used in production environment
// Copy this file to database.php and update the credentials

// Production database configuration
$db_config = [
    'host' => 'localhost',
    'port' => '3306', // Standard MySQL port for DirectAdmin
    'dbname' => 'primacom_CRM', // Actual database name
    'username' => 'primacom_bloguser', // Actual database username
    'password' => 'pJnL53Wkhju2LaGPytw8', // Actual database password
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false, // Disable persistent connections for shared hosting
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]
];

/**
 * Base Database Class
 * Provides common database operations and connection management
 * Production-optimized version with enhanced error handling and logging
 */
class Database {
    private static $instance = null;
    private $connection;
    private $connectionAttempts = 0;
    private $maxConnectionAttempts = 3;
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance of Database
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create database connection with retry logic
     */
    private function connect() {
        global $db_config;
        
        while ($this->connectionAttempts < $this->maxConnectionAttempts) {
            try {
                $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
                $this->connection = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
                
                // Test the connection
                $this->connection->query('SELECT 1');
                
                // Log successful connection (for monitoring)
                error_log("Database connection established successfully");
                return;
                
            } catch (PDOException $e) {
                $this->connectionAttempts++;
                $errorMsg = "Database connection attempt {$this->connectionAttempts} failed: " . $e->getMessage();
                error_log($errorMsg);
                
                if ($this->connectionAttempts >= $this->maxConnectionAttempts) {
                    // Log critical error
                    error_log("CRITICAL: Database connection failed after {$this->maxConnectionAttempts} attempts");
                    throw new Exception("Database connection failed. Please contact system administrator.");
                }
                
                // Wait before retry (exponential backoff)
                sleep(pow(2, $this->connectionAttempts - 1));
            }
        }
    }
    
    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() {
        // Check if connection is still alive
        if (!$this->isConnectionAlive()) {
            $this->reconnect();
        }
        return $this->connection;
    }
    
    /**
     * Check if database connection is alive
     * @return bool
     */
    private function isConnectionAlive() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Reconnect to database
     */
    private function reconnect() {
        $this->connection = null;
        $this->connectionAttempts = 0;
        $this->connect();
    }
    
    /**
     * Execute a query and return results
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError("Query failed", $e, $sql, $params);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Execute a query and return single row
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError("QueryOne failed", $e, $sql, $params);
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Execute an insert/update/delete query
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError("Execute failed", $e, $sql, $params);
            throw new Exception("Database operation failed");
        }
    }
    
    /**
     * Get last inserted ID
     * @return string
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Check if connection is active
     * @return bool
     */
    public function isConnected() {
        return $this->connection !== null && $this->isConnectionAlive();
    }
    
    /**
     * Log database errors with context
     * @param string $message
     * @param PDOException $e
     * @param string $sql
     * @param array $params
     */
    private function logError($message, $e, $sql = '', $params = []) {
        $logMessage = $message . ": " . $e->getMessage();
        if ($sql) {
            $logMessage .= " | SQL: " . $sql;
        }
        if (!empty($params)) {
            $logMessage .= " | Params: " . json_encode($params);
        }
        error_log($logMessage);
    }
    
    /**
     * Get database statistics for monitoring
     * @return array
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Get connection info
            $stats['server_info'] = $this->connection->getAttribute(PDO::ATTR_SERVER_INFO);
            $stats['server_version'] = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
            $stats['connection_status'] = $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS);
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Failed to get database stats: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Get database instance (backward compatibility)
 * @return PDO
 */
function getDBConnection() {
    return Database::getInstance()->getConnection();
}

/**
 * Health check function for monitoring
 * @return bool
 */
function checkDatabaseHealth() {
    try {
        $db = Database::getInstance();
        return $db->isConnected();
    } catch (Exception $e) {
        error_log("Database health check failed: " . $e->getMessage());
        return false;
    }
}
?>