<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Include utility functions
require_once('../../includes/functions.php');
// Include header
include('../../layouts/header.php');

// Include database config
require_once('../../includes/config.php');

// ใช้การเชื่อมต่อฐานข้อมูลที่มีอยู่แล้วจาก config.php
$conn = $database->getConnection();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบก่อนใช้งาน";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
    // ตรวจสอบว่าบริษัทได้รับการอนุมัติแล้วหรือไม่

}

// กำหนดการแสดงผลตามประเภทผู้ใช้
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

if ($user_type == 'company') {
    $check_company_query = "SELECT is_verified FROM companies WHERE company_id = :company_id";
    $check_stmt = $conn->prepare($check_company_query);
    $check_stmt->bindValue(':company_id', $user_id);
    $check_stmt->execute();
    $company_status = $check_stmt->fetch(PDO::FETCH_ASSOC);

    // ถ้าบริษัทยังไม่ได้รับการอนุมัติ
    if (!$company_status || $company_status['is_verified'] != 1) {
        $status_message = "";
        if (!$company_status) {
            $status_message = "ไม่พบข้อมูลบริษัทของคุณ";
        } else if ($company_status['is_verified'] == 0) {
            $status_message = "บริษัทของคุณอยู่ในสถานะรอการพิจารณา";
        } else if ($company_status['is_verified'] == 2) {
            $status_message = "บริษัทของคุณไม่ผ่านการพิจารณา";
        }
?>
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle"></i> ไม่สามารถโพสต์งานได้</h4>
            <p><?php echo $status_message; ?> กรุณาติดต่อผู้ดูแลระบบเพื่อดำเนินการต่อไป</p>
        </div>
<?php
        // ซ่อนปุ่มเพิ่มงานใหม่
        $can_post_job = false;
    } else {
        // บริษัทได้รับการอนุมัติแล้ว
        $can_post_job = true;
    }
} else {
    $can_post_job = false;
}
// ดึงข้อมูลประเภทงานเพื่อใช้ในการกรอง
$categories_query = "SELECT DISTINCT job_category FROM jobs_posts WHERE job_category IS NOT NULL";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// ดึงค่าการค้นหาและกรอง
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0; // เพิ่มการรับค่า company_id

// ถ้ามีการกรองตาม company_id ให้ดึงข้อมูลบริษัทมาแสดง
$company_name = '';
if ($company_id > 0) {
    $company_query = "SELECT company_name FROM companies WHERE company_id = :company_id";
    $company_stmt = $conn->prepare($company_query);
    $company_stmt->bindValue(':company_id', $company_id);
    $company_stmt->execute();
    $company_data = $company_stmt->fetch(PDO::FETCH_ASSOC);
    if ($company_data) {
        $company_name = $company_data['company_name'];
    }
}

// สร้าง query ตามประเภทผู้ใช้และการกรอง
$query = "";
$params = [];

if ($user_type == 'company') {
    // บริษัทจะเห็นเฉพาะงานของตัวเอง
    $query = "SELECT * FROM jobs_posts WHERE company_id = :user_id";
    $params[':user_id'] = $user_id;

    // เพิ่มกรองตามประเภทงาน (ถ้ามี)
    if (!empty($selected_category)) {
        $query .= " AND job_category = :category";
        $params[':category'] = $selected_category;
    }

    // เพิ่มการค้นหาด้วย keyword (ถ้ามี)
    if (!empty($keyword)) {
        $query .= " AND (job_title LIKE :keyword OR job_description LIKE :keyword OR location LIKE :keyword)";
        $params[':keyword'] = "%$keyword%";
    }
} else {
    // แอดมินและนักศึกษา
    $query = "SELECT jp.*, c.company_name 
          FROM jobs_posts jp 
          JOIN companies c ON jp.company_id = c.company_id
          WHERE c.is_verified = 1";

    // กรองตาม company_id ถ้ามีการระบุ
    if ($company_id > 0) {
        $query .= " AND jp.company_id = :company_id";
        $params[':company_id'] = $company_id;
    }

    // นักศึกษาจะเห็นเฉพาะงานที่เปิดรับสมัคร
    if ($user_type == 'student') {
        $query .= " AND jp.is_active = 1 AND jp.expire_date >= CURDATE()";
    }

    // เพิ่มกรองตามประเภทงาน (ถ้ามี)
    if (!empty($selected_category)) {
        $query .= " AND jp.job_category = :category";
        $params[':category'] = $selected_category;
    }

    // เพิ่มการค้นหาด้วย keyword (ถ้ามี)
    if (!empty($keyword)) {
        $query .= " AND (jp.job_title LIKE :keyword OR jp.job_description LIKE :keyword 
                   OR jp.location LIKE :keyword OR c.company_name LIKE :keyword)";
        $params[':keyword'] = "%$keyword%";
    }
}

// นักศึกษาจะเห็นเฉพาะงานที่เปิดรับสมัคร
if ($user_type == 'student') {
    $query .= " AND jp.is_active = 1 AND jp.expire_date >= CURDATE()";
}

// เพิ่มกรองตามประเภทงาน (ถ้ามี)
if (!empty($selected_category)) {
    $query .= " AND jp.job_category = :category";
    $params[':category'] = $selected_category;
}

