<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
error_reporting(E_ALL);
// นำเข้าไฟล์ที่จำเป็น
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// สร้างอ็อบเจ็กต์ฐานข้อมูลและการยืนยันตัวตน
$db = new Database();
$auth = new Auth($db->getConnection());

// ตรวจสอบการเชื่อมต่อกับฐานข้อมูล
if (!$db->getConnection()) {
    die("ไม่สามารถเชื่อมต่อกับฐานข้อมูลได้");
}
// ตรวจสอบการล็อกอิน
$auth->requireLogin();

// ตรวจสอบสิทธิ์การเข้าถึง
if (!$auth->isCompany() && !$auth->isAdmin() && !$auth->isStudent()) {
    header("Location: ../../index.php");
    exit;
}



// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// ตรวจสอบว่ามีพารามิเตอร์ ID หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ไม่พบรหัสการสัมภาษณ์";
    header("Location: index.php");
    exit;
}

$interview_id = intval($_GET['id']);

try {
    // ดึงข้อมูลการสัมภาษณ์พร้อมข้อมูลที่เกี่ยวข้อง
    $sql = "SELECT i.*, a.application_id, a.post_id, a.student_id, a.apply_date, a.status as app_status,
    a.cover_letter, a.expected_salary, a.available_start_date, a.available_hours, a.message,
    s.first_name, s.last_name, s.email, s.phone, s.faculty_name, s.major_name, s.year, s.gpa, s.skill, s.experience,
    j.job_title, j.job_description, j.positions, j.min_salary, j.max_salary, j.requirement, j.location,
    c.company_id, c.company_name, c.contact_person, c.contact_email, c.contact_phone, c.address
    FROM interviews i
    JOIN applications a ON i.application_id = a.application_id
    JOIN jobs_posts j ON a.post_id = j.post_id
    JOIN students s ON a.student_id = s.student_id
    JOIN companies c ON j.company_id = c.company_id
    WHERE i.interview_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute([$interview_id]);;
    $interview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่าพบข้อมูลหรือไม่
    if (!$interview) {
        throw new Exception("ไม่พบข้อมูลการสัมภาษณ์");
    }
    
    // ตรวจสอบสิทธิ์การเข้าถึง (กรณีเป็นบริษัท)
    if ($auth->isCompany() && $interview['company_id'] != $user_id) {
        throw new Exception("คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้");
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: index.php");
    exit;
}

// เตรียมส่วนหัวของหน้า
$pageTitle = "รายละเอียดการสัมภาษณ์";
include '../../layouts/header.php';

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">รายละเอียดการสัมภาษณ์</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">หน้าหลัก</a></li>
                        <li class="breadcrumb-item"><a href="index.php">การสัมภาษณ์</a></li>
                        <li class="breadcrumb-item active">รายละเอียด</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                    ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
            </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-12">
                    <!-- ข้อมูลการสัมภาษณ์ -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">ข้อมูลการสัมภาษณ์</h3>
                            <div class="card-tools">
                                <?php if ($auth->isCompany()): ?>
                                <a href="edit.php?id=<?php echo $interview_id; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> แก้ไข
                                </a>
                                <?php endif; ?>
                                <a href="index.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-left"></i> กลับ
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">รหัสการสัมภาษณ์</th>
                                            <td><?php echo $interview['interview_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>วันที่สัมภาษณ์</th>
                                            <td><?php echo date('d/m/Y', strtotime($interview['interview_date'])); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>เวลา</th>
                                            <td><?php echo $interview['interview_time'] ? date('H:i', strtotime($interview['interview_time'])) : '-'; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ประเภทการสัมภาษณ์</th>
                                            <td>
                                                <?php 
                                                $type = $interview['interview_type'];
                                                switch($type) {
                                                    case 'in-person':
                                                        echo 'สัมภาษณ์แบบพบหน้า';
                                                        break;
                                                    case 'phone':
                                                        echo 'สัมภาษณ์ทางโทรศัพท์';
                                                        break;
                                                    case 'video':
                                                        echo 'สัมภาษณ์ทางวิดีโอ';
                                                        break;
                                                    case 'group':
                                                        echo 'สัมภาษณ์แบบกลุ่ม';
                                                        break;
                                                    default:
                                                        echo $type;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>สถานที่/ช่องทางการสัมภาษณ์</th>
                                            <td><?php echo htmlspecialchars($interview['interview_location'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>สถานะ</th>
                                            <td>
                                                <?php 
                                                $status = $interview['status'];
                                                $statusText = '';
                                                $statusClass = '';
                                                
                                                switch($status) {
                                                    case 'scheduled':
                                                        $statusText = 'อยู่ระหว่างสัมภาษณ์';
                                                        $statusClass = 'bg-primary';
                                                        break;
                                                    case 'completed':
                                                        $statusText = 'เสร็จสิ้น';
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'canceled':
                                                        $statusText = 'ยกเลิก';
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    case 'rescheduled':
                                                        $statusText = 'เลื่อนออกไป';
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                    default:
                                                        $statusText = 'รอดำเนินการ';
                                                        $statusClass = 'bg-warning';
                                                }
                                                ?>
                                                <span
                                                    class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">ตำแหน่งงาน</th>
                                            <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>บริษัท</th>
                                            <td><?php echo htmlspecialchars($interview['company_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ผู้สมัคร</th>
                                            <td><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>อีเมลผู้สมัคร</th>
                                            <td><?php echo htmlspecialchars($interview['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>เบอร์โทรผู้สมัคร</th>
                                            <td><?php echo htmlspecialchars($interview['phone']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>สถานะใบสมัคร</th>
                                            <td>
                                                <?php 
                                                $appStatus = $interview['app_status'];
                                                $appStatusText = '';
                                                $appStatusClass = '';
                                                
                                                switch($appStatus) {
                                                    case 'pending':
                                                        $appStatusText = 'รอตรวจสอบ';
                                                        $appStatusClass = 'bg-secondary';
                                                        break;
                                                    case 'reviewing':
                                                        $appStatusText = 'กำลังตรวจสอบ';
                                                        $appStatusClass = 'bg-info';
                                                        break;
                                                    case 'interview':
                                                        $appStatusText = 'นัดสัมภาษณ์';
                                                        $appStatusClass = 'bg-primary';
                                                        break;
                                                    case 'accepted':
                                                        $appStatusText = 'ผ่านการคัดเลือก';
                                                        $appStatusClass = 'bg-success';
                                                        break;
                                                    case 'rejected':
                                                        $appStatusText = 'ไม่ผ่านการคัดเลือก';
                                                        $appStatusClass = 'bg-danger';
                                                        break;
                                                        case 'available':
                                                            $appStatusText = 'จบการทำงาน';
                                                            $appStatusClass = 'bg-danger';
                                                            break;
                                                    default:
                                                        $appStatusText = $appStatus;
                                                        $appStatusClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span
                                                    class="badge <?php echo $appStatusClass; ?>"><?php echo $appStatusText; ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($interview['interview_notes'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">บันทึกเพิ่มเติม</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['interview_notes'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- รายละเอียดการสมัคร -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">รายละเอียดการสมัคร</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>ข้อมูลนักศึกษา</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">รหัสนักศึกษา</th>
                                            <td><?php echo $interview['student_id']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>ชื่อ-นามสกุล</th>
                                            <td><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>อีเมล</th>
                                            <td><?php echo htmlspecialchars($interview['email']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>เบอร์โทร</th>
                                            <td><?php echo htmlspecialchars($interview['phone']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>คณะ/ภาควิชา</th>
                                            <td>
                                                <?php 
                                                // หากต้องการแสดงชื่อคณะและสาขา จะต้องดึงข้อมูลเพิ่มเติม
                                                echo "คณะ: " . $interview['faculty_name'] . " / สาขา: " . $interview['major_name'];
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ชั้นปี</th>
                                            <td><?php echo $interview['year']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>เกรดเฉลี่ย</th>
                                            <td><?php echo $interview['gpa']; ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h5>ข้อมูลการสมัคร</h5>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">วันที่สมัคร</th>
                                            <td><?php echo date('d/m/Y', strtotime($interview['apply_date'])); ?></td>
                                        </tr>
                                        <tr>
                                            <th>เงินเดือนที่คาดหวัง</th>
                                            <td><?php echo number_format($interview['expected_salary']); ?> บาท</td>
                                        </tr>
                                        <tr>
                                            <th>วันที่สามารถเริ่มงานได้</th>
                                            <td><?php echo $interview['available_start_date'] ? date('d/m/Y', strtotime($interview['available_start_date'])) : '-'; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>ชั่วโมงทำงานที่สะดวก</th>
                                            <td><?php echo htmlspecialchars($interview['available_hours'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($interview['cover_letter'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">จดหมายปะหน้า</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['cover_letter'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($interview['message'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">ข้อความเพิ่มเติม</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($interview['skill']) || !empty($interview['experience'])): ?>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <?php if (!empty($interview['skill'])): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">ทักษะ</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['skill'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if (!empty($interview['experience'])): ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">ประสบการณ์</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['experience'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- รายละเอียดตำแหน่งงาน -->
                    <div class="card collapsed-card">
                        <!-- <div class="card-header">
                            <h3 class="card-title">รายละเอียดตำแหน่งงาน</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div> -->
                        <div class="card-body" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">ตำแหน่งงาน</th>
                                            <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>จำนวนตำแหน่ง</th>
                                            <td><?php echo $interview['positions']; ?></td>
                                        </tr>
                                        <tr>
                                            <th>เงินเดือน</th>
                                            <td>
                                                <?php
                                                if (!empty($interview['min_salary']) && !empty($interview['max_salary'])) {
                                                    echo number_format($interview['min_salary']) . ' - ' . number_format($interview['max_salary']) . ' บาท';
                                                } elseif (!empty($interview['min_salary'])) {
                                                    echo 'ตั้งแต่ ' . number_format($interview['min_salary']) . ' บาท';
                                                } elseif (!empty($interview['max_salary'])) {
                                                    echo 'ไม่เกิน ' . number_format($interview['max_salary']) . ' บาท';
                                                } else {
                                                    echo 'ไม่ระบุ';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>สถานที่ปฏิบัติงาน</th>
                                            <td><?php echo htmlspecialchars($interview['location'] ?? '-'); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 30%">บริษัท</th>
                                            <td><?php echo htmlspecialchars($interview['company_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>ผู้ติดต่อ</th>
                                            <td><?php echo htmlspecialchars($interview['contact_person'] ?? '-'); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>อีเมลติดต่อ</th>
                                            <td><?php echo htmlspecialchars($interview['contact_email'] ?? '-'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>เบอร์โทรติดต่อ</th>
                                            <td><?php echo htmlspecialchars($interview['contact_phone'] ?? '-'); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($interview['job_description'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">รายละเอียดงาน</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['job_description'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($interview['requirement'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="card-title">คุณสมบัติที่ต้องการ</h4>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(htmlspecialchars($interview['requirement'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ปุ่มการดำเนินการ -->
                    <?php if ($auth->isCompany()): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">การดำเนินการ</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="edit.php?id=<?php echo $interview_id; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> แก้ไขข้อมูลการสัมภาษณ์
                                    </a>
                                    <a href="../applications/view.php?id=<?php echo $interview['application_id']; ?>"
                                        class="btn btn-info">
                                        <i class="fas fa-file-alt"></i> ดูใบสมัคร
                                    </a>

                                    <!-- เพิ่มปุ่มผ่านการคัดเลือก/ไม่ผ่านการคัดเลือก -->
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#acceptModal">ผ่านการคัดเลือก</button>
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#rejectModal">ไม่ผ่านการคัดเลือก</button>

                                    <a href="delete.php?id=<?php echo $interview_id; ?>" class="btn btn-danger"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบการสัมภาษณ์นี้?');">
                                        <i class="fas fa-trash"></i> ลบการสัมภาษณ์
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Modal ผ่านการคัดเลือก -->
                    <div class="modal fade" id="acceptModal" tabindex="-1" aria-labelledby="acceptModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="acceptModalLabel">ยืนยันการผ่านการคัดเลือก</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form action="<?php echo ROOT_URL; ?>/modules/applications/update_status.php"
                                    method="post">
                                    <div class="modal-body">
                                        <p>คุณต้องการยืนยันว่าผู้สมัครนี้
                                            <strong><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></strong>
                                            ผ่านการคัดเลือกใช่หรือไม่?
                                        </p>
                                        <div class="form-group">
                                            <label for="accept-comment">ข้อความแจ้งผู้สมัคร (ไม่บังคับ)</label>
                                            <textarea class="form-control" id="accept-comment" name="comment"
                                                rows="3"></textarea>
                                        </div>
                                        <input type="hidden" name="application_id" value="<?php echo $interview['application_id']; ?>">
<input type="hidden" name="interview_id" value="<?php echo $interview['interview_id']; ?>">
<input type="hidden" name="post_id" value="<?php echo $interview['post_id']; ?>">
<input type="hidden" name="status" value="accepted">
<input type="hidden" name="redirect"value="<?php echo ROOT_URL; ?>/modules/interviews/view.php?id=<?php echo $interview['interview_id']; ?>">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-success">ยืนยัน</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal ไม่ผ่านการคัดเลือก -->
                    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="rejectModalLabel">ยืนยันการไม่ผ่านการคัดเลือก</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <form action="<?php echo ROOT_URL; ?>/modules/applications/update_status.php"
                                    method="post">
                                    <div class="modal-body">
                                        <p>คุณต้องการยืนยันว่าผู้สมัครนี้
                                            <strong><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?></strong>
                                            ไม่ผ่านการคัดเลือกใช่หรือไม่?
                                        </p>
                                        <div class="form-group">
                                            <label for="reject-comment">ข้อความแจ้งผู้สมัคร (ไม่บังคับ)</label>
                                            <textarea class="form-control" id="reject-comment" name="comment"
                                                rows="3"></textarea>
                                        </div>
                                        <input type="hidden" name="application_id" value="<?php echo $interview['application_id']; ?>">
<input type="hidden" name="interview_id" value="<?php echo $interview['interview_id']; ?>">
<input type="hidden" name="post_id" value="<?php echo $interview['post_id']; ?>">
<input type="hidden" name="status" value="accepted">
                                        <input type="hidden" name="redirect"
                                            value="<?php echo ROOT_URL; ?>/modules/interviews/view.php?id=<?php echo $interview['interview_id']; ?>">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">ยกเลิก</button>
                                        <button type="submit" class="btn btn-danger">ยืนยัน</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>