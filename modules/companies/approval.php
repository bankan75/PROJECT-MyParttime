<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// นำเข้าไฟล์ที่จำเป็น
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// สร้างอ็อบเจ็กต์ฐานข้อมูลและการยืนยันตัวตน
$db = new Database();
$auth = new Auth($db->getConnection());

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะบริษัทเท่านั้น)
if (!$auth->isAdmin()) {
    // ถ้าไม่ใช่บริษัท ให้เปลี่ยนเส้นทาง
    header("Location: ../../index.php");
    exit;
}
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

$page_title = "อนุมัติข้อมูลบริษัท";
include(BASE_PATH . '/layouts/header.php');

// Get all pending companies
$sql = "SELECT * FROM companies WHERE is_verified = 0 ORDER BY created_at DESC";
$pending_companies = $database->getRows($sql);

// Count pending companies
$total_pending = count($pending_companies);

// Process approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_company'])) {
    $company_id = intval($_POST['company_id']);
    
    // Update company status to verified
    $sql = "UPDATE companies SET is_verified = 1, verified_at = NOW() WHERE company_id = ?";
    $result = $database->execute($sql, [$company_id]);
    
    if ($result) {
        $_SESSION['success'] = "อนุมัติบริษัทเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอนุมัติบริษัท";
    }
    
    // Redirect to refresh the page
    header("Location: approval.php");
    exit();
}

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">อนุมัติบริษัทรอพิจารณา</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fas fa-arrow-left"></i> กลับไปหน้าหลัก
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Approval Stats Card -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>รายการบริษัทรอพิจารณา (<?php echo $total_pending; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if ($total_pending == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>ไม่มีบริษัทที่รอการอนุมัติ</h4>
                <p class="text-muted">ขณะนี้ไม่มีบริษัทที่รอการพิจารณา</p>
                <a href="index.php" class="btn btn-primary mt-2">
                    <i class="fas fa-arrow-left me-1"></i> กลับไปหน้าหลัก
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">ลำดับ</th>
                            <th width="20%">ชื่อบริษัท</th>
                            <th width="15%">ประเภทธุรกิจ</th>
                            <th width="15%">บุคคลที่ติดต่อ</th>
                            <th width="15%">อีเมล/เบอร์โทร</th>
                            <th width="10%" class="text-center">วันที่สมัคร</th>
                            <th width="15%" class="text-center">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_companies as $index => $company): ?>
                            <tr>
                                <td class="text-center"><?php echo $company['company_id']; ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($company['company_name']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($company['business_type']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($company['contact_email']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($company['contact_phone']); ?></small>
                                </td>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($company['created_at'])); ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $company['company_id']; ?>" class="btn btn-sm btn-info text-white" data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $company['company_id']; ?>" title="อนุมัติบริษัท">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?php echo $company['company_id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title" id="approveModalLabel">
                                                        <i class="fas fa-check-circle me-2"></i>อนุมัติบริษัท
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>คุณต้องการอนุมัติบริษัท <strong><?php echo htmlspecialchars($company['company_name']); ?></strong> ใช่หรือไม่?</p>
                                                    <p>เมื่ออนุมัติแล้ว บริษัทนี้จะมีสถานะ "พร้อมทำงาน" ทันที</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i>ยกเลิก
                                                    </button>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="company_id" value="<?php echo $company['company_id']; ?>">
                                                        <button type="submit" name="approve_company" class="btn btn-success">
                                                            <i class="fas fa-check me-1"></i>อนุมัติ
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto close alerts after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});
</script>

<?php include(BASE_PATH . '/layouts/footer.php'); ?>