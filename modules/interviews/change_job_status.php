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

// ตรวจสอบว่าเป็นนักศึกษาเท่านั้น
if (!$auth->isStudent()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: ../../index.php");
    exit;
}

// ตรวจสอบการส่งพารามิเตอร์
if (!isset($_GET['action']) || !isset($_GET['id'])) {
    $_SESSION['error_message'] = "ข้อมูลไม่ครบถ้วน";
    header("Location: index.php");
    exit;
}

$action = $_GET['action'];
$application_id = $_GET['id'];
$student_id = $_SESSION['user_id'];

// ตรวจสอบว่าการสมัครงานนี้เป็นของนักศึกษาที่ล็อกอินหรือไม่
$application_check_query = "SELECT COUNT(*) as count FROM applications 
                          WHERE application_id = ? AND student_id = ?";
$stmt = $db->getConnection()->prepare($application_check_query);
$stmt->execute([$application_id, $student_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ดำเนินการนี้";
    header("Location: index.php");
    exit;
}

// ดำเนินการตามคำสั่ง
if ($action == "unemployed") {
    // อัปเดตสถานะการสมัครงานเป็น 'completed'
    $update_query = "UPDATE applications SET status = 'completed' WHERE application_id = ?";
    $stmt = $db->getConnection()->prepare($update_query);
    
    if ($stmt->execute([$application_id])) {
        $_SESSION['success_message'] = "อัปเดตสถานะเป็นว่างงานเรียบร้อยแล้ว คุณสามารถสมัครงานใหม่ได้";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัปเดตสถานะ";
    }
}

// กลับไปหน้าที่มาจาก
header("Location: index.php");
exit;