<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    // ฟังก์ชันใหม่สำหรับ login แบบรวม
    public function universalLogin($identifier, $password) {
        // ตรวจสอบว่าเป็นรหัสนักศึกษา (13 หลัก)
        if (preg_match('/^\d{13}$/', $identifier)) {
            return $this->login($identifier, $password); // เรียกฟังก์ชัน login ของนักศึกษา
        }
        // ตรวจสอบว่าเป็นอีเมล (มี @)
        elseif (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->companyLogin($identifier, $password); // เรียกฟังก์ชัน login ของบริษัท
        }
        // ถ้าไม่ใช่ทั้งสองอย่าง ถือว่าเป็น username ของ admin
        else {
            return $this->adminLogin($identifier, $password); // เรียกฟังก์ชัน login ของ admin
        }
    }

    // ฟังก์ชัน login เดิมสำหรับนักศึกษา
    public function login($student_code, $password) {
        // คงโค้ดเดิมไว้
        error_log("Attempting login with student ID: " . $student_code);
        try {
            if (!preg_match('/^\d{13}$/', $student_code)) {
                error_log("Invalid student ID format: " . $student_code);
                return false;
            }
            $sql = "SELECT * FROM students WHERE student_code = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$student_code]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                error_log("User not found with student ID: " . $student_code);
                return false;
            }
            if (!is_string($user['password'])) {
                error_log("ERROR: Password in database is not stored as a string! Current type: " . gettype($user['password']));
                return false;
            }
            $passwordVerified = password_verify($password, $user['password']);
            if ($passwordVerified) {
                $_SESSION['user_id'] = $user['student_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['is_logged_in'] = true;
                error_log("Login successful - Session created for user ID: " . $user['student_id']);
                return true;
            }
            error_log("Login failed - Details: " . print_r($user, true));
            return false;
        } catch (Exception $e) {
            error_log("Login exception: " . $e->getMessage());
            return false;
        }
    }

    // ฟังก์ชัน login เดิมสำหรับบริษัท
    public function companyLogin($email, $password) {
        // คงโค้ดเดิมไว้
        try {
            $sql = "SELECT * FROM companies WHERE contact_email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Company login attempt: " . $email);
            error_log("Company data found: " . ($company ? "Yes" : "No"));
            if (!$company) {
                error_log("Company not found with email: " . $email);
                return false;
            }
            if (isset($company['is_approved']) && $company['is_approved'] != 1) {
                error_log("Company account not approved: " . $email);
                return false;
            }
            $passwordVerified = password_verify($password, $company['password']);
            if ($passwordVerified) {
                $_SESSION['user_id'] = $company['company_id'];
                $_SESSION['user_name'] = $company['company_name'];
                $_SESSION['user_type'] = 'company';
                $_SESSION['is_logged_in'] = true;
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Company login exception: " . $e->getMessage());
            return false;
        }
    }

    // ฟังก์ชัน login เดิมสำหรับ admin
    public function adminLogin($username, $password) {
        // คงโค้ดเดิมไว้
        try {
            $sql = "SELECT * FROM admins WHERE username = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Admin login attempt: " . $username);
            error_log("Admin data found: " . ($admin ? "Yes" : "No"));
            if (!$admin) {
                error_log("Admin not found with username: " . $username);
                return false;
            }
            $passwordVerified = password_verify($password, $admin['password']);
            if ($passwordVerified) {
                $_SESSION['user_id'] = $admin['admin_id'];
                $_SESSION['user_name'] = $admin['name'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['is_logged_in'] = true;
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Admin login exception: " . $e->getMessage());
            return false;
        }
    }

    // ฟังก์ชันอื่น ๆ คงไว้ตามเดิม
    public function isLoggedIn() {
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }

    public function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_type']);
        unset($_SESSION['is_logged_in']);
        session_destroy();
        return true;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    public function getUserType() {
        return $_SESSION['user_type'] ?? null;
    }

    public function isCompany() {
        return $this->getUserType() === 'company';
    }

    public function isStudent() {
        return $this->getUserType() === 'student';
    }

    public function isAdmin() {
        return $this->getUserType() === 'admin';
    }
    // เพิ่มฟังก์ชัน isStudentInterview เพื่อตรวจสอบว่านักศึกษามีสิทธิ์ดูการสัมภาษณ์นั้นหรือไม่
public function canAccessInterview($interview_id) {
    if ($this->isAdmin() || $this->isCompany()) {
        return true; // แอดมินและบริษัทเข้าถึงได้ทุกการสัมภาษณ์
    } elseif ($this->isStudent()) {
        // ตรวจสอบว่าเป็นการสัมภาษณ์ของนักศึกษาคนนี้หรือไม่
        $student_id = $_SESSION['user_id'];
        $sql = "SELECT i.* FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                WHERE a.student_id = ? AND i.interview_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$student_id, $interview_id]);
        return $stmt->rowCount() > 0;
    }
    return false;
}
}
?>