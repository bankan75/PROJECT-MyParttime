<?php
include('../../includes/config.php');
include('../../includes/functions.php');
include('../../layouts/header.php'); // เพิ่ม header สำหรับ UI

// ตรวจสอบการล็อกอินและสิทธิ์บริษัท
if (!$auth->isLoggedIn() || !$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีบริษัท";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

$company_id = $_SESSION['user_id'];

// ตรวจสอบ employment_id จาก GET
$employment_id = isset($_GET['employment_id']) ? intval($_GET['employment_id']) : 0;
if ($employment_id <= 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลการจ้างงาน";
    header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
    exit;
}

// ดึงข้อมูลการจ้างงาน
$employment_sql = "SELECT e.*, s.student_code, s.title, s.first_name, s.last_name, j.job_title
                  FROM employments e
                  JOIN students s ON e.student_id = s.student_id
                  JOIN jobs_posts j ON j.company_id = e.company_id
                  WHERE e.employment_id = ? AND e.company_id = ? AND e.status = 'accepted'";
$employment_stmt = $db->prepare($employment_sql);
$employment_stmt->execute([$employment_id, $company_id]);
$employment = $employment_stmt->fetch(PDO::FETCH_ASSOC);

if (!$employment) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลการจ้างงานหรือคุณไม่มีสิทธิ์เข้าถึง";
    header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
    exit;
}

// จัดการ POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบข้อมูล POST
    if (!isset($_POST['employment_id']) || empty($_POST['employment_id'])) {
        $_SESSION['error_message'] = "ไม่พบข้อมูล ID การจ้างงาน";
        header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
        exit;
    }
    if (!isset($_POST['termination_reason']) || empty(trim($_POST['termination_reason']))) {
        $_SESSION['error_message'] = "กรุณาระบุเหตุผลในการยกเลิกการจ้างงาน";
        header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
        exit;
    }
    if (!isset($_POST['termination_date']) || empty($_POST['termination_date'])) {
        $_SESSION['error_message'] = "กรุณาระบุวันที่สิ้นสุดการจ้างงาน";
        header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
        exit;
    }

    // รับและทำความสะอาดข้อมูล
    $employment_id = intval($_POST['employment_id']);
    $termination_reason = trim($_POST['termination_reason']);
    $termination_date = $_POST['termination_date'];
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // ตรวจสอบรูปแบบวันที่
    if (!DateTime::createFromFormat('Y-m-d', $termination_date)) {
        $_SESSION['error_message'] = "รูปแบบวันที่ไม่ถูกต้อง";
        header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
        exit;
    }

    // เริ่ม transaction
    $db->beginTransaction();
    try {
        // อัปเดตสถานะการจ้างงาน
        $update_sql = "UPDATE employments SET 
                      status = 'terminated', 
                      termination_reason = ?, 
                      termination_date = ?, 
                      termination_comment = ?, 
                      updated_at = NOW() 
                      WHERE employment_id = ? AND company_id = ?";
        $update_stmt = $db->prepare($update_sql);
        $update_stmt->execute([$termination_reason, $termination_date, $comment, $employment_id, $company_id]);

        // อัปเดตสถานะนักศึกษา
        $update_student_sql = "UPDATE students SET employment_status = 'unemployed', updated_at = NOW() WHERE student_id = ?";
        $update_student_stmt = $db->prepare($update_student_sql);
        $update_student_stmt->execute([$employment['student_id']]);

        // อัปเดตสถานะใบสมัคร (ถ้ามี)
        $update_application_sql = "UPDATE applications SET status = 'available', updated_at = NOW() 
                                  WHERE student_id = ? AND status = 'accepted'";
        $update_application_stmt = $db->prepare($update_application_sql);
        $update_application_stmt->execute([$employment['student_id']]);

        // บันทึกประวัติการจ้างงาน
        $history_sql = "INSERT INTO employment_history (employment_id, student_id, company_id, action, reason, comment, action_date, created_at)
                        VALUES (?, ?, ?, 'terminated', ?, ?, ?, NOW())";
        $history_stmt = $db->prepare($history_sql);
        $history_stmt->execute([
            $employment_id,
            $employment['student_id'],
            $company_id,
            $termination_reason,
            $comment,
            $termination_date
        ]);

        // Commit transaction
        $db->commit();
        $_SESSION['success_message'] = "ยกเลิกการจ้างงานสำเร็จ";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
    exit;
}
?>

<!-- UI สำหรับยืนยันการยกเลิก -->
<div class="container-fluid">
    <h1 class="mt-4">ยุติการจ้างงาน</h1>
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-user-minus me-2"></i> ยุติการจ้างงาน</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <p><strong>ข้อมูลพนักงาน:</strong></p>
                <p>รหัสนักศึกษา: <?php echo htmlspecialchars($employment['student_code']); ?><br>
                   ชื่อ: <?php echo htmlspecialchars($employment['title'] . ' ' . $employment['first_name'] . ' ' . $employment['last_name']); ?><br>
                   ตำแหน่ง: <?php echo htmlspecialchars($employment['job_title']); ?><br>
                   วันที่เริ่มงาน: <?php echo date('d/m/Y', strtotime($employment['start_date'])); ?></p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="employment_id" value="<?php echo $employment_id; ?>">
                <div class="mb-3">
                    <label for="termination_reason" class="form-label">เหตุผลในการยุติการจ้างงาน <span class="text-danger">*</span></label>
                    <select name="termination_reason" class="form-select" required>
                        <option value="">เลือกเหตุผล</option>
                        <option value="สิ้นสุดสัญญา">สิ้นสุดสัญญา</option>
                        <option value="ลาออก">ลาออก</option>
                        <option value="ผลการทำงานไม่เป็นไปตามเกณฑ์">ผลการทำงานไม่เป็นไปตามเกณฑ์</option>
                        <option value="ปรับโครงสร้างองค์กร">ปรับโครงสร้างองค์กร</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="comment" class="form-label">ความคิดเห็นเพิ่มเติม (ถ้ามี)</label>
                    <textarea class="form-control" id="comment" name="comment" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label for="termination_date" class="form-label">วันที่สิ้นสุดการจ้างงาน <span class="text-danger">*</span></label>
                    <input type="date" name="termination_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <small>หมายเหตุ: การยุติการจ้างงานจะเปลี่ยนสถานะนักศึกษาเป็น "ว่างงาน" และอนุญาตให้สมัครงานใหม่ได้</small>
                </div>
                <div class="d-flex justify-content-end">
                    <a href="<?php echo ROOT_URL; ?>/modules/current_employees/current_employees.php" class="btn btn-secondary me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-danger">ยืนยันการยุติการจ้างงาน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../../layouts/footer.php'); ?>