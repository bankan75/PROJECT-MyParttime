<?php
// เริ่มบัฟเฟอร์เอาท์พุต
ob_start();

// รวมไฟล์กำหนดค่า
require_once(__DIR__ . '/../../includes/config.php');
// Include header
include(BASE_PATH . '/layouts/header.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ และเป็นบริษัทหรือแอดมินเท่านั้น
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || 
    ($_SESSION['user_type'] != 'company' && $_SESSION['user_type'] != 'admin')) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: " . ROOT_URL . "/modules/jobs/index.php");
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
// เพิ่มที่ต้นหน้า add.php
if ($user_type == 'company') {
    // ตรวจสอบสถานะบริษัท
    $check_company_query = "SELECT is_verified FROM companies WHERE company_id = :company_id";
    $check_stmt = $database->prepare($check_company_query);
    $check_stmt->bindValue(':company_id', $user_id);
    $check_stmt->execute();
    $company_status = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // ถ้าบริษัทยังไม่ได้รับการอนุมัติ ให้เปลี่ยนเส้นทาง
    if (!$company_status || $company_status['is_verified'] != 1) {
        $_SESSION['error_message'] = "บริษัทของคุณยังไม่ได้รับการอนุมัติ ไม่สามารถโพสต์งานได้";
        header("Location: index.php");
        exit;
    }
} else {
    // ถ้าไม่ใช่บริษัท ไม่อนุญาตให้เข้าถึงหน้านี้
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: index.php");
    exit;
}
// ถ้าเป็นแอดมิน ดึงรายชื่อบริษัททั้งหมด
$companies = [];
if ($user_type == 'admin') {
    $companyQuery = "SELECT company_id, company_name FROM companies WHERE is_approved = 1";
    $companies = $database->getRows($companyQuery);
}

