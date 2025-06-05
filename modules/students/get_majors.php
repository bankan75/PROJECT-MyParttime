<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// รับค่าคณะจาก AJAX request
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : '';

if (empty($faculty)) {
    // ถ้าไม่มีการเลือกคณะ ส่งกลับทุกสาขา
    $sql = "SELECT DISTINCT major_name FROM students ORDER BY major_name";
    $params = [];
} else {
    // ถ้ามีการเลือกคณะ ส่งกลับเฉพาะสาขาที่อยู่ในคณะนั้น
    $sql = "SELECT DISTINCT major_name FROM students WHERE faculty_name = ? ORDER BY major_name";
    $params = [$faculty];
}

try {
    $majors = $database->getRows($sql, $params);
    
    // ส่งข้อมูลสาขากลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode($majors);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาด
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}