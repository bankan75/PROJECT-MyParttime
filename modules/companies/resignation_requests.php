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
if (!$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("Location: " . ROOT_URL . "/index.php");
    exit;
}

$company_id = $_SESSION['user_id'];

// เตรียมส่วนหัวของหน้า
$pageTitle = "จัดการคำร้องขอลาออก";
include(BASE_PATH . '/layouts/header.php');

// ดำเนินการอนุมัติหรือปฏิเสธคำร้อง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['request_action'];
    $comment = $_POST['comment'] ?? '';
    
    try {
        // ตรวจสอบว่าคำร้องนี้เป็นของบริษัทนี้หรือไม่
        $check_query = "SELECT r.*, a.student_id, a.status AS application_status
                      FROM resignation_requests r
                      JOIN applications a ON r.application_id = a.application_id
                      JOIN jobs_posts j ON a.post_id = j.post_id
                      WHERE r.request_id = ? AND j.company_id = ? AND r.status = 'pending'";
        $stmt = $db->getConnection()->prepare($check_query);
        $stmt->execute([$request_id, $company_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $_SESSION['error_message'] = "ไม่พบคำร้องขอลาออกหรือคุณไม่มีสิทธิ์ดำเนินการนี้";
        } else {
            // อัปเดตสถานะคำร้อง
            $new_status = ($action === 'approve') ? 'approved' : 'rejected';
            $update_query = "UPDATE resignation_requests 
                           SET status = ?, comment = ?, processed_date = NOW() 
                           WHERE request_id = ?";
            $stmt = $db->getConnection()->prepare($update_query);
            $stmt->execute([$new_status, $comment, $request_id]);
            
            // ถ้าอนุมัติ ให้อัปเดตสถานะการสมัครงานเป็น available
if ($action === 'approve') {
    $update_app_query = "UPDATE applications 
                        SET status = 'available', updated_at = NOW() 
                        WHERE application_id = ?";
    $stmt = $db->getConnection()->prepare($update_app_query);
    $stmt->execute([$request['application_id']]);
                
                $_SESSION['success_message'] = "อนุมัติคำร้องขอลาออกเรียบร้อยแล้ว";
            } else {
                $_SESSION['success_message'] = "ปฏิเสธคำร้องขอลาออกเรียบร้อยแล้ว";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// ดึงรายการคำร้องขอลาออกที่รออนุมัติ
$requests = [];
try {
    $query = "SELECT r.*, s.first_name, s.last_name, s.student_code, j.job_title, a.status AS application_status
              FROM resignation_requests r
              JOIN applications a ON r.application_id = a.application_id
              JOIN students s ON r.student_id = s.student_id
              JOIN jobs_posts j ON a.post_id = j.post_id
              WHERE j.company_id = ?
              ORDER BY r.submit_date DESC";
    $stmt = $db->getConnection()->prepare($query);
    $stmt->execute([$company_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">จัดการคำร้องขอลาออก</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard.php">หน้าหลัก</a></li>
                        <li class="breadcrumb-item active">คำร้องขอลาออก</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">รายการคำร้องขอลาออก</h3>
                        </div>

                        <div class="card-body">
                            <?php if (count($requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>รหัสคำร้อง</th>
                                            <th>นักศึกษา</th>
                                            <th>ตำแหน่งงาน</th>
                                            <th>วันที่ยื่นคำร้อง</th>
                                            <th>วันที่ต้องการลาออก</th>
                                            <th>สถานะ</th>
                                            <th>การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo $request['request_id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?><br>
                                                <small class="text-muted"><?php echo $request['student_code']; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($request['job_title']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($request['submit_date'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($request['resignation_date'])); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                $status_text = '';
                                                switch ($request['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-warning';
                                                        $status_text = 'รออนุมัติ';
                                                        break;
                                                    case 'approved':
                                                        $status_class = 'bg-success';
                                                        $status_text = 'อนุมัติแล้ว';
                                                        break;
                                                    case 'rejected':
                                                        $status_class = 'bg-danger';
                                                        $status_text = 'ปฏิเสธแล้ว';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($request['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-primary btn-sm mb-1" data-bs-toggle="modal"
                                                    data-bs-target="#viewRequestModal<?php echo $request['request_id']; ?>">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </button>
                                                <?php else: ?>
                                                <button type="button" class="btn btn-info btn-sm mb-1" data-bs-toggle="modal"
                                                    data-bs-target="#viewRequestModal<?php echo $request['request_id']; ?>">
                                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal ดูรายละเอียดคำร้อง -->
                                        <div class="modal fade" id="viewRequestModal<?php echo $request['request_id']; ?>" tabindex="-1"
                                            aria-labelledby="viewRequestModalLabel<?php echo $request['request_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header <?php echo ($request['status'] === 'pending') ? 'bg-primary' : (($request['status'] === 'approved') ? 'bg-success' : 'bg-danger'); ?>">
                                                        <h5 class="modal-title text-white" id="viewRequestModalLabel<?php echo $request['request_id']; ?>">
                                                            รายละเอียดคำร้องขอลาออก #<?php echo $request['request_id']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h5>ข้อมูลนักศึกษา</h5>
                                                        <p>ชื่อ-นามสกุล: <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?><br>
                                                            รหัสนักศึกษา: <?php echo $request['student_code']; ?></p>
                                                        
                                                        <h5>ข้อมูลงาน</h5>
                                                        <p>ตำแหน่ง: <?php echo htmlspecialchars($request['job_title']); ?></p>
                                                        
                                                        <h5>รายละเอียดการลาออก</h5>
                                                        <p>วันที่ต้องการลาออก: <?php echo date('d/m/Y', strtotime($request['resignation_date'])); ?><br>
                                                            วันที่ยื่นคำร้อง: <?php echo date('d/m/Y H:i:s', strtotime($request['submit_date'])); ?></p>
                                                        
                                                        <div class="card mb-3">
                                                            <div class="card-header">เหตุผลในการลาออก</div>
                                                            <div class="card-body">
                                                                <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if ($request['status'] !== 'pending'): ?>
                                                        <div class="card mb-3">
                                                            <div class="card-header">ความคิดเห็นจากบริษัท</div>
                                                            <div class="card-body">
                                                                <?php echo !empty($request['comment']) ? nl2br(htmlspecialchars($request['comment'])) : 'ไม่มีความคิดเห็น'; ?>
                                                            </div>
                                                        </div>
                                                        <p>วันที่ดำเนินการ: <?php echo date('d/m/Y H:i:s', strtotime($request['processed_date'])); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                        <form method="post">
                                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label for="comment<?php echo $request['request_id']; ?>" class="form-label">ความคิดเห็น</label>
                                                                <textarea class="form-control" id="comment<?php echo $request['request_id']; ?>" name="comment" rows="3"></textarea>
                                                            </div>
                                                            
                                                            <div class="d-flex justify-content-between">
                                                                <button type="submit" name="request_action" value="reject" class="btn btn-danger">
                                                                    <i class="fas fa-times"></i> ปฏิเสธคำร้อง
                                                                </button>
                                                                <button type="submit" name="request_action" value="approve" class="btn btn-success">
                                                                    <i class="fas fa-check"></i> อนุมัติการลาออก
                                                                </button>
                                                            </div>
                                                        </form>
                                                        <?php else: ?>
                                                        <div class="text-center p-3">
                                                            <span class="badge <?php echo ($request['status'] === 'approved') ? 'bg-success' : 'bg-danger'; ?> p-2">
                                                                <?php echo ($request['status'] === 'approved') ? 'คำร้องนี้ได้รับการอนุมัติแล้ว' : 'คำร้องนี้ถูกปฏิเสธแล้ว'; ?>
                                                            </span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">ไม่พบคำร้องขอลาออก</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../layouts/footer.php'; ?>