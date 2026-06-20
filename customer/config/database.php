<?php
// Database configuration file
class Database {
    private $host = 'localhost';
    private $db_name = 'mavics_resort';
    private $username = 'root';
    private $password = '';
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
}

// Database helper functions
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Check database connection
function checkDatabaseConnection() {
    try {
        $conn = getDBConnection();
        return $conn !== null;
    } catch (Exception $e) {
        return false;
    }
}
?>