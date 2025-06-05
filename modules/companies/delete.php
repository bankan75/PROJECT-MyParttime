<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// นำเข้าไฟล์ที่จำเป็น
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// สร้างอ็อบเจ็กต์ฐานข้อมูลและการยืนยันตัวตน
$db = new Database();
$auth = new Auth($db->getConnection());

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะบริษัทเท่านั้น)
if (!$auth->isAdmin()) {
    // ถ้าไม่ใช่บริษัท ให้เปลี่ยนเส้นทาง
    header("Location: ../../index.php");
    exit;
}


// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid company ID.";
    header("Location: index.php");
    exit;
}

$company_id = (int)$_GET['id'];

// Get company details before deletion (for logging)
$sql = "SELECT company_name FROM companies WHERE company_id = ?";
$company = $db->getRow($sql, [$company_id]);

// Check if company exists
if (!$company) {
    $_SESSION['error_message'] = "Company not found.";
    header("Location: index.php");
    exit;
}

try {
    // Delete the company
    $sql = "DELETE FROM companies WHERE company_id = ?";
    $result = $db->execute($sql, [$company_id]);
    
    if ($result) {
        // Log activity if function exists and admin_id is set
        if (function_exists('logActivity') && isset($_SESSION['admin_id'])) {
            logActivity($db, $_SESSION['admin_id'], 'delete', 'companies', $company_id, $company['company_name']);
        }
        
        $_SESSION['success_message'] = "Company '" . htmlspecialchars($company['company_name']) . "' deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Failed to delete company. Please try again.";
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    error_log("Exception in delete.php: " . $e->getMessage());
}

// Redirect back to companies list
header("Location: index.php");
exit;
?>