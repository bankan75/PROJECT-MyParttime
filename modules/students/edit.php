<?php
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if student exists
if ($student_id <= 0) {
    $_SESSION['error'] = "ไม่พบข้อมูลนักศึกษา";
    header("Location: index.php");
    exit;
}

// Get student data
$sql = "SELECT * FROM students WHERE student_id = ?";
$student = $database->getRow($sql, [$student_id]);

if (!$student) {
    $_SESSION['error'] = "ไม่พบข้อมูลนักศึกษารหัส $student_id";
    header("Location: index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null;
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $faculty_name = $_POST['faculty_name'] ?? null;
    $major_name = $_POST['major_name'] ?? null;
    $year = $_POST['year'] ?? null;
    $gpa = $_POST['gpa'] ?? null;
    $skill = $_POST['skill'] ?? '';
    $experience = $_POST['experience'] ?? '';
    
    // Handle password change if provided
    $password_update = '';
    $password_params = [];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_update = ", password = ?";
        $password_params[] = $password;
    }
    
    // Validate required fields
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "กรุณากรอกชื่อ";
    }
    
    if (empty($last_name)) {
        $errors[] = "กรุณากรอกนามสกุล";
    }
    
    if (empty($email)) {
        $errors[] = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    }
    
    // Check if email already exists (excluding current student)
    if (empty($errors)) {
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE email = ? AND student_id != ?";
        $check_result = $database->getRow($check_sql, [$email, $student_id]);
        if ($check_result['count'] > 0) {
            $errors[] = "อีเมลนี้มีในระบบแล้ว";
        }
    }
    
    // If no errors, update student
    if (empty($errors)) {
        $sql = "UPDATE students SET 
                title = ?, 
                first_name = ?, 
                last_name = ?, 
                birth_date = ?, 
                email = ?, 
                phone = ?, 
                faculty_namev = ?, 
                major_name = ?, 
                year = ?, 
                gpa = ?, 
                skill = ?, 
                experience = ?
                $password_update
                WHERE student_id = ?";
        
        $params = [
            $title, 
            $first_name, 
            $last_name, 
            $birth_date ? $birth_date : null, 
            $email, 
            $phone, 
            $faculty_name ? $faculty_name : null, 
            $major_name ? $major_name : null, 
            $year ? $year : null, 
            $gpa ? $gpa : null, 
            $skill, 
            $experience
        ];
        
        // Add password param if set
        if (!empty($password_params)) {
            $params = array_merge($params, $password_params);
        }
        
        // Add student ID as the last parameter
        $params[] = $student_id;
        
        $result = $database->execute($sql, $params);
        
        if ($result) {
            $_SESSION['success'] = "อัปเดตข้อมูลนักศึกษาเรียบร้อยแล้ว";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}

// Set page title
$page_title = "แก้ไขข้อมูลนักศึกษา: " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/header.php';

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการ
        </a>
    </div>
</div>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">รหัสนักศึกษา</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['student_code']); ?>" readonly>
                    <div class="form-text">ไม่สามารถแก้ไขรหัสนักศึกษาได้</div>
                </div>
                <div class="col-md-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน">
                    <div class="form-text">กรอกเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่าน</div>
                </div>
                <div class="col-md-4">
                    <label for="title" class="form-label">คำนำหน้า</label>
                    <select class="form-select" id="title" name="title">
                        <option value="">-- เลือก --</option>
                        <option value="นาย" <?php echo ($student['title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                        <option value="นาง" <?php echo ($student['title'] == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                        <option value="นางสาว" <?php echo ($student['title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="birth_date" class="form-label">วันเกิด</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($student['birth_date']); ?>">
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" maxlength="15">
                </div>
            </div>
            
            <div class="row mb-3">
            <div class="col-md-4">
                    <label for="faculty_name" class="form-label">คณะ</label>
                    <input type="text" class="form-control" id="faculty_name" name="faculty_name" value="<?php echo htmlspecialchars($student['faculty_name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="major_name" class="form-label">สาขา</label>
                    <input type="text" class="form-control" id="major_name" name="major_name" value="<?php echo htmlspecialchars($student['major_name'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">ชั้นปี</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">-- เลือก --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($student['year'] == $i) ? 'selected' : ''; ?>>
                                ปี <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="gpa" class="form-label">เกรดเฉลี่ย</label>
                    <input type="number" class="form-control" id="gpa" name="gpa" step="0.01" min="0" max="4.00" value="<?php echo htmlspecialchars($student['gpa'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="skill" class="form-label">ทักษะความสามารถ</label>
                    <textarea class="form-control" id="skill" name="skill" rows="3"><?php echo htmlspecialchars($student['skill'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="experience" class="form-label">ประสบการณ์</label>
                    <textarea class="form-control" id="experience" name="experience" rows="3"><?php echo htmlspecialchars($student['experience'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกข้อมูล
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>