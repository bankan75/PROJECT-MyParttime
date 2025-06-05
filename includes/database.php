<?php
// Database connection and helper class
class Database {
    private $host = "localhost";
    private $db_name = "myparttimedb";
    private $username = "root";
    private $password = "";
    private $connection;

    public function __construct() {
        $this->connect(); // เริ่มต้นการเชื่อมต่อใน constructor
    }

    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Connection error: " . $e->getMessage());
            $this->connection = null;
        }
    }

    public function getConnection() {
        if ($this->connection === null) {
            $this->connect(); // พยายามเชื่อมต่อใหม่ถ้ายังไม่มี
        }
        return $this->connection;
    }

    // Get single row
    public function getRow($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    // Get multiple rows
    public function getRows($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    // Execute insert/update/delete query
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    // Get the last inserted ID
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    // Execute a query and return the PDO statement
    public function query($sql) {
        return $this->getConnection()->query($sql);
    }

    // Prepare a statement
    public function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }

    // Get one value
    public function getOne($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_NUM);
            return $result ? $result[0] : 0;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return 0;
        }
    }
}
?>