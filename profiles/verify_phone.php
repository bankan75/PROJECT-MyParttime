<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// ตรวจสอบว่ามีการล็อกอินหรือไม่ (โดยไม่จำกัดประเภทผู้ใช้)
if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

$user_type = $_SESSION['user_type']; // company, student, หรือ admin
$user_id = $_SESSION['user_id'];

// ตรวจสอบว่ามีข้อมูลเบอร์โทรศัพท์ใหม่ที่ต้องการยืนยันหรือไม่
if (!isset($_SESSION['verify_new_phone'])) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลเบอร์โทรศัพท์ที่ต้องการยืนยัน';
    
    // เด้งกลับไปยังหน้าแก้ไขข้อมูลตามประเภทผู้ใช้
    if ($user_type === 'company') {
        header('Location: edit_company_profile.php');
    } elseif ($user_type === 'student') {
        header('Location: edit_student_profile.php');
    } elseif ($user_type === 'admin') {
        header('Location: edit_admin_profile.php');
    }
    exit;
}

$new_phone = $_SESSION['verify_new_phone'];

// จัดการการส่งฟอร์มยืนยัน OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once(INCLUDES_PATH . '/otp_functions.php');
    
    // ตรวจสอบปุ่มที่กด
    if (isset($_POST['verify_otp'])) {
        $otp_code = $_POST['otp_code'] ?? '';
        
        if (empty($otp_code)) {
            $_SESSION['error_message'] = 'กรุณากรอกรหัส OTP';
        } else {
            // เรียกใช้ฟังก์ชัน verifyOTP
            $otp_code = trim($_POST['otp_code']);

            if (verifyOTP($database, $user_type, $user_id, 'phone', $otp_code)) {
                // อัปเดตเบอร์โทรศัพท์ใหม่ในฐานข้อมูลตามประเภทผู้ใช้
                if ($user_type === 'company') {
                    $sql = "UPDATE companies SET contact_phone = ?, updated_at = NOW() WHERE company_id = ?";
                } elseif ($user_type === 'student') {
                    $sql = "UPDATE students SET phone = ?, updated_at = NOW() WHERE student_id = ?";
                } elseif ($user_type === 'admin') {
                    $sql = "UPDATE admins SET phone = ?, updated_at = NOW() WHERE admin_id = ?";
                }
                
                $stmt = $database->prepare($sql);
                $result = $stmt->execute([$new_phone, $user_id]);
                
                if ($result) {
                    // ล้างค่า session ที่ใช้ในกระบวนการ
                    unset($_SESSION['verify_new_phone']);
                    
                    $_SESSION['success_message'] = 'อัปเดตเบอร์โทรศัพท์สำเร็จ';
                    
                    // เด้งกลับไปยังหน้าแก้ไขข้อมูลตามประเภทผู้ใช้
                    if ($user_type === 'company') {
                        header('Location: edit_company_profile.php');
                    } elseif ($user_type === 'student') {
                        header('Location: edit_student_profile.php');
                    } elseif ($user_type === 'admin') {
                        header('Location: edit_admin_profile.php');
                    }
                    exit;
                } else {
                    $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการอัปเดตเบอร์โทรศัพท์: ' . $database->errorInfo()[2];
                }
            } else {
                $_SESSION['error_message'] = 'รหัส OTP ไม่ถูกต้องหรือหมดอายุ กรุณาตรวจสอบหรือขอรหัสใหม่';
            }
        }
    }
    
    // ถ้าร้องขอให้ส่ง OTP ใหม่
    elseif (isset($_POST['resend_otp'])) {
        // ลบ OTP เก่าก่อนสร้างใหม่
        $stmt = $database->prepare("DELETE FROM otp_verifications WHERE user_type = ? AND user_id = ? AND verification_type = ?");
        $stmt->execute([$user_type, $user_id, 'phone']);
        
        // สร้าง OTP ใหม่
        $otp = createOTP($database, $user_type, $user_id, 'phone', $new_phone);
        
        if ($otp) {
            if (sendPhoneOTP($new_phone, $otp)) {
                $_SESSION['success_message'] = 'ส่งรหัสยืนยันใหม่แล้ว รหัสของคุณคือ: ' . $otp;
            } else {
                $_SESSION['error_message'] = 'ไม่สามารถส่งข้อความยืนยันได้ กรุณาลองใหม่อีกครั้ง';
            }
        } else {
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการสร้างรหัสยืนยันใหม่';
        }
    }
    
    // ถ้าต้องการยกเลิกการเปลี่ยนเบอร์โทรศัพท์
    elseif (isset($_POST['cancel'])) {
        unset($_SESSION['verify_new_phone']);
        
        // เด้งกลับไปยังหน้าแก้ไขข้อมูลตามประเภทผู้ใช้
        if ($user_type === 'company') {
            header('Location: edit_company_profile.php');
        } elseif ($user_type === 'student') {
            header('Location: edit_student_profile.php');
        } elseif ($user_type === 'admin') {
            header('Location: edit_admin_profile.php');
        }
        exit;
    }
}