// เพิ่มการค้นหาด้วย keyword (ถ้ามี)
if (!empty($keyword)) {
    $query .= " AND (jp.job_title LIKE :keyword OR jp.job_description LIKE :keyword 
                   OR jp.location LIKE :keyword OR c.company_name LIKE :keyword)";
    $params[':keyword'] = "%$keyword%";
}


// ทำการ execute query และดึงข้อมูลทั้งหมด
$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนดฟังก์ชันสำหรับแสดงสถานะงาน
function getStatusBadge($status)
{
    switch (strtolower($status)) {
        case 'active':
            return '<span class="badge bg-success">เปิดรับสมัคร</span>';
        case 'expired':
            return '<span class="badge bg-warning">หมดเวลารับสมัคร</span>';
        case 'inactive':
            return '<span class="badge bg-danger">ปิดรับสมัคร</span>';
        default:
            return '<span class="badge bg-secondary">ไม่ระบุ</span>';
    }
}
?>

<div class="container-fluid">

    <h1 class="mt-4">
        <?php if ($user_type == 'company'): ?>
            จัดการงาน
        <?php elseif ($user_type == 'student'): ?>
            งานทั้งหมด
        <?php else: ?>
            ข้อมูลการจัดการงาน
        <?php endif; ?>
        <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
    </h1>

    <!-- การค้นหาและกรอง -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="keyword" class="form-label">คำค้นหา</label>
                    <input type="text" class="form-control" id="keyword" name="keyword"
                        placeholder="ชื่องาน, รายละเอียด, สถานที่" value="<?php echo htmlspecialchars($keyword); ?>">
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">ประเภทงาน</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"
                                <?php echo ($selected_category == $category) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">ค้นหา</button>
                    <a href="index.php" class="btn btn-danger   ">ล้างการค้นหา</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($selected_category) || !empty($keyword) || !empty($company_name)): ?>
        <div class="alert alert-info">
            <?php if (!empty($company_name)): ?>
                <strong>บริษัท:</strong> <?php echo htmlspecialchars($company_name); ?>
            <?php endif; ?>

            <?php if (!empty($selected_category)): ?>
                <?php if (!empty($company_name)): ?> | <?php endif; ?>
                <strong>ประเภทงาน:</strong> <?php echo htmlspecialchars($selected_category); ?>
            <?php endif; ?>

            <?php if (!empty($keyword)): ?>
                <?php if (!empty($company_name) || !empty($selected_category)): ?> | <?php endif; ?>
                <strong>คำค้นหา:</strong> <?php echo htmlspecialchars($keyword); ?>
            <?php endif; ?>

            <a href="index.php" class="float-end"><i class="fas fa-times"></i> ล้างตัวกรอง</a>
        </div>
    <?php endif; ?>

    <?php if ($user_type == 'company' && $can_post_job): ?>
        <div class="mb-4">
            <div class="btn-group" role="group">
                <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                    <i class="me-1"></i> หน้าหลัก
                </a>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> เพิ่มงานใหม่
                </a>
            </div>
        </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            รายการงาน
            <?php if (!empty($result)): ?>
                <span class="badge bg-primary"><?php echo count($result); ?> รายการ</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="jobsDataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <?php if ($user_type != 'company'): ?>
                                <th>บริษัท</th>
                            <?php endif; ?>
                            <th>ตำแหน่งงาน</th>
                            <th>รายละเอียด</th>
                            <th>ประเภทงาน</th>
                            <th>เงินเดือน (บาท)</th>
                            <th>วันที่ประกาศ</th>
                            <th>วันหมดอายุ</th>
                            <th>สถานะ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($result as $row):
                            $is_expired = strtotime($row['expire_date']) < strtotime(date('Y-m-d'));
                            $status_class = '';
                            $status_text = '';

                            if ($row['is_active'] == 1) {
                                if (!$is_expired) {
                                    $status_class = 'success';
                                    $status_text = 'เปิดรับสมัคร';
                                } else {
                                    $status_class = 'warning';
                                    $status_text = 'หมดเวลารับสมัคร';
                                }
                            } else {
                                $status_class = 'danger';
                                $status_text = 'ปิดรับสมัคร';
                            }

                        ?>

                            <tr>
                                <td><?php echo $i++; ?></td>
                                <?php if ($user_type != 'company'): ?>
                                    <td><?php echo htmlspecialchars($row['company_name'] ?? ''); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['job_description']); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($row['job_category'] ?? 'อื่นๆ'); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($row['min_salary'], 2) . ' - ' . number_format($row['max_salary'], 2); ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['post_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['expire_date'])); ?></td>
                                <td><?php echo getStatusBadge($row['is_active'] ? ($is_expired ? 'expired' : 'active') : 'inactive'); ?>
                                </td>
                                <td class="text-center">
                                    <a href="view.php?id=<?php echo $row['post_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($user_type == 'company' && $row['company_id'] == $user_id): ?>
                                    <a href="edit.php?id=<?php echo $row['post_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $row['post_id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($result) == 0): ?>
                            <tr>
                                <td colspan="<?php echo ($user_type != 'company') ? '10' : '9'; ?>" class="text-center">
                                    ไม่พบข้อมูล</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#jobsDataTable').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            }
        });
    });

    function confirmDelete(postId) {
        Swal.fire({
            title: 'ข้อความจากเว็ปไซต์!',
            text: 'คุณแน่ใจหรือไม่ที่จะลบข้อมูลนี้?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${postId}`;
            }
        });
    }
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>