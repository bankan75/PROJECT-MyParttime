<?php
require_once('includes/config.php');

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    // ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
    if (empty($email) || empty($new_password)) {
        $message = 'กรุณาระบุอีเมลและรหัสผ่านใหม่';
    } elseif (strlen($new_password) < 8) {
        $message = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
    } else {
        try {
            // ตรวจสอบว่าอีเมลนี้อยู่ในตารางใดตารางหนึ่ง (students, companies, admins)
            $user_type = null;
            $user_id = null;

            // ตรวจสอบในตาราง students
            $stmt = $db->prepare("SELECT student_id FROM students WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $user_type = 'student';
                $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['student_id'];
            }

            // ถ้าไม่พบใน students ให้ตรวจสอบใน companies
            if (!$user_type) {
                $stmt = $db->prepare("SELECT company_id FROM companies WHERE contact_email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $user_type = 'company';
                    $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['company_id'];
                }
            }

            // ถ้าไม่พบใน companies ให้ตรวจสอบใน admins
            if (!$user_type) {
                $stmt = $db->prepare("SELECT admin_id FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $user_type = 'admin';
                    $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['admin_id'];
                }
            }

            // ถ้าไม่พบผู้ใช้
            if (!$user_type) {
                $message = 'ไม่พบผู้ใช้งานที่ใช้อีเมลนี้';
            } else {
                // เข้ารหัสรหัสผ่านใหม่
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // อัปเดตรหัสผ่านตามประเภทผู้ใช้
                if ($user_type === 'student') {
                    $stmt = $db->prepare("UPDATE students SET password = ? WHERE email = ?");
                } elseif ($user_type === 'company') {
                    $stmt = $db->prepare("UPDATE companies SET password = ? WHERE contact_email = ?");
                } else { // admin
                    $stmt = $db->prepare("UPDATE admins SET password = ? WHERE email = ?");
                }

                $result = $stmt->execute([$hashed_password, $email]);

                if ($result) {
                    $success = true;
                    $message = 'รีเซ็ตรหัสผ่านสำเร็จแล้ว ท่านสามารถ <a href="login.php" class="alert-link">เข้าสู่ระบบ</a> ด้วยรหัสผ่านใหม่ได้เลย';
                } else {
                    $message = 'ไม่สามารถรีเซ็ตรหัสผ่านได้: ' . implode(' ', $stmt->errorInfo());
                }
            }
        } catch (Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
    body {
        background-color: #f8f9fa;
        height: 100vh;
        display: flex;
        align-items: center;
        padding-top: 0;
    }
    .reset-container {
        max-width: 420px;
        margin: 0 auto;
    }
    .card {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .card-header {
        background-color: #007bff;
        color: white;
        text-align: center;
        padding: 1.5rem;
        border-radius: 0.25rem 0.25rem 0 0 !important;
    }
    .btn-primary {
        width: 100%;
        padding: 0.6rem;
    }
    .form-label {
        font-weight: 500;
        color: #000000;
    }
    </style>
</head>
<body>
    <div class="container reset-container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <img src="/myparttime/assets/images/logo-U-thon1.png" alt="Thonburi University Logo" style="width: 100px; height: auto; display: block; margin: 0 auto 10px;">
                <h4 class="mb-0">รีเซ็ตรหัสผ่าน</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <span class="input-group-text toggle-password" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="toggle-icon-new_password"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <span class="input-group-text toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="toggle-icon-confirm_password"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">รีเซ็ตรหัสผ่าน</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <a href="login.php">กลับไปหน้าเข้าสู่ระบบ</a>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/assets/css/bootstrap.bundle.min.js?v=<?php echo time(); ?>">
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById('toggle-icon-' + inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>