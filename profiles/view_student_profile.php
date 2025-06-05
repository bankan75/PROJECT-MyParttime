<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

// Get student ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลนักศึกษา';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

$student_id = $_GET['id'];

// Get student data
$student = getStudentProfile($database, $student_id);
$accepted_jobs = [];
$query = "SELECT a.*, j.job_title, j.location, j.work_days, j.work_hours, c.company_name, c.logo_path 
          FROM applications a 
          JOIN jobs_posts j ON a.post_id = j.post_id 
          JOIN companies c ON j.company_id = c.company_id 
          WHERE a.student_id = ? AND a.status = 'accepted'";
$stmt = $database->prepare($query);
$stmt->execute([$student_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    $accepted_jobs[] = $row;
}
if (!$student) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลนักศึกษา';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}
// ดึงข้อมูลการสมัครงานที่มีสถานะ "accepted" (ล่าสุดเท่านั้น)
$accepted_jobs = [];
$query = "SELECT a.*, j.job_title, j.location, j.work_days, j.work_hours, c.company_name, c.logo_path 
          FROM applications a 
          JOIN jobs_posts j ON a.post_id = j.post_id 
          JOIN companies c ON j.company_id = c.company_id 
          WHERE a.student_id = ? AND a.status = 'accepted'
          ORDER BY a.updated_at DESC LIMIT 1";
$stmt = $database->prepare($query);
$stmt->execute([$student_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($result as $row) {
    $accepted_jobs[] = $row;
}

$profile_image_url = getProfileImageUrl($student['profile_image'], 'student');
?>
<div class="btn-group float-end" role="group">
    <a href="/Myparttime/dashboard.php" class="btn btn-primary">
        <i class="fas fa-home me-1"></i> หน้าหลัก
    </a>
</div>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
<div class="container px-4 py-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo $profile_image_url; ?>" alt="รูปโปรไฟล์" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                    <h5 class="my-3"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></h5>
                    <p class="text-muted mb-1"><?php echo $student['student_code']; ?></p>
                    <p class="text-muted mb-4"><?php echo $student['faculty_name'] . ' - ' . $student['major_name']; ?></p>
                     <!-- แสดงสถานะการทำงาน -->
                     <?php if (!empty($accepted_jobs)): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-briefcase me-2"></i>สถานะ: <strong>ได้งานแล้ว</strong>

                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-search me-2"></i>สถานะ: <strong>ว่างงาน/กำลังหางาน</strong>
                        </div>
                    <?php endif; ?>
                    <!-- ปุ่มกลับไปหน้าก่อนหน้า -->
                    <a href="javascript:history.back()" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php if (!empty($accepted_jobs)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>งานที่ได้รับการคัดเลือก</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($accepted_jobs as $job): ?>
                            <div class="d-flex align-items-center mb-3">
                                <?php
                                $company_logo = !empty($job['logo_path']) ? ROOT_URL . $job['logo_path'] : ROOT_URL . '/assets/images/company-default.png';
                                ?>
                                <img src="<?php echo $company_logo; ?>" alt="<?php echo $job['company_name']; ?>" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-1"><?php echo $job['job_title']; ?></h5>
                                    <p class="mb-1 text-muted"><?php echo $job['company_name']; ?></p>
                                    <p class="mb-0 small">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo $job['location']; ?> |
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo $job['work_days']; ?> |
                                        <i class="fas fa-clock me-1"></i><?php echo $job['work_hours']; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ชื่อ-นามสกุล</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">อีเมล</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['email']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เบอร์โทรศัพท์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['phone']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">วันเกิด</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo formatDate($student['birth_date']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลการศึกษา</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">รหัสนักศึกษา</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['student_code']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">คณะ</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['faculty_name']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">สาขาวิชา</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['major_name']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ชั้นปี</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['year']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เกรดเฉลี่ย</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['gpa']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ทักษะและประสบการณ์</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ทักษะ</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['skill'] ?: 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ประสบการณ์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['experience'] ?: 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>