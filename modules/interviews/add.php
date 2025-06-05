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

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะบริษัทเท่านั้น)
if (!$auth->isCompany()) {
    // ถ้าไม่ใช่บริษัท ให้เปลี่ยนเส้นทาง
    header("Location: ../../index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$company_id = $_SESSION['user_id'];

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ดึงข้อมูลจากฟอร์ม
        $application_id = $_POST['application_id'];
        $interview_date = $_POST['interview_date'];
        $interview_time = $_POST['interview_time'];
        $interview_type = $_POST['interview_type'];
        $interview_location = $_POST['interview_location'];
        $interview_notes = $_POST['interview_notes'];
        $status = $_POST['status'];
        
        // ตรวจสอบว่าใบสมัครนี้เป็นของบริษัทนี้หรือไม่
        $check_sql = "SELECT a.application_id 
                      FROM applications a 
                      JOIN jobs_posts j ON a.post_id = j.post_id 
                      WHERE a.application_id = ? AND j.company_id = ?";
        $check_stmt = $db->getConnection()->prepare($check_sql);
        $check_stmt->execute([$application_id, $company_id]);
        
        if ($check_stmt->rowCount() === 0) {
            throw new Exception("ไม่พบใบสมัครที่เลือกหรือไม่มีสิทธิ์เข้าถึง");
        }
        
        // เพิ่มข้อมูลการสัมภาษณ์
        $sql = "INSERT INTO interviews (application_id, interview_date, interview_time, interview_type, interview_location, interview_notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->getConnection()->prepare($sql);
        $result = $stmt->execute([$application_id, $interview_date, $interview_time, $interview_type, $interview_location, $interview_notes, $status]);
        
        if ($result) {
            // อัปเดตสถานะของใบสมัครเป็น 'interview' ถ้ายังไม่ได้เป็น
            $update_sql = "UPDATE applications SET status = 'interview' WHERE application_id = ? AND status != 'accepted'";
            $update_stmt = $db->getConnection()->prepare($update_sql);
            $update_stmt->execute([$application_id]);
            
            // บันทึกประวัติการเปลี่ยนแปลงสถานะ
            $history_sql = "INSERT INTO application_status_history (application_id, old_status, new_status, status, comment, created_at, created_by, changed_by_type) 
                            VALUES (?, 'reviewing', 'interview', 'interview', ?, NOW(), ?, 'company')";
            $history_stmt = $db->getConnection()->prepare($history_sql);
            $history_stmt->execute([$application_id, "นัดสัมภาษณ์วันที่ ".$interview_date, $company_id]);
            
            // แสดงข้อความสำเร็จและเปลี่ยนเส้นทาง
            $_SESSION['success_message'] = "เพิ่มการสัมภาษณ์เรียบร้อยแล้ว";
            header("Location: index.php");
            exit;
        } else {
            throw new Exception("ไม่สามารถเพิ่มข้อมูลได้");
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// เตรียมส่วนหัวของหน้า
$pageTitle = "เพิ่มการสัมภาษณ์";
include '../../layouts/header.php';

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">เพิ่มการสัมภาษณ์</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/Myparttime/dashboard.php">หน้าหลัก</a></li>
                        <li class="breadcrumb-item"><a href="index.php">การสัมภาษณ์</a></li>
                        <li class="breadcrumb-item active">เพิ่มการสัมภาษณ์</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">ข้อมูลการสัมภาษณ์</h3>
                        </div>
                        
                        <form action="add.php" method="post">
                            <div class="card-body">
                                <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="application_id">เลือกใบสมัคร</label>
                                    <select name="application_id" id="application_id" class="form-control" required>
                                        <option value="">-- เลือกใบสมัคร --</option>
                                        <?php
                                        // ดึงรายการใบสมัครที่มีสถานะ 'pending', 'reviewing' หรือ 'interview'
                                        $sql = "SELECT a.application_id, a.status, s.first_name, s.last_name, j.job_title 
                                                FROM applications a 
                                                JOIN students s ON a.student_id = s.student_id 
                                                JOIN jobs_posts j ON a.post_id = j.post_id 
                                                WHERE j.company_id = ? 
                                                AND a.status IN ('pending', 'reviewing', 'interview') 
                                                ORDER BY a.apply_date DESC";
                                        $stmt = $db->getConnection()->prepare($sql);
                                        $stmt->execute([$company_id]);
                                        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($applications as $app) {
                                            echo '<option value="' . $app['application_id'] . '">' . 
                                                  'ID: ' . $app['application_id'] . ' - ' . 
                                                  htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) . ' - ' . 
                                                  htmlspecialchars($app['job_title']) . ' (' . 
                                                  ($app['status'] == 'pending' ? 'รอตรวจสอบ' : 
                                                   ($app['status'] == 'reviewing' ? 'กำลังตรวจสอบ' : 'นัดสัมภาษณ์แล้ว')) . ')' . 
                                                  '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="interview_date">วันที่สัมภาษณ์</label>
                                    <input type="date" name="interview_date" id="interview_date" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="interview_time">เวลาสัมภาษณ์</label>
                                    <input type="time" name="interview_time" id="interview_time" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="interview_type">ประเภทการสัมภาษณ์</label>
                                    <select name="interview_type" id="interview_type" class="form-control" required>
                                        <option value="in-person">สัมภาษณ์แบบพบหน้า</option>
                                        <option value="phone">สัมภาษณ์ทางโทรศัพท์</option>
                                        <option value="video">สัมภาษณ์ทางวิดีโอ</option>
                                        <option value="group">สัมภาษณ์แบบกลุ่ม</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="interview_location">สถานที่/ช่องทางการสัมภาษณ์</label>
                                    <input type="text" name="interview_location" id="interview_location" class="form-control" placeholder="เช่น ที่อยู่บริษัท, ลิงก์ Zoom, หมายเลขโทรศัพท์">
                                </div>

                                <div class="form-group">
                                    <label for="interview_notes">บันทึกเพิ่มเติม</label>
                                    <textarea name="interview_notes" id="interview_notes" class="form-control" rows="3" placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับการสัมภาษณ์"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="status">สถานะ</label>
                                    <select name="status" id="status" class="form-control" required>
                                        <option value="scheduled">กำหนดการแล้ว</option>
                                        <option value="pending">รอดำเนินการ</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                                <a href="index.php" class="btn btn-default">ยกเลิก</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>