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
$user_type = $_SESSION['user_type'];

// ตรวจสอบว่ามีพารามิเตอร์ ID หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบรหัสการสัมภาษณ์";
    header("Location: index.php");
    exit;
}

$interview_id = $_GET['id'];

// ตรวจสอบและประมวลผลการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interview_date = $_POST['interview_date'] ?? null;
    $interview_time = $_POST['interview_time'] ?? null;
    $interview_type = $_POST['interview_type'] ?? null;
    $interview_location = $_POST['interview_location'] ?? null;
    $status = $_POST['status'] ?? null;
    $interview_notes = $_POST['interview_notes'] ?? null;
    
    // ตรวจสอบความถูกต้องของข้อมูล
    $errors = [];
    
    if (empty($interview_date)) {
        $errors[] = "กรุณาระบุวันที่สัมภาษณ์";
    }
    
    if (empty($interview_type)) {
        $errors[] = "กรุณาระบุประเภทการสัมภาษณ์";
    }
    
    if (empty($status)) {
        $errors[] = "กรุณาระบุสถานะการสัมภาษณ์";
    }
    
    // ถ้าไม่มีข้อผิดพลาด ดำเนินการต่อ
    if (empty($errors)) {
        try {
            // อัปเดตข้อมูลในฐานข้อมูล
            $sql = "UPDATE interviews SET 
                    interview_date = ?, 
                    interview_time = ?, 
                    interview_type = ?, 
                    interview_location = ?, 
                    status = ?, 
                    interview_notes = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE interview_id = ? AND EXISTS (
                        SELECT 1 FROM applications a 
                        JOIN jobs_posts j ON a.post_id = j.post_id 
                        WHERE a.application_id = interviews.application_id 
                        AND j.company_id = ?
                    )";
                    
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->execute([
                $interview_date, 
                $interview_time, 
                $interview_type, 
                $interview_location, 
                $status, 
                $interview_notes, 
                $interview_id, 
                $user_id
            ]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "อัปเดตข้อมูลการสัมภาษณ์เรียบร้อยแล้ว";
                header("Location: view.php?id=" . $interview_id);
                exit;
            } else {
                $_SESSION['error_message'] = "ไม่สามารถอัปเดตข้อมูลได้ หรือไม่มีการเปลี่ยนแปลงข้อมูล";
                header("Location: view.php?id=" . $interview_id);
                exit;
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}

// ดึงข้อมูลการสัมภาษณ์จากฐานข้อมูล
try {
    $sql = "SELECT i.*, a.application_id, a.post_id, a.student_id, 
            j.job_title, j.job_description, j.positions, 
            c.company_id, c.company_name, c.contact_person, c.contact_email,
            s.first_name, s.last_name
            FROM interviews i
            JOIN applications a ON i.application_id = a.application_id
            JOIN jobs_posts j ON a.post_id = j.post_id
            JOIN students s ON a.student_id = s.student_id
            JOIN companies c ON j.company_id = c.company_id
            WHERE i.interview_id = ? AND c.company_id = ?";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$interview_id, $user_id]);
    $interview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$interview) {
        $_SESSION['error_message'] = "ไม่พบข้อมูลการสัมภาษณ์ หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
        header("Location: index.php");
        exit;
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

// เตรียมส่วนหัวของหน้า
$pageTitle = "แก้ไขข้อมูลการสัมภาษณ์";
include '../../layouts/header.php';

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">แก้ไขข้อมูลการสัมภาษณ์</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">หน้าหลัก</a></li>
                        <li class="breadcrumb-item"><a href="index.php">การสัมภาษณ์</a></li>
                        <li class="breadcrumb-item"><a href="view.php?id=<?php echo $interview_id; ?>">รายละเอียด</a></li>
                        <li class="breadcrumb-item active">แก้ไข</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">แก้ไขข้อมูลการสัมภาษณ์</h3>
                        </div>
                        <div class="card-body">
                            <form action="edit.php?id=<?php echo $interview_id; ?>" method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="job_title">ตำแหน่งงาน</label>
                                            <input type="text" class="form-control" id="job_title" value="<?php echo htmlspecialchars($interview['job_title']); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="student_name">ผู้สมัคร</label>
                                            <input type="text" class="form-control" id="student_name" value="<?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="interview_date">วันที่สัมภาษณ์ <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="interview_date" name="interview_date" value="<?php echo $interview['interview_date']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="interview_time">เวลาสัมภาษณ์</label>
                                            <input type="time" class="form-control" id="interview_time" name="interview_time" value="<?php echo $interview['interview_time']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="interview_type">ประเภทการสัมภาษณ์ <span class="text-danger">*</span></label>
                                            <select class="form-control" id="interview_type" name="interview_type" required>
                                                <option value="">--- เลือกประเภทการสัมภาษณ์ ---</option>
                                                <option value="in-person" <?php echo ($interview['interview_type'] == 'in-person') ? 'selected' : ''; ?>>สัมภาษณ์แบบพบหน้า</option>
                                                <option value="phone" <?php echo ($interview['interview_type'] == 'phone') ? 'selected' : ''; ?>>สัมภาษณ์ทางโทรศัพท์</option>
                                                <option value="video" <?php echo ($interview['interview_type'] == 'video') ? 'selected' : ''; ?>>สัมภาษณ์ทางวิดีโอ</option>
                                                <option value="group" <?php echo ($interview['interview_type'] == 'group') ? 'selected' : ''; ?>>สัมภาษณ์แบบกลุ่ม</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="interview_location">สถานที่/ช่องทางการสัมภาษณ์</label>
                                            <input type="text" class="form-control" id="interview_location" name="interview_location" value="<?php echo htmlspecialchars($interview['interview_location'] ?? ''); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="status">สถานะ <span class="text-danger">*</span></label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="">--- เลือกสถานะ ---</option>
                                                <option value="scheduled" <?php echo ($interview['status'] == 'scheduled') ? 'selected' : ''; ?>>กำหนดการแล้ว</option>
                                                <option value="completed" <?php echo ($interview['status'] == 'completed') ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                                                <option value="canceled" <?php echo ($interview['status'] == 'canceled') ? 'selected' : ''; ?>>ยกเลิก</option>
                                                <option value="rescheduled" <?php echo ($interview['status'] == 'rescheduled') ? 'selected' : ''; ?>>เลื่อนออกไป</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="interview_notes">บันทึกเพิ่มเติม</label>
                                    <textarea class="form-control" id="interview_notes" name="interview_notes" rows="5"><?php echo htmlspecialchars($interview['interview_notes'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
                                    <a href="view.php?id=<?php echo $interview_id; ?>" class="btn btn-default">ยกเลิก</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>