// ดึงข้อมูลผู้ใช้เพื่อแสดงเบอร์โทรศัพท์ปัจจุบัน
$current_phone = '';
if ($user_type === 'company') {
    $company = getCompanyProfile($database, $user_id);
    $current_phone = $company['contact_phone'] ?? '';
} elseif ($user_type === 'student') {
    $student = getStudentProfile($database, $user_id);
    $current_phone = $student['phone'] ?? '';
} elseif ($user_type === 'admin') {
    $admin = getAdminProfile($database, $user_id); // สร้างฟังก์ชันนี้ถ้ายังไม่มี
    $current_phone = $admin['phone'] ?? '';
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">ยืนยันการเปลี่ยนเบอร์โทรศัพท์</h4>
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
                    
                    <div class="alert alert-info">
                        <p><strong>เบอร์โทรศัพท์ปัจจุบัน:</strong> <?php echo htmlspecialchars($current_phone); ?></p>
                        <p><strong>เบอร์โทรศัพท์ใหม่:</strong> <?php echo htmlspecialchars($new_phone); ?></p>
                    </div>
                    
                    <p>รหัส OTP ได้ถูกส่งไปยังเบอร์โทรศัพท์ <?php echo htmlspecialchars($new_phone); ?> กรุณากรอกรหัสเพื่อยืนยันการเปลี่ยนแปลง</p>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="otp_code" class="form-label">รหัส OTP</label>
                            <input type="text" class="form-control form-control-lg text-center" id="otp_code" name="otp_code" placeholder="กรอกรหัส OTP" maxlength="6" required>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="verify_otp" class="btn btn-primary">ยืนยัน</button>
                            <button type="submit" name="resend_otp" class="btn btn-outline-secondary">ส่งรหัสใหม่</button>
                            <button type="submit" name="cancel" class="btn btn-danger">ยกเลิก</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <small class="text-muted">รหัส OTP จะหมดอายุภายใน 15 นาที</small>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // แยกฟอร์มสำหรับปุ่มต่างๆ
    const form = document.querySelector('form');
    const otpInput = document.getElementById('otp_code');
    const verifyBtn = document.querySelector('button[name="verify_otp"]');
    
    // ตรวจสอบค่าว่างเฉพาะเมื่อกดปุ่มยืนยัน
    verifyBtn.addEventListener('click', function(e) {
        if (otpInput.value.trim() === '') {
            e.preventDefault();
            alert('กรุณากรอกรหัส OTP');
        }
    });
    
    // ยกเลิกการบังคับกรอกข้อมูลสำหรับปุ่มอื่น
    const resendBtn = document.querySelector('button[name="resend_otp"]');
    const cancelBtn = document.querySelector('button[name="cancel"]');
    
    resendBtn.addEventListener('click', function() {
        otpInput.removeAttribute('required');
    });
    
    cancelBtn.addEventListener('click', function() {
        otpInput.removeAttribute('required');
    });
});
</script>
<?php include '../layouts/footer.php'; ?>