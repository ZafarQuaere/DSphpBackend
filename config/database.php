<?php
/**
 * Database Configuration
 * Production-ready database connection class with security and performance optimizations
 */

require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;
    
    // Connection options
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];

    public function __construct() {
        // Get database credentials from environment variables
        $this->host = getenv('DB_HOST');
        $this->db_name = getenv('DB_NAME');
        $this->username = getenv('DB_USER');
        $this->password = getenv('DB_PASS');
        
        // Validate credentials
        if (!$this->host || !$this->db_name || !$this->username) {
            error_log('Database configuration error: Missing required credentials');
            if (ENVIRONMENT !== 'production') {
                throw new Exception('Database configuration error: Missing required credentials');
            }
        }
    }

    /**
     * Get database connection
     * @return PDO|null
     */
    public function getConnection() {
        if ($this->conn === null) {
            try {
                // Create DSN
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                
                // Create connection with options
                $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
                
                // Additional security settings
                $this->conn->exec("SET SESSION sql_mode = 'TRADITIONAL,NO_ENGINE_SUBSTITUTION'");
                
                // Set timezone to match PHP timezone
                $this->conn->exec("SET time_zone = '+05:30'"); // India Standard Time
                
            } catch(PDOException $e) {
                // Log the error securely
                error_log('Database connection failed: ' . $e->getMessage());
                
                // In production, don't expose database errors
                if (ENVIRONMENT === 'production') {
                    return null;
                } else {
                    throw $e;
                }
            }
        }
        
        return $this->conn;
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction() {
        try {
            $conn = $this->getConnection();
            return $conn ? $conn->beginTransaction() : false;
        } catch (Exception $e) {
            error_log('Transaction begin failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit() {
        try {
            $conn = $this->getConnection();
            return $conn ? $conn->commit() : false;
        } catch (Exception $e) {
            error_log('Transaction commit failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollback() {
        try {
            $conn = $this->getConnection();
            return $conn ? $conn->rollback() : false;
        } catch (Exception $e) {
            error_log('Transaction rollback failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if connection is alive
     * @return bool
     */
    public function ping() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                $conn->query('SELECT 1');
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?> 