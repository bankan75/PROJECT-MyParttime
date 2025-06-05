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

// Debug mode - ให้แสดงข้อผิดพลาดทั้งหมด
error_reporting(E_ALL);
ini_set('display_errors', 1);

$page_title = "Add New Company";
require_once '../../layouts/header.php';

if (!isset($database)) {
    $errors[] = "Database connection error";
    error_log("Database connection error in add.php");
}
// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $state = sanitize($_POST['state'] ?? '');
    $zip = sanitize($_POST['zip'] ?? '');
    $contact_person = sanitize($_POST['contact_person'] ?? '');
    $status = sanitize($_POST['status'] ?? 'pending');
    $description = sanitize($_POST['description'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Company name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    // If no errors, insert new company
    if (empty($errors)) {
        try {
            // Combine address fields
            $full_address = $address;
            if (!empty($city)) $full_address .= ", " . $city;
            if (!empty($state)) $full_address .= ", " . $state;
            if (!empty($zip)) $full_address .= " " . $zip;
            
            // Update your SQL statement to match all required fields:
            $sql = "INSERT INTO companies (
                company_name, 
                username, 
                password, 
                business_type, 
                company_desc, 
                address, 
                contact_person, 
                contact_email, 
                contact_phone, 
                is_approved
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            // Generate a temporary username (should be unique - consider adding a timestamp)
            $temp_username = strtolower(str_replace(' ', '_', $name)) . '_' . time();

            $params = [
                $name,                                  // company_name
                $temp_username,                         // username
                password_hash('temp_password123', PASSWORD_DEFAULT), // password
                'General',                              // business_type
                $description,                           // company_desc
                $full_address,                          // address
                $contact_person,                        // contact_person
                $email,                                 // contact_email
                $phone,                                 // contact_phone
                ($status == 'active' ? 1 : 0),          // is_approved (was is_verified)
            ];
            
            // แสดงคำสั่ง SQL เพื่อดีบัก
            // แสดงข้อมูล SQL และ parameters สำหรับ debug
            echo "<!-- DEBUG INFO: ";
            echo "SQL: " . $sql . "<br>";
            echo "PARAMS: ";
            print_r($params);
            echo " -->";

            // เปลี่ยนจากการใช้ $database      ->execute เป็นการใช้ prepare และ execute โดยตรง
            $stmt = $database->prepare($sql);
            $result = $stmt->execute($params);
            
            // ในไฟล์ add.php หลังจากบันทึกข้อมูลสำเร็จ
// ในไฟล์ add.php หลังจากบันทึกข้อมูลสำเร็จ
if ($result) {
    // Get the last inserted ID
    $company_id = $database->lastInsertId();
    
    // Log activity if function exists and admin_id is set
    if (function_exists('logActivity') && isset($_SESSION['admin_id'])) {
        logActivity($database, $_SESSION['admin_id'], 'create', 'companies', $company_id);
    }
    
    // Set success message and redirect
    $_SESSION['success_message'] = "Company added successfully";
    header("Location: index.php");
    exit;
} else {
                // แสดงข้อมูลข้อผิดพลาดจาก PDO
                echo "<!-- PDO Error: ";
                print_r($stmt->errorInfo());
                echo " -->";
                $errors[] = "Failed to add company: Database error";
                $errors[] = "Debug info: Check your SQL statement and parameters";
            }
        } catch (Exception $e) {
            $errors[] = "Database Exception: " . $e->getMessage();
            error_log("Exception in add.php: " . $e->getMessage());
        }
    }
}
?>

<!-- Rest of your HTML code remains the same -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Companies
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

<!-- Add Company Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Company Name *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo $_POST['contact_person'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone *</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" value="<?php echo $_POST['address'] ?? ''; ?>">
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?php echo $_POST['city'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="state" class="form-label">State/Province</label>
                    <input type="text" class="form-control" id="state" name="state" value="<?php echo $_POST['state'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label for="zip" class="form-label">ZIP/Postal Code</label>
                    <input type="text" class="form-control" id="zip" name="zip" value="<?php echo $_POST['zip'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : ''; ?>>พร้อมทำงาน</option>
                    <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'pending') ? 'selected' : ''; ?>>รอพิจารณา</option>
                    <option value="suspended" <?php echo (isset($_POST['status']) && $_POST['status'] == 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">คำอธิบาย</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
            </div>

            <!-- ปุ่มบันทึกข้อมูล -->
            <div class="mb-3 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึกข้อมูล
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>