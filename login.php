<?php
// Include configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// ถ้าล็อกอินแล้วให้ redirect ไปที่หน้า dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// ตรวจสอบการส่ง form
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($username)) {
        $errors[] = 'กรุณาระบุชื่อผู้ใช้ รหัสนักศึกษา หรืออีเมล';
    }
    
    if (empty($password)) {
        $errors[] = 'กรุณาระบุรหัสผ่าน';
    }
    
    // ล็อกอินถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        // ตรวจสอบว่าล็อกอินด้วยอีเมลหรือไม่ (สำหรับบริษัท)
        if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
            if ($auth->companyLogin($username, $password)) {
                header('Location: dashboard.php');
                exit;
            }
        }
        // ตรวจสอบว่าเป็นรหัสนักศึกษาหรือไม่ (ตัวเลข 13 หลัก)
        elseif (preg_match('/^\d{13}$/', $username)) {
            if ($auth->login($username, $password)) {
                header('Location: index.php');
                exit;
            }
        }
        // ถ้าไม่ใช่ทั้งสองกรณี ให้ทดลองล็อกอินด้วยผู้ดูแลระบบ
        else {
            if ($auth->adminLogin($username, $password)) {
                header('Location: dashboard.php');
                exit;
            }
        }
        
        // ถ้ามาถึงจุดนี้แสดงว่าล็อกอินไม่สำเร็จ
        $errors[] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}

// ไม่ต้องรวม header.php และ sidebar.php ในหน้า login
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
    body {
        background-color: rgb(0, 0, 0);
        height: 100vh;
        display: flex;
        align-items: center;
        padding-top: 0;
    }
    .login-container {
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
        color:rgb(5, 5, 7); /* เปลี่ยนสีตัวอักษรเป็นน้ำเงินเข้ม */
    }
    .login-footer {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    .login-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .note-text {
        font-size: 0.875rem;
        color: rgb(0, 0, 0);
        margin-top: 0.25rem;
    }
</style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
        <div class="card-header">
    <img src="/myparttime/assets/images/logo-U-thon1.png" alt="Thonburi University Logo" style="width: 100px; height: auto; display: block; margin: 0 auto 10px;">
    <h4 class="mb-0">เข้าสู่ระบบ <?php echo SITE_NAME; ?></h4>
</div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้ / รหัสนักศึกษา / อีเมล</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="note-text">
                            ใช้รหัสนักศึกษา 13 หลักสำหรับนักศึกษา, อีเมลสำหรับบริษัท, หรือชื่อผู้ใช้สำหรับผู้ดูแลระบบ
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <span class="input-group-text toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="toggle-icon-password"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none me-2">ลงทะเบียนบัญชีใหม่</a> | 
                        <a href="reset_password.php" class="text-decoration-none ms-2">ลืมรหัสผ่าน?</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="login-footer">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo SITE_VERSION; ?>
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