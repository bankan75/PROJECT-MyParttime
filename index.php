<?php
// เริ่มต้น session
session_start();

// รวมไฟล์การตั้งค่าและฟังก์ชัน
require_once 'includes/auth.php';
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// ตรวจสอบว่าผู้ใช้เป็นบริษัทหรือแอดมินหรือไม่ ถ้าใช่ให้ redirect ไปหน้าอื่น
if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'company' || $_SESSION['user_type'] === 'admin')) {
    // ถ้าเป็นบริษัท ให้ redirect ไปที่หน้าแดชบอร์ดบริษัท
    if ($_SESSION['user_type'] === 'company') {
        header('Location: dashboard.php');
        exit;
    }
    // ถ้าเป็นแอดมิน ให้ redirect ไปที่หน้าแดชบอร์ดแอดมิน
    elseif ($_SESSION['user_type'] === 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

// สร้างอินสแตนซ์ของคลาส Database
$database = new Database();

// ดึงข้อมูลหมวดหมู่งาน (categories) จากฐานข้อมูล
$categories_query = "SELECT DISTINCT job_category FROM jobs_posts WHERE job_category IS NOT NULL AND job_category != ''";
$db_categories = $database->query($categories_query)->fetchAll(PDO::FETCH_COLUMN);

// ถ้าไม่มีข้อมูลหมวดหมู่ในฐานข้อมูล ให้ใช้ค่าเริ่มต้น
if (empty($db_categories)) {
    $categories = [
        ['name' => 'ร้านอาหาร', 'icon' => 'utensils', 'keyword' => 'ร้านอาหาร'],
        ['name' => 'ค้าปลีก', 'icon' => 'shopping-basket', 'keyword' => 'ค้าปลีก'],
        ['name' => 'สำนักงาน', 'icon' => 'desktop', 'keyword' => 'สำนักงาน'],
        ['name' => 'ส่งของ', 'icon' => 'motorcycle', 'keyword' => 'ส่งของ'],
        ['name' => 'จัดงานอีเวนท์', 'icon' => 'calendar-alt', 'keyword' => 'อีเวนท์'],
        ['name' => 'อื่นๆ', 'icon' => 'ellipsis-h', 'keyword' => 'อื่นๆ'],
    ];
} else {
    // สร้างอาร์เรย์ของหมวดหมู่จากข้อมูลในฐานข้อมูล
    $categories = [];
    $icons = [
        'ร้านอาหาร' => 'utensils',
        'ค้าปลีก' => 'shopping-basket',
        'สำนักงาน' => 'desktop',
        'ส่งของ' => 'motorcycle',
        'จัดงานอีเวนท์' => 'calendar-alt',
        'อื่นๆ' => 'ellipsis-h'
    ];
    
    foreach ($db_categories as $category) {
        $icon = isset($icons[$category]) ? $icons[$category] : 'tag';
        $categories[] = [
            'name' => $category,
            'icon' => $icon,
            'keyword' => $category
        ];
    }
    
    // เพิ่มหมวดหมู่ 'อื่นๆ' ถ้ายังไม่มี
    $has_other = false;
    foreach ($categories as $cat) {
        if ($cat['name'] === 'อื่นๆ') {
            $has_other = true;
            break;
        }
    }
    
    if (!$has_other) {
        $categories[] = ['name' => 'อื่นๆ', 'icon' => 'ellipsis-h', 'keyword' => 'อื่นๆ'];
    }
}

?>

<?php include 'layouts/header.php'; ?>

<div class="row">
    <div class="col-md-9">
        <!-- Hero Section -->
        <div class="card mb-4">
            <div class="card-body text-center bg-light p-5">
                <h1 class="display-5 fw-bold mb-3">ค้นหางานพาร์ทไทม์ที่ใช่สำหรับคุณ</h1>
                <p class="lead mb-4">เว็บไซต์รวบรวมงานพาร์ทไทม์หลากหลายประเภท ให้คุณเลือกสมัครได้ตามความต้องการ</p>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="modules/jobs/index.php" class="btn btn-primary btn-lg px-4 gap-3">ค้นหางานพาร์ทไทม์</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">ค้นหางานพาร์ทไทม์</h5>
                <form action="modules/jobs/index.php" method="GET">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <input type="text" name="keyword" class="form-control" placeholder="คีย์เวิร์ด / ตำแหน่งงาน / ชื่อบริษัท">
                        </div>
                        <div class="col-md-4 mb-2">
                            <input type="text" name="location" class="form-control" placeholder="สถานที่ทำงาน (เช่น กรุงเทพมหานคร)">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="category" class="form-select">
                                <option value="">ทุกประเภทงาน</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Latest Jobs Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">งานพาร์ทไทม์ล่าสุด</h5>
            </div>
            <div class="card-body">
                <?php
                // Query ดึงงานล่าสุด 5 รายการ
                $sql = "SELECT jp.*, c.company_name 
                        FROM jobs_posts jp 
                        JOIN companies c ON jp.company_id = c.company_id 
                        WHERE jp.status = 'เปิดรับสมัคร' AND jp.is_active = 1 
                        ORDER BY jp.post_date DESC 
                        LIMIT 5";
                $result = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                if ($result && count($result) > 0) {
                    foreach ($result as $row) {
                        ?>
                        <div class="job-item mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>
                                        <a href="modules/jobs/view.php?id=<?php echo $row['post_id']; ?>">
                                            <?php echo htmlspecialchars($row['job_title']); ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-building "></i> <?php echo htmlspecialchars($row['company_name']); ?> |
                                        <i class="fas fa-map-marker-alt "></i> <?php echo htmlspecialchars($row['location']); ?> |
                                        <i class="fas fa-tag "></i> <?php echo htmlspecialchars($row['job_category'] ?? 'อื่นๆ'); ?>
                                    </p>
                                    <p class="mb-1">
                                        <?php echo mb_substr(htmlspecialchars($row['job_description']), 0, 100, 'UTF-8'); ?>...
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="mb-2">
                                        <span class="badge bg-success">
                                            <?php echo number_format($row['min_salary'], 2); ?> - 
                                            <?php echo number_format($row['max_salary'], 2); ?> บาท
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> โพสต์เมื่อ
                                        <?php echo date('d/m/Y', strtotime($row['post_date'])); ?>
                                    </small><br>
                                    <a href="modules/jobs/view.php?id=<?php echo $row['post_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary mt-2">ดูรายละเอียด</a>
                                </div>
                            </div>
                        </div>
                        <?php
                        // เพิ่มเส้นแบ่ง ยกเว้นรายการสุดท้าย
                        if ($row !== end($result)) {
                            echo '<hr>';
                        }
                    }
                    ?>
                    <div class="text-center mt-3">
                        <a href="modules/jobs/index.php" class="btn btn-outline-primary">ดูงานพาร์ทไทม์ทั้งหมด</a>
                    </div>
                    <?php
                } else {
                    echo '<p class="text-center">ไม่มีงานพาร์ทไทม์ที่เปิดรับสมัครในขณะนี้</p>';
                }
                ?>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">หมวดหมู่งานพาร์ทไทม์</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-4 mb-4">
                        <div class="p-3 border rounded">
                            <i class="fas fa-<?php echo $category['icon']; ?> fa-3x mb-3 text-primary"></i>
                            <h5><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="small">งานพาร์ทไทม์เกี่ยวกับ<?php echo htmlspecialchars($category['name']); ?></p>
                            <a href="modules/jobs/index.php?category=<?php echo urlencode($category['name']); ?>" 
                               class="btn btn-sm btn-outline-primary">ดูงาน</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">ทำไมต้องหางานกับเรา?</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center mb-4">
                        <div class="p-3">
                            <i class="fas fa-search fa-3x mb-3 text-success"></i>
                            <h5>ค้นหางานง่าย</h5>
                            <p>ค้นหางานพาร์ทไทม์ได้ง่ายด้วยระบบค้นหาที่ใช้งานง่าย</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="p-3">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <h5>งานคุณภาพ</h5>
                            <p>งานพาร์ทไทม์จากบริษัทที่ผ่านการตรวจสอบแล้ว</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-4">
                        <div class="p-3">
                            <i class="fas fa-bolt fa-3x mb-3 text-success"></i>
                            <h5>สมัครงานได้ทันที</h5>
                            <p>สมัครงานพาร์ทไทม์ผ่านระบบออนไลน์ได้ทันที</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <!-- Sidebar -->
    <div class="col-md-3">
        
        <!-- Login Box -->
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">เข้าสู่ระบบ</h5>
                </div>
                <div class="card-body">
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="register.php">สมัครสมาชิกใหม่</a> | 
                        <a href="forgot-password.php">ลืมรหัสผ่าน</a>
                    </div>
                </div>
            </div>

<?php else: ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">ยินดีต้อนรับ</h5>
        </div>
        <div class="card-body">
            <?php
            // ดึงข้อมูลโปรไฟล์ของนักศึกษา
            if ($_SESSION['user_type'] === 'student') {
                $student_id = $_SESSION['user_id'];
                $student = getStudentProfile($database, $student_id);
                
                // ดึง URL รูปโปรไฟล์
                $profile_image_url = getProfileImageUrl($student['profile_image'] ?? '', 'student');
            ?>
                <div class="text-center mb-3">
                    <img src="<?php echo $profile_image_url; ?>" alt="รูปโปรไฟล์" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                </div>
                <p>สวัสดี, <?php echo htmlspecialchars($student['first_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? ''); ?></p>
                <p class="small text-muted"><?php echo $student['faculty_name'] ?? ''; ?> - <?php echo $student['major_name'] ?? ''; ?></p>
            <?php
            } else {
            ?>
            <?php
            }
            ?>
            <div class="d-grid gap-2">
                <!-- <a href="dashboard.php" class="btn btn-primary">แดชบอร์ด</a> -->
                <?php if ($_SESSION['user_type'] === 'student'): ?>
                <a href="profiles/student_profile.php" class="btn btn-outline-info">โปรไฟล์ของฉัน</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-danger">ออกจากระบบ</a>
            </div>
        </div>
    </div>
<?php endif; ?>

        <!-- Categories Box -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">ประเภทงาน</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <!-- เพิ่มตัวเลือกแสดงงานทั้งหมด -->
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="modules/jobs/index.php" class="text-decoration-none">
                            <i class="fas fa-list me-2"></i> งานทั้งหมด
                        </a>
                        <?php 
                        // ดึงจำนวนงานทั้งหมดที่เปิดรับสมัคร
                        $all_jobs_count = $database->query("SELECT COUNT(*) FROM jobs_posts WHERE is_active = 1 AND expire_date >= CURDATE()")->fetchColumn();
                        ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $all_jobs_count; ?></span>
                    </li>
                    
                    <?php foreach ($categories as $category): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="modules/jobs/index.php?category=<?php echo urlencode($category['name']); ?>" class="text-decoration-none">
                            <i class="fas fa-<?php echo $category['icon']; ?> me-2"></i> <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php 
                        // ดึงจำนวนงานในแต่ละประเภท
                        $cat_jobs_query = "SELECT COUNT(*) FROM jobs_posts WHERE job_category = :category AND is_active = 1 AND expire_date >= CURDATE()";
                        $stmt = $database->prepare($cat_jobs_query);
                        $stmt->bindParam(':category', $category['name']);
                        $stmt->execute();
                        $cat_jobs_count = $stmt->fetchColumn();
                        ?>
                        <span class="badge bg-primary rounded-pill"><?php echo $cat_jobs_count; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Statistics Box -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">สถิติ</h5>
            </div>
            <div class="card-body">
                <?php
                // ดึงสถิติจากฐานข้อมูล
                $stats = [
                    'jobs' => $database->query("SELECT COUNT(*) FROM jobs_posts WHERE is_active = 1")->fetchColumn(),
                    'companies' => $database->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
                    'students' => $database->query("SELECT COUNT(*) FROM students")->fetchColumn(), // แก้จาก users เป็น students
                ];
                ?>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-briefcase fa-2x text-primary me-3"></i>
                            <div>
                                <div class="small text-muted">งานที่เปิดรับ</div>
                                <div class="fw-bold"><?php echo $stats['jobs']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-building fa-2x text-success me-3"></i>
                            <div>
                                <div class="small text-muted">บริษัท</div>
                                <div class="fw-bold"><?php echo $stats['companies']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-graduate fa-2x text-info me-3"></i>
                            <div>
                                <div class="small text-muted">นักศึกษาลงทะเบียน</div>
                                <div class="fw-bold"><?php echo $stats['students']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Check if the welcome message has been shown already
if (!isset($_SESSION['welcome_shown'])) {
    // Set the flag to prevent showing the message again
    $_SESSION['welcome_shown'] = true;
    
    echo '<script>
        Swal.fire({
            title: "ยินดีต้อนรับ!",
            text: "เข้าสู่ MyPartTime.",
            icon: "success",
            confirmButtonText: "OK"
        });
    </script>';
}
?>
<?php include 'layouts/footer.php'; ?>