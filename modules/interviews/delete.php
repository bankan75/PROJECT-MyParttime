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

// ตรวจสอบสิทธิ์การเข้าถึง (อนุญาตเฉพาะบริษัทเท่านั้น)
if (!$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่ามีพารามิเตอร์ ID หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบรหัสการสัมภาษณ์";
    header("Location: index.php");
    exit;
}

$interview_id = $_GET['id'];

try {
    // ตรวจสอบว่าการสัมภาษณ์นี้เป็นของบริษัทนี้จริงหรือไม่
    $checkSql = "SELECT i.*, a.application_id
                 FROM interviews i
                 JOIN applications a ON i.application_id = a.application_id
                 JOIN jobs_posts j ON a.post_id = j.post_id
                 WHERE i.interview_id = ? AND j.company_id = ?";
    $checkStmt = $db->getConnection()->prepare($checkSql);
    $checkStmt->execute([$interview_id, $user_id]);
    $interview = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$interview) {
        throw new Exception("ไม่พบข้อมูลการสัมภาษณ์ หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้");
    }
    
    // ลบข้อมูลการสัมภาษณ์
    $deleteSql = "DELETE FROM interviews WHERE interview_id = ?";
    $deleteStmt = $db->getConnection()->prepare($deleteSql);
    $deleteStmt->execute([$interview_id]);
    
    if ($deleteStmt->rowCount() > 0) {
        // ลบข้อมูลสำเร็จ
        $_SESSION['success_message'] = "ลบข้อมูลการสัมภาษณ์เรียบร้อยแล้ว";
        
        // อัปเดตสถานะใบสมัครเป็น 'reviewing' หากปัจจุบันเป็น 'interview'
        $updateSql = "UPDATE applications 
                      SET status = 'reviewing' 
                      WHERE application_id = ? AND status = 'interview'";
        $updateStmt = $db->getConnection()->prepare($updateSql);
        $updateStmt->execute([$interview['application_id']]);
    } else {
        throw new Exception("ไม่สามารถลบข้อมูลการสัมภาษณ์ได้");
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

// กลับไปยังหน้ารายการการสัมภาษณ์
header("Location: index.php");
exit;
?>