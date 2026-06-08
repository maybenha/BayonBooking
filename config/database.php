<?php
// ==========================================
// DATABASE CONFIGURATION
// File: config/database.php
// ==========================================

class Database {
    private $host = "localhost";
    private $db_name = "bopha_hotel";  // Changed to match your database name
    private $username = "root";         // Update with your DB username
    private $password = "123456";             // Update with your DB password
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                   $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>