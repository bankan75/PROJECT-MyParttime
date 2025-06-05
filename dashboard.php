<?php
// Include header
include('layouts/header.php'); // เตรียมส่วนหัวของหน้า



$auth->requireLogin();

// ตรวจสอบประเภทผู้ใช้และเด้งกลับถ้าเป็นนักศึกษา
if ($_SESSION['user_type'] == 'student') {
    header("Location: index.php");
    exit;
}


// Get statistics based on user type
$user_type = $_SESSION['user_type'] ?? '';
$stats = getDashboardStats($db);
function getStatusBadge($status)
{
    switch (strtolower($status)) {
        case 'completed':
            return '<span class="badge bg-success">เสร็จสิ้น</span>';
        case 'scheduled':
            return '<span class="badge bg-primary">อยู่ระหว่างสัมภาษณ์</span>';
         case 'canceled':
                '<span class="badge bg-danger">ยกเลิก</span>';
            case 'rescheduled':
              '<span class="badge bg-warning">เลื่อนออกไป</span>';
            default:
            return '<span class="badge bg-secobg-warningndary">รอดำเนินการ</span>';
    }
}
?>

<div class="container-fluid">
    <h1 class="mt-0 mb-4">Dashboard</h1>
    <?php if ($user_type == 'company'): ?>
    <!-- Dashboard สำหรับบริษัท -->
    <div class="row">
        <!-- Inside the company dashboard section -->
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">งานที่ประกาศ</h5>
                    <p class="card-text display-4">
                        <?php
                            $company_id = $_SESSION['user_id'];
                            $sql = "SELECT COUNT(*) as total FROM jobs_posts WHERE company_id = ?";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$company_id]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php" class="btn btn-light mt-2">จัดการงาน</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">ใบสมัครทั้งหมด</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนใบสมัครของงานในบริษัทนี้
                            $sql = "SELECT COUNT(a.application_id) as total 
                            FROM applications a 
                            JOIN jobs_posts j ON a.post_id = j.post_id 
                            WHERE j.company_id = ?";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$company_id]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/jobs/applications.php"
                        class="btn btn-light mt-2">ดูใบสมัคร</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">การสัมภาษณ์ที่กำลังจะมาถึง</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนการสัมภาษณ์ที่กำลังจะมาถึงของบริษัทนี้
                            $company_id = $_SESSION['user_id'];
$sql = "SELECT COUNT(i.interview_id) as total 
        FROM interviews i 
        JOIN applications a ON i.application_id = a.application_id
        JOIN jobs_posts j ON a.post_id = j.post_id
        JOIN students s ON a.student_id = s.student_id
        WHERE j.company_id = ? AND i.updated_at >= CURDATE()
        ORDER BY i.updated_at ASC, i.interview_date ASC";
