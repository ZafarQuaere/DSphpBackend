<?php
class Database {
    // Database credentials - using environment variables when available
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Get database credentials from environment variables if available
        // Otherwise use default values
        $this->host = getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost';
        $this->db_name = getenv('DB_NAME') ? getenv('DB_NAME') : 'DB_DilliStyle';
        $this->username = getenv('DB_USER') ? getenv('DB_USER') : 'admin_zafar';
        $this->password = getenv('DB_PASS') ? getenv('DB_PASS') : 'Zafima@20';
    }

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?> 