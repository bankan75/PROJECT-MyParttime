<?php
include('../../includes/functions.php');
// Include header
include('../../layouts/header.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่และเป็นแอดมินหรือไม่
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// ดึงข้อมูลประกาศงานทั้งหมด
$job_sql = "SELECT j.post_id, j.job_title, c.company_name 
            FROM jobs_posts j 
            JOIN companies c ON j.company_id = c.company_id 
            ORDER BY j.post_date DESC";
$job_stmt = $db->prepare($job_sql);
$job_stmt->execute();
$jobs = $job_stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีการเลือกประกาศงานหรือไม่
$selected_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : 'all';

// ดึงข้อมูลบริษัททั้งหมด
$company_sql = "SELECT company_id, company_name FROM companies ORDER BY company_name";
$company_stmt = $db->prepare($company_sql);
$company_stmt->execute();
$companies = $company_stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบการกรองบริษัท
$selected_company_id = isset($_GET['company_id']) ? $_GET['company_id'] : 'all';

// สร้าง SQL query ตามการเลือกกรอง
$params = [];
$sql_conditions = [];

// Base SQL query
$base_sql = "SELECT a.*, j.job_title, j.post_date, j.expire_date, s.first_name, s.last_name, s.student_code, s.faculty_name, s.major_name, s.year, s.gpa, c.company_name
             FROM applications a
             JOIN jobs_posts j ON a.post_id = j.post_id
             JOIN students s ON a.student_id = s.student_id
             JOIN companies c ON j.company_id = c.company_id";

// กรองตามงาน
if ($selected_job_id !== 'all') {
    $sql_conditions[] = "j.post_id = ?";
    $params[] = $selected_job_id;
}

// กรองตามบริษัท
if ($selected_company_id !== 'all') {
    $sql_conditions[] = "j.company_id = ?";
    $params[] = $selected_company_id;
}

// รวม conditions (ถ้ามี)
if (!empty($sql_conditions)) {
    $base_sql .= " WHERE " . implode(" AND ", $sql_conditions);
}

// เพิ่มการเรียงลำดับ
$base_sql .= " ORDER BY a.apply_date DESC";

$stmt = $db->prepare($base_sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กรองสถานะ (ถ้ามี)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
if ($status_filter !== 'all') {
    $filtered_applications = array_filter($applications, function($app) use ($status_filter) {
        return $app['status'] === $status_filter;
    });
    $applications = $filtered_applications;
}

// สถิตินักศึกษาที่ได้งานและว่างงาน
$employed_sql = "SELECT COUNT(DISTINCT student_id) as count FROM students WHERE employment_status = 'employed'";
$employed_result = $db->prepare($employed_sql);
$employed_result->execute();
$employed_count = $employed_result->fetch(PDO::FETCH_ASSOC)['count'];

$unemployed_sql = "SELECT COUNT(DISTINCT student_id) as count FROM students WHERE employment_status = 'unemployed'";
$unemployed_result = $db->prepare($unemployed_sql);
$unemployed_result->execute();
$unemployed_count = $unemployed_result->fetch(PDO::FETCH_ASSOC)['count'];

$total_students = $employed_count + $unemployed_count;

// ฟังก์ชันแปลงสถานะเป็นภาษาไทยและกำหนดสี
function getStatusBadge($status) {
    switch(strtolower($status)) {
        case 'pending':
            return '<span class="badge bg-warning">รอการพิจารณา</span>';
            case 'request_documents':
                return '<span class="badge bg-warning">ขอเอกสารเพิ่ม</span>';
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">รายการใบสมัครงานทั้งหมด (สำหรับผู้ดูแลระบบ)</h1>
        <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
    </div>

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

    <!-- สถิตินักศึกษา -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary h-75">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-users fa-3x me-3"></i>
                    <div>
                        <h5 class="card-title">นักศึกษาทั้งหมด</h5>
                        <h2 class="display-4"><?php echo $total_students; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success h-75">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-briefcase fa-3x me-3"></i>
                    <div>
                        <h5 class="card-title">นักศึกษาที่ได้งาน</h5>
                        <h2 class="display-4"><?php echo $employed_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning h-75">
                <div class="card-body d-flex align-items-center">
                    <i class="fas fa-user-times fa-3x me-3"></i>
                    <div>
                        <h5 class="card-title">นักศึกษาที่ว่างงาน</h5>
                        <h2 class="display-4"><?php echo $unemployed_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตัวกรองและตัวเลือก -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="company_id" class="form-label">บริษัท</label>
                    <select name="company_id" id="company_id" class="form-select">
                        <option value="all" <?php if($selected_company_id === 'all') echo 'selected'; ?>>ทุกบริษัท</option>
                        <?php foreach($companies as $company): ?>
                        <option value="<?php echo $company['company_id']; ?>"
                            <?php if($selected_company_id == $company['company_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($company['company_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="job_id" class="form-label">ตำแหน่งงาน</label>
                    <select name="job_id" id="job_id" class="form-select">
                        <option value="all" <?php if($selected_job_id === 'all') echo 'selected'; ?>>ทุกตำแหน่งงาน</option>
                        <?php foreach($jobs as $job): ?>
                        <option value="<?php echo $job['post_id']; ?>"
                            <?php if($selected_job_id == $job['post_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($job['job_title']) . ' - ' . htmlspecialchars($job['company_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">สถานะ</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" <?php if($status_filter === 'all') echo 'selected'; ?>>ทุกสถานะ</option>
                        <option value="pending" <?php if($status_filter === 'pending') echo 'selected'; ?>>รอการพิจารณา</option>
                        <option value="reviewing" <?php if($status_filter === 'reviewing') echo 'selected'; ?>>กำลังพิจารณา</option>
                        <option value="interview" <?php if($status_filter === 'interview') echo 'selected'; ?>>นัดสัมภาษณ์</option>
                        <option value="accepted" <?php if($status_filter === 'accepted') echo 'selected'; ?>>ผ่านการคัดเลือก</option>
                        <option value="rejected" <?php if($status_filter === 'rejected') echo 'selected'; ?>>ไม่ผ่านการคัดเลือก</option>
                        <option value="cancelled" <?php if($status_filter === 'cancelled') echo 'selected'; ?>>ยกเลิก</option>
                        <option value="available" <?php if($status_filter === 'available') echo 'selected'; ?>>จบการทำงาน</option>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                    <a href="<?php echo ROOT_URL; ?>/modules/admin/applications.php" class="btn btn-secondary">ล้างตัวกรอง</a>
                </div>
            </form>
        </div>
    </div>

    <!-- รายการใบสมัคร -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-list me-1"></i> รายการใบสมัครงาน</h5>
                <div>
                    <button type="button" class="btn btn-success btn-success-light" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-danger btn-danger-light ms-2" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (count($applications) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="applicationsTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>ชื่อนักศึกษา</th>
                            <th>บริษัท</th>
                            <th>ตำแหน่งงาน</th>
                            <th>วันที่สมัคร</th>
                            <th>คณะ/สาขา</th>
                            <th>GPA</th>
                            <th>สถานะ</th>
                            <th>ดูรายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $application): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                        // ดึงรูปโปรไฟล์ของนักศึกษา
                                        $profile_sql = "SELECT profile_image FROM students WHERE student_id = ?";
                                        $profile_stmt = $db->prepare($profile_sql);
                                        $profile_stmt->execute([$application['student_id']]);
                                        $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);
                                        $profile_image = !empty($profile['profile_image']) ? ROOT_URL . '/' . $profile['profile_image'] : ROOT_URL . '/assets/images/default-profile.png';
                                    ?>
                                    <img src="<?php echo $profile_image; ?>" class="rounded-circle me-2"
                                        style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <span class="fw-bold"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($application['student_code']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($application['company_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($application['apply_date'])); ?></td>
                            <td><?php echo htmlspecialchars($application['faculty_name']) . ' / ' . htmlspecialchars($application['major_name']); ?></td>
                            <td><?php echo htmlspecialchars($application['gpa']); ?></td>
                            <td class="text-center"><?php echo getStatusBadge($application['status']); ?></td>
                            <td class="text-center">
                                <a href="<?php echo ROOT_URL; ?>/modules/applications/view.php?id=<?php echo $application['application_id']; ?>&admin=true"
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
                <i class="fas fa-info-circle me-2"></i>ไม่พบข้อมูลใบสมัครที่ตรงตามเงื่อนไขการค้นหา
            </div>
            <?php endif; ?>
        </div>
    </div>

   <!-- Hidden table for export -->
<div id="exportTable" style="display:none; font-family: 'THSarabunNew', Arial, sans-serif;">
    <h2 style="font-family: 'THSarabunNew', Arial, sans-serif;"><?php echo SITE_NAME; ?> - รายงานข้อมูลใบสมัครงาน</h2>
    <p style="font-family: 'THSarabunNew', Arial, sans-serif;">วันที่ออกรายงาน: <?php echo date('d/m/Y H:i'); ?></p>
    <table style="border-collapse: collapse; width: 100%;">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="border: 1px solid #000; padding: 8px;">ชื่อนักศึกษา</th>
                <th style="border: 1px solid #000; padding: 8px;">รหัสนักศึกษา</th>
                <th style="border: 1px solid #000; padding: 8px;">บริษัท</th>
                <th style="border: 1px solid #000; padding: 8px;">ตำแหน่งงาน</th>
                <th style="border: 1px solid #000; padding: 8px;">วันที่สมัคร</th>
                <th style="border: 1px solid #000; padding: 8px;">คณะ</th>
                <th style="border: 1px solid #000; padding: 8px;">สาขา</th>
                <th style="border: 1px solid #000; padding: 8px;">GPA</th>
                <th style="border: 1px solid #000; padding: 8px;">สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($applications as $application): ?>
            <tr>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['student_code']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['company_name']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['job_title']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo date('d/m/Y', strtotime($application['apply_date'])); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['faculty_name']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['major_name']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;"><?php echo htmlspecialchars($application['gpa']); ?></td>
                <td style="border: 1px solid #000; padding: 8px;">
                    <?php 
                    switch(strtolower($application['status'])) {
                        case 'pending': echo 'รอการพิจารณา'; break;
                        case 'reviewing': echo 'กำลังพิจารณา'; break;
                        case 'interview': echo 'นัดสัมภาษณ์'; break;
                        case 'accepted': echo 'ผ่านการคัดเลือก'; break;
                        case 'rejected': echo 'ไม่ผ่านการคัดเลือก'; break;
                        case 'cancelled': echo 'ยกเลิก'; break;
                        case 'available': echo 'จบการทำงาน'; break;
                        default: echo htmlspecialchars($application['status']);
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <!-- สรุปสถิติ -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-bar me-1"></i>
                    สรุปสถิติการสมัครงาน
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // นับจำนวนใบสมัครตามสถานะ
                        $all_applications = count($applications);
                        $pending_count = count(array_filter($applications, function($app) { return $app['status'] === 'pending'; }));
                        $reviewing_count = count(array_filter($applications, function($app) { return $app['status'] === 'reviewing'; }));
                        $interview_count = count(array_filter($applications, function($app) { return $app['status'] === 'interview'; }));
                        $accepted_count = count(array_filter($applications, function($app) { return $app['status'] === 'accepted'; }));
                        $rejected_count = count(array_filter($applications, function($app) { return $app['status'] === 'rejected'; }));
                        $cancelled_count = count(array_filter($applications, function($app) { return $app['status'] === 'cancelled'; }));
                        $completed_count = count(array_filter($applications, function($app) { return $app['status'] === 'available'; }));
                        ?>

                        <div class="col-md-2">
                            <div class="card text-center bg-light mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $all_applications; ?></h5>
                                    <p class="card-text">ทั้งหมด</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-warning bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $pending_count; ?></h5>
                                    <p class="card-text">รอพิจารณา</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-info bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $reviewing_count; ?></h5>
                                    <p class="card-text">กำลังพิจารณา</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-primary bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $interview_count; ?></h5>
                                    <p class="card-text">นัดสัมภาษณ์</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-success bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $accepted_count; ?></h5>
                                    <p class="card-text">ผ่านการคัดเลือก</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-danger bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $rejected_count; ?></h5>
                                    <p class="card-text">ไม่ผ่านการคัดเลือก</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center bg-secondary bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $completed_count; ?></h5>
                                    <p class="card-text">จบการทำงาน</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTable
    $('#applicationsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/th.json'
        },
        order: [
            [3, 'desc']
        ]
    });
    
    // เมื่อเลือกบริษัท ให้โหลดข้อมูลประกาศงานของบริษัทนั้น
    $('select[name="company_id"]').change(function() {
        var companyId = $(this).val();
        
        if (companyId !== 'all') {
            $.ajax({
                url: '<?php echo ROOT_URL; ?>/ajax/get_jobs_by_company.php',
                type: 'GET',
                data: {company_id: companyId},
                dataType: 'json',
                success: function(data) {
                    var jobSelect = $('select[name="job_id"]');
                    jobSelect.empty();
                    jobSelect.append('<option value="all">ทุกประกาศงาน</option>');
                    
                    $.each(data, function(index, job) {
                        jobSelect.append('<option value="' + job.post_id + '">' + job.job_title + '</option>');
                    });
                },
                error: function() {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูลประกาศงาน');
                }
            });
        } else {
            $.ajax({
                url: '<?php echo ROOT_URL; ?>/ajax/get_all_jobs.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    var jobSelect = $('select[name="job_id"]');
                    jobSelect.empty();
                    jobSelect.append('<option value="all">ทุกประกาศงาน</option>');
                    
                    $.each(data, function(index, job) {
                        jobSelect.append('<option value="' + job.post_id + '">' + job.job_title + '</option>');
                    });
                },
                error: function() {
                    console.error('เกิดข้อผิดพลาดในการดึงข้อมูลประกาศงาน');
                }
            });
        }
    });
});

// Export to Excel
function exportToExcel() {
    Swal.fire({
        title: 'กำลังส่งออกข้อมูล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js';
    script.onload = function() {
        const table = document.getElementById('exportTable');
        const ws = XLSX.utils.table_to_sheet(table, {raw: true});
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Applications");
        const fileName = 'applications_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
        XLSX.writeFile(wb, fileName);
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'ส่งออกเสร็จสิ้น',
            text: 'ส่งออกข้อมูลเป็นไฟล์ Excel เรียบร้อยแล้ว',
            timer: 2000,
            showConfirmButton: false
        });
    };
    script.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลด SheetJS library ได้'
        });
    };
    document.body.appendChild(script);
}

// Export to PDF
function exportToPDF() {
    Swal.fire({
        title: 'กำลังส่งออกข้อมูล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get the export table
    const element = document.getElementById('exportTable');

    // Create a temporary container for rendering
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.left = '-9999px';
    tempContainer.style.fontFamily = 'THSarabunNew, Arial, sans-serif'; // Specify Thai font
    tempContainer.innerHTML = element.outerHTML;
    document.body.appendChild(tempContainer);

    // Force a repaint to ensure rendering
    tempContainer.offsetHeight; // Trigger reflow

    // Configure html2pdf options
    const opt = {
        margin: [0.5, 0.5, 0.5, 0.5], // Consistent margins
        filename: 'applications_report_' + new Date().toISOString().split('T')[0] + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            useCORS: true, 
            logging: true, 
            windowWidth: 1200 // Ensure consistent rendering width
        },
        jsPDF: { 
            unit: 'in', 
            format: 'letter', 
            orientation: 'portrait' 
        }
    };

    // Add Thai font support
    const addThaiFont = (pdf) => {
    if (window.thaiFont && window.thaiFont.data) {
        pdf.addFileToVFS('THSarabunNew.ttf', window.thaiFont.data);
        pdf.addFont('THSarabunNew.ttf', 'THSarabunNew', 'normal');
        pdf.setFont('THSarabunNew');
    } else {
        console.warn('Thai font not loaded, falling back to default');
    }
};

    // Generate PDF with a slight delay to ensure rendering
    setTimeout(() => {
        html2pdf().from(tempContainer).set(opt).toPdf().get('pdf').then((pdf) => {
            addThaiFont(pdf); // Embed Thai font
            pdf.setFontSize(12);
        }).save().then(() => {
            // Clean up
            document.body.removeChild(tempContainer);
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'ส่งออกเสร็จสิ้น',
                text: 'ส่งออกข้อมูลเป็นไฟล์ PDF เรียบร้อยแล้ว',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            // Clean up on error
            document.body.removeChild(tempContainer);
            console.error('PDF Export Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถส่งออกเป็น PDF ได้: ' + err.message
            });
        });
    }, 100); // 100ms delay to ensure DOM readiness
}
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>