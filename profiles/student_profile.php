<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// ตรวจสอบว่าเป็นนักศึกษาที่ล็อกอินแล้ว
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$student = getStudentProfile($database, $student_id);

if (!$student) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลนักศึกษา';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

// จัดการการอัปโหลดรูปโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadProfileImage($_FILES['profile_image'], 'student', $student_id);

        if ($upload_result['success']) {
            // ลบรูปเก่าถ้ามี
            if (!empty($student['profile_image'])) {
                deleteOldProfileImage($student['profile_image']);
            }

            // อัปเดตพาธรูปใหม่ในฐานข้อมูล
            if (updateStudentProfileImage($database, $student_id, $upload_result['file_path'])) {
                $_SESSION['success_message'] = 'อัปโหลดรูปโปรไฟล์สำเร็จ';
                // อัปเดตข้อมูลที่แสดง
                $student['profile_image'] = $upload_result['file_path'];
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลรูปโปรไฟล์';
            }
        } else {
            $_SESSION['error_message'] = $upload_result['message'];
        }

        // Redirect เพื่อรีเฟรชหน้า
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
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
    <a href="/Myparttime/index.php" class="btn btn-primary">
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

                            <!-- เพิ่มปุ่มเรียก modal -->
                            <div class="mt-2">
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#resignationModal">
                                    <i class="fas fa-user-minus me-1"></i> แจ้งลาออก/เปลี่ยนสถานะ
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-search me-2"></i>สถานะ: <strong>ว่างงาน/กำลังหางาน</strong>
                        </div>
                    <?php endif; ?>

                    <!-- ปุ่มเปลี่ยนรูปโปรไฟล์ -->
                    <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#profileImageModal">
                        <i class="fas fa-camera me-2"></i>เปลี่ยนรูปโปรไฟล์
                    </button>

                    <!-- ปุ่มแก้ไขโปรไฟล์ -->
                    <a href="edit_student_profile.php" class="btn btn-outline-primary mb-2">
                        <i class="fas fa-edit me-2"></i>แก้ไขโปรไฟล์
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- แสดงข้อมูลงานที่ได้รับการยอมรับ -->
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
                            <p class="mb-0">มหาวิทยาลัย</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['university_name'] ?? $student['university'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">คณะ</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['faculty_name'] ?? $student['faculty'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">สาขาวิชา</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['major_name'] ?? $student['major'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ระดับการศึกษา</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['education_level'] ?? $student['education'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ชั้นปี</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['year'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เกรดเฉลี่ย</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['gpa'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- เพิ่มส่วนที่อยู่ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ที่อยู่</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ที่อยู่</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['address'] ?: 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">จังหวัด</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['province'] ?: 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">รหัสไปรษณีย์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['postal_code'] ?: 'ไม่ระบุ'; ?></p>
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
                            <p class="text-muted mb-0"><?php echo $student['skill'] ?? $student['skill'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เกี่ยวกับฉัน</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $student['experience'] ?? $student['experience'] ?? 'ไม่ระบุ'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal อัปโหลดรูปโปรไฟล์ -->
<div class="modal fade" id="profileImageModal" tabindex="-1" aria-labelledby="profileImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileImageModalLabel">อัปโหลดรูปโปรไฟล์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">เลือกรูปภาพ (สูงสุด 5MB)</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" required>
                        <div class="form-text">รองรับไฟล์ภาพประเภท JPEG, PNG, GIF</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">อัปโหลด</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal ยื่นคำร้องขอลาออก -->
<?php if (!empty($accepted_jobs)): ?>
    <div class="modal fade" id="resignationModal" tabindex="-1" aria-labelledby="resignationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="resignationModalLabel">ยื่นคำร้องขอลาออก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../modules/students/process_resignation.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="application_id" value="<?php echo $accepted_jobs[0]['application_id']; ?>">
                        <input type="hidden" name="job_title" value="<?php echo htmlspecialchars($accepted_jobs[0]['job_title']); ?>">
                        <input type="hidden" name="company_name" value="<?php echo htmlspecialchars($accepted_jobs[0]['company_name']); ?>">

                        <div class="alert alert-info">
                            <p><strong>ข้อมูลงานปัจจุบัน:</strong></p>
                            <p>ตำแหน่ง: <?php echo htmlspecialchars($accepted_jobs[0]['job_title']); ?><br>
                                บริษัท: <?php echo htmlspecialchars($accepted_jobs[0]['company_name']); ?></p>
                        </div>

                        <div class="mb-3">
                            <label for="resignation_reason" class="form-label">เหตุผลในการลาออก <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="resignation_reason" name="resignation_reason" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="resignation_date" class="form-label">วันที่ต้องการลาออก <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="resignation_date" name="resignation_date" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>หมายเหตุ: การลาออกต้องได้รับการอนุมัติจากบริษัทก่อน คุณจะสามารถสมัครงานใหม่ได้หลังจากได้รับการอนุมัติแล้วเท่านั้น</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning">ยื่นคำร้องขอลาออก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../layouts/footer.php'; ?>