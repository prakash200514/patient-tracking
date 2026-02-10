<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($full_name, $email, $password, $role) {
        $query = "INSERT INTO " . $this->table_name . " (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)";
        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(":full_name", $full_name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":role", $role);

        try {
            if($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch(PDOException $e) {
            // Check for duplicate entry (Error 23000)
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e; // Re-throw other errors
        }
        return false;
    }

    public function login($email, $password) {
        $query = "SELECT id, full_name, password, role FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                if(session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }

    public function getUserRole() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['role']) ? $_SESSION['role'] : null;
    }

    public function logout() {
        if(session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header("Location: login.php"); // Redirect to login page
        exit;
    }
}
?>
