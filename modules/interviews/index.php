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

// ตรวจสอบสิทธิ์การเข้าถึง
if (!$auth->isCompany() && !$auth->isAdmin() && !$auth->isStudent()) {
    header("Location: ../../index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// เตรียมส่วนหัวของหน้า
$pageTitle = "จัดการการสัมภาษณ์";
include(BASE_PATH . '/layouts/header.php');

// Fetch interviews
try {
    if ($auth->isCompany()) {
        $sql = "SELECT i.*, a.application_id, s.first_name, s.last_name, s.email, s.phone,
                j.job_title, c.company_name
                FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                JOIN jobs_posts j ON a.post_id = j.post_id
                JOIN students s ON a.student_id = s.student_id
                JOIN companies c ON j.company_id = c.company_id
                WHERE j.company_id = ?
                ORDER BY i.interview_date DESC, i.interview_time DESC";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$user_id]);
    } elseif ($auth->isStudent()) {
        $sql = "SELECT i.*, a.application_id, s.first_name, s.last_name, s.email, s.phone,
                j.job_title, c.company_name
                FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                JOIN jobs_posts j ON a.post_id = j.post_id
                JOIN students s ON a.student_id = s.student_id
                JOIN companies c ON j.company_id = c.company_id
                WHERE a.student_id = ?
                ORDER BY i.interview_date DESC, i.interview_time DESC";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $sql = "SELECT i.*, a.application_id, s.first_name, s.last_name, s.email, s.phone,
                j.job_title, c.company_name
                FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                JOIN jobs_posts j ON a.post_id = j.post_id
                JOIN students s ON a.student_id = s.student_id
                JOIN companies c ON j.company_id = c.company_id
                ORDER BY i.interview_date DESC, i.interview_time DESC";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute();
    }
    
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
    $interviews = [];
}

// Calculate interview statistics by status
$all_interviews = count($interviews);
$scheduled_count = count(array_filter($interviews, function($interview) { return $interview['status'] === 'scheduled'; }));
$completed_count = count(array_filter($interviews, function($interview) { return $interview['status'] === 'completed'; }));
$canceled_count = count(array_filter($interviews, function($interview) { return $interview['status'] === 'canceled'; }));
$rescheduled_count = count(array_filter($interviews, function($interview) { return $interview['status'] === 'rescheduled'; }));
$pending_count = count(array_filter($interviews, function($interview) { return $interview['status'] === 'pending' || empty($interview['status']); }));
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">จัดการการสัมภาษณ์</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <?php if ($auth->isStudent()): ?>
                            <a href="../../index.php">หน้าหลัก</a>
                            <?php else: ?>
                            <a href="../../dashboard.php">หน้าหลัก</a>
                            <?php endif; ?>
                        </li>
                        <li class="breadcrumb-item active">การสัมภาษณ์</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- Interview Statistics -->
            <?php if ($auth->isAdmin()): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-chart-bar me-1"></i>
                            สรุปสถิติการนัดสัมภาษณ์
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="card text-center bg-light mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $all_interviews; ?></h5>
                                            <p class="card-text">ทั้งหมด</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center bg-primary mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $scheduled_count; ?></h5>
                                            <p class="card-text">กำหนดการแล้ว</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center bg-success mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $completed_count; ?></h5>
                                            <p class="card-text">เสร็จสิ้น</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center bg-danger mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $canceled_count; ?></h5>
                                            <p class="card-text">ยกเลิก</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center bg-warning mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $rescheduled_count; ?></h5>
                                            <p class="card-text">เลื่อนออกไป</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="card text-center bg-secondary bg-opacity-25 mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $pending_count; ?></h5>
                                            <p class="card-text">รอดำเนินการ</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">รายการการสัมภาษณ์</h3>
                                <div class="card-tools">
                                    <?php if ($auth->isCompany()): ?>
                                    <a href="add.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> เพิ่มการสัมภาษณ์
                                    </a>
                                    <?php endif; ?>
                                    <!-- Add Export Buttons -->
                                    <?php if ($auth->isAdmin()): ?>
                                    <button type="button" class="btn btn-success btn-sm ms-2" onclick="exportToExcel()">
                                        <i class="fas fa-file-excel me-1"></i> Excel
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm ms-2" onclick="exportToPDF()">
                                        <i class="fas fa-file-pdf me-1"></i> PDF
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if (count($interviews) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>รหัส</th>
                                            <th>ตำแหน่งงาน</th>
                                            <th>ผู้สมัคร</th>
                                            <th>วันที่สัมภาษณ์</th>
                                            <th>เวลา</th>
                                            <th>ประเภท</th>
                                            <th>สถานะ</th>
                                            <th>การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($interviews as $interview): ?>
                                        <tr>
                                            <td><?php echo $interview['interview_id']; ?></td>
                                            <td><?php echo htmlspecialchars($interview['job_title']); ?></td>
                                            <td><?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($interview['interview_date'])); ?>
                                            </td>
                                            <td><?php echo $interview['interview_time'] ? date('H:i', strtotime($interview['interview_time'])) : '-'; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($interview['interview_type']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                switch($interview['status']) {
                                                    case 'scheduled':
                                                        $statusText = 'กำหนดการแล้ว';
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
                                                        $statusClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span
                                                    class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $interview['interview_id']; ?>"
                                                    class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> รายละเอียด
                                                </a>
                                                <?php if ($auth->isCompany()): ?>
                                                <a href="edit.php?id=<?php echo $interview['interview_id']; ?>"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </a>
                                                <a href="javascript:void(0)"
                                                    onclick="confirmDelete(<?php echo $interview['interview_id']; ?>)"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> ลบ
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">ไม่พบข้อมูลการสัมภาษณ์</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden table for export -->
            <div id="exportTable" style="display:none; font-family: 'THSarabunNew', Arial, sans-serif;">
                <h2 style="font-family: 'THSarabunNew', Arial, sans-serif;"><?php echo SITE_NAME; ?> -
                    รายงานข้อมูลการสัมภาษณ์</h2>
                <p style="font-family: 'THSarabunNew', Arial, sans-serif;">วันที่ออกรายงาน:
                    <?php echo date('d/m/Y H:i'); ?></p>
                <table style="border-collapse: collapse; width: 100%;">
                    <thead style="background-color: #f2f2f2;">
                        <tr>
                            <th style="border: 1px solid #000; padding: 8px;">รหัส</th>
                            <th style="border: 1px solid #000; padding: 8px;">ตำแหน่งงาน</th>
                            <th style="border: 1px solid #000; padding: 8px;">ผู้สมัคร</th>
                            <th style="border: 1px solid #000; padding: 8px;">วันที่สัมภาษณ์</th>
                            <th style="border: 1px solid #000; padding: 8px;">เวลา</th>
                            <th style="border: 1px solid #000; padding: 8px;">ประเภท</th>
                            <th style="border: 1px solid #000; padding: 8px;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interviews as $interview): ?>
                        <tr>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo htmlspecialchars($interview['interview_id']); ?></td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo htmlspecialchars($interview['job_title']); ?></td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo htmlspecialchars($interview['first_name'] . ' ' . $interview['last_name']); ?>
                            </td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo date('d/m/Y', strtotime($interview['interview_date'])); ?></td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo $interview['interview_time'] ? date('H:i', strtotime($interview['interview_time'])) : '-'; ?>
                            </td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php echo htmlspecialchars($interview['interview_type']); ?></td>
                            <td style="border: 1px solid #000; padding: 8px;">
                                <?php
                                switch($interview['status']) {
                                    case 'scheduled': echo 'กำหนดการแล้ว'; break;
                                    case 'completed': echo 'เสร็จสิ้น'; break;
                                    case 'canceled': echo 'ยกเลิก'; break;
                                    case 'rescheduled': echo 'เลื่อนออกไป'; break;
                                    default: echo 'รอดำเนินการ';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Export to Excel
function exportToExcel() {
    Swal.fire({
        title: 'กำลังส่งออกข้อมูล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js';
    script.onload = function() {
        const table = document.getElementById('exportTable');
        const ws = XLSX.utils.table_to_sheet(table, {
            raw: true
        });
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Interviews");
        const fileName = 'interviews_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
        XLSX.writeFile(wb, fileName);
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'ส่งออกเสร็จสิ้น',
            text: 'ส่งออกข้อมูลเป็นไฟล์ Excel เรียบร้อยแล้ว',
            timer: 2000,
            showConfirmButton: false
        });
    };
    script.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลด SheetJS library ได้'
        });
    };
    document.body.appendChild(script);
}

// Export to PDF
function exportToPDF() {
    Swal.fire({
        title: 'กำลังส่งออกข้อมูล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get the export table
    const element = document.getElementById('exportTable');

    // Create a temporary container for rendering
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.left = '-9999px';
    tempContainer.style.fontFamily = 'THSarabunNew, Arial, sans-serif';
    tempContainer.innerHTML = element.outerHTML;
    document.body.appendChild(tempContainer);

    // Force a repaint to ensure rendering
    tempContainer.offsetHeight;

    // Configure html2pdf options
    const opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: 'interviews_report_' + new Date().toISOString().split('T')[0] + '.pdf',
        image: {
            type: 'jpeg',
            quality: 0.98
        },
        html2canvas: {
            scale: 2,
            useCORS: true,
            logging: true,
            windowWidth: 1200
        },
        jsPDF: {
            unit: 'in',
            format: 'letter',
            orientation: 'portrait'
        }
    };

    // Add Thai font support
    const addThaiFont = (pdf) => {
        if (window.thaiFont && window.thaiFont.data) {
            pdf.addFileToVFS('THSarabunNew.ttf', window.thaiFont.data);
            pdf.addFont('THSarabunNew.ttf', 'THSarabunNew', 'normal');
            pdf.setFont('THSarabunNew');
        } else {
            console.warn('Thai font not loaded, falling back to default');
        }
    };

    // Generate PDF with a slight delay to ensure rendering
    setTimeout(() => {
        html2pdf().from(tempContainer).set(opt).toPdf().get('pdf').then((pdf) => {
            addThaiFont(pdf);
            pdf.setFontSize(12);
        }).save().then(() => {
            // Clean up
            document.body.removeChild(tempContainer);
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'ส่งออกเสร็จสิ้น',
                text: 'ส่งออกข้อมูลเป็นไฟล์ PDF เรียบร้อยแล้ว',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            // Clean up on error
            document.body.removeChild(tempContainer);
            console.error('PDF Export Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถส่งออกเป็น PDF ได้: ' + err.message
            });
        });
    }, 100);
}

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

<?php include '../../layouts/footer.php'; ?>