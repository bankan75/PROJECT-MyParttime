<?php
// Get user type for conditional menu display
$user_type = $_SESSION['user_type'] ?? '';
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">

        <ul class="nav flex-column">
            <?php if ($user_type == 'company'): ?>
                <!-- เมนูสำหรับบริษัท -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'jobs') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/jobs/index.php">
                        <i class="fas fa-briefcase me-1"></i> จัดการงาน
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'applications') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/jobs/applications.php">
                        <i class="fas fa-file-alt me-1"></i> จัดการใบสมัคร
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'interviews') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/interviews/index.php">
                        <i class="fas fa-calendar-check me-1"></i> จัดการการสัมภาษณ์
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'current_employees') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/current_employees/current_employees.php">
                        <i class="fas fa-users me-1"></i> พนักงานปัจจุบัน
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'resignation_requests') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/companies/resignation_requests.php">
                        <i class="fas fa-door-open me-1"></i> จัดการคำร้องขอลาออก
                    </a>
                </li>
            <?php elseif ($user_type == 'admin'): ?>
                <!-- เมนูสำหรับแอดมิน -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'companies') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/companies/index.php">
                        <i class="fas fa-building me-1"></i> จัดการบริษัท
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'students') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/students/index.php">
                        <i class="fas fa-user-graduate me-1"></i> จัดการนักศึกษา
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'jobs') !== false && !strpos($current_page, 'applications')) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/jobs/index.php">
                        <i class="fas fa-briefcase me-1"></i> ข้อมูลการจัดการงาน
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'applications') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/admin/applications.php">
                        <i class="fas fa-file-alt me-1"></i> ข้อมูลใบสมัคร
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'interviews') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/interviews/index.php">
                        <i class="fas fa-calendar-check me-1"></i> ข้อมูลการนัดสัมภาษณ์
                    </a>
                </li>
                
            <?php elseif ($user_type == 'student'): ?>
                <!-- เมนูสำหรับนักศึกษา -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'indexs') !== false && !strpos($current_page, 'applications')) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i> หน้าหลัก
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'jobs') !== false && !strpos($current_page, 'applications')) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/jobs/index.php">
                        <i class="fas fa-briefcase me-1"></i> ค้นหางาน
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'applications') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/applications/index.php">
                        <i class="fas fa-file-alt me-1"></i> ใบสมัครของฉัน
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'interviews') !== false) ? 'active' : ''; ?>"
                        href="<?php echo ROOT_URL; ?>/modules/interviews/index.php">
                        <i class="fas fa-calendar-check me-1"></i> การนัดสัมภาษณ์ของฉัน
                    </a>
                </li>
            <?php else: ?>
                <!-- Fallback if no valid user type -->
                <li class="nav-item">
                    <span class="nav-link text-muted">กรุณาเข้าสู่ระบบในฐานะนักศึกษา,บริษัทหรือแอดมิน</span>
                </li>
            <?php endif; ?>

            <!-- เพิ่มเมนู Profile ใน Sidebar -->
            <?php if ($user_type == 'student' || $user_type == 'company'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_page, 'profile') !== false) ? 'active' : ''; ?>"
                        href="<?php echo $profile_url; ?>">
                        <i class="fas fa-user me-1"></i> โปรไฟล์
                    </a>
                </li>
            <?php endif; ?>

            <!-- เมนูทั่วไปสำหรับทุกประเภทผู้ใช้ -->
            <li class="nav-item">
                <a class="nav-link" href="<?php echo ROOT_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ
                </a>
            </li>
        </ul>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ดึง URL ปัจจุบัน
        const currentPath = window.location.pathname;
        
        // ตรวจสอบว่าอยู่หน้าไหนและเพิ่ม class active
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            // ตรวจสอบว่า URL ปัจจุบันตรงกับ href ของลิงก์หรือไม่
            if (href && currentPath.includes(href)) {
                link.classList.add('active');
            }
            
            // กรณีพิเศษสำหรับหน้าบริษัททั้งหมด
            if (currentPath.includes('/Myparttime/modules/companies/') && href && href.includes('/Myparttime/modules/companies/')) {
                link.classList.add('active');
            }
        });
    });
    </script>
</nav>