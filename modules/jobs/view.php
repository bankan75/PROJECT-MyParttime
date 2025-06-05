<?php
// Include header
include('../../layouts/header.php');

// Include database config
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/Database.php';

// สร้างการเชื่อมต่อกับฐานข้อมูล
$db = new Database();
$conn = $db->getConnection();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบก่อนใช้งาน";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน";
    header("Location: index.php");
    exit;
}

$job_id = $_GET['id'];
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
// ตรวจสอบว่านักศึกษาเคยสมัครงานนี้แล้วหรือไม่
$check_sql = "SELECT * FROM applications WHERE student_id = ? AND post_id = ? AND status <> 'cancelled'";
$check_stmt = $db->prepare($check_sql);
$check_stmt->execute([$user_id, $job_id]);
$existing_application = $check_stmt->fetch();

if ($existing_application) {
    $_SESSION['error_message'] = "คุณได้สมัครงานนี้ไปแล้ว กรุณายกเลิกการสมัครเดิมก่อนสมัครใหม่";
    header("Location: " . ROOT_URL . "/modules/jobs/index.php?id=" . $post_id);
    exit;
}
// ดึงข้อมูลงาน
$query = "SELECT j.*, c.company_name, c.contact_email, c.contact_phone 
          FROM jobs_posts j 
          JOIN companies c ON j.company_id = c.company_id
          WHERE j.post_id = ?";

// ถ้าเป็นบริษัท ให้ตรวจสอบว่าเป็นงานของตัวเองหรือไม่
if ($user_type == 'company') {
    $query .= " AND j.company_id = ?";
}

// ถ้าเป็นนักศึกษา ให้แสดงเฉพาะงานที่เปิดรับสมัคร
if ($user_type == 'student') {
    $query .= " AND j.is_active = 1 AND j.expire_date >= CURDATE()";
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

// ตรวจสอบสถานะของงาน
$is_expired = strtotime($job['expire_date']) < strtotime(date('Y-m-d'));
$status_class = '';
$status_text = '';

if ($job['is_active'] == 1) {
    if (!$is_expired) {
        $status_class = 'success';
        $status_text = 'เปิดรับสมัคร';
    } else {
        $status_class = 'warning';
        $status_text = 'หมดเวลารับสมัคร';
    }
} else {
    $status_class = 'danger';
    $status_text = 'ปิดรับสมัคร';
}

// ดึงจำนวนผู้สมัครงานนี้
$query = "SELECT COUNT(*) as total_applicants FROM applications WHERE post_id = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $job_id, PDO::PARAM_INT);
$stmt->execute();
$applicants_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_applicants = $applicants_data['total_applicants'];

// แปลงประเภทเงินเดือนเป็นข้อความ
$salary_types = [
    1 => 'รายชั่วโมง',
    2 => 'รายวัน',
    3 => 'รายเดือน'
];
$salary_type_text = isset($salary_types[$job['salary_type']]) ? $salary_types[$job['salary_type']] : 'ไม่ระบุ';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">รายละเอียดงาน</h1>
        <div>
            <a href="index.php" class="btn btn-primary">กลับไปหน้ารายการงาน</a>



            <?php if ($user_type == 'student' && !$is_expired && $job['is_active'] == 1): ?>
    <?php
    // ตรวจสอบว่านักศึกษามีงานแล้วหรือไม่
    $has_job_query = "SELECT COUNT(*) as has_job FROM applications 
                      WHERE student_id = ? AND status = 'accepted'";
    $stmt = $conn->prepare($has_job_query);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $job_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $has_job = $job_result['has_job'] > 0;
    
    if (!$has_job): // แสดงปุ่มสมัครเมื่อยังไม่มีงาน
    ?>
        <a href="../applications/add.php?job_id=<?php echo $job_id; ?>" class="btn btn-success">
            <i class="fas fa-paper-plane"></i> สมัครงาน
        </a>
    <?php else: // แสดงข้อความเมื่อมีงานแล้ว ?>
        <button class="btn btn-danger" disabled>
            <i class="fas fa-briefcase"></i> คุณมีงานแล้ว ไม่สามารถสมัครงานใหม่ได้
        </button>
    <?php endif; ?>
<?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i><?php echo htmlspecialchars($job['job_title']); ?></h5>
                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4>รายละเอียดงาน</h4>
                    <p><?php echo nl2br(htmlspecialchars($job['job_description'])); ?></p>

                    <h4 class="mt-4">คุณสมบัติผู้สมัคร</h4>
                    <p><?php echo nl2br(htmlspecialchars($job['requirement'])); ?></p>

                    <h4 class="mt-4">สถานที่ปฏิบัติงาน</h4>
                    <p><?php echo nl2br(htmlspecialchars($job['location'])); ?></p>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">ข้อมูลบริษัท</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>บริษัท:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                            <?php if ($user_type != 'student' || $job['is_active'] == 1): ?>
                                <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($job['contact_email']); ?></p>
                                <p><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($job['contact_phone']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">ข้อมูลการจ้างงาน</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>ตำแหน่งที่รับ:</strong> <?php echo htmlspecialchars($job['positions']); ?> ตำแหน่ง</p>
                            <p><strong>เงินเดือน:</strong> <?php echo number_format($job['min_salary'], 2) . ' - ' . number_format($job['max_salary'], 2); ?> บาท (<?php echo $salary_type_text; ?>)</p>
                            <p><strong>วันทำงาน:</strong> <?php echo htmlspecialchars($job['work_days']); ?></p>
                            <p><strong>เวลาทำงาน:</strong> <?php echo htmlspecialchars($job['work_hours']); ?></p>
                            <p><strong>วันที่ประกาศ:</strong> <?php echo date('d/m/Y', strtotime($job['post_date'])); ?></p>
                            <p><strong>วันหมดเวลารับสมัคร:</strong> <?php echo date('d/m/Y', strtotime($job['expire_date'])); ?></p>

                            <?php if ($user_type != 'student'): ?>
                                <p><strong>จำนวนผู้สมัคร:</strong> <?php echo $total_applicants; ?> คน</p>
                                <?php if ($total_applicants > 0 && ($user_type == 'company' || $user_type == 'admin')): ?>
                                    <a href="../applications/company_index.php?job_id=<?php echo $job_id; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-users"></i> ดูรายชื่อผู้สมัคร
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <small class="text-muted">แก้ไขล่าสุด: <?php echo date('d/m/Y H:i:s', strtotime($job['update_date'])); ?></small>
        </div>
    </div>
</div>

<?php
// Include footer
include('../../layouts/footer.php');
?>