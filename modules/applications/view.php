<?php
// Include header
include('../../layouts/header.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!$auth->isLoggedIn()) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบเพื่อดูข้อมูล";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง id มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลการสมัครงาน";
    header("Location: " . ROOT_URL . "/modules/applications/index.php");
    exit;
}

$application_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$is_student = $auth->isStudent();
$is_company = $auth->isCompany();
$is_admin = $auth->isAdmin();

// สร้าง SQL ตามบทบาทผู้ใช้
if ($is_student) {
    // นักศึกษาดูได้เฉพาะการสมัครงานของตัวเอง
    $sql = "SELECT a.*, j.job_title, j.job_description, j.min_salary, j.max_salary, j.work_days, j.work_hours, j.location,
                   j.post_id, j.post_date, j.expire_date, c.company_id, c.company_name, c.logo_path, c.website, 
                   s.first_name, s.last_name, s.phone, s.email, s.student_code, s.faculty_name, s.major_name, s.year
            FROM applications a
            JOIN jobs_posts j ON a.post_id = j.post_id
            JOIN companies c ON j.company_id = c.company_id
            JOIN students s ON a.student_id = s.student_id
            WHERE a.application_id = ? AND a.student_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$application_id, $user_id]);
} elseif ($is_company) {
    // บริษัทดูได้เฉพาะการสมัครงานในประกาศของตัวเอง
    $sql = "SELECT a.*, j.job_title, j.job_description, j.min_salary, j.max_salary, j.work_days, j.work_hours, j.location,
    j.post_id, j.post_date, j.expire_date, c.company_id, c.company_name, c.logo_path, c.website,
    s.first_name, s.last_name, s.phone, s.email, s.student_code, s.faculty_name, s.major_name, s.year
FROM applications a
JOIN jobs_posts j ON a.post_id = j.post_id
JOIN companies c ON j.company_id = c.company_id
JOIN students s ON a.student_id = s.student_id
WHERE a.application_id = ? AND j.company_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$application_id, $user_id]);
} elseif ($is_admin) {
    // แอดมินดูได้ทั้งหมด
    $sql = "SELECT a.*, j.job_title, j.job_description, j.min_salary, j.max_salary, j.work_days, j.work_hours, j.location,
    j.post_id, j.post_date, j.expire_date, c.company_id, c.company_name, c.logo_path, c.website,
    s.first_name, s.last_name, s.phone, s.email, s.student_code, s.faculty_name, s.major_name, s.year
FROM applications a
JOIN jobs_posts j ON a.post_id = j.post_id
JOIN companies c ON j.company_id = c.company_id
JOIN students s ON a.student_id = s.student_id
WHERE a.application_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$application_id]);
} else {
    // ผู้ใช้อื่นๆ ไม่มีสิทธิ์ดู
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
    header("Location: " . ROOT_URL . "/dashboard.php");
    exit;
}

$application = $stmt->fetch(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีข้อมูลการสมัครงานหรือไม่
if (!$application) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลการสมัครงาน หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้";

    if ($is_student) {
        header("Location: " . ROOT_URL . "/modules/applications/index.php");
    } elseif ($is_company) {
        header("Location: " . ROOT_URL . "/modules/jobs/applications.php?id=" . $application['post_id']);
    } else {
        header("Location: " . ROOT_URL . "/dashboard.php");
    }
    exit;
}

// ดึงประวัติการเปลี่ยนสถานะ (ถ้ามี)
$status_history_sql = "SELECT * FROM application_status_history 
                      WHERE application_id = ? 
                      ORDER BY created_at DESC";
$status_history_stmt = $db->prepare($status_history_sql);
$status_history_stmt->execute([$application_id]);
$status_history = $status_history_stmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันแปลงสถานะเป็นภาษาไทย
function getStatusThai($status)
{
    switch ($status) {
        case 'pending':
            return 'รอการพิจารณา';
        case 'request_documents':
            return 'ขอเอกสารเพิ่ม';
        case 'reviewing':
            return 'กำลังพิจารณา';
        case 'interview':
            return 'นัดสัมภาษณ์';
        case 'accepted':
            return 'ผ่านการคัดเลือก';
        case 'rejected':
            return 'ไม่ผ่านการคัดเลือก';
        case 'cancelled':
            return 'ยกเลิก';
        case 'available':
            return 'จบการทำงาน';
        default:
            return $status;
    }
}

