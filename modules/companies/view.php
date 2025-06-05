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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid company ID.";
    header("Location: index.php");
    exit;
}

$company_id = (int)$_GET['id'];

// Get company details
$sql = "SELECT * FROM companies WHERE company_id = ?";
$company = $database->getRow($sql, [$company_id]);

// Check if company exists
if (!$company) {
    $_SESSION['error_message'] = "Company not found.";
    header("Location: index.php");
    exit;
}

// Set status text and class based on is_verified
$status_text = '';
$status_class = '';

switch ($company['is_verified']) {
    case 0:
        $status_text = 'รอพิจารณา';
        $status_class = 'bg-warning';
        break;
    case 1:
        $status_text = 'ผ่านการพิจารณา';
        $status_class = 'bg-success';
        break;
    case 2:
        $status_text = 'ไม่ผ่านการพิจารณา';
        $status_class = 'bg-danger';
        break;
    default:
        $status_text = 'รอพิจารณา';
        $status_class = 'bg-warning';
}

$page_title = "Company Details: " . htmlspecialchars($company['company_name']);
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($company['company_name']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> กลับไปที่บริษัท
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0"><i class="fas fa-building"></i> ข้อมูลบริษัท</h5>
    </div>
    <div class="card-body">
    <?php if ($company['logo_path']): ?>
<div class="text-center mb-4">

    <img src="<?php echo ROOT_URL . htmlspecialchars($company['logo_path']); ?>" class="img-fluid rounded" style="max-height: 150px;" alt="Company Logo">
</div>
<?php else: ?>

<?php endif; ?>
        
        <div class="row">
            <!-- ข้อมูลทั่วไป -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลทั่วไป</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">รหัสบริษัท</th>
                            <td><?php echo $company['company_id']; ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ชื่อบริษัท</th>
                            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">เลขจดทะเบียนบริษัท</th>
                            <td><?php echo htmlspecialchars($company['tax_id']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ประเภทธุรกิจ</th>
                            <td><?php echo htmlspecialchars($company['business_type']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ประเภทบริษัท</th>
                            <td><?php echo htmlspecialchars($company['company_type']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">ภาคธุรกิจ</th>
                            <td><?php echo htmlspecialchars($company['business_sector'] ?? 'ไม่ระบุ'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">สถานะ</th>
                            <td><span class="badge rounded-pill <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
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
                            <th scope="row" style="width: 150px;">ผู้ติดต่อ</th>
                            <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">อีเมล</th>
                            <td><?php echo htmlspecialchars($company['contact_email']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">เบอร์โทรศั</th>
                            <td><?php echo htmlspecialchars($company['contact_phone']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">เว็บไซต์</th>
                            <td>
                                <?php if (!empty($company['website'])): ?>
                                    <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a>
                                <?php else: ?>
                                    ไม่ระบุ
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- วันที่สำคัญ -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">วันที่สำคัญ</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">วันที่ลงทะเบียน</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($company['registration_date'])); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">สร้างบัญชี</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($company['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">อัพเดทล่าสุด</th>
                            <td><?php echo $company['updated_at'] ? date('d/m/Y H:i', strtotime($company['updated_at'])) : 'ไม่เคย'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ข้อมูลบัญชี -->
            <div class="col-md-6 mb-4">
                <h6 class="text-muted mb-3">ข้อมูลบัญชี</h6>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <th scope="row" style="width: 150px;">ชื่อผู้ใช้</th>
                            <td><?php echo htmlspecialchars($company['username']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ที่อยู่ -->
        <div class="mt-4">
            <h6 class="text-muted mb-3">ที่อยู่</h6>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th scope="row" style="width: 150px;">ที่อยู่</th>
                        <td><?php echo htmlspecialchars($company['address']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">จังหวัด</th>
                        <td><?php echo htmlspecialchars($company['province'] ?? 'ไม่ระบุ'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">รหัสไปรษณีย์</th>
                        <td><?php echo htmlspecialchars($company['postal_code'] ?? 'ไม่ระบุ'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- รายละเอียดบริษัท -->
        <?php if (!empty($company['company_desc'])): ?>
        <div class="mt-4">
            <h6 class="text-muted mb-3">รายละเอียดบริษัท</h6>
            <div class="card">
                <div class="card-body bg-light">
                    <?php echo nl2br(htmlspecialchars($company['company_desc'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <div class="btn-group">
            <!-- <a href="edit.php?id=<?php echo $company_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> แก้ไข
            </a> -->
            
            <!-- Status management dropdown -->
            <div class="btn-group">
                <button type="button" class="btn <?php echo $status_class; ?> dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-check-circle"></i> เปลี่ยนสถานะ
                </button>
                <ul class="dropdown-menu">
                    <?php if ($company['is_verified'] != 1): ?>
                        <li>
    <a class="dropdown-item" href="javascript:void(0)" 
        onclick="confirmStatusChange(<?php echo $company_id; ?>, 1, 'พร้อมทำงาน')">
        <span class="text-success"><i class="fas fa-check-circle"></i> พร้อมทำงาน</span>
    </a>
</li>
<?php endif; ?>
<?php if ($company['is_verified'] != 0): ?>
<li>
    <a class="dropdown-item" href="javascript:void(0)" 
        onclick="confirmStatusChange(<?php echo $company_id; ?>, 0, 'รอพิจารณา')">
        <span class="text-warning"><i class="fas fa-clock"></i> รอพิจารณา</span>
    </a>
</li>
<?php endif; ?>

<?php if ($company['is_verified'] != 2): ?>
<li>
    <a class="dropdown-item" href="javascript:void(0)" 
        onclick="confirmStatusChange(<?php echo $company_id; ?>, 2, 'ไม่ผ่านการพิจารณา')">
        <span class="text-danger"><i class="fas fa-times-circle"></i> ไม่ผ่านการพิจารณา</span>
    </a>
</li>
<?php endif; ?>
                </ul>
            </div>
            
            <!-- <a href="delete.php?id=<?php echo $company_id; ?>" class="btn btn-danger" 
               onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบบริษัทนี้?')">
                <i class="fas fa-trash"></i> ลบ
            </a> -->
        </div>
    </div>
</div>

<script>
function confirmStatusChange(companyId, status, statusText) {
    Swal.fire({
        title: 'ข้อความจากเว็ปไซต์!',
        text: `คุณแน่ใจหรือไม่ที่จะเปลี่ยนสถานะเป็น ${statusText}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'OK',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `toggle_verify.php?id=${companyId}&status=${status}`;
        }
    });
}
</script>

<?php
// Log activity if function exists
if (function_exists('logActivity') && isset($_SESSION['admin_id'])) {
    logActivity($database, $_SESSION['admin_id'], 'view', 'companies', $company_id);
}

include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>