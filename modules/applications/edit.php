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

// ตรวจสอบว่ามี ID สำหรับการแก้ไขหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลที่ต้องการแก้ไข";
    header("Location: index.php");
    exit;
}

$application_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// ตรวจสอบว่าผู้ใช้มีสิทธิ์ในการแก้ไขใบสมัครนี้หรือไม่
$can_edit = false;

if ($user_type == 'student') {
    // นักศึกษาสามารถแก้ไขได้เฉพาะใบสมัครของตัวเอง และต้องอยู่ในสถานะ pending เท่านั้น
    $check_query = "SELECT a.*, j.job_title, j.company_id, c.company_name
                    FROM applications a
                    JOIN jobs_posts j ON a.post_id = j.post_id
                    JOIN companies c ON j.company_id = c.company_id
                    WHERE a.application_id = :application_id 
                    AND a.student_id = :student_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':application_id', $application_id);
    $check_stmt->bindValue(':student_id', $user_id);
    $check_stmt->execute();
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($application && strtolower($application['status']) == 'pending') {
        $can_edit = true;
    }
} elseif ($user_type == 'admin') {
    // แอดมินสามารถแก้ไขได้ทุกใบสมัคร
    $check_query = "SELECT a.*, j.job_title, j.company_id, c.company_name, 
                    s.first_name, s.last_name
                    FROM applications a
                    JOIN jobs_posts j ON a.post_id = j.post_id
                    JOIN companies c ON j.company_id = c.company_id
                    JOIN students s ON a.student_id = s.student_id
                    WHERE a.application_id = :application_id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bindValue(':application_id', $application_id);
    $check_stmt->execute();
    $application = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        $can_edit = true;
    }
}

// ถ้าไม่มีสิทธิ์แก้ไข
if (!$can_edit || !$application) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์แก้ไขใบสมัครนี้หรือใบสมัครอยู่ในสถานะที่ไม่สามารถแก้ไขได้";
    header("Location: " . ROOT_URL . "/dashboard.php");
    exit;
}

// รองรับการอัปโหลดเอกสาร
$resume_path = $application['resume_path']; // เก็บค่าเดิมไว้ก่อน
$upload_error = "";

// ถ้ามีการส่งฟอร์มการแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ตรวจสอบการอัปโหลดไฟล์ Resume ใหม่
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0 && $_FILES['resume']['size'] > 0) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_size = 5 * 1024 * 1024; // 5 MB
        
        $file_type = $_FILES['resume']['type'];
        $file_size = $_FILES['resume']['size'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
            // ตรวจสอบและสร้างโฟลเดอร์ upload ถ้ายังไม่มี
            $upload_dir = '../../uploads/resumes/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // สร้างชื่อไฟล์ใหม่
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $new_filename = 'resume_' . $application_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
                // ลบไฟล์เก่า (ถ้ามี)
                if (!empty($resume_path) && file_exists('../../' . $resume_path)) {
                    unlink('../../' . $resume_path);
                }
                
                // เก็บพาธใหม่
                $resume_path = '/uploads/resumes/' . $new_filename;
            } else {
                $upload_error = "ไม่สามารถอัปโหลดไฟล์ได้ โปรดลองอีกครั้ง";
            }
        } else {
            if (!in_array($file_type, $allowed_types)) {
                $upload_error = "รองรับเฉพาะไฟล์ PDF และ Word เท่านั้น";
            } else {
                $upload_error = "ขนาดไฟล์ต้องไม่เกิน 5 MB";
            }
        }
    }
    
    // ถ้าไม่มีข้อผิดพลาดในการอัปโหลด
    if (empty($upload_error)) {
        // ดึงข้อมูลจากฟอร์ม
        $cover_letter = $_POST['cover_letter'] ?? '';
        $expected_salary = floatval($_POST['expected_salary']);
        $available_start_date = $_POST['available_start_date'] ?? null;
        $available_hours = $_POST['available_hours'] ?? '';
        $additional_info = $_POST['additional_info'] ?? '';
        $message = $_POST['message'] ?? '';
        
        // อัปเดตข้อมูลใบสมัคร
        $update_query = "UPDATE applications SET 
                        cover_letter = :cover_letter, 
                        expected_salary = :expected_salary, 
                        available_start_date = :available_start_date, 
                        available_hours = :available_hours, 
                        additional_info = :additional_info, 
                        message = :message,
                        resume_path = :resume_path,
                        updated_at = NOW()
                        WHERE application_id = :application_id";
        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindValue(':cover_letter', $cover_letter);
        $update_stmt->bindValue(':expected_salary', $expected_salary);
        $update_stmt->bindValue(':available_start_date', $available_start_date);
        $update_stmt->bindValue(':available_hours', $available_hours);
        $update_stmt->bindValue(':additional_info', $additional_info);
        $update_stmt->bindValue(':message', $message);
        $update_stmt->bindValue(':resume_path', $resume_path);
        $update_stmt->bindValue(':application_id', $application_id);
        
        try {
            $update_stmt->execute();
            
            $_SESSION['success_message'] = "แก้ไขข้อมูลใบสมัครเรียบร้อยแล้ว";
            
            // กำหนดหน้าที่จะ redirect ไปตามประเภทผู้ใช้
            if ($user_type == 'student') {
                header("Location: index.php");
            } else {
                header("Location: view.php?id=" . $application_id);
            }
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . $e->getMessage();
        }
    }
}