// ฟังก์ชันแปลงสถานะเป็นสี
function getStatusColor($status)
{
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'request_documents':
            return 'warning';
        case 'reviewing':
            return 'info';
        case 'interview':
            return 'primary';
        case 'accepted':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'cancelled':
            return 'secondary';
        case 'available':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<style>
    .profile-section {
        position: sticky;
        top: 20px;
    }
    .card-header h5 {
        font-size: 1.2rem;
    }
    .card-body .mb-3 {
        font-size: 0.95rem;
    }
    .card img {
        max-width: 100%;
        height: auto;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">รายละเอียดการสมัครงาน</h1>
        <div>
            <?php if ($is_student): ?>
            <a href="<?php echo ROOT_URL; ?>/modules/applications/index.php" class="btn btn-primary me-2">
                <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการสมัครงาน
            </a>
            <?php elseif ($is_company): ?>
            <a href="<?php echo ROOT_URL; ?>/modules/jobs/applications.php?id=<?php echo $application['post_id']; ?>"
                class="btn btn-primary me-2">
                <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการผู้สมัคร
            </a>
            <?php elseif ($is_admin): ?>
            <a href="<?php echo ROOT_URL; ?>/modules/admin/applications.php" class="btn btn-primary me-2">
                <i class="fas fa-arrow-left"></i> กลับไปหน้าจัดการการสมัครงาน
            </a>
            <?php endif; ?>

            <?php if ($is_student && $application['status'] === 'pending'): ?>
            <a href="<?php echo ROOT_URL; ?>/modules/applications/edit.php?id=<?php echo $application_id; ?>"
                class="btn btn-warning">
                <i class="fas fa-edit"></i> แก้ไขใบสมัคร
            </a>
            <?php endif; ?>
            
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- ข้อมูลนักศึกษาและบริษัท (ด้านขวา) -->
        <div class="col-md-4 order-md-2">
            <div class="profile-section">
                <!-- ข้อมูลนักศึกษา -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>ข้อมูลผู้สมัคร</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php
                            // ดึงรูปโปรไฟล์ของนักศึกษา
                            $profile_sql = "SELECT profile_image FROM students WHERE student_id = ?";
                            $profile_stmt = $db->prepare($profile_sql);
                            $profile_stmt->execute([$application['student_id']]);
                            $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                            $profile_image = !empty($profile['profile_image']) ? ROOT_URL . '/' . $profile['profile_image'] : ROOT_URL . '/assets/images/default-profile.png';
                            ?>
                            <img src="<?php echo $profile_image; ?>" class="rounded-circle img-thumbnail"
                                style="width: 150px; height: 150px; object-fit: cover;">
                        </div>

                        <div class="mb-3">
                            <strong>ชื่อ-นามสกุล:</strong>
                            <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>รหัสนักศึกษา:</strong> <?php echo htmlspecialchars($application['student_code']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>คณะ:</strong> <?php echo htmlspecialchars($application['faculty_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>สาขา:</strong> <?php echo htmlspecialchars($application['major_name']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>ชั้นปี:</strong> <?php echo htmlspecialchars($application['year']); ?>
                        </div>

                        <?php if ($is_company || $is_admin): ?>
                        <div class="mb-

3">
                            <strong>อีเมล:</strong> <?php echo htmlspecialchars($application['email']); ?>
                        </div>
                        <div class="mb-3">
                            <strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars($application['phone']); ?>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo ROOT_URL; ?>/profiles/view_student_profile.php?id=<?php echo $application['student_id']; ?>"
                                class="btn btn-primary w-100">
                                <i class="fas fa-user me-1"></i> ดูโปรไฟล์นักศึกษา
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ข้อมูลบริษัท -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-building me-2"></i>ข้อมูลบริษัท</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php
                            $company_logo = !empty($application['logo_path']) ? ROOT_URL . '/' . $application['logo_path'] : ROOT_URL . '/assets/images/default-company.png';
                            ?>
                            <img src="<?php echo $company_logo; ?>" class="img-thumbnail"
                                style="max-height: 100px; max-width: 200px;">
                        </div>

                        <div class="mb-3">
                            <strong>ชื่อบริษัท:</strong> <?php echo htmlspecialchars($application['company_name']); ?>
                        </div>
                        <?php if (!empty($application['website'])): ?>
                        <div class="mb-3">
                            <strong>เว็บไซต์:</strong>
                            <a href="<?php echo htmlspecialchars($application['website']); ?>" target="_blank">
                                <?php echo htmlspecialchars($application['website']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="mt-4">
                            <a href="<?php echo ROOT_URL; ?>/profiles/view_company_profile.php?id=<?php echo $application['company_id']; ?>"
                                class="btn btn-primary w-100">
                                <i class="fas fa-building me-1"></i> ดูข้อมูลบริษัท
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ข้อมูลการสมัครงาน (ด้านซ้าย) -->
        <div class="col-md-8 order-md-1">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>ข้อมูลการสมัครงาน</h5>
                    <span class="badge bg-<?php echo getStatusColor($application['status']); ?> fs-6">
                        <?php echo getStatusThai($application['status']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">ข้อมูลงาน</h5>
                            <div class="mb-3">
                                <strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($application['job_title']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>บริษัท:</strong> <?php echo htmlspecialchars($application['company_name']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>เงินเดือน:</strong>
                                <?php echo number_format($application['min_salary'], 2) . ' - ' . number_format($application['max_salary'], 2); ?>
                                บาท
                            </div>
                            <div class="mb-3">
                                <strong>วันทำงาน:</strong> <?php echo htmlspecialchars($application['work_days']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>เวลาทำงาน:</strong> <?php echo htmlspecialchars($application['work_hours']); ?>
                            </div>
                            <div class="mb-3">
                                <strong>สถานที่ทำงาน:</strong> <?php echo htmlspecialchars($application['location']); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">ข้อมูลการสมัคร</h5>
                            <div class="mb-3">
                                <strong>วันที่สมัคร:</strong>
                                <?php echo date('d/m/Y H:i', strtotime($application['apply_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>เงินเดือนที่คาดหวัง:</strong>
                                <?php echo number_format($application['expected_salary'], 2); ?> บาท
                            </div>
                            <div class="mb-3">
                                <strong>วันที่พร้อมเริ่มงาน:</strong>
                                <?php echo date('d/m/Y', strtotime($application['available_start_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>วันและเวลาที่สะดวกทำงาน:</strong><br>
                                <?php echo nl2br(htmlspecialchars($application['available_hours'])); ?>
                            </div>
                            <?php if (!empty($application['resume_path'])): ?>
                            <div class="mb-3">
                                <strong>เรซูเม่:</strong>
                                <a href="<?php echo ROOT_URL . '/' . $application['resume_path']; ?>" target="_blank"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-file-download"></i> ดาวน์โหลด
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($application['message'])): ?>
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">ข้อความถึงผู้ว่าจ้าง</h5>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($application['message'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_company && ($application['status'] === 'pending' || $application['status'] === 'reviewing'|| $application['status'] === 'request_documents')): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h5>อัปเดตสถานะการสมัคร</h5>
                        <form method="POST" action="<?php echo ROOT_URL; ?>/modules/applications/update_status.php"
                            id="statusForm">
                            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                            <input type="hidden" name="post_id" value="<?php echo $application['post_id']; ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <select name="status" class="form-select" required
                                        onchange="toggleDocumentRequest(this.value)">
                                        <option value="">เลือกสถานะ</option>
                                        <option value="reviewing"
                                            <?php if ($application['status'] === 'reviewing') echo 'selected'; ?>>
                                            กำลังพิจารณา</option>
                                        <option value="interview">นัดสัมภาษณ์</option>
                                        <option value="accepted">ผ่านการคัดเลือก</option>
                                        <option value="rejected">ไม่ผ่านการคัดเลือก</option>

                                        <option value="request_documents">ขอเอกสารเพิ่ม</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" name="comment" class="form-control"
                                        placeholder="ความคิดเห็นเพิ่มเติม (ถ้ามี)">
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                                        <i class="fas fa-save me-1"></i> บันทึกการเปลี่ยนแปลง
                                    </button>
                                </div>
                            </div>
                        </form>
                        <!-- Modal for Document Request -->
                        <div class="modal fade" id="documentRequestModal" tabindex="-1"
                            aria-labelledby="documentRequestModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="documentRequestModalLabel">ขอเอกสารเพิ่ม</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="documentForm"
                                            action="<?php echo ROOT_URL; ?>/modules/applications/update_status.php"
                                            method="POST">
                                            <input type="hidden" name="application_id"
                                                value="<?php echo $application_id; ?>">
                                            <input type="hidden" name="post_id"
                                                value="<?php echo $application['post_id']; ?>">
                                            <input type="hidden" name="status" value="request_documents">
                                            <div class="mb-3">
                                                <label for="required_documents" class="form-label">เอกสารที่ต้องการ
                                                    (แยกด้วยเครื่องหมายคอมมา):</label>
                                                <textarea name="required_documents" id="required_documents"
                                                    class="form-control" rows="3"
                                                    placeholder="เช่น ใบรับรองแพทย์, ใบระเบียนแสดงผลการเรียน"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">บันทึก</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($application['status'] === 'interview'): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>ข้อมูลการนัดสัมภาษณ์</h5>
                </div>
                <div class="card-body">
                    <?php
                        // ดึงข้อมูลการนัดสัมภาษณ์
                        $interview_sql = "SELECT * FROM interviews WHERE application_id = ? ORDER BY interview_id DESC LIMIT 1";
                        $interview_stmt = $db->prepare($interview_sql);
                        $interview_stmt->execute([$application_id]);
                        $interview = $interview_stmt->fetch(PDO::FETCH_ASSOC);

                        if ($interview):
                        ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>วันที่สัมภาษณ์:</strong>
                                <?php echo date('d/m/Y', strtotime($interview['interview_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>เวลา:</strong>
                                <?php echo date('H:i', strtotime($interview['interview_time'])); ?> น.
                            </div>
                            <div class="mb-3">
                                <strong>รูปแบบการสัมภาษณ์:</strong>
                                <?php echo $interview['interview_type'] === 'online' ? 'ออนไลน์' : 'ที่บริษัท'; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if ($interview['interview_type'] === 'online'): ?>
                            <div class="mb-3">
                                <strong>ลิงก์การสัมภาษณ์:</strong>
                                <a href="<?php echo htmlspecialchars($interview['interview_link']); ?>"
                                    target="_blank">
                                    <?php echo htmlspecialchars($interview['interview_link']); ?>
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <strong>สถานที่สัมภาษณ์:</strong>
                                <?php echo htmlspecialchars($interview['interview_location']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($interview['interview_notes'])): ?>
                            <div class="mb-3">
                                <strong>หมายเหตุ:</strong>
                                <?php echo nl2br(htmlspecialchars($interview['interview_notes'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> ยังไม่มีข้อมูลการนัดสัมภาษณ์
                    </div>
                    <?php if ($is_company): ?>
                    <a href="<?php echo ROOT_URL; ?>/modules/interviews/add.php?application_id=<?php echo $application_id; ?>"
                        class="btn btn-primary">
                        <i class="fas fa-calendar-plus me-1"></i> สร้างนัดสัมภาษณ์
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($status_history)): ?>
            <div class="card mb-4">
            <?php if ($is_student && $application['status'] === 'request_documents' && !empty($application['required_documents'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>เอกสารที่ต้องส่งเพิ่ม</h5>
                </div>
                <div class="card-body">
                    <p><strong>บริษัทร้องขอเอกสารเพิ่มเติม:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($application['required_documents'])); ?></p>
                    <form method="POST" action="<?php echo ROOT_URL; ?>/modules/applications/submit_documents.php"
                        enctype="multipart/form-data">
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        <div class="mb-3">
                            <label for="documents" class="form-label">แนบเอกสาร (อัปโหลดได้หลายไฟล์):</label>
                            <input type="file" name="documents[]" class="form-control" multiple>
                        </div>
                        <button type="submit" class="btn btn-primary">ส่งเอกสาร</button>
                    </form>
                    <?php if (!empty($application['submitted_documents'])): ?>
                    <div class="mt-3">
                        <h6>เอกสารที่ส่งแล้ว:</h6>
                        <?php
                                $submitted_docs = explode(',', $application['submitted_documents']);
                                foreach ($submitted_docs as $doc) {
                                    if (!empty(trim($doc))) {
                                        echo "<a href='" . ROOT_URL . "/" . trim($doc) . "' target='_blank' class='btn btn-sm btn-success mb-1'><i class='fas fa-file'></i> " . basename(trim($doc)) . "</a><br>";
                                    }
                                }
                                ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>ประวัติการเปลี่ยนสถานะ</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>สถานะเดิม</th>
                                    <th>สถานะใหม่</th>
                                    <th>ผู้ทำรายการ</th>
                                    <th>ความคิดเห็น</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($status_history as $history): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo getStatusColor($history['old_status']); ?>">
                                            <?php echo getStatusThai($history['old_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-<?php echo getStatusColor($history['new_status']); ?>">
                                            <?php echo getStatusThai($history['new_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($history['changed_by_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($history['comment'] ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_company && $application['status'] === 'request_documents'): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-file-download me-2"></i>เอกสารที่นักศึกษาส่งมา</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($application['required_documents'])): ?>
                    <p><strong>เอกสารที่ร้องขอ:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($application['required_documents'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($application['submitted_documents'])): ?>
                    <h6>เอกสารที่นักศึกษาอัปโหลด:</h6>
                    <?php
                    $submitted_docs = explode(',', $application['submitted_documents']);
                    if (count($submitted_docs) > 0 && !empty(trim($submitted_docs[0]))): ?>
                    <ul class="list-group">
                        <?php foreach ($submitted_docs as $doc): 
                        $doc = trim($doc);
                        if (!empty($doc)): ?>
                        <li class="list-group-item">
                            <a href="<?php echo ROOT_URL . '/' . $doc; ?>" target="_blank"
                                class="btn btn-sm btn-success">
                                <i class="fas fa-download me-1"></i> ดาวน์โหลด: <?php echo basename($doc); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-muted">ยังไม่มีเอกสารที่นักศึกษาอัปโหลด</p>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-muted">ยังไม่มีเอกสารที่นักศึกษาอัปโหลด</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_student && ($application['status'] === 'pending' || $application['status'] === 'reviewing')): ?>
            <!-- สำหรับนักศึกษา - ยกเลิกการสมัคร -->
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>ยกเลิกการสมัคร</h5>
                </div>
                <div class="card-body">
                    <p>หากคุณต้องการยกเลิกการสมัครงานนี้ กรุณากดปุ่มด้านล่าง</p>
                    <form method="POST" action="<?php echo ROOT_URL; ?>/modules/applications/cancel.php"
                        onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการสมัครงานนี้?');">
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-times-circle me-1"></i> ยกเลิกการสมัคร
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleDocumentRequest(status) {
    if (status === 'request_documents') {
        $('#documentRequestModal').modal('show');
        $('#statusForm').find('button[type="submit"]').prop('disabled', true);
    } else {
        $('#documentRequestModal').modal('hide');
        $('#statusForm').find('button[type="submit"]').prop('disabled', false);
    }
}
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>