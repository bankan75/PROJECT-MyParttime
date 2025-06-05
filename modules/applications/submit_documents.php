<?php
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();
$conn = $db->getConnection();
$auth = new Auth($conn);

if (!$auth->isLoggedIn() || !$auth->isStudent()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีนักศึกษา";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id'])) {
    $application_id = intval($_POST['application_id']);
    $student_id = $_SESSION['user_id'];

    $checkSql = "SELECT * FROM applications WHERE application_id = ? AND student_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$application_id, $student_id]);

    if ($checkStmt->rowCount() == 0) {
        $_SESSION['error_message'] = "ไม่พบใบสมัครงานหรือคุณไม่มีสิทธิ์แก้ไข";
        header("Location: " . ROOT_URL . "/modules/applications/view.php?id=" . $application_id);
        exit;
    }

    $application = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ($application['status'] !== 'request_documents') {
        $_SESSION['error_message'] = "สถานะใบสมัครไม่สามารถส่งเอกสารได้ในขณะนี้";
        header("Location: " . ROOT_URL . "/modules/applications/view.php?id=" . $application_id);
        exit;
    }

    $uploaded_docs = [];
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/myparttime/uploads/documents/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['documents']['name'][0])) {
        $file_count = count($_FILES['documents']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['documents']['tmp_name'][$i];
                $name = $_FILES['documents']['name'][$i];
                $file_name = uniqid() . '_' . basename($name);
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_docs[] = 'uploads/documents/' . $file_name;
                } else {
                    $_SESSION['error_message'] = "ไม่สามารถย้ายไฟล์: " . $name;
                }
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์: " . $_FILES['documents']['error'][$i];
            }
        }

        if (!empty($uploaded_docs)) {
            $current_docs = $application['submitted_documents'] ? $application['submitted_documents'] : '';
            $new_docs = $current_docs . (!empty($current_docs) ? ',' : '') . implode(',', $uploaded_docs);

            $updateSql = "UPDATE applications SET submitted_documents = ?, updated_at = NOW() WHERE application_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$new_docs, $application_id]);

            $_SESSION['success_message'] = "ส่งเอกสารเรียบร้อยแล้ว";
        }
    } else {
        $_SESSION['error_message'] = "กรุณาเลือกไฟล์เอกสาร";
    }

    header("Location: " . ROOT_URL . "/modules/applications/view.php?id=" . $application_id);
    exit;
}
?>