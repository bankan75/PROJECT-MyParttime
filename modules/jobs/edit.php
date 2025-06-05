<?php
ob_start();
// Include header
include('../../layouts/header.php');

// Include database config
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/Database.php';

// สร้างการเชื่อมต่อกับฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่ และเป็นบริษัทหรือแอดมินเท่านั้น
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) ||
    ($_SESSION['user_type'] != 'company' && $_SESSION['user_type'] != 'admin')
) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: " . ROOT_URL . "/modules/jobs/index.php");
    exit;
}

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน";
    header("Location: index.php");
    exit;
}

$job_id = $_GET['id'];

// ดึงข้อมูลงานที่ต้องการแก้ไข
$query = "SELECT * FROM jobs_posts WHERE post_id = ?";
if ($user_type == 'company') {
    $query .= " AND company_id = ?";
}

$stmt = $conn->prepare($query);

if ($user_type == 'company') {
    $stmt->bindParam(1, $job_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $user_id, PDO::PARAM_INT);
} else {
    $stmt->bindParam(1, $job_id, PDO::PARAM_INT);
}

$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
    header("Location: index.php");
    exit;
}

$job = $result;


// ถ้าเป็นแอดมิน ดึงรายชื่อบริษัททั้งหมด
$companies = [];
if ($user_type == 'admin') {
    $companyQuery = "SELECT company_id, company_name FROM companies WHERE is_approved = 1";
    $companyResult = $conn->query($companyQuery);
    while ($row = $companyResult->fetch(PDO::FETCH_ASSOC)) {
        $companies[] = $row;
    }
}

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
    $job_category = $_POST['job_category'];
    $job_description = $_POST['job_description'];
    $positions = $_POST['positions'];
    $min_salary = $_POST['min_salary'];
    $max_salary = $_POST['max_salary'];
    $salary_type = $_POST['salary_type'];
    $work_days = $_POST['work_days'];
    $work_hours = $_POST['work_hours'];
    $requirement = $_POST['requirement'];
    $location = $_POST['location'];
    $expire_date = $_POST['expire_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

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

    if (empty($expire_date)) {
        $errors[] = "กรุณาระบุวันหมดเวลารับสมัคร";
    }

    if (empty($errors)) {
        // อัปเดตข้อมูลในฐานข้อมูล
        $query = "UPDATE jobs_posts SET company_id = ?, job_title = ?, job_category = ?, job_description = ?, positions = ?, 
                             min_salary = ?, max_salary = ?, salary_type = ?, work_days = ?, 
                             work_hours = ?, requirement = ?, location = ?, expire_date = ?, 
                             is_active = ? 
         WHERE post_id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(1, $company_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $job_title, PDO::PARAM_STR);
        $stmt->bindParam(3, $job_category, PDO::PARAM_STR);
        $stmt->bindParam(4, $job_description, PDO::PARAM_STR);
        $stmt->bindParam(5, $positions, PDO::PARAM_STR);
        $stmt->bindParam(6, $min_salary, PDO::PARAM_STR);
        $stmt->bindParam(7, $max_salary, PDO::PARAM_STR);
        $stmt->bindParam(8, $salary_type, PDO::PARAM_INT);
        $stmt->bindParam(9, $work_days, PDO::PARAM_STR);
        $stmt->bindParam(10, $work_hours, PDO::PARAM_STR);
        $stmt->bindParam(11, $requirement, PDO::PARAM_STR);
        $stmt->bindParam(12, $location, PDO::PARAM_STR);
        $stmt->bindParam(13, $expire_date, PDO::PARAM_STR);
        $stmt->bindParam(14, $is_active, PDO::PARAM_INT);
        $stmt->bindParam(15, $job_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "แก้ไขข้อมูลงานเรียบร้อยแล้ว";
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . $stmt->errorInfo()[2];
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-4">แก้ไขข้อมูลงาน</h1>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            แก้ไขข้อมูลงาน
        </div>
        <div class="card-body">
            <form method="post" action="">
                <?php if ($user_type == 'admin'): ?>
                    <div class="mb-3">
                        <label for="company_id" class="form-label">บริษัท</label>
                        <select class="form-select" id="company_id" name="company_id" required>
                            <option value="">เลือกบริษัท</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['company_id']; ?>" <?php echo ($job['company_id'] == $company['company_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($company['company_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="job_title" class="form-label">ตำแหน่งงาน</label>
                    <input type="text" class="form-control" id="job_title" name="job_title" value="<?php echo htmlspecialchars($job['job_title'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="job_category" class="form-label">ประเภทงาน</label>
                    <select class="form-select" id="job_category" name="job_category" required>
                        <option value="">เลือกประเภทงาน</option>
                        <?php foreach ($job_categories as $category => $description): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($job['job_category'] == $category) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?> - <?php echo htmlspecialchars($description); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="job_description" class="form-label">รายละเอียดงาน</label>
                    <textarea class="form-control" id="job_description" name="job_description" rows="3" required><?php echo htmlspecialchars($job['job_description'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="positions" class="form-label">จำนวนตำแหน่งที่รับ</label>
                    <input type="text" class="form-control" id="positions" name="positions" value="<?php echo htmlspecialchars($job['positions'] ?? ''); ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="min_salary" class="form-label">เงินเดือนต่ำสุด (บาท)</label>
                        <input type="number" class="form-control" id="min_salary" name="min_salary" value="<?php echo $job['min_salary'] ?? '0'; ?>" min="0" step="0.01" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="max_salary" class="form-label">เงินเดือนสูงสุด (บาท)</label>
                        <input type="number" class="form-control" id="max_salary" name="max_salary" value="<?php echo $job['max_salary'] ?? '0'; ?>" min="0" step="0.01" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="salary_type" class="form-label">ประเภทการจ่ายเงิน</label>
                    <select class="form-select" id="salary_type" name="salary_type">
                        <option value="1" <?php echo ($job['salary_type'] == 1) ? 'selected' : ''; ?>>รายชั่วโมง</option>
                        <!-- แก้ไขข้อผิดพลาด: ลบตัวเลือกซ้ำและแก้ไขค่า -->
                        <option value="2" <?php echo ($job['salary_type'] == 2) ? 'selected' : ''; ?>>รายวัน</option>
                        <option value="3" <?php echo ($job['salary_type'] == 3) ? 'selected' : ''; ?>>รายเดือน</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="work_days" class="form-label">วันทำงาน</label>
                    <input type="text" class="form-control" id="work_days" name="work_days" value="<?php echo htmlspecialchars($job['work_days'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="work_hours" class="form-label">เวลาทำงาน</label>
                    <input type="text" class="form-control" id="work_hours" name="work_hours" value="<?php echo htmlspecialchars($job['work_hours'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="requirement" class="form-label">คุณสมบัติผู้สมัคร</label>
                    <textarea class="form-control" id="requirement" name="requirement" rows="3"><?php echo htmlspecialchars($job['requirement'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">สถานที่ปฏิบัติงาน</label>
                    <textarea class="form-control" id="location" name="location" rows="2"><?php echo htmlspecialchars($job['location'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="expire_date" class="form-label">วันหมดเวลารับสมัคร</label>
                    <input type="date" class="form-control" id="expire_date" name="expire_date" value="<?php echo $job['expire_date'] ?? ''; ?>" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo ($job['is_active'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_active">เปิดรับสมัคร</label>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <a href="index.php" class="btn btn-secondary">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
include('../../layouts/footer.php');
ob_end_flush();
?>