$stmt = $db->prepare($sql);
$stmt->execute([$company_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/interviews/index.php"
                        class="btn btn-light mt-2">ดูการสัมภาษณ์</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">พนักงานทั้งหมด</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนพนักงานทั้งหมดที่ทำงานอยู่ในบริษัทนี้
                            $sql = "SELECT COUNT(*) as total
                                    FROM employments e
                                    WHERE e.company_id = ?
                                    AND e.status = 'accepted'";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$company_id]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/current_employees/current_employees.php"
                        class="btn btn-light mt-2">ดูพนักงาน</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">รายการคำร้องขอลาออก</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนพนักงานทั้งหมดที่ทำงานอยู่ในบริษัทนี้
                            
$sql = "SELECT COUNT(*) as total
FROM resignation_requests r
JOIN applications a ON r.application_id = a.application_id
JOIN jobs_posts j ON a.post_id = j.post_id
WHERE j.company_id = ?
AND r.status = 'pending'";
$stmt = $db->prepare($sql);
$stmt->execute([$company_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/current_employees/current_employees.php"
                        class="btn btn-light mt-2">ดูพนักงาน</a>
                </div>
            </div>
        </div>
    </div>


    <!-- แสดงการสัมภาษณ์ที่กำลังจะมาถึง -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-check me-1"></i>
            การสัมภาษณ์ที่กำลังจะมาถึง
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>เวลา</th>
                            <th>ชื่องาน</th>
                            <th>ชื่อนักศึกษา</th>
                            <th>สถานะ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $company_id = $_SESSION['user_id'];
                            $sql = "SELECT i.*, j.job_title, CONCAT(s.first_name, ' ', s.last_name) as student_name 
        FROM interviews i 
        JOIN applications a ON i.application_id = a.application_id
        JOIN jobs_posts j ON a.post_id = j.post_id
        JOIN students s ON a.student_id = s.student_id
        WHERE j.company_id = ? AND i.updated_at >= CURDATE()
        ORDER BY i.updated_at ASC, i.interview_date ASC
        LIMIT 5";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$company_id]);
                            $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($interviews) > 0) {
                                foreach ($interviews as $interview) {
                                    echo '<tr>';
                                    echo '<td>' . formatDate($interview['interview_date']) . '</td>';
                                    echo '<td>' . $interview['updated_at'] . '</td>';
                                    echo '<td>' . $interview['job_title'] . '</td>';
                                    echo '<td>' . $interview['student_name'] . '</td>';
                                    echo '<td>' . getStatusBadge($interview['status']) . '</td>';
                                    echo '<td><a href="' . ROOT_URL . '/modules/interviews/view.php?id=' . $interview['interview_id'] . '" class="btn btn-sm btn-primary">ดูรายละเอียด</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">ไม่มีการสัมภาษณ์ที่กำลังจะมาถึง</td></tr>';
                            }
                            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($user_type == 'student'): ?>
    <!-- Dashboard สำหรับนักศึกษา -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">ใบสมัครของฉัน</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนใบสมัครของนักศึกษานี้
                            $student_id = $_SESSION['user_id'];
                            $sql = "SELECT COUNT(*) as total FROM applications WHERE student_id = ?";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$student_id]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">การสัมภาษณ์ที่กำลังจะมาถึง</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนการสัมภาษณ์ที่กำลังจะมาถึงของนักศึกษานี้
                            $student_id = $_SESSION['user_id'];
                            $sql = "SELECT COUNT(*) as total 
                                FROM interviews i 
                                JOIN applications a ON i.application_id = a.application_id
                                WHERE a.student_id = ? AND i.interview_date >= CURDATE()";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$student_id]);
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">งานที่เปิดรับ</h5>
                    <p class="card-text display-4">
                        <?php
                            // แสดงจำนวนงานที่เปิดรับทั้งหมด
                            $sql = "SELECT COUNT(*) as total FROM jobs_posts WHERE is_active = 1 AND expire_date >= CURDATE()";
                            $stmt = $db->prepare($sql);
                            $stmt->execute();
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo $result['total'] ?? 0;
                            ?>
                    </p>
                    <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php" class="btn btn-light mt-2">ดูงานทั้งหมด</a>
                </div>
            </div>
        </div>
    </div>

    <!-- แสดงรายการใบสมัครล่าสุด -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-alt me-1"></i>
            ใบสมัครล่าสุดของฉัน
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>วันที่สมัคร</th>
                            <th>ตำแหน่งงาน</th>
                            <th>บริษัท</th>
                            <th>สถานะ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $student_id = $_SESSION['user_id'];
                            $sql = "SELECT a.*, j.job_title, c.company_name 
                                FROM applications a 
                                JOIN jobs_posts j ON a.post_id = j.post_id
                                JOIN companies c ON j.company_id = c.company_id
                                WHERE a.student_id = ?
                                ORDER BY a.apply_date DESC
                                LIMIT 5";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$student_id]);
                            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($applications) > 0) {
                                foreach ($applications as $application) {
                                    echo '<tr>';
                                    echo '<td>' . formatDate($application['apply_date']) . '</td>';
                                    echo '<td>' . $application['job_title'] . '</td>';
                                    echo '<td>' . $application['company_name'] . '</td>';
                                    echo '<td>' . getStatusBadge($application['status']) . '</td>';
                                    echo '<td><a href="' . ROOT_URL . '/modules/applications/view.php?id=' . $application['application_id'] . '" class="btn btn-sm btn-primary">ดูรายละเอียด</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">คุณยังไม่มีใบสมัครงาน</td></tr>';
                            }
                            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- แสดงการสัมภาษณ์ที่กำลังจะมาถึง -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-check me-1"></i>
            การสัมภาษณ์ที่กำลังจะมาถึง
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>เวลา</th>
                            <th>ชื่องาน</th>
                            <th>บริษัท</th>
                            <th>สถานะ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $student_id = $_SESSION['user_id'];
                            $sql = "SELECT i.*, j.job_title, c.company_name 
                                FROM interviews i 
                                JOIN applications a ON i.application_id = a.application_id
                                JOIN jobs_posts j ON a.post_id = j.post_id
                                JOIN students s ON a.student_id = s.student_id
                                WHERE j.company_id = ? AND i.updated_at >= CURDATE()
                                ORDER BY i.updated_at ASC, i.interview_date ASC
                                LIMIT 5";
                            $stmt = $db->prepare($sql);
                            $stmt->execute([$student_id]);
                            $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($interviews) > 0) {
                                foreach ($interviews as $interview) {
                                    echo '<tr>';
                                    echo '<td>' . formatDate($interview['interview_date']) . '</td>';
                                    echo '<td>' . $interview['interview_time'] . '</td>';
                                    echo '<td>' . $interview['job_title'] . '</td>';
                                    echo '<td>' . $interview['company_name'] . '</td>';
                                    echo '<td>' . getStatusBadge($interview['status']) . '</td>';
                                    echo '<td><a href="' . ROOT_URL . '/modules/interviews/view.php?id=' . $interview['interview_id'] . '" class="btn btn-sm btn-primary">ดูรายละเอียด</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">ไม่มีการสัมภาษณ์ที่กำลังจะมาถึง</td></tr>';
                            }
                            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- แสดงงานแนะนำ -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-briefcase me-1"></i>
            งานแนะนำสำหรับคุณ
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ตำแหน่งงาน</th>
                            <th>บริษัท</th>
                            <th>ประเภทงาน</th>
                            <th>เงินเดือน</th>
                            <th>วันที่ประกาศ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            // Inside the แสดงงานแนะนำ section in dashboard.php
                            $sql = "SELECT j.*, c.company_name 
FROM jobs_posts j
JOIN companies c ON j.company_id = c.company_id
WHERE j.status = 'active' AND j.expire_date >= CURDATE()
-- Add recommendation logic based on student major/skills if available
-- Example: AND j.required_skills LIKE '%' || (SELECT major FROM students WHERE student_id = ?) || '%'
ORDER BY j.post_date DESC
LIMIT 5";
                            $stmt = $db->prepare($sql);
                            // If using student data filtering: $stmt->execute([$student_id]);
                            $stmt->execute();
                            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($jobs) > 0) {
                                foreach ($jobs as $job) {
                                    echo '<tr>';
                                    echo '<td><a href="' . ROOT_URL . '/modules/jobs/view.php?id=' . $job['post_id'] . '">' . $job['job_title'] . '</a></td>';
                                    echo '<td>' . $job['company_name'] . '</td>';
                                    echo '<td>' . ($job['job_description'] ?? '-') . '</td>';
                                    echo '<td>' . number_format($job['min_salary']) . ' - ' . number_format($job['max_salary']) . ' บาท</td>';
                                    echo '<td>' . formatDate($job['post_date']) . '</td>';
                                    echo '<td><a href="' . ROOT_URL . '/modules/jobs/view.php?id=' . $job['post_id'] . '" class="btn btn-sm btn-primary">ดูรายละเอียด</a></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">ไม่มีงานที่เปิดรับในขณะนี้</td></tr>';
                            }
                            ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php elseif ($user_type == 'admin'): ?>
<!-- Dashboard สำหรับแอดมิน -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">บริษัททั้งหมด</h5>
                <p class="card-text display-4"><?php echo $stats['total_companies']; ?></p>
                <a href="<?php echo ROOT_URL; ?>/modules/companies/index.php"
                    class="btn btn-light mt-2">จัดการบริษัท</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">นักศึกษาทั้งหมด</h5>
                <p class="card-text display-4"><?php echo $stats['total_students']; ?></p>
                <a href="<?php echo ROOT_URL; ?>/modules/students/index.php"
                    class="btn btn-light mt-2">จัดการนักศึกษา</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">งานที่เปิดรับ</h5>
                <p class="card-text display-4"><?php echo $stats['active_jobs']; ?></p>
                <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php"
                    class="btn btn-light mt-2">ข้อมูลการจัดการงาน</a>
            </div>
        </div>
    </div>
</div>
<div class="row">
        <div class="col-md-6 mb-4">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">ใบสมัครทั้งหมด</h5>
                    <p class="card-text display-4"><?php echo $stats['total_applications']; ?></p>
                    <a href="<?php echo ROOT_URL; ?>/modules/admin/applications.php"
                        class="btn btn-light mt-2">ข้อมูลใบสมัคร</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">การสัมภาษณ์ที่กำลังจะมาถึง</h5>
                    <p class="card-text display-4"><?php echo $stats['upcoming_interviews']; ?></p>
                    <a href="<?php echo ROOT_URL; ?>/modules/interviews/index.php"
                        class="btn btn-light mt-2">ข้อมูลการนัดสัมภาษณ์</a>
                </div>
            </div>
        </div>
    </div>
<!-- ตารางแสดงรายชื่อนักศึกษาที่ได้งาน -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-users me-1"></i>
        รายชื่อนักศึกษาที่ได้งาน
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <?php
            // Query to fetch employed students
            $sql = "SELECT s.student_id, s.first_name, s.last_name, s.student_code, s.faculty_name, s.major_name,
                           e.position, e.start_date, c.company_name
                    FROM employments e
                    JOIN students s ON e.student_id = s.student_id
                    JOIN companies c ON e.company_id = c.company_id
                    WHERE e.status = 'accepted'
                    ORDER BY e.start_date DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $employed_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <?php if (count($employed_students) > 0): ?>
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>คณะ</th>
                            <th>สาขา</th>
                            <th>ตำแหน่งงาน</th>
                            <th>บริษัท</th>
                            <th>วันที่เริ่มงาน</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employed_students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['faculty_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['major_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['position']); ?></td>
                                <td><?php echo htmlspecialchars($student['company_name']); ?></td>
                                <td><?php echo formatDate($student['start_date']); ?></td>
                                <td>
                                    <a href="<?php echo ROOT_URL; ?>/modules/students/view.php?id=<?php echo $student['student_id']; ?>"
                                       class="btn btn-sm btn-primary">ดูโปรไฟล์</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">ไม่มีนักศึกษาที่ได้งานในขณะนี้</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div>
<?php
if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;

    echo '<script>
        Swal.fire({
            title: "ยินดีต้อนรับ!",
            text: "เข้าสู่ MyPartTime.",
            icon: "success",
            confirmButtonText: "OK"
        });
    </script>';
}
?>
<?php
// Include footer
include('layouts/footer.php');
?>