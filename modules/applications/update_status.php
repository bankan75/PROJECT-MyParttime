<?php
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

if (!$auth->isLoggedIn() || !$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีบริษัท";
    header("Location: ../../login.php");
    exit;
}

$company_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $interview_id = isset($_POST['interview_id']) ? intval($_POST['interview_id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $comment = isset($_POST['comment']) ? htmlspecialchars(trim($_POST['comment'])) : '';
    $required_documents = isset($_POST['required_documents']) ? trim($_POST['required_documents']) : '';

    // ตรวจสอบว่าพารามิเตอร์ที่จำเป็นครบถ้วน
    if ($application_id <= 0 || empty($status)) {
        $_SESSION['error_message'] = "ข้อมูลไม่ครบถ้วน: รหัสใบสมัครหรือสถานะไม่ถูกต้อง";
        header("Location: ../jobs/applications.php?id=" . $post_id);
        exit;
    }

    // ตรวจสอบสถานะ
    if (!in_array($status, ['reviewing', 'interview', 'accepted', 'rejected', 'request_documents'])) {
        $_SESSION['error_message'] = "สถานะไม่ถูกต้อง";
        header("Location: ../jobs/applications.php?id=" . $post_id);
        exit;
    }

    if ($application_id <= 0) {
        $_SESSION['error_message'] = "รหัสใบสมัครงานไม่ถูกต้อง";
        header("Location: ../jobs/applications.php?id=" . $post_id);
        exit;
    }

    $checkSql = "SELECT a.* FROM applications a 
                 JOIN jobs_posts j ON a.post_id = j.post_id 
                 WHERE a.application_id = ? AND j.company_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$application_id, $company_id]);

    if ($checkStmt->rowCount() == 0) {
        $_SESSION['error_message'] = "ไม่พบใบสมัครงานที่ต้องการอัปเดตหรือคุณไม่มีสิทธิ์ในการแก้ไข";
        header("Location: ../jobs/applications.php?id=" . $post_id);
        exit;
    }

    try {
        $conn->beginTransaction();

        // ดึงสถานะเดิม
        $currentStatusSql = "SELECT status FROM applications WHERE application_id = ?";
        $currentStatusStmt = $conn->prepare($currentStatusSql);
        $currentStatusStmt->execute([$application_id]);
        $currentStatus = $currentStatusStmt->fetchColumn();

        if ($currentStatus === false) {
            error_log("Failed to fetch current status for application_id: $application_id");
            throw new Exception("ไม่สามารถดึงสถานะเดิมได้");
        }
        // อัปเดตสถานะใบสมัครและเอกสารที่ต้องการ
        if ($status === 'request_documents' && !empty($required_documents)) {
            $updateAppSql = "UPDATE applications SET status = ?, required_documents = ?, updated_at = NOW() WHERE application_id = ?";
            $updateAppStmt = $conn->prepare($updateAppSql);
            $updateAppStmt->execute([$status, $required_documents, $application_id]);
        } else {
            $updateAppSql = "UPDATE applications SET status = ?, updated_at = NOW() WHERE application_id = ?";
            $updateAppStmt = $conn->prepare($updateAppSql);
            $updateAppStmt->execute([$status, $application_id]);
        }

    
        // อัปเดตสถานะการสัมภาษณ์
        if ($interview_id > 0) {
            $newInterviewStatus = ($status === 'accepted') ? 'completed' : 'rejected';
            $interviewSql = "UPDATE interviews SET status = ?, updated_at = NOW() 
                            WHERE interview_id = ? AND application_id = ?";
            $interviewStmt = $conn->prepare($interviewSql);
            $interviewStmt->execute([$newInterviewStatus, $interview_id, $application_id]);
        }
    
        // บันทึกประวัติ
        $historySql = "INSERT INTO application_status_history 
                       (application_id, old_status, new_status, status, comment, created_at, created_by, changed_by_type) 
                       VALUES (?, ?, ?, ?, ?, NOW(), ?, 'company')";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->execute([$application_id, $currentStatus, $status, $status, $comment, $company_id]);
    
        // ส่งการแจ้งเตือน
        $checkNotifTableSql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'notifications'";
        $checkNotifTableStmt = $conn->query($checkNotifTableSql);
        
        if ($checkNotifTableStmt && $checkNotifTableStmt->rowCount() > 0) {
            $statusMessage = '';
            switch($status) {
                case 'reviewing':
                    $statusMessage = 'กำลังพิจารณา';
                    break;
                case 'interview':
                    $statusMessage = 'นัดสัมภาษณ์';
                    break;
                case 'accepted':
                    $statusMessage = 'ผ่านการคัดเลือก';
                    break;
                case 'rejected':
                    $statusMessage = 'ไม่ผ่านการคัดเลือก';
                    break;
                default:
                    $statusMessage = htmlspecialchars($status);
            }
            
            if (empty($statusMessage)) {
                $_SESSION['error_message'] = "ไม่สามารถสร้างข้อความแจ้งเตือนได้";
                $conn->rollBack();
                header("Location: ../jobs/applications.php?id=" . $post_id);
                exit;
            }
            
            // ปรับคำสั่ง SQL โดยลบคอลัมน์ link
            $notifSql = "INSERT INTO notifications 
                        (user_id, type, message, is_read, created_at) 
                        SELECT a.student_id, 'application_status', 
                              CONCAT('สถานะใบสมัครของคุณสำหรับตำแหน่ง \"', j.job_title, '\" ได้เปลี่ยนเป็น \"', ?, '\"'), 
                              0, NOW()
                        FROM applications a
                        JOIN jobs_posts j ON a.post_id = j.post_id
                        WHERE a.application_id = ?";
            $notifStmt = $conn->prepare($notifSql);
            $notifStmt->execute([$statusMessage, $application_id]);
        }

        $conn->commit();

        if ($status === 'request_documents') {
            $_SESSION['success_message'] = "ได้ร้องขอเอกสารเพิ่มเรียบร้อยแล้ว";
        } elseif ($status === 'reviewing') {
            $_SESSION['success_message'] = "อัปเดตสถานะเรียบร้อยแล้ว - กำลังพิจารณาใบสมัคร";
        } elseif ($status === 'interview') {
            $_SESSION['success_message'] = "อัปเดตสถานะเรียบร้อยแล้ว - กรุณาสร้างนัดสัมภาษณ์";
        } elseif ($status === 'accepted') {
            $_SESSION['success_message'] = "อัปเดตสถานะเรียบร้อยแล้ว - ผู้สมัครผ่านการคัดเลือก";
        } elseif ($status === 'rejected') {
            $_SESSION['success_message'] = "อัปเดตสถานะเรียบร้อยแล้ว - ผู้สมัครไม่ผ่านการคัดเลือก";
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("SQL Error: " . $e->getMessage());
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header("Location: ../jobs/applications.php?id=" . $post_id);
        exit;
    }

    // กำหนด redirect ตามสถานะที่เลือก
    if ($status === 'interview') {
        header("Location: ../interviews/index.php");
    } else {
        header("Location: ../applications/view.php?id=" . $post_id);
    }
    exit;
} else {
    $_SESSION['error_message'] = "การเข้าถึงไม่ถูกต้อง";
    header("Location: ../jobs/applications.php?id=" . $post_id);
    exit;
}
?>