// เตรียมข้อมูลหมวดหมู่งาน
$job_categories = [
    'ร้านอาหาร' => 'งานเกี่ยวกับร้านอาหาร, คาเฟ่, บาร์',
    'ค้าปลีก' => 'งานในร้านค้า, ซุปเปอร์มาร์เก็ต, ห้างสรรพสินค้า',
    'สำนักงาน' => 'งานธุรการ, ประสานงาน, ช่วยงานสำนักงาน',
    'ส่งของ' => 'งานส่งของ, ขับรถส่งสินค้า, ขนส่ง',
    'จัดงานอีเวนท์' => 'งานจัดงานอีเวนท์, งานแสดง, นิทรรศการ',
    'อื่นๆ' => 'ประเภทงานอื่นๆ ที่ไม่อยู่ในหมวดข้างต้น'
];

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $company_id = ($user_type == 'admin') ? $_POST['company_id'] : $user_id;
    $job_title = $_POST['job_title'];
    $job_description = $_POST['job_description'];
    $positions = $_POST['positions'];
    $min_salary = $_POST['min_salary'];
    $max_salary = $_POST['max_salary'];
    $salary_type = $_POST['salary_type'];
    $work_days = $_POST['work_days'];
    $work_hours = $_POST['work_hours'];
    $requirement = $_POST['requirement'];
    $location = $_POST['location'];
    $post_date = date('Y-m-d');
    $expire_date = $_POST['expire_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $job_category = $_POST['job_category']; // เพิ่มการรับค่าหมวดหมู่งาน
    
    // ตรวจสอบข้อมูล
    $errors = [];
    
    if (empty($job_title)) {
        $errors[] = "กรุณาระบุตำแหน่งงาน";
    }
    
    if (empty($job_description)) {
        $errors[] = "กรุณาระบุรายละเอียดงาน";
    }
    
    if (empty($positions)) {
        $errors[] = "กรุณาระบุจำนวนตำแหน่งที่รับ";
    }
    
    if (empty($min_salary) || empty($max_salary)) {
        $errors[] = "กรุณาระบุเงินเดือน";
    } elseif ($min_salary > $max_salary) {
        $errors[] = "เงินเดือนต่ำสุดต้องน้อยกว่าหรือเท่ากับเงินเดือนสูงสุด";
    }
    
    if (empty($job_category)) {
        $errors[] = "กรุณาเลือกประเภทงาน";
    }
    
    if (empty($expire_date)) {
        $errors[] = "กรุณาระบุวันหมดเวลารับสมัคร";
    } elseif (strtotime($expire_date) < strtotime($post_date)) {
        $errors[] = "วันหมดเวลารับสมัครต้องมากกว่าหรือเท่ากับวันที่ประกาศ";
    }
    
    if (empty($errors)) {
        // เพิ่มข้อมูลลงฐานข้อมูล
        $query = "INSERT INTO jobs_posts (company_id, job_title, job_description, positions, min_salary, max_salary, 
                                        salary_type, work_days, work_hours, requirement, location, post_date, 
                                        expire_date, status, is_active, job_category) 
                  VALUES (:company_id, :job_title, :job_description, :positions, :min_salary, :max_salary, 
                         :salary_type, :work_days, :work_hours, :requirement, :location, 
                         :post_date, :expire_date, 'เปิดรับสมัคร', :is_active, :job_category)";
        
        $params = [
            ':company_id' => $company_id,
            ':job_title' => $job_title,
            ':job_description' => $job_description,
            ':positions' => $positions,
            ':min_salary' => $min_salary,
            ':max_salary' => $max_salary,
            ':salary_type' => $salary_type,
            ':work_days' => $work_days,
            ':work_hours' => $work_hours,
            ':requirement' => $requirement,
            ':location' => $location,
            ':post_date' => $post_date,
            ':expire_date' => $expire_date,
            ':is_active' => $is_active,
            ':job_category' => $job_category
        ];
        
        if ($database->execute($query, $params)) {
            $_SESSION['success_message'] = "เพิ่มงานใหม่เรียบร้อยแล้ว";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการเพิ่มงาน";
        }
    } else {
        $_SESSION['error_message'] = implode("<br>", $errors);
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-4">เพิ่มงานใหม่</h1>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            กรอกข้อมูลงาน
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <?php if ($user_type == 'admin'): ?>
                <div class="mb-3">
                    <label for="company_id" class="form-label">บริษัท</label>
                    <select class="form-select" id="company_id" name="company_id" required>
                        <option value="">เลือกบริษัท</option>
                        <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['company_id']; ?>">
                            <?php echo htmlspecialchars($company['company_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="job_category" class="form-label">ประเภทงาน</label>
                    <select class="form-select" id="job_category" name="job_category" required>
                        <option value="">เลือกประเภทงาน</option>
                        <?php foreach ($job_categories as $category => $description): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?> - <?php echo htmlspecialchars($description); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="job_title" class="form-label">ตำแหน่งงาน</label>
                    <input type="text" class="form-control" id="job_title" name="job_title" required>
                </div>
                
                <div class="mb-3">
                    <label for="job_description" class="form-label">รายละเอียดงาน</label>
                    <textarea class="form-control" id="job_description" name="job_description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="positions" class="form-label">จำนวนตำแหน่งที่รับ</label>
                    <input type="text" class="form-control" id="positions" name="positions" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="min_salary" class="form-label">เงินเดือนต่ำสุด (บาท)</label>
                        <input type="number" class="form-control" id="min_salary" name="min_salary" min="0" step="0.01" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="max_salary" class="form-label">เงินเดือนสูงสุด (บาท)</label>
                        <input type="number" class="form-control" id="max_salary" name="max_salary" min="0" step="0.01" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="salary_type" class="form-label">ประเภทการจ่ายเงิน</label>
                    <select class="form-select" id="salary_type" name="salary_type">
                        <option value="1">รายชั่วโมง</option>
                        <option value="2">รายวัน</option>
                        <option value="3">รายสัปดาห์</option>
                        <option value="4">รายเดือน</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="work_days" class="form-label">วันทำงาน</label>
                    <input type="text" class="form-control" id="work_days" name="work_days" placeholder="เช่น จันทร์-ศุกร์">
                </div>
                
                <div class="mb-3">
                    <label for="work_hours" class="form-label">เวลาทำงาน</label>
                    <input type="text" class="form-control" id="work_hours" name="work_hours" placeholder="เช่น 09:00-17:00">
                </div>
                
                <div class="mb-3">
                    <label for="requirement" class="form-label">คุณสมบัติที่ต้องการ</label>
                    <textarea class="form-control" id="requirement" name="requirement" rows="5"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="location" class="form-label">สถานที่ปฏิบัติงาน</label>
                    <textarea class="form-control" id="location" name="location" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="expire_date" class="form-label">วันหมดเวลารับสมัคร</label>
                    <input type="date" class="form-control" id="expire_date" name="expire_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                    <label class="form-check-label" for="is_active">เปิดรับสมัคร</label>
                </div>
                
                <button type="submit" class="btn btn-primary">บันทึก</button>
                <a href="index.php" class="btn btn-secondary">ยกเลิก</a>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include(BASE_PATH . '/layouts/footer.php');
?>