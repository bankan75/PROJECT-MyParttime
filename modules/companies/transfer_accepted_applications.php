<?php
// ไฟล์นี้จะทำการโอนย้ายข้อมูลจาก applications ที่มีสถานะ accepted ไปยัง employments

include('../../includes/config.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นบริษัทหรือไม่
if (!$auth->isLoggedIn() || !$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีบริษัท";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

$company_id = $company_id = $_SESSION['user_id'];

// ดึงข้อมูลการสมัครที่มีสถานะ accepted แต่ยังไม่ได้อยู่ในตาราง employments
$sql = "SELECT a.*, j.job_title, j.min_salary, j.max_salary 
        FROM applications a
        JOIN jobs_posts j ON a.post_id = j.post_id
        WHERE a.status = 'accepted' 
        AND j.company_id = ?
        AND NOT EXISTS (
            SELECT 1 FROM employments e 
            WHERE e.student_id = a.student_id 
            AND e.company_id = j.company_id
            AND e.status = 'accepted'
        )";

$stmt = $db->prepare($sql);
$stmt->execute([$company_id]);
$accepted_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($accepted_applications) > 0) {
    // เตรียม query สำหรับการเพิ่มข้อมูลในตาราง employments
    $employment_sql = "INSERT INTO employments 
                       (student_id, company_id, position, start_date, salary, status, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, 'accepted', NOW(), NOW())";
    
    $employment_stmt = $db->prepare($employment_sql);
    
    // เตรียม query สำหรับอัปเดต employment_status
    $update_student_sql = "UPDATE students SET employment_status = 'employed', updated_at = NOW() WHERE student_id = ?";
    $update_student_stmt = $db->prepare($update_student_sql);
    
    // ทำการเพิ่มข้อมูลลงในตาราง employments และอัปเดต employment_status
    $success_count = 0;
    
    foreach ($accepted_applications as $app) {
        // ใช้วันที่เริ่มงานจากการสมัคร หรือใช้วันที่ปัจจุบันถ้าไม่มีข้อมูล
        $start_date = !empty($app['available_start_date']) ? $app['available_start_date'] : date('Y-m-d');
        
        // ใช้เงินเดือนที่คาดหวังจากการสมัคร หรือใช้ค่าเฉลี่ยของเงินเดือนขั้นต่ำและสูงสุดจากประกาศงาน
        $salary = !empty($app['expected_salary']) ? $app['expected_salary'] : 
                (($app['min_salary'] + $app['max_salary']) / 2);
        
        // เริ่ม transaction เพื่อให้แน่ใจว่าทั้งสอง operation สำเร็จ
        $db->beginTransaction();
        try {
            $result = $employment_stmt->execute([
                $app['student_id'],
                $company_id,
                $app['job_title'],
                $start_date,
                $salary
            ]);
            
            if ($result) {
                // อัปเดต employment_status เป็น employed
                $update_student_stmt->execute([$app['student_id']]);
                $success_count++;
                $db->commit();
            } else {
                $db->rollBack();
            }
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการโอนย้ายข้อมูล: " . $e->getMessage();
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['success_message'] = "โอนย้ายข้อมูลนักศึกษาที่ผ่านการคัดเลือกไปยังพนักงานปัจจุบันเรียบร้อยแล้ว จำนวน " . $success_count . " คน";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการโอนย้ายข้อมูล กรุณาลองใหม่อีกครั้ง";
    }
} else {
    $_SESSION['info_message'] = "ไม่พบข้อมูลนักศึกษาที่ผ่านการคัดเลือกที่ต้องโอนย้าย";
}

// กลับไปยังหน้าพนักงานปัจจุบัน
header("Location: " . ROOT_URL . "/modules/current_employees/current_employees.php");
exit;
?>