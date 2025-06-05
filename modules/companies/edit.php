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
// Include header
include('../../layouts/header.php');

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

$page_title = "แก้ไขสถานะบริษัท: " . htmlspecialchars($company['company_name']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only get status from the form
    $status = sanitize($_POST['status'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    // If no errors, update company status
    if (empty($errors)) {
        try {
            // Convert status to is_verified
            $is_verified = ($status == 'active') ? 1 : 0;
            
            // Update SQL - only update the is_verified field
            $sql = "UPDATE companies SET 
                    is_verified = ?,
                    updated_at = NOW()
                    WHERE company_id = ?";
                    
            $params = [
                $is_verified,
                $company_id
            ];
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                // Log activity if function exists and admin_id is set
                if (function_exists('logActivity') && isset($_SESSION['admin_id'])) {
                    logActivity($db, $_SESSION['admin_id'], 'update', 'companies', $company_id);
                }
                
                $_SESSION['success_message'] = "อัพเดทสถานะเรียบร้อยแล้ว";
                header("Location: view.php?id=".$company_id);
                exit;
            } else {
                $errors[] = "Failed to update company status: Database error";
            }
        } catch (Exception $e) {
            $errors[] = "Database Exception: " . $e->getMessage();
            error_log("Exception in edit_status.php: " . $e->getMessage());
        }
    }
} else {
    // Pre-fill form with existing status data
    $status = ($company['is_verified'] == 1) ? 'active' : 'pending';
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับไปที่บริษัท
        </a>
        <a href="view.php?id=<?php echo $company_id; ?>" class="btn btn-sm btn-info ms-2">
            <i class="fas fa-eye"></i> ดูรายละเอียด
        </a>
    </div>
</div>

<!-- Display Errors -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Edit Company Status Form -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">แก้ไขสถานะบริษัท</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>ชื่อบริษัท:</strong> <?php echo htmlspecialchars($company['company_name']); ?></p>
                <p><strong>บุคคลที่ติดต่อ:</strong> <?php echo htmlspecialchars($company['contact_person']); ?></p>
                <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($company['contact_email']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>เบอร์:</strong> <?php echo htmlspecialchars($company['contact_phone']); ?></p>
                <p><strong>ประเภทธุรกิจ:</strong> <?php echo htmlspecialchars($company['business_type'] ?? 'General'); ?></p>
                <p><strong>สถานะปัจจุบัน:</strong> 
                    <span class="badge rounded-pill <?php echo ($company['is_verified'] == 1) ? 'bg-success' : 'bg-warning'; ?>">
                        <?php echo ($company['is_verified'] == 1) ? 'พร้อมทำงาน' : 'รอพิจารณา'; ?>
                    </span>
                </p>
            </div>
        </div>

        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="status" class="form-label">สถานะ *</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>พร้อมทำงาน</option>
                        <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>รอพิจารณา</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
                <a href="view.php?id=<?php echo $company_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>