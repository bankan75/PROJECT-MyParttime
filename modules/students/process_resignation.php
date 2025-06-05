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
    header("Location: " . ROOT_URL . "/index.php");
    exit;
}

// ตรวจสอบว่าเป็นการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "การร้องขอไม่ถูกต้อง";
    header("Location: " . ROOT_URL . "/profiles/student_profile.php");
    exit;
}

// ตรวจสอบข้อมูลที่ส่งมา
if (empty($_POST['application_id']) || empty($_POST['resignation_reason']) || empty($_POST['resignation_date'])) {
    $_SESSION['error_message'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
    header("Location: " . ROOT_URL . "/profiles/student_profile.php");
    exit;
}

$application_id = $_POST['application_id'];
$resignation_reason = $_POST['resignation_reason'];
$resignation_date = $_POST['resignation_date'];
$student_id = $_SESSION['user_id'];

// ตรวจสอบว่าการสมัครงานนี้เป็นของนักศึกษาที่ล็อกอินหรือไม่
$application_check_query = "SELECT a.*, j.job_title, j.company_id, c.company_name 
                          FROM applications a 
                          JOIN jobs_posts j ON a.post_id = j.post_id 
                          JOIN companies c ON j.company_id = c.company_id
                          WHERE a.application_id = ? AND a.student_id = ? AND a.status = 'accepted'";
$stmt = $db->getConnection()->prepare($application_check_query);
$stmt->execute([$application_id, $student_id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลการสมัครงานที่ต้องการหรือคุณไม่มีสิทธิ์ดำเนินการนี้";
    header("Location: " . ROOT_URL . "/profiles/student_profile.php");
    exit;
}

// บันทึกคำร้องขอลาออกลงในฐานข้อมูล
// (สร้างตาราง resignation_requests ก่อนถ้ายังไม่มี)
try {
    // เช็คว่ามีคำร้องที่ยังไม่ได้รับการอนุมัติหรือไม่
    $check_query = "SELECT COUNT(*) as count FROM resignation_requests 
                  WHERE application_id = ? AND status = 'pending'";
    $stmt = $db->getConnection()->prepare($check_query);
    $stmt->execute([$application_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $_SESSION['error_message'] = "คุณมีคำร้องขอลาออกที่รออนุมัติอยู่แล้ว";
        header("Location: " . ROOT_URL . "/profiles/student_profile.php");
        exit;
    }

    // บันทึกคำร้องใหม่
    $insert_query = "INSERT INTO resignation_requests (application_id, student_id, company_id, reason, resignation_date, submit_date, status) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
    $stmt = $db->getConnection()->prepare($insert_query);
    $result = $stmt->execute([
        $application_id, 
        $student_id, 
        $application['company_id'], 
        $resignation_reason, 
        $resignation_date
    ]);

    if ($result) {
        // ส่งการแจ้งเตือนให้บริษัท (ถ้ามีระบบการแจ้งเตือน)
        // sendNotificationToCompany($application['company_id'], 'resignation_request', ...);
        
        $_SESSION['success_message'] = "ยื่นคำร้องขอลาออกเรียบร้อยแล้ว กรุณารอการอนุมัติจากบริษัท";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกคำร้องขอลาออก";
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// กลับไปหน้าโปรไฟล์
header("Location: " . ROOT_URL . "/profiles/student_profile.php");
exit;