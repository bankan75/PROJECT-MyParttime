<?php
include('../../includes/functions.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นแอดมินหรือไม่
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// ดึงข้อมูลประกาศงานทั้งหมด
$sql = "SELECT post_id, job_title FROM jobs_posts ORDER BY post_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ส่งกลับข้อมูลเป็น JSON
header('Content-Type: application/json');
echo json_encode($jobs);
?>