<?php
// Include header
include('../../layouts/header.php');


// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นนักศึกษาหรือไม่
if (!$auth->isLoggedIn() || !$auth->isStudent()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีนักศึกษา";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง job_id มาหรือไม่
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน";
    header("Location: " . ROOT_URL . "/modules/jobs/index.php");
    exit;
}

$job_id = $_GET['job_id'];
$student_id = $_SESSION['user_id'];

// ตรวจสอบว่าเคยสมัครงานนี้แล้วหรือไม่
$check_sql = "SELECT * FROM applications WHERE post_id = ? AND student_id = ?";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute([$job_id, $student_id]);
$existing_application = $check_stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_application) {
    $_SESSION['error_message'] = "คุณได้สมัครงานนี้ไปแล้ว";
    header("Location: " . ROOT_URL . "/modules/jobs/view.php?id=" . $job_id);
    exit;
}

// ดึงข้อมูลงาน
$job_sql = "SELECT j.*, c.company_name 
            FROM jobs_posts j 
            JOIN companies c ON j.company_id = c.company_id
            WHERE j.post_id = ? AND j.is_active = 1 AND j.expire_date >= CURDATE()";
$job_stmt = $db->prepare($job_sql);
$job_stmt->execute([$job_id]);
$job = $job_stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน หรืองานนี้ไม่เปิดรับสมัครแล้ว";
    header("Location: " . ROOT_URL . "/modules/jobs/index.php");
    exit;
}

// ดึงข้อมูลนักศึกษา
$student_sql = "SELECT * FROM students WHERE student_id = ?";
$student_stmt = $db->prepare($student_sql);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // เตรียมข้อมูล
        $application_date = date('Y-m-d H:i:s');
        $message = $_POST['message'] ?? '';
        $expected_salary = $_POST['expected_salary'] ?? 0;
        $available_start_date = $_POST['available_start_date'] ?? null;
        $available_hours = $_POST['available_hours'] ?? '';
        
        // เตรียม statement สำหรับการเพิ่มข้อมูล
