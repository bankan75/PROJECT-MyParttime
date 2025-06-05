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

// Get student data with full details
$sql = "SELECT * FROM students WHERE student_id = ?";
$student = $database->getRow($sql, [$student_id]);

if (!$student) {
    $_SESSION['error'] = "ไม่พบข้อมูลนักศึกษารหัส $student_id";
    header("Location: index.php");
    exit;
}

// Get applied jobs count
$applications_sql = "SELECT COUNT(*) as count FROM applications WHERE student_id = ?";
$applications_count = $database->getRow($applications_sql, [$student_id]);

// Set page title and include header
$page_title = "ข้อมูลนักศึกษา: " . $student['first_name'] . ' ' . $student['last_name'];
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/header.php';

// Determine back link based on user type (company or not)
$back_link = "index.php";
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'company') {
    $back_link = "/Myparttime/modules/current_employees/current_employees.php";
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="<?php echo $back_link; ?>" class="btn btn-sm btn-primary me-2">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการ
        </a>

    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-user"></i> ข้อมูลนักศึกษา</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <!-- ข้อมูลส่วนตัว -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลส่วนตัว</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">รหัสนักศึกษา</th>
                            <td><?php echo sanitize($student['student_code']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ชื่อ-นามสกุล</th>
                            <td><?php echo sanitize($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">วันเกิด</th>
                            <td><?php echo $student['birth_date'] ? date('d/m/Y', strtotime($student['birth_date'])) : 'ไม่ระบุ'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ข้อมูลการติดต่อ -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลการติดต่อ</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">อีเมล</th>
                            <td><?php echo sanitize($student['email']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">เบอร์โทรศัพท์</th>
                            <td><?php echo sanitize($student['phone'] ?? 'ไม่ระบุ'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ข้อมูลการศึกษา -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลการศึกษา</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">คณะ</th>
                            <td><?php echo getFacultyName($student['faculty_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">สาขา</th>
                            <td><?php echo sanitize($student['major_name'] ?? 'ไม่ระบุ'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ชั้นปี</th>
                            <td><?php echo $student['year'] ? 'ปี ' . $student['year'] : 'ไม่ระบุ'; ?></td>
                        </tr>
                        <tr>
                            <th scope="row">เกรดเฉลี่ย</th>
                            <td><?php echo $student['gpa'] ? number_format($student['gpa'], 2) : 'ไม่ระบุ'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ข้อมูลสมัครงาน -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลสมัครงาน</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">ทักษะ</th>
                            <td><?php echo nl2br(sanitize($student['skill'] ?? 'ไม่ระบุ')); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ประสบการณ์</th>
                            <td><?php echo nl2br(sanitize($student['experience'] ?? 'ไม่ระบุ')); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">งานที่สมัคร</th>
                            <td><?php echo $applications_count['count']; ?> งาน</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($applications_count['count'] > 0): ?>
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="card-title mb-0"><i class="fas fa-briefcase"></i> ประวัติการสมัครงาน</h5>
    </div>
    <div class="card-body">
        <?php
        // Get application history
        $applications_sql = "SELECT a.*, j.job_title, j.company_id, c.company_name 
                            FROM applications a 
                            JOIN jobs_posts j ON a.post_id = j.post_id 
                            JOIN companies c ON j.company_id = c.company_id 
                            WHERE a.student_id = ? 
                            ORDER BY a.apply_date DESC";
        $applications = $database->getRows($applications_sql, [$student_id]);
        
        if ($applications): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>บริษัท</th>
                        <th>ตำแหน่งงาน</th>
                        <th>วันที่สมัคร</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?php echo sanitize($app['company_name']); ?></td>
                        <td><?php echo sanitize($app['job_title']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($app['apply_date'])); ?></td>
                        <td>
                            <?php 
                            switch($app['status']) {
                                case 'pending':
                                    echo '<span class="badge bg-warning">รอพิจารณา</span>';
                                    break;
                                case 'accepted':
                                    echo '<span class="badge bg-success">ผ่านการคัดเลือก</span>';
                                    break;
                                case 'rejected':
                                    echo '<span class="badge bg-danger">ไม่ผ่านการคัดเลือก</span>';
                                    break;
                                case 'interview':
                                    echo '<span class="badge bg-danger">นัดสัมภาษณ์</span>';
                                    break;
                                    case 'available':
                                        echo '<span class="badge bg-danger">จบการทำงาน</span>';
                                        break;
                                default:
                                    echo '<span class="badge bg-secondary">'. sanitize($app['status']) .'</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted">ไม่มีประวัติการสมัครงาน</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>