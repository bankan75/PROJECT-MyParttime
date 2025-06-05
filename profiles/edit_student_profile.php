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

// จัดการการส่งฟอร์มแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่าเป็นการแก้ไขข้อมูลทั่วไป
    if (isset($_POST['update_student'])) {
        $title = $_POST['title'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $address = $_POST['address'] ?? '';
        $province = $_POST['province'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $student_code = $_POST['student_code'] ?? '';
        $university = $_POST['university_name'] ?? '';
        $faculty = $_POST['faculty_name'] ?? '';
        $major = $_POST['major_name'] ?? '';
        $education_level = $_POST['education_level'] ?? '';
        $year = $_POST['year'] ?? '';
        $gpa = $_POST['gpa'] ?? '';
        $skill = $_POST['skill'] ?? '';
        $experience = $_POST['experience'] ?? '';
        
        // ตรวจสอบความถูกต้องของข้อมูล
        if (empty($title) || empty($first_name) || empty($last_name) || 
            empty($birth_date) || empty($address) || empty($province) || 
            empty($postal_code) || empty($student_code) || empty($university) ||
            empty($faculty) || empty($major) || empty($education_level) || empty($year)) {
            $_SESSION['error_message'] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        } else {
            // อัปเดตข้อมูลในฐานข้อมูล

// แก้ไขในส่วนการอัปเดตข้อมูล
$sql = "UPDATE students SET 
    title = :title, 
    first_name = :first_name, 
    last_name = :last_name,
    birth_date = :birth_date,
    address = :address,
    province = :province,
    postal_code = :postal_code,
    student_code = :student_code,
    university_name = :university_name,
    faculty_name = :faculty_name,
    major_name = :major_name,
    education_level = :education_level,
    year = :year,
    gpa = :gpa,
    skill = :skill,
    experience = :experience,
    updated_at = NOW()
    WHERE student_id = :student_id";  

$stmt = $database->prepare($sql);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':first_name', $first_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':birth_date', $birth_date);
$stmt->bindParam(':address', $address);
$stmt->bindParam(':province', $province);
$stmt->bindParam(':postal_code', $postal_code);
$stmt->bindParam(':student_code', $student_code);
$stmt->bindParam(':university_name', $university);
$stmt->bindParam(':faculty_name', $faculty);
$stmt->bindParam(':major_name', $major);
$stmt->bindParam(':education_level', $education_level);
$stmt->bindParam(':year', $year);
$stmt->bindParam(':gpa', $gpa);
$stmt->bindParam(':skill', $skill);
$stmt->bindParam(':experience', $experience);
$stmt->bindParam(':student_id', $student_id);

// ทดลองดูค่า error ของ SQL
if ($stmt->execute()) {
    $_SESSION['success_message'] = 'อัปเดตข้อมูลนักศึกษาสำเร็จ';
    // เปลี่ยนเป็นการ redirect ด้วย JavaScript
    echo "<script>window.location = 'student_profile.php';</script>";
    exit;
} else {
    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . print_r($stmt->errorInfo(), true);
}
        }
    }

    // ตรวจสอบว่าเป็นการเริ่มกระบวนการเปลี่ยนอีเมล
    if (isset($_POST['request_email_change'])) {
        $new_email = $_POST['new_email'] ?? '';
        
        if (empty($new_email)) {
            $_SESSION['error_message'] = 'กรุณากรอกอีเมลใหม่';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error_message'] = 'รูปแบบอีเมลไม่ถูกต้อง';
        } else {
            // ตรวจสอบว่าอีเมลใหม่ซ้ำกับนักศึกษาอื่นหรือไม่
            $check_email_sql = "SELECT COUNT(*) FROM students WHERE email = ? AND student_id != ?";
            $check_stmt = $database->prepare($check_email_sql);
            $check_stmt->execute([$new_email, $student_id]);
            $email_exists = $check_stmt->fetchColumn();

            if ($email_exists > 0) {
                $_SESSION['error_message'] = 'อีเมลนี้มีการใช้งานโดยนักศึกษาอื่นแล้ว กรุณาใช้อีเมลอื่น';
                header('Location: edit_student_profile.php');
                exit;
            }
            
            // สร้าง OTP และบันทึกลงฐานข้อมูล
            require_once(INCLUDES_PATH . '/otp_functions.php');
            $otp = createOTP($database, 'student', $student_id, 'email', $new_email);
            
            if ($otp) {
                // แสดง OTP บนหน้าเว็บ (ในความเป็นจริงควรส่งอีเมล)
                if (sendEmailOTP($new_email, $otp)) {
                    $_SESSION['success_message'] = 'รหัสยืนยันของคุณคือ: ' . $otp . ' กรุณานำไปกรอกในหน้ายืนยัน';
                    $_SESSION['verify_new_email'] = $new_email;
                    header('Location: verify_email.php');
                    exit;
                } else {
                    $_SESSION['error_message'] = 'ไม่สามารถส่งอีเมลยืนยันได้ กรุณาลองใหม่อีกครั้ง';
                }
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการสร้างรหัสยืนยัน';
            }
        }
    }
    
    // ตรวจสอบว่าเป็นการเริ่มกระบวนการเปลี่ยนเบอร์โทร
    if (isset($_POST['request_phone_change'])) {
        $new_phone = $_POST['new_phone'] ?? '';
        
        if (empty($new_phone)) {
            $_SESSION['error_message'] = 'กรุณากรอกเบอร์โทรศัพท์ใหม่';
        } elseif (!preg_match('/^[0-9]{10}$/', $new_phone)) {
            $_SESSION['error_message'] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (กรุณากรอกเฉพาะตัวเลข 10 หลัก)';
        } else {
            // ตรวจสอบว่าเบอร์โทรใหม่ซ้ำกับนักศึกษาอื่นหรือไม่
            $check_phone_sql = "SELECT COUNT(*) FROM students WHERE phone = ? AND student_id != ?";
            $check_stmt = $database->prepare($check_phone_sql);
            $check_stmt->execute([$new_phone, $student_id]);
            $phone_exists = $check_stmt->fetchColumn();

            if ($phone_exists > 0) {
                $_SESSION['error_message'] = 'เบอร์โทรศัพท์นี้มีการใช้งานโดยนักศึกษาอื่นแล้ว กรุณาใช้เบอร์อื่น';
                header('Location: edit_student_profile.php');
                exit;
            }
            
            // สร้าง OTP และบันทึกลงฐานข้อมูล
            require_once(INCLUDES_PATH . '/otp_functions.php');
            $otp = createOTP($database, 'student', $student_id, 'phone', $new_phone);
            
            if ($otp) {
                // แสดง OTP บนหน้าเว็บ (ในความเป็นจริงควรส่ง SMS)
                $_SESSION['success_message'] = 'รหัสยืนยันของคุณคือ: ' . $otp . ' กรุณานำไปกรอกในหน้ายืนยัน';
                $_SESSION['verify_new_phone'] = $new_phone;
                header('Location: verify_phone.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการสร้างรหัสยืนยัน';
            }
        }
    }
}
?>
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
        <div class="col-12 mb-4">
            <h2>แก้ไขข้อมูลนักศึกษา</h2>
            <p>แก้ไขข้อมูลส่วนตัวของคุณ หมายเหตุ: การเปลี่ยนอีเมลหรือเบอร์โทรศัพท์จำเป็นต้องยืนยันด้วยรหัส OTP</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลส่วนตัว</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="title" class="form-label">คำนำหน้า *</label>
                                <select class="form-select" id="title" name="title" required>
                                    <option value="">เลือกคำนำหน้า</option>
                                    <option value="นาย" <?php echo ($student['title'] === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                    <option value="นาง" <?php echo ($student['title'] === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                    <option value="นางสาว" <?php echo ($student['title'] === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">ชื่อ *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                    value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label for="last_name" class="form-label">นามสกุล *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                    value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="birth_date" class="form-label">วันเกิด *</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                value="<?php echo htmlspecialchars($student['birth_date']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">ที่อยู่ *</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">จังหวัด *</label>
                                <select class="form-select" id="province" name="province" required>
                                    <option value="">เลือกจังหวัด</option>
                                    <option value="กรุงเทพมหานคร" <?php echo ($student['province'] === 'กรุงเทพมหานคร') ? 'selected' : ''; ?>>
                                        กรุงเทพมหานคร</option>
                                    <option value="เชียงใหม่" <?php echo ($student['province'] === 'เชียงใหม่') ? 'selected' : ''; ?>>
                                        เชียงใหม่</option>
                                    <option value="นนทบุรี" <?php echo ($student['province'] === 'นนทบุรี') ? 'selected' : ''; ?>>นนทบุรี</option>
                                    <option value="ปทุมธานี" <?php echo ($student['province'] === 'ปทุมธานี') ? 'selected' : ''; ?>>ปทุมธานี</option>
                                    <option value="สมุทรปราการ" <?php echo ($student['province'] === 'สมุทรปราการ') ? 'selected' : ''; ?>>
                                        สมุทรปราการ</option>
                                    <!-- เพิ่มจังหวัดอื่นๆ ตามต้องการ -->
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">รหัสไปรษณีย์ *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                    value="<?php echo htmlspecialchars($student['postal_code'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">ข้อมูลการศึกษา</h5>

                        <div class="mb-3">
                            <label for="student_code" class="form-label">รหัสนักศึกษา</label>
                            <input type="text" class="form-control" id="student_code" name="student_code" 
                                value="<?php echo htmlspecialchars($student['student_code']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="university_name" class="form-label">มหาวิทยาลัย</label>
                            <input type="text" class="form-control" id="university_name" name="university_name" 
                                value="<?php echo htmlspecialchars($student['university_name'] ?? $student['university'] ?? ''); ?>" readonly>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="faculty_name" class="form-label">คณะ</label>
                                <input type="text" class="form-control" id="faculty_name" name="faculty_name" 
                                    value="<?php echo htmlspecialchars($student['faculty_name'] ?? $student['faculty'] ?? ''); ?>" readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="major_name" class="form-label">สาขาวิชา</label>
                                <input type="text" class="form-control" id="major_name" name="major_name" 
                                    value="<?php echo htmlspecialchars($student['major_name'] ?? $student['major'] ?? ''); ?>" readonly>
                            </div>
                        </div>

                        <div class="row">
                        <div class="col-md-6 mb-3">
                                <label for="education_level" class="form-label">ระดับการศึกษา</label>
                                <input type="text" class="form-control" id="education_level" name="education_level"
                                    value="<?php echo htmlspecialchars($student['education_level'] ?? $student['education'] ?? ''); ?>"
                                    readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">ชั้นปี</label>
                                <input type="text" class="form-control" id="year" name="year"
                                    value="<?php echo htmlspecialchars($student['year'] ?? $student['year'] ?? ''); ?>"
                                    readonly>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="gpa" class="form-label">เกรดเฉลี่ย</label>
                                <input type="text" class="form-control" id="gpa" name="gpa" 
                                    value="<?php echo htmlspecialchars($student['gpa'] ?? ''); ?>" 
                                    pattern="[0-4](\.[0-9]{1,2})?" placeholder="เช่น 3.50"readonly>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">ทักษะและประสบการณ์</h5>

                        <div class="mb-3">
                            <label for="skill" class="form-label">ทักษะ</label>
                            <textarea class="form-control" id="skill" name="skill" rows="3"
                                placeholder="ระบุทักษะของคุณ เช่น โปรแกรมมิ่ง, การสื่อสาร, ภาษาอังกฤษ"><?php echo htmlspecialchars($student['skill'] ?? $student['skills'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="experience" class="form-label">เกี่ยวกับฉัน</label>
                            <textarea class="form-control" id="experience" name="experience" rows="4"
                                placeholder="แนะนำตัวเองสั้นๆ"><?php echo htmlspecialchars($student['experience'] ?? $student['experience'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="update_student" class="btn btn-primary">บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- การเปลี่ยนอีเมล -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนอีเมล</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_email" class="form-label">อีเมลปัจจุบัน</label>
                            <input type="email" class="form-control" id="current_email" 
                                value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_email" class="form-label">อีเมลใหม่</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="request_email_change" class="btn btn-warning">ขอเปลี่ยนอีเมล</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- การเปลี่ยนเบอร์โทรศัพท์ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนเบอร์โทรศัพท์</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_phone" class="form-label">เบอร์โทรศัพท์ปัจจุบัน</label>
                            <input type="text" class="form-control" id="current_phone" 
                                value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_phone" class="form-label">เบอร์โทรศัพท์ใหม่</label>
                            <input type="text" class="form-control" id="new_phone" name="new_phone" pattern="[0-9]{10}" 
                                placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="request_phone_change" class="btn btn-warning">ขอเปลี่ยนเบอร์โทรศัพท์</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- การเปลี่ยนรหัสผ่าน -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนรหัสผ่าน</h5>
                </div>
                <div class="card-body">
                    <p>หากต้องการเปลี่ยนรหัสผ่าน กรุณาคลิกที่ปุ่มด้านล่าง</p>
                    <div class="text-end">
                        <a href="change_password.php" class="btn btn-warning">เปลี่ยนรหัสผ่าน</a>
                    </div>
                </div>
            </div>

            <!-- รูปโปรไฟล์ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">รูปโปรไฟล์</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?php echo getProfileImageUrl($student['profile_image'] ?? '', 'student'); ?>" 
                            class="img-thumbnail rounded-circle" alt="Student Profile" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    <p>หากต้องการเปลี่ยนรูปโปรไฟล์ กรุณาคลิกที่ปุ่มด้านล่าง</p>
                    <div class="text-end">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#profileImageModal">
                            อัปเดตรูปโปรไฟล์
                        </button>
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
            <form method="post" action="student_profile.php" enctype="multipart/form-data">
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

<?php include '../layouts/footer.php'; ?>