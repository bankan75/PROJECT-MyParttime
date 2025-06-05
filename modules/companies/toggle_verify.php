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
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';


// Check if ID and status are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error_message'] = "ข้อมูลไม่ครบถ้วน";
    header("Location: index.php");
    exit;
}

$company_id = (int)$_GET['id'];
$status = (int)$_GET['status'];

// Validate status (0 = pending, 1 = verified/ready, 2 = rejected)
if ($status < 0 || $status > 2) {
    $_SESSION['error_message'] = "สถานะไม่ถูกต้อง";
    header("Location: view.php?id=" . $company_id);
    exit;
}

// Get current company status
$sql = "SELECT is_verified FROM companies WHERE company_id = ?";
$current = $database->getRow($sql, [$company_id]);

if (!$current) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลบริษัท";
    header("Location: index.php");
    exit;
}

// Update verification status
$sql = "UPDATE companies SET is_verified = ?, updated_at = NOW() WHERE company_id = ?";
$result = $database->execute($sql, [$status, $company_id]);

if ($result) {
    // Get status text for success message
    $status_text = '';
    switch ($status) {
        case 0:
            $status_text = 'รอพิจารณา';
            break;
        case 1:
            $status_text = 'พร้อมทำงาน';
            break;
        case 2:
            $status_text = 'ไม่ผ่านการพิจารณา';
            break;
    }
    
    $_SESSION['success_message'] = "อัพเดทสถานะบริษัทเป็น '{$status_text}' เรียบร้อยแล้ว";
    
    // Log activity if function exists
    if (function_exists('logActivity') && isset($_SESSION['admin_id'])) {
        logActivity($database, $_SESSION['admin_id'], 'update_status', 'companies', $company_id, "Changed status to {$status_text}");
    }
} else {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัพเดทสถานะ";
}

// Redirect back to company view page
header("Location: view.php?id=" . $company_id);
exit;
?>