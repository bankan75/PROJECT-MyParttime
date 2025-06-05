<?php
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// Get student ID from URL
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if student exists
if ($student_id <= 0) {
    $_SESSION['error'] = "ไม่พบข้อมูลนักศึกษา";
    header("Location: index.php");
    exit;
}

// Get student data
$sql = "SELECT * FROM students WHERE student_id = ?";
$student = $database->getRow($sql, [$student_id]);

if (!$student) {
    $_SESSION['error'] = "ไม่พบข้อมูลนักศึกษารหัส $student_id";
    header("Location: index.php");
    exit;
}

// Check if form is submitted (deletion confirmed)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    // Check if student has associated applications
    $check_sql = "SELECT COUNT(*) as count FROM applications WHERE student_id = ?";
    $check_result = $database->getRow($check_sql, [$student_id]);
    
    if ($check_result['count'] > 0) {
        $_SESSION['error'] = "ไม่สามารถลบข้อมูลนักศึกษาได้ เนื่องจากมีการสมัครงานที่เกี่ยวข้องอยู่";
        header("Location: index.php");
        exit;
    }
    
    // Delete student
    $delete_sql = "DELETE FROM students WHERE student_id = ?";
    $result = $database->execute($delete_sql, [$student_id]);
    
    if ($result) {
        $_SESSION['success'] = "ลบข้อมูลนักศึกษาเรียบร้อยแล้ว";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล";
        header("Location: index.php");
        exit;
    }
}

// Set page title and include header
$page_title = "ลบข้อมูลนักศึกษา";
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/header.php';

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับไปหน้ารายการ
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="alert alert-danger">
            <h4><i class="fas fa-exclamation-triangle"></i> ยืนยันการลบข้อมูล</h4>
            <p>คุณกำลังจะลบข้อมูลนักศึกษา: <strong><?php echo sanitize($student['first_name'] . ' ' . $student['last_name']); ?></strong></p>
            <p>รหัสนักศึกษา: <strong><?php echo sanitize($student['student_code']); ?></strong></p>
            <p>การลบข้อมูลนี้ไม่สามารถกู้คืนได้ คุณต้องการดำเนินการต่อหรือไม่?</p>
            
            <form method="POST" action="">
                <div class="d-flex gap-2">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> ยืนยันการลบ
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>