<?php
include('../../includes/functions.php');
// เตรียมส่วนหัวของหน้า
include('../../layouts/header.php');



// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นบริษัทหรือไม่
if (!$auth->isLoggedIn() || !$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีบริษัท";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

$company_id = $_SESSION['user_id'];

// ดึงข้อมูลประกาศงานทั้งหมดของบริษัท
$job_sql = "SELECT post_id, job_title FROM jobs_posts WHERE company_id = ? ORDER BY post_date DESC";
$job_stmt = $db->prepare($job_sql);
$job_stmt->execute([$company_id]);
$jobs = $job_stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่ามีการเลือกประกาศงานหรือไม่
$selected_job_id = isset($_GET['job_id']) ? $_GET['job_id'] : 'all';

// สร้าง SQL query ตามการเลือกประกาศงาน
if ($selected_job_id === 'all') {
    $sql = "SELECT a.*, j.job_title, j.post_date, j.expire_date, s.first_name, s.last_name, s.student_code, s.faculty_name, s.major_name, s.year, s.gpa
    FROM applications a
    JOIN jobs_posts j ON a.post_id = j.post_id
    JOIN students s ON a.student_id = s.student_id
    WHERE j.company_id = ?
    ORDER BY a.apply_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id]);
} else {
    $sql = "SELECT a.*, j.job_title, j.post_date, j.expire_date, s.first_name, s.last_name, s.student_code, s.faculty_name, s.major_name, s.year, s.gpa
    FROM applications a
    JOIN jobs_posts j ON a.post_id = j.post_id
    JOIN students s ON a.student_id = s.student_id
    WHERE j.company_id = ? AND j.post_id = ?
    ORDER BY a.apply_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id, $selected_job_id]);
}

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กรองสถานะ (ถ้ามี)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
if ($status_filter !== 'all') {
    $filtered_applications = array_filter($applications, function ($app) use ($status_filter) {
        return $app['status'] === $status_filter;
    });
    $applications = $filtered_applications;
}

// ฟังก์ชันแปลงสถานะเป็นภาษาไทยและกำหนดสี
function getStatusBadge($status)
{
    switch (strtolower($status)) {
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
            return '<span class="badge bg-secondary">ยกเลิก</span>';
        case 'available':
            return '<span class="badge bg-danger">จบการทำงาน</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">รายการใบสมัครงาน</h1>
        <div class="btn-group float-end " role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="me-1"></i> หน้าหลัก
            </a>
            <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php" class="btn btn-primary">
                <i class="fas fa-list-alt me-1"></i> จัดการประกาศงาน
            </a>
        </div>
    </div>


    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- ตัวกรองและตัวเลือก -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="row">
                <div class="col-md-6">
                    <form method="GET" action="" class="d-flex">
                        <select name="job_id" class="form-select me-2">
                            <option value="all" <?php if ($selected_job_id === 'all') echo 'selected'; ?>>ทุกประกาศงาน
                            </option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?php echo $job['post_id']; ?>"
                                    <?php if ($selected_job_id == $job['post_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($job['job_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">กรอง</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="btn-group float-end" role="group">
                        <a href="?status=all<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">ทั้งหมด</a>
                        <a href="?status=pending<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">รอพิจารณา</a>
                        <a href="?status=reviewing<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'reviewing' ? 'btn-info' : 'btn-outline-info'; ?>">กำลังพิจารณา</a>
                        <a href="?status=interview<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'interview' ? 'btn-primary' : 'btn-outline-primary'; ?>">นัดสัมภาษณ์</a>
                        <a href="?status=accepted<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'accepted' ? 'btn-success' : 'btn-outline-success'; ?>">ผ่าน</a>
                        <a href="?status=rejected<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">ไม่ผ่าน</a>
                        <a href="?status=available<?php if ($selected_job_id !== 'all') echo '&job_id=' . $selected_job_id; ?>"
                            class="btn <?php echo $status_filter === 'available' ? 'btn-danger' : 'btn-outline-danger'; ?>">จบ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- รายการใบสมัคร -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-1"></i>
            รายการใบสมัครงาน
            <?php if ($selected_job_id !== 'all'): ?>
                <?php
                $job_title = '';
                foreach ($jobs as $job) {
                    if ($job['post_id'] == $selected_job_id) {
                        $job_title = $job['job_title'];
                        break;
                    }
                }
                ?>
                - <?php echo htmlspecialchars($job_title); ?>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (count($applications) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="applicationsTable" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>ชื่อนักศึกษา</th>
                                <th>ตำแหน่งงาน</th>
                                <th>วันที่สมัคร</th>
                                <th>คณะ/สาขา</th>
                                <th>GPA</th>
                                <th>สถานะ</th>
                                <th>จัดการ</th>
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
                                                <span
                                                    class="fw-bold"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span><br>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($application['student_code']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($application['apply_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($application['faculty_name']) . ' / ' . htmlspecialchars($application['major_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($application['gpa']); ?></td>
                                    <td class="text-center"><?php echo getStatusBadge($application['status']); ?></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="<?php echo ROOT_URL; ?>/modules/applications/view.php?id=<?php echo $application['application_id']; ?>"
                                                class="btn btn-sm btn-info">
                                                <i class=" "></i> ดูรายละเอียด
                                            </a>
                                            <?php if ($application['status'] === 'interview'): ?>
                                            <?php endif; ?>
                                        </div>
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
                        $pending_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'pending';
                        }));
                        $reviewing_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'reviewing';
                        }));
                        $interview_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'interview';
                        }));
                        $accepted_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'accepted';
                        }));
                        $rejected_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'rejected';
                        }));
                        $cancelled_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'cancelled';
                        }));
                        $available_count = count(array_filter($applications, function ($app) {
                            return $app['status'] === 'available';
                        }));
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
                            <div class="card text-center bg-danger bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $available_count; ?></h5>
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

