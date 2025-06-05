<?php
// เพิ่มการเปิด output buffer ที่ต้นไฟล์
ob_start();

// Include configuration
require_once(__DIR__. '/../includes/auth.php');
require_once(__DIR__ . '/../includes/config.php');

// ไม่ต้องเรียกใช้ requireLogin() สำหรับหน้าเว็บทั่วไป
// $auth->requireLogin();

// Get current page for navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$profile_url = '';
if(isset($_SESSION['user_type'])) {
    switch($_SESSION['user_type']) {
        case 'student':
            $profile_url = ROOT_URL . '/profiles/student_profile.php';
            break;
        case 'company':
            $profile_url = ROOT_URL . '/profiles/company_profile.php';
            break;
        case 'admin':
            $profile_url = ROOT_URL . '/profiles/admin_profile.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/assets/css/bootstrap.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/assets/css/all.min.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo ROOT_URL; ?>/dashboard.php">
            <img src="<?php echo ROOT_URL; ?>/assets/images/logo-U-thon1.png" alt="Thonburi University Logo" class="me-2" style="height: 40px;">
            <span><?php echo SITE_NAME; ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarTop">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle  me-1"></i>
                        <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?php echo ROOT_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar    -->
            <?php include(__DIR__ . '/sidebar.php'); ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- แสดงข้อความแจ้งความสำเร็จ -->

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                