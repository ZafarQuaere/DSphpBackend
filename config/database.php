<?php
// Attempt to load .env file if it exists (for local development)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) { // Check for Composer autoload
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
            $dotenv->load();
        } catch (Dotenv\Exception\InvalidPathException $e) {
            // .env file not found, or other issue, proceed with defaults or existing env vars
            // You might want to log this for debugging: error_log("Dotenv: " . $e->getMessage());
        }
    }
} elseif (file_exists(__DIR__ . '/../phpdotenv/src/Dotenv.php')) { // Fallback for manual include (less common)
    // This part is a basic example and might need adjustment based on how phpdotenv is manually included
    // require_once __DIR__ . '/../phpdotenv/src/Autoloader.php';
    // Dotenv\\Autoloader::register();
    // if (file_exists(__DIR__ . '/../.env')) {
    //     $dotenv = Dotenv\\Dotenv::createImmutable(__DIR__ . '/../');
    //     $dotenv->load();
    // }
}

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
        $this->db_name = getenv('DB_NAME') ? getenv('DB_NAME') : 'db_dillistyle';
        $this->username = getenv('DB_USER') ? getenv('DB_USER') : 'root';
        $this->password = getenv('DB_PASS') ? getenv('DB_PASS') : ''; // Empty password for local development
    }

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            // echo "Connection error: " . $e->getMessage(); // Original problematic line
            // Allow the exception to be caught by the calling script or re-throw if necessary
            // For now, simply not setting $this->conn will be handled by the calling script (e.g. index.php)
            // or an exception could be thrown here to be caught higher up: throw $e;
            throw $e; // Re-throw the PDOException to get detailed error in API response
        }

        return $this->conn;
    }
}
?> 