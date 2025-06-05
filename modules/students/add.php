<?php
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_code = $_POST['student_code'] ?? '';
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
    
    // Hash the password if provided
    $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
    
    // Validate required fields
    $errors = [];
    if (empty($student_code)) {
        $errors[] = "รหัสนักศึกษาไม่สามารถเว้นว่างได้";
    } elseif (!preg_match('/^\d{13}$/', $student_code)) {
        $errors[] = "รหัสนักศึกษาต้องเป็นตัวเลข 13 หลัก";
    }
    
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
    
    // Check if student code already exists
    if (empty($errors)) {
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE student_code = ?";
        $check_result = $database->getRow($check_sql, [$student_code]);
        if ($check_result['count'] > 0) {
            $errors[] = "รหัสนักศึกษานี้มีในระบบแล้ว";
        }
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $check_sql = "SELECT COUNT(*) as count FROM students WHERE email = ?";
        $check_result = $database->getRow($check_sql, [$email]);
        if ($check_result['count'] > 0) {
            $errors[] = "อีเมลนี้มีในระบบแล้ว";
        }
    }
    
    // If no errors, insert student
    if (empty($errors)) {
        $sql = "INSERT INTO students (student_code, password, title, first_name, last_name, birth_date, 
                email, phone, faculty_name, major_name, year, gpa, skill, experience) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $student_code, 
            $password, 
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
        
        $result = $database->execute($sql, $params);
        
        if ($result) {
            // Success
            $_SESSION['success'] = "เพิ่มข้อมูลนักศึกษาเรียบร้อยแล้ว";
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}

// Set page title
$page_title = "เพิ่มข้อมูลนักศึกษา";
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/header.php';


// Get list of faculties
$faculties_sql = "SELECT DISTINCT faculty_id FROM students ORDER BY faculty_id";
$faculties = $database->getRows($faculties_sql);
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
                    <label for="student_code" class="form-label">รหัสนักศึกษา <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="student_code" name="student_code" value="<?php echo $_POST['student_code'] ?? ''; ?>" required maxlength="13" placeholder="กรุณากรอกรหัสนักศึกษา 13 หลัก">
                    <div class="form-text">เช่น 6501103071016</div>
                </div>
                <div class="col-md-4">
                    <label for="password" class="form-label">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="(ค่าเริ่มต้น: 123456)">
                    <div class="form-text">หากเว้นว่างจะใช้รหัสผ่านเริ่มต้น: 123456</div>
                </div>
                <div class="col-md-4">
                    <label for="title" class="form-label">คำนำหน้า</label>
                    <select class="form-select" id="title" name="title">
                        <option value="">-- เลือก --</option>
                        <option value="นาย" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นาย') ? 'selected' : ''; ?>>นาย</option>
                        <option value="นาง" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นาง') ? 'selected' : ''; ?>>นาง</option>
                        <option value="นางสาว" <?php echo (isset($_POST['title']) && $_POST['title'] == 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="birth_date" class="form-label">วันเกิด</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $_POST['birth_date'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" maxlength="15">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="faculty_id" class="form-label">คณะ</label>
                    <select class="form-select" id="faculty_id" name="faculty_id">
                        <option value="">-- เลือกคณะ --</option>
                        <?php 
                        // Generate faculty options
                        $faculty_list = [
                            1 => 'วิศวกรรมศาสตร์',
                            2 => 'วิทยาศาสตร์',
                            3 => 'บริหารธุรกิจ',
                            4 => 'ศึกษาศาสตร์',
                            5 => 'นิติศาสตร์',
                            6 => 'ศิลปศาสตร์',
                            7 => 'สถาปัตยกรรมศาสตร์',
                            8 => 'เทคโนโลยีสารสนเทศ',
                        ];
                        
                        foreach ($faculty_list as $id => $name) {
                            $selected = (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $id) ? 'selected' : '';
                            echo "<option value=\"$id\" $selected>$name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="major_name" class="form-label">สาขา</label>
                    <input type="text" class="form-control" id="major_name" name="major_name" value="<?php echo $_POST['major_name'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">ชั้นปี</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">-- เลือก --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['year']) && $_POST['year'] == $i) ? 'selected' : ''; ?>>
                                ปี <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="gpa" class="form-label">เกรดเฉลี่ย</label>
                    <input type="number" class="form-control" id="gpa" name="gpa" step="0.01" min="0" max="4.00" value="<?php echo $_POST['gpa'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="skill" class="form-label">ทักษะความสามารถ</label>
                    <textarea class="form-control" id="skill" name="skill" rows="3"><?php echo $_POST['skill'] ?? ''; ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="experience" class="form-label">ประสบการณ์</label>
                    <textarea class="form-control" id="experience" name="experience" rows="3"><?php echo $_POST['experience'] ?? ''; ?></textarea>
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