$insert_sql = "INSERT INTO applications (post_id, student_id, apply_date, message, expected_salary, 
available_start_date, available_hours, status, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
$insert_stmt = $db->prepare($insert_sql);
        
        // ประมวลผลไฟล์ resume ถ้ามีการอัปโหลด
        $resume_path = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            // ตรวจสอบชนิดไฟล์
            if (!in_array($_FILES['resume']['type'], $allowed_types)) {
                throw new Exception("กรุณาอัปโหลดไฟล์ PDF หรือ DOC หรือ DOCX เท่านั้น");
            }
            
            // ตรวจสอบขนาดไฟล์
            if ($_FILES['resume']['size'] > $max_size) {
                throw new Exception("ขนาดไฟล์ต้องไม่เกิน 5MB");
            }
            
            // สร้างโฟลเดอร์เก็บไฟล์ถ้ายังไม่มี
            $upload_dir = BASE_PATH . '/uploads/resumes/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // สร้างชื่อไฟล์ใหม่
            $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $new_filename = 'resume_' . $student_id . '_' . $job_id . '_' . time() . '.' . $file_extension;
            $resume_path = 'uploads/resumes/' . $new_filename;
            $upload_path = $upload_dir . $new_filename;
            
            // อัปโหลดไฟล์
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
                throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
            }
        }
        
        // ทำการบันทึกข้อมูล
        $result = $insert_stmt->execute([
            $job_id,
            $student_id,
            $application_date,
            $message,
            $expected_salary,
            $available_start_date,
            $available_hours,
        ]);
        
        // ถ้ามีการอัปโหลดไฟล์ ให้อัปเดตข้อมูล resume_path
        if ($resume_path) {
            $application_id = $db->lastInsertId();
            $update_sql = "UPDATE applications SET resume_path = ? WHERE application_id = ?";
            $update_stmt = $db->prepare($update_sql);
            $update_stmt->execute([$resume_path, $application_id]);
        }
        
        if ($result) {
            $_SESSION['success_message'] = "ส่งใบสมัครงานสำเร็จ";
            header("Location: " . ROOT_URL . "/modules/applications/index.php");
            exit;
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">สมัครงาน: <?php echo htmlspecialchars($job['job_title']); ?></h1>
        <a href="<?php echo ROOT_URL; ?>/modules/jobs/view.php?id=<?php echo $job_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายละเอียดงาน
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>แบบฟอร์มสมัครงาน</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">บริษัท</label>
                                <input type="text" class="form-control" id="company_name" value="<?php echo htmlspecialchars($job['company_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="job_title" class="form-label">ตำแหน่งงาน</label>
                                <input type="text" class="form-control" id="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" id="fullname" value="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="student_code" class="form-label">รหัสนักศึกษา</label>
                                <input type="text" class="form-control" id="student_code" value="<?php echo htmlspecialchars($student['student_code']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expected_salary" class="form-label">เงินเดือนที่คาดหวัง (บาท)</label>
                                <input type="number" class="form-control" id="expected_salary" name="expected_salary" min="0" step="0.01" required>
                                <small class="text-muted">เงินเดือนที่บริษัทเสนอ: <?php echo number_format($job['min_salary'], 2) . ' - ' . number_format($job['max_salary'], 2); ?> บาท</small>
                            </div>
                            <div class="col-md-6">
                                <label for="available_start_date" class="form-label">วันที่พร้อมเริ่มงาน</label>
                                <input type="date" class="form-control" id="available_start_date" name="available_start_date" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="available_hours" class="form-label">วันและเวลาที่สะดวกทำงาน</label>
                            <textarea class="form-control" id="available_hours" name="available_hours" rows="3" required
                                placeholder="ระบุวันและเวลาที่คุณสะดวกในการทำงาน เช่น 'จันทร์-ศุกร์ 13.00-17.00 น., เสาร์-อาทิตย์ 9.00-17.00 น.'"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">ข้อความถึงผู้ว่าจ้าง (ไม่บังคับ)</label>
                            <textarea class="form-control" id="message" name="message" rows="5"
                                placeholder="แนะนำตัวเองเพิ่มเติม ประสบการณ์ ทักษะพิเศษ หรือข้อมูลอื่นๆ ที่เกี่ยวข้องกับการสมัครงานนี้"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="resume" class="form-label">อัปโหลดเรซูเม่ (PDF, DOC, DOCX ไม่เกิน 5MB)</label>
                            <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx">
                            <small class="text-muted">ไฟล์เรซูเม่หรือประวัติส่วนตัวของคุณ</small>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>ส่งใบสมัครงาน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>ข้อมูลงาน</h5>
                </div>
                <div class="card-body">
                    <p><strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($job['job_title']); ?></p>
                    <p><strong>บริษัท:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                    <p><strong>เงินเดือน:</strong> <?php echo number_format($job['min_salary'], 2) . ' - ' . number_format($job['max_salary'], 2); ?> บาท</p>
                    <p><strong>สถานที่ทำงาน:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                    <p><strong>วันทำงาน:</strong> <?php echo htmlspecialchars($job['work_days']); ?></p>
                    <p><strong>เวลาทำงาน:</strong> <?php echo htmlspecialchars($job['work_hours']); ?></p>
                    <p><strong>รับสมัครถึง:</strong> <?php echo date('d/m/Y', strtotime($job['expire_date'])); ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>คำแนะนำการสมัครงาน</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li class="mb-2">กรอกข้อมูลให้ครบถ้วนและถูกต้อง</li>
                        <li class="mb-2">ระบุวันและเวลาที่สะดวกทำงานให้ชัดเจน</li>
                        <li class="mb-2">เตรียมเรซูเม่ที่เป็นปัจจุบันและเกี่ยวข้องกับงาน</li>
                        <li class="mb-2">แนะนำตัวเองในข้อความถึงผู้ว่าจ้างอย่างเหมาะสม</li>
                        <li>หลังจากส่งใบสมัครแล้ว คุณสามารถตรวจสอบสถานะได้ในหน้า "การสมัครงานของฉัน"</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include('../../layouts/footer.php');
?>