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

// ตรวจสอบสิทธิ์การเข้าถึง - อนุญาตเฉพาะนักศึกษา
if (!$auth->isStudent()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะนักศึกษาเท่านั้น";
    header("Location: " . ROOT_URL . "/index.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// ดึงข้อมูลการสมัครงานทั้งหมดของนักศึกษา
$sql = "SELECT a.*, j.job_title, j.post_date, j.expire_date, c.company_name, c.logo_path
        FROM applications a
        JOIN jobs_posts j ON a.post_id = j.post_id
        JOIN companies c ON j.company_id = c.company_id
        WHERE a.student_id = ?
        ORDER BY a.apply_date DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$student_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ฟังก์ชันแปลงสถานะเป็นภาษาไทยและกำหนดสี
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
    <h1 class="mt-4 mb-4">การสมัครงานของฉัน
    <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
    </h1>

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

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-1"></i>
            รายการการสมัครงานทั้งหมด
        </div>
        <div class="card-body">
            <?php if (count($applications) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="applicationsTable" width="100%" cellspacing="0">
                        <thead class="table-light">
                            <tr>
                                <th>บริษัท</th>
                                <th>ตำแหน่ง</th>
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
        <td>
            <div class="d-flex align-items-center">
                <?php if (!empty($application['logo_path'])): ?>
                    <img src="<?php echo ROOT_URL . '/' . $application['logo_path']; ?>" alt="<?php echo htmlspecialchars($application['company_name']); ?>" class="me-2" style="width: 40px; height: 40px; object-fit: contain;">
                <?php else: ?>
                    <div class="me-2 bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-building text-secondary"></i>
                    </div>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($application['company_name']); ?></span>
            </div>
        </td>
        <td><?php echo htmlspecialchars($application['job_title']); ?></td>
        <td><?php echo date('d/m/Y H:i', strtotime($application['apply_date'])); ?></td>
        <td><?php echo number_format($application['expected_salary'], 2); ?> บาท</td>
        <td><?php echo date('d/m/Y', strtotime($application['available_start_date'])); ?></td>
        <td class="text-center">
            <?php if ($application['status'] === 'request_documents'): ?>
                <span class="badge bg-warning" data-bs-toggle="tooltip" data-bs-placement="top" title="คลิกเพื่อดูรายละเอียด">
                    <i class="fas fa-file-alt me-1"></i>ขอเอกสารเพิ่ม
                </span>
            <?php else: ?>
                <?php echo getStatusBadge($application['status']); ?>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <a href="<?php echo ROOT_URL; ?>/modules/applications/view.php?id=<?php echo $application['application_id']; ?>" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i> ดูรายละเอียด
            </a>
            <?php if ($application['status'] === 'pending' && $application['status'] !== 'request_documents'): ?>
                <a href="<?php echo ROOT_URL; ?>/modules/applications/edit.php?id=<?php echo $application['application_id']; ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> แก้ไข
                </a>
                <a href="<?php echo ROOT_URL; ?>/modules/applications/cancel.php?id=<?php echo $application['application_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณต้องการยกเลิกการสมัครงานนี้ใช่หรือไม่?');">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>คุณยังไม่มีประวัติการสมัครงาน
                </div>
                <div class="text-center mt-4">
                    <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>ค้นหางานที่น่าสนใจ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- คำแนะนำและข้อมูลเพิ่มเติม -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-info-circle me-1"></i>
                    คำอธิบายสถานะการสมัคร
                </div>
                <div class="card-body">
                    <div class="mb-2"><span class="badge bg-warning">รอการพิจารณา</span> -
                        ใบสมัครของคุณอยู่ในคิวรอการพิจารณาจากทางบริษัท</div>
                    <div class="mb-2"><span class="badge bg-info">กำลังพิจารณา</span> - บริษัทกำลังพิจารณาใบสมัครของคุณ
                    </div>
                    <div class="mb-2"><span class="badge bg-primary">นัดสัมภาษณ์</span> - บริษัทต้องการนัดสัมภาษณ์คุณ
                        โปรดตรวจสอบรายละเอียดในหน้ารายละเอียดการสมัคร</div>
                    <div class="mb-2"><span class="badge bg-success">ผ่านการคัดเลือก</span> -
                        คุณผ่านการคัดเลือกให้เข้าทำงานแล้ว</div>
                    <div class="mb-2"><span class="badge bg-danger">ไม่ผ่านการคัดเลือก</span> - ขออภัย
                        คุณไม่ผ่านการคัดเลือกในตำแหน่งนี้</div>
                    <div class="mb-2"><span class="badge bg-danger">จบการทำงาน</span> - นักศึกษาลาออกจาก ที่ทำงานโดยบริษัทเป็นฝ่ายอนุมัติ</div>
                    <div class="mb-2"><span class="badge bg-secondary">ยกเลิก</span> -
                        ใบสมัครนี้ถูกยกเลิกโดยคุณหรือบริษัท</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-lightbulb me-1"></i>
                    เคล็ดลับการสมัครงาน
                </div>
                <div class="card-body">
                    <ul>
                        <li>ตรวจสอบรายละเอียดในใบสมัครให้ครบถ้วนและถูกต้อง</li>
                        <li>อัปโหลดเรซูเม่ที่เป็นปัจจุบันและเกี่ยวข้องกับงานที่สมัคร</li>
                        <li>ระบุเวลาทำงานที่ยืดหยุ่นเพื่อเพิ่มโอกาสในการรับเข้าทำงาน</li>
                        <li>ใส่ข้อความแนะนำตัวที่น่าสนใจและเกี่ยวข้องกับงาน</li>
                        <li>ตั้งเงินเดือนที่คาดหวังให้อยู่ในช่วงที่บริษัทกำหนด</li>
                        <li>ตรวจสอบอีเมลและโทรศัพท์ของคุณเป็นประจำเพื่อไม่พลาดการติดต่อจากบริษัท</li>
                    </ul>
                </div>
            </div>
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
                [2, 'desc']
            ]
        });
    });
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>