<!-- Modal อัปเดตสถานะ -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateStatusModalLabel">อัปเดตสถานะการสมัคร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo ROOT_URL; ?>/modules/applications/update_status.php">
                <div class="modal-body">
                    <input type="hidden" name="application_id" value="<?php echo $interview['application_id']; ?>">
                    <input type="hidden" name="interview_id" value="<?php echo $interview['interview_id']; ?>">
                    <input type="hidden" name="post_id" value="<?php echo $interview['post_id']; ?>">
                    <input type="hidden" name="status" value="accepted">
                    <input type="hidden" name="redirect"
                        value="<?php echo ROOT_URL; ?>/modules/jobs/applications.php<?php echo $selected_job_id !== 'all' ? '?job_id=' . $selected_job_id : ''; ?><?php echo $status_filter !== 'all' ? ($selected_job_id !== 'all' ? '&' : '?') . 'status=' . $status_filter : ''; ?>">

                    <div class="mb-3">
                        <label for="status" class="form-label">เลือกสถานะใหม่</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">เลือกสถานะ</option>
                            <option value="reviewing">กำลังพิจารณา</option>
                            <option value="interview">นัดสัมภาษณ์</option>
                            <option value="accepted">ผ่านการคัดเลือก</option>
                            <option value="rejected">ไม่ผ่านการคัดเลือก</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label">ความคิดเห็นเพิ่มเติม</label>
                        <textarea name="comment" id="comment" class="form-control" rows="3"
                            placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
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
                [2, 'desc']
            ] // เรียงตามวันที่สมัคร (หลังไปหน้า)
        });
    });

    $('.update-status').on('click', function() {
        var applicationId = $(this).attr('data-application-id');
        var postId = $(this).attr('data-post-id');
        if (!applicationId || !postId) {
            alert('ข้อมูลใบสมัครหรือตำแหน่งงานไม่ครบถ้วน');
            return;
        }
        $('#application_id').val(applicationId);
        $('#post_id').val(postId);
    });
    $('form[action*="update_status.php"]').on('submit', function(e) {
        var applicationId = $('#application_id').val();
        if (!applicationId || applicationId == '0') {
            e.preventDefault();
            alert('รหัสใบสมัครไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
            return false;
        }
    });
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>