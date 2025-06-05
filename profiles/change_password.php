<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

$user_type = $_SESSION['user_type']; // company, student, หรือ admin
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้ตามประเภท
$user_data = null;
if ($user_type === 'student') {
    $user_data = getStudentProfile($database, $user_id);
} elseif ($user_type === 'company') {
    $user_data = getCompanyProfile($database, $user_id);
} elseif ($user_type === 'admin') {
    $user_data = getAdminProfile($database, $user_id);
}

// หากไม่พบข้อมูลผู้ใช้
if (!$user_data) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลผู้ใช้';
    
    // เด้งกลับไปยังหน้า dashboard
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

// จัดการการส่งฟอร์มเปลี่ยนรหัสผ่าน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบปุ่มที่กด
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // ตรวจสอบความถูกต้องของข้อมูล
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error_message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
        } elseif (strlen($new_password) < 8) {
            $_SESSION['error_message'] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        } else {
            // ตรวจสอบรหัสผ่านปัจจุบัน
            if ($user_type === 'student') {
                $sql = "SELECT password FROM students WHERE student_id = ?";
            } elseif ($user_type === 'company') {
                $sql = "SELECT password FROM companies WHERE company_id = ?";
            } elseif ($user_type === 'admin') {
                $sql = "SELECT password FROM admins WHERE admin_id = ?";
            }
            
            $stmt = $database->prepare($sql);
            $stmt->execute([$user_id]);
            $stored_password = $stmt->fetchColumn();
            
            // ตรวจสอบรหัสผ่านปัจจุบันด้วย password_verify
            if (!password_verify($current_password, $stored_password)) {
                $_SESSION['error_message'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } else {
                // เข้ารหัสรหัสผ่านใหม่
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // อัปเดตรหัสผ่านในฐานข้อมูลตามประเภทผู้ใช้
                if ($user_type === 'student') {
                    $sql = "UPDATE students SET password = ?, updated_at = NOW() WHERE student_id = ?";
                } elseif ($user_type === 'company') {
                    $sql = "UPDATE companies SET password = ?, updated_at = NOW() WHERE company_id = ?";
                } elseif ($user_type === 'admin') {
                    $sql = "UPDATE admins SET password = ?, updated_at = NOW() WHERE admin_id = ?";
                }
                
                $stmt = $database->prepare($sql);
                $result = $stmt->execute([$hashed_password, $user_id]);
                
                if ($result) {
                    $_SESSION['success_message'] = 'เปลี่ยนรหัสผ่านสำเร็จ กรุณาใช้รหัสผ่านใหม่ในการเข้าสู่ระบบครั้งถัดไป';
                    
                    // เด้งกลับไปยังหน้าแก้ไขข้อมูลตามประเภทผู้ใช้
                    if ($user_type === 'student') {
                        header('Location: edit_student_profile.php');
                    } elseif ($user_type === 'company') {
                        header('Location: edit_company_profile.php');
                    } elseif ($user_type === 'admin') {
                        header('Location: edit_admin_profile.php');
                    }
                    exit;
                } else {
                    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: ' . $database->errorInfo()[2];
                }
            }
        }
    }
    
    // ถ้าต้องการยกเลิกการเปลี่ยนรหัสผ่าน
    elseif (isset($_POST['cancel'])) {
        // เด้งกลับไปยังหน้าแก้ไขข้อมูลตามประเภทผู้ใช้
        if ($user_type === 'student') {
            header('Location: edit_student_profile.php');
        } elseif ($user_type === 'company') {
            header('Location: edit_company_profile.php');
        } elseif ($user_type === 'admin') {
            header('Location: edit_admin_profile.php');
        }
        exit;
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">เปลี่ยนรหัสผ่าน</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                                echo $_SESSION['error_message']; 
                                unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?php 
                                echo $_SESSION['success_message']; 
                                unset($_SESSION['success_message']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                minlength="8" placeholder="รหัสผ่านอย่างน้อย 8 ตัวอักษร" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                minlength="8" placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle me-2"></i>คำแนะนำในการตั้งรหัสผ่าน:
                                <ul class="mb-0 mt-1">
                                    <li>ควรมีความยาวอย่างน้อย 8 ตัวอักษร</li>
                                    <li>ควรใช้ตัวอักษรผสมตัวเลข</li>
                                    <li>ควรมีอักขระพิเศษ เช่น !@#$%^&*</li>
                                    <li>ควรมีตัวอักษรทั้งตัวพิมพ์เล็กและพิมพ์ใหญ่</li>
                                </ul>
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" name="change_password" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                            <button type="submit" name="cancel" class="btn btn-secondary">ยกเลิก</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบการตรงกันของรหัสผ่าน
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            e.preventDefault();
            alert('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน');
        }
    });
    
    // แสดงความซับซ้อนของรหัสผ่าน (optional)
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        // ตรวจสอบความซับซ้อนของรหัสผ่านตามต้องการ
        // และแสดงผลให้ผู้ใช้เห็น
    });
    
    // ยกเลิกการบังคับกรอกข้อมูลสำหรับปุ่มยกเลิก
    const cancelBtn = document.querySelector('button[name="cancel"]');
    cancelBtn.addEventListener('click', function() {
        document.getElementById('current_password').removeAttribute('required');
        document.getElementById('new_password').removeAttribute('required');
        document.getElementById('confirm_password').removeAttribute('required');
    });
    
    // เพิ่มฟังก์ชันแสดง/ซ่อนรหัสผ่าน (optional)
    // สามารถเพิ่มไอคอนตาและฟังก์ชันในการแสดง/ซ่อนรหัสผ่านได้
});
</script>

<?php include '../layouts/footer.php'; ?>