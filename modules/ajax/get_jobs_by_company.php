<?php
include('../../includes/functions.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นแอดมินหรือไม่
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// ตรวจสอบว่ามีการส่ง company_id มาหรือไม่
if (!isset($_GET['company_id']) || empty($_GET['company_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Company ID is required']);
    exit;
}

$company_id = $_GET['company_id'];

// ดึงข้อมูลประกาศงานตามบริษัท
$sql = "SELECT post_id, job_title FROM jobs_posts WHERE company_id = ? ORDER BY post_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$company_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ส่งกลับข้อมูลเป็น JSON
header('Content-Type: application/json');
echo json_encode($jobs);
?>