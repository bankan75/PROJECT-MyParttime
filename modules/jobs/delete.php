<?php
// Include database config
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// สร้างการเชื่อมต่อกับฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ และเป็นบริษัทหรือแอดมินเท่านั้น
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || 
    ($_SESSION['user_type'] != 'company' && $_SESSION['user_type'] != 'admin')) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน";
    header("Location: index.php");
    exit;
}

$job_id = $_GET['id'];

// ตรวจสอบว่างานนี้เป็นของบริษัทที่กำลังล็อกอินอยู่หรือไม่ (กรณีเป็นบริษัท)
if ($user_type == 'company') {
    $query = "SELECT post_id FROM jobs_posts WHERE post_id = :job_id AND company_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ลบงานนี้";
        header("Location: index.php");
        exit;
    }
}

// ลบข้อมูลงาน
$query = "DELETE FROM jobs_posts WHERE post_id = :job_id";
if ($user_type == 'company') {
    $query .= " AND company_id = :user_id";
}

$stmt = $conn->prepare($query);
$stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);

if ($user_type == 'company') {
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}

if ($stmt->execute()) {
    // ลบข้อมูลการสมัครงานที่เกี่ยวข้องกับงานนี้ (เป็นการป้องกันข้อมูลค้างในระบบ)
    $query = "DELETE FROM applications WHERE post_id = :job_id";
        $stmt = $conn->prepare($query);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $_SESSION['success_message'] = "ลบข้อมูลงานเรียบร้อยแล้ว";
} else {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->errorInfo()[2];
}

header("Location: index.php");
exit;
?>