// Include header
include('../../layouts/header.php');
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-edit me-2"></i>แก้ไขใบสมัครงาน</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $upload_error; ?>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>ข้อมูลงาน</h5>
                    <p><strong>ตำแหน่งงาน:</strong> <?php echo htmlspecialchars($application['job_title'] ?? ''); ?></p>
                    <p><strong>บริษัท:</strong> <?php echo htmlspecialchars($application['company_name'] ?? ''); ?></p>
                </div>
                <div class="col-md-6">
                    <?php if ($user_type == 'admin'): ?>
                        <h5>ข้อมูลผู้สมัคร</h5>
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? '')); ?></p>
                    <?php endif; ?>
                    <p><strong>วันที่สมัคร:</strong> <?php echo date('d/m/Y H:i', strtotime($application['apply_date'] ?? 'now')); ?></p>
                    <p><strong>สถานะปัจจุบัน:</strong> 
                        <span class="badge bg-info"><?php echo getApplicationStatus($application['status'] ?? ''); ?></span>
                    </p>
                </div>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="expected_salary" class="form-label">เงินเดือนที่คาดหวัง (บาท) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="expected_salary" name="expected_salary" 
                               value="<?php echo htmlspecialchars($application['expected_salary'] ?? '0'); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="available_start_date" class="form-label">วันที่พร้อมเริ่มงาน <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="available_start_date" name="available_start_date" 
                               value="<?php echo htmlspecialchars($application['available_start_date'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="available_hours" class="form-label">เวลาทำงานที่สะดวก <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="available_hours" name="available_hours" 
                           value="<?php echo htmlspecialchars($application['available_hours'] ?? ''); ?>" 
                           placeholder="เช่น จันทร์-ศุกร์ 10:00-17:00, เสาร์-อาทิตย์ 09:00-18:00" required>
                </div>
                
                <div class="mb-3">
                    <label for="cover_letter" class="form-label">จดหมายสมัครงาน</label>
                    <textarea class="form-control" id="cover_letter" name="cover_letter" rows="5"
                              placeholder="แนะนำตัวเองและอธิบายว่าทำไมคุณเหมาะสมกับตำแหน่งนี้"><?php echo htmlspecialchars($application['cover_letter'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">ข้อความถึงผู้ประกาศงาน <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="message" name="message" rows="3" 
                              placeholder="ข้อความสั้นๆ ถึงผู้ประกาศงาน" required><?php echo htmlspecialchars($application['message'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="additional_info" class="form-label">ข้อมูลเพิ่มเติม</label>
                    <textarea class="form-control" id="additional_info" name="additional_info" rows="3"
                              placeholder="ข้อมูลอื่นๆ ที่ต้องการแจ้งให้ผู้ประกาศงานทราบ"><?php echo htmlspecialchars($application['additional_info'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="resume" class="form-label">อัปเดต Resume (PDF หรือ Word)</label>
                    <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx">
                    <?php if (!empty($application['resume_path'])): ?>
                        <div class="mt-2">
                            <p class="text-success">
                                <i class="fas fa-file-alt me-2"></i>มีไฟล์ Resume อัปโหลดแล้ว 
                                <a href="<?php echo htmlspecialchars($application['resume_path'] ?? ''); ?>" download class="btn btn-sm btn-info ms-2">
                                    <i class="fas fa-download me-1"></i>ดาวน์โหลด
                                </a>
                            </p>
                        </div>
                    <?php endif; ?>
                    <small class="form-text text-muted">รองรับเฉพาะไฟล์ PDF และ Word ขนาดไม่เกิน 5 MB</small>
                </div>
                
                <div class="d-flex justify-content-end">
                    <a href="<?php echo ($user_type == 'student') ? 'index.php' : 'view.php?id=' . $application_id; ?>" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>ยกเลิก
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// ฟังก์ชันแสดงสถานะใบสมัครเป็นภาษาไทย
function getApplicationStatus($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'รอการตรวจสอบ';
        case 'reviewing':
            return 'กำลังพิจารณา';
        case 'interview':
            return 'นัดสัมภาษณ์';
        case 'accepted':
            return 'ผ่านการคัดเลือก';
        case 'rejected':
            return 'ไม่ผ่านการคัดเลือก';
        case 'cancelled':
            return 'ยกเลิกแล้ว';
        default:
            return 'ไม่ระบุ';
    }
}

// Include footer
include('../../layouts/footer.php');
?>