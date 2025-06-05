<?php
include('../../includes/functions.php');
// Include header
include('../../layouts/header.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!$auth->isLoggedIn()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบ";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ตรวจสอบสิทธิ์การเข้าถึง - อนุญาตเฉพาะบริษัทและผู้ดูแลระบบ
if (!$auth->isCompany() && !$auth->isAdmin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะบริษัทและผู้ดูแลระบบเท่านั้น";
    header("Location: " . ROOT_URL . "/index.php");
    exit;
}

// ตรวจสอบว่ามีการส่ง job_id มาหรือไม่
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลงาน";
    header("Location: ../jobs/index.php");
    exit;
}

$job_id = $_GET['job_id'];
$company_id = $_SESSION['user_id'];

// ตรวจสอบว่างานนี้เป็นของบริษัทนี้หรือไม่ (ยกเว้นผู้ดูแลระบบ)
if ($auth->isCompany()) {
    $job_query = "SELECT * FROM jobs_posts WHERE post_id = ? AND company_id = ?";
    $stmt = $db->prepare($job_query);
    $stmt->execute([$job_id, $company_id]);
    if ($stmt->rowCount() == 0) {
        $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้";
        header("Location: ../jobs/index.php");
        exit;
    }
}

// ดึงข้อมูลงาน
$job_query = "SELECT * FROM jobs_posts WHERE post_id = ?";
$stmt = $db->prepare($job_query);
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลผู้สมัคร
$sql = "SELECT a.*, s.first_name, s.last_name, s.email, s.phone, s.faculty_name, s.major_name, s.year
        FROM applications a
        JOIN students s ON a.student_id = s.student_id
        WHERE a.post_id = ?
        ORDER BY a.apply_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$job_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
function getStatusBadge($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return '<span class="badge bg-warning">รอการพิจารณา</span>';
        case 'reviewing':
        case 'in progress':
            return '<span class="badge bg-info">กำลังพิจารณา</span>';
        case 'interview':
        case 'scheduled':
            return '<span class="badge bg-primary">นัดสัมภาษณ์</span>';
        case 'accepted':
        case 'approved':
        case 'hired':
        case 'active':
            return '<span class="badge bg-success">ผ่านการคัดเลือก</span>';
        case 'rejected':
            return '<span class="badge bg-danger">ไม่ผ่านการคัดเลือก</span>';
        case 'cancelled':
        case 'canceled':
        case 'expired':
            return '<span class="badge bg-danger">ยกเลิก</span>';
        case 'completed':
        case 'available':
            return '<span class="badge bg-danger">จบการทำงาน</span>';

        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-4 mb-4">รายชื่อผู้สมัครงาน: <?php echo htmlspecialchars($job['job_title']); ?></h1>
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="me-1"></i> หน้าหลัก
            </a>
    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-users me-1"></i>
            รายชื่อผู้สมัครทั้งหมด
        </div>
        <div class="card-body">
            <?php if (count($applications) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="applicationsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th>วันที่สมัคร</th>
                            <th>เงินเดือนที่คาดหวัง</th>
                            <th>วันที่พร้อมเริ่มงาน</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($application['apply_date'])); ?></td>
                            <td><?php echo number_format($application['expected_salary'], 2); ?> บาท</td>
                            <td><?php echo date('d/m/Y', strtotime($application['available_start_date'])); ?></td>
                            <td class="text-center"><?php echo getStatusBadge($application['status']); ?></td>
                            <td class="text-center">
                                <a href="<?php echo ROOT_URL; ?>/modules/applications/view.php?id=<?php echo $application['application_id']; ?>"
                                    class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>ยังไม่มีผู้สมัครสำหรับงานนี้
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#applicationsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/th.json'
        },
        order: [
            [1, 'desc']
        ]
    });
});
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>