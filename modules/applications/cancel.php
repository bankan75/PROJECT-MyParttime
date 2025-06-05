<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include utility functions
require_once('../../includes/functions.php');

// Include database config
require_once('../../includes/config.php');

// ใช้การเชื่อมต่อฐานข้อมูลที่มีอยู่แล้วจาก config.php
$conn = $database->getConnection();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบก่อนใช้งาน";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ตรวจสอบว่ามี ID ในการยกเลิกหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลที่ต้องการยกเลิก";
    header("Location: my_applications.php");
    exit;
}

$application_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการยกเลิกใบสมัครนี้หรือไม่
$can_cancel = false;
$post_id = null; // เก็บ post_id เพื่อใช้ในการตรวจสอบว่าเคยสมัครงานนี้หรือยัง

if ($user_type == 'student') {
    // นักศึกษาสามารถยกเลิกได้เฉพาะใบสมัครของตัวเอง
    $check_query = "SELECT a.*, j.job_title, a.post_id 
                    FROM applications a
                    JOIN jobs_posts j ON a.post_id = j.post_id
                    WHERE a.application_id = :application_id 
                    AND a.student_id = :student_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':application_id', $application_id);
    $check_stmt->bindValue(':student_id', $user_id);
    $check_stmt->execute();
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        // ตรวจสอบว่าสถานะปัจจุบันยังยกเลิกได้หรือไม่ (pending หรือ reviewing)
        if (in_array(strtolower($application['status']), ['pending', 'reviewing'])) {
            $can_cancel = true;
            $post_id = $application['post_id'];
        }
    }
} elseif ($user_type == 'company') {
    // บริษัทสามารถยกเลิกได้เฉพาะใบสมัครที่สมัครมาที่งานของบริษัทตัวเอง
    $check_query = "SELECT a.*, j.job_title, a.post_id
                    FROM applications a
                    JOIN jobs_posts j ON a.post_id = j.post_id
                    WHERE a.application_id = :application_id 
                    AND j.company_id = :company_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':application_id', $application_id);
    $check_stmt->bindValue(':company_id', $user_id);
    $check_stmt->execute();
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        // บริษัทสามารถยกเลิกได้ทุกสถานะ
        $can_cancel = true;
        $post_id = $application['post_id'];
    }
} elseif ($user_type == 'admin') {
    // แอดมินสามารถยกเลิกได้ทุกใบสมัคร
    $check_query = "SELECT a.*, j.job_title, a.post_id
                    FROM applications a
                    JOIN jobs_posts j ON a.post_id = j.post_id
                    WHERE a.application_id = :application_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':application_id', $application_id);
    $check_stmt->execute();
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        $can_cancel = true;
        $post_id = $application['post_id'];
    }
}

// ถ้าไม่มีสิทธิ์ยกเลิก
if (!$can_cancel || !$application) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์ยกเลิกใบสมัครนี้หรือใบสมัครอยู่ในสถานะที่ไม่สามารถยกเลิกได้";
    header("Location: " . ROOT_URL . "/dashboard.php");
    exit;
}

// ถ้ามีการส่งฟอร์มยืนยันการยกเลิก
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancel'])) {
    try {
        $conn->beginTransaction();
        
        // ลบข้อมูลจากตาราง application_status_history ก่อน (ลบประวัติการเปลี่ยนสถานะ)
        $delete_history_query = "DELETE FROM application_status_history WHERE application_id = :application_id";
        $delete_history_stmt = $conn->prepare($delete_history_query);
        $delete_history_stmt->bindValue(':application_id', $application_id);
        $delete_history_stmt->execute();
        
        // ตรวจสอบและลบข้อมูลการนัดสัมภาษณ์ ถ้ามี
        $delete_interview_query = "DELETE FROM interviews WHERE application_id = :application_id";
        $delete_interview_stmt = $conn->prepare($delete_interview_query);
        $delete_interview_stmt->bindValue(':application_id', $application_id);
        $delete_interview_stmt->execute();
        
        // ลบข้อมูลใบสมัครงาน
        $delete_application_query = "DELETE FROM applications WHERE application_id = :application_id";
        $delete_application_stmt = $conn->prepare($delete_application_query);
        $delete_application_stmt->bindValue(':application_id', $application_id);
        $delete_application_stmt->execute();
        
        $conn->commit();
        
        $_SESSION['success_message'] = "ยกเลิกใบสมัครเรียบร้อยแล้ว คุณสามารถสมัครงานนี้ได้อีกครั้ง";
        
        // กำหนดหน้าที่จะ redirect ไปตามประเภทผู้ใช้
        if ($user_type == 'student') {
            // ถ้าต้องการให้กลับไปที่หน้าประกาศงานที่เพิ่งยกเลิก สามารถทำได้ดังนี้
            header("Location: " . ROOT_URL . "/modules/applications/index.php?id=" . $post_id);
        } else {
            header("Location: index.php");
        }
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการยกเลิกใบสมัคร: " . $e->getMessage();
        header("Location: view.php?id=" . $application_id);
        exit;
    }
}

// Include header
include('../../layouts/header.php');
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5><i class="fas fa-times-circle me-2"></i>ยกเลิกใบสมัคร</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>คำเตือน</h5>
                <p>คุณกำลังจะยกเลิกใบสมัครงานสำหรับตำแหน่ง: <strong><?php echo htmlspecialchars($application['job_title']); ?></strong></p>
                <p><strong>หมายเหตุ:</strong> การยกเลิกจะทำให้ข้อมูลการสมัครงานนี้ถูกลบออกจากระบบ คุณสามารถสมัครงานนี้ได้อีกครั้งหากต้องการ</p>
                <p>คุณแน่ใจหรือไม่ที่จะดำเนินการต่อ?</p>
            </div>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="cancel_reason" class="form-label">เหตุผลในการยกเลิก (ไม่บังคับ)</label>
                    <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" placeholder="โปรดระบุเหตุผลในการยกเลิกใบสมัคร"></textarea>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="<?php echo ($user_type == 'student') ? 'index.php' : 'index.php'; ?>" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>ย้อนกลับ
                    </a>
                    <button type="submit" name="confirm_cancel" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i>ยืนยันการยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include('../../layouts/footer.php');
?>