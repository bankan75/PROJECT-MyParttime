<?php
include('../../includes/functions.php');
include('../../includes/auth.php');

// ตรวจสอบว่า $db ถูกกำหนดค่าแล้ว
if (!isset($db)) {
    error_log("Database connection error: \$db variable not set");
    die("ไม่สามารถเชื่อมต่อกับฐานข้อมูล กรุณาตรวจสอบการตั้งค่าการเชื่อมต่อ");
}

// สร้างอ็อบเจ็กต์ Auth
$auth = new Auth($db);

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบ
if (!$auth->isLoggedIn()) {
    $_SESSION['error_message'] = "กรุณาเข้าสู่ระบบก่อนดำเนินการ";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Check if POST request and has application ID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : null;
    $admin_id = $_SESSION['admin_id']; // Assuming admin ID is stored in session
    
    // Validate application_id
    if ($application_id <= 0) {
        $_SESSION['error'] = "รหัสใบสมัครไม่ถูกต้อง";
        header("Location: index.php");
        exit();
    }
    
    // Check if application exists
    $checkSql = "SELECT * FROM applications WHERE application_id = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "ไม่พบใบสมัครที่ต้องการอัปเดต";
        header("Location: index.php");
        exit();
    }
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update application status
        $updateSql = "UPDATE applications SET status = ?, updated_at = NOW() WHERE application_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $status, $application_id);
        $updateStmt->execute();
        
        // Record status change in history
        $historySql = "INSERT INTO application_status_history (application_id, status, changed_by, changed_by_type, notes) VALUES (?, ?, ?, 'admin', ?)";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("isis", $application_id, $status, $admin_id, $notes);
        $historyStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "อัปเดตสถานะใบสมัครเรียบร้อยแล้ว";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $e->getMessage();
    }
    
    // Redirect back to the application listing or view page
    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
        header("Location: " . $_POST['redirect']);
    } else {
        header("Location: index.php");
    }
    exit();
} else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['application_id'])) {
    // If accessed via GET, show the status update form
    $application_id = intval($_GET['application_id']);
    
    // Fetch current application details
    $sql = "SELECT a.*, jp.job_title, s.first_name, s.last_name, c.company_name 
            FROM applications a
            JOIN jobs_posts jp ON a.post_id = jp.post_id
            JOIN students s ON a.student_id = s.student_id
            JOIN companies c ON jp.company_id = c.company_id
            WHERE a.application_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error'] = "ไม่พบใบสมัครที่ต้องการอัปเดต";
        header("Location: index.php");
        exit();
    }
    
    $application = $result->fetch_assoc();
    
    // Fetch status history
    $historySql = "SELECT h.*, 
              CASE 
                  WHEN h.changed_by_type = 'company' THEN c.company_name
                  WHEN h.changed_by_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  ELSE 'System'
              END as changed_by_name
              FROM application_status_history h
              LEFT JOIN companies c ON h.changed_by = c.company_id AND h.changed_by_type = 'company'
              LEFT JOIN students s ON h.changed_by = s.student_id AND h.changed_by_type = 'student'
              WHERE h.application_id = ?
              ORDER BY h.created_at DESC";
    $historyStmt = $conn->prepare($historySql);
    $historyStmt->bind_param("i", $application_id);
    $historyStmt->execute();
    $historyResult = $historyStmt->get_result();
    
    // Include header
    include '../../layouts/header.php';

?>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">จัดการสถานะใบสมัคร</h1>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">ข้อมูลใบสมัคร</h6>
                    </div>
                    <div class="card-body">
                        <h5>ตำแหน่งงาน: <?php echo htmlspecialchars($application['job_title']); ?></h5>
                        <p>บริษัท: <?php echo htmlspecialchars($application['company_name']); ?></p>
                        <p>ผู้สมัคร: <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></p>
                        <p>วันที่สมัคร: <?php echo date('d/m/Y H:i', strtotime($application['apply_date'])); ?></p>
                        <p>สถานะปัจจุบัน: <span class="badge badge-<?php echo getStatusBadgeClass($application['status']); ?>"><?php echo htmlspecialchars($application['status']); ?></span></p>
                    </div>
                </div>
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">อัปเดตสถานะ</h6>
                    </div>
                    <div class="card-body">
                        <form action="change_status.php" method="POST">
                            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                            <input type="hidden" name="redirect" value="<?php echo isset($_GET['redirect']) ? $_GET['redirect'] : 'view.php?application_id=' . $application_id; ?>">
                            
                            <div class="form-group">
                                <label for="status">สถานะใหม่:</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="pending" <?php echo ($application['status'] == 'pending') ? 'selected' : ''; ?>>รอพิจารณา (Pending)</option>
                                    <option value="reviewing" <?php echo ($application['status'] == 'reviewing') ? 'selected' : ''; ?>>กำลังพิจารณา (Reviewing)</option>
                                    <option value="shortlisted" <?php echo ($application['status'] == 'shortlisted') ? 'selected' : ''; ?>>ผ่านการคัดเลือกเบื้องต้น (Shortlisted)</option>
                                    <option value="interview" <?php echo ($application['status'] == 'interview') ? 'selected' : ''; ?>>นัดสัมภาษณ์ (Interview)</option>
                                    <option value="offered" <?php echo ($application['status'] == 'offered') ? 'selected' : ''; ?>>เสนอรับเข้าทำงาน (Offered)</option>
                                    <option value="hired" <?php echo ($application['status'] == 'hired') ? 'selected' : ''; ?>>รับเข้าทำงานแล้ว (Hired)</option>
                                    <option value="rejected" <?php echo ($application['status'] == 'rejected') ? 'selected' : ''; ?>>ปฏิเสธ (Rejected)</option>
                                    <option value="withdrawn" <?php echo ($application['status'] == 'withdrawn') ? 'selected' : ''; ?>>ถอนตัว (Withdrawn)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">หมายเหตุ:</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                            <a href="view.php?application_id=<?php echo $application_id; ?>" class="btn btn-secondary">ยกเลิก</a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">ประวัติการเปลี่ยนสถานะ</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($historyResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>วันที่เปลี่ยน</th>
                                            <th>สถานะ</th>
                                            <th>ผู้เปลี่ยน</th>
                                            <th>หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($history = $historyResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                                                <td><span class="badge badge-<?php echo getStatusBadgeClass($history['status']); ?>"><?php echo htmlspecialchars($history['status']); ?></span></td>
                                                <td><?php echo $history['changed_by_type'] == 'admin' ? htmlspecialchars($history['admin_name']) : 'ระบบ'; ?></td>
                                                <td><?php echo $history['notes'] ? htmlspecialchars($history['notes']) : '-'; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">ยังไม่มีประวัติการเปลี่ยนสถานะ</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
    // Include footer
    include '../../layouts/footer.php';
} else {
    // Invalid access
    $_SESSION['error'] = "การเข้าถึงไม่ถูกต้อง";
    header("Location: index.php");
    exit();
}

// Helper function to determine badge class based on status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'secondary';
        case 'reviewing':
            return 'info';
        case 'shortlisted':
            return 'primary';
        case 'interview':
            return 'warning';
        case 'offered':
            return 'success';
        case 'hired':
            return 'success';
        case 'rejected':
            return 'danger';
        case 'withdrawn':
            return 'dark';
        default:
            return 'secondary';
    }
}
?>