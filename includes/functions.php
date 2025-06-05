<?php
// Common functions for the admin panel
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
// Sanitize input - add function exists check
if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Format date for display
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd M Y') {
        return date($format, strtotime($date));
    }
}

// Generate pagination links
if (!function_exists('getPagination')) {
    function getPagination($current_page, $total_pages, $url) {
        $html = '<nav aria-label="Page navigation"><ul class="pagination">';
        
        // Previous button
        if ($current_page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page - 1) . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $current_page) {
                $html .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
            }
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page + 1) . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>';
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
}

// Generate status badge
if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        $badge_class = '';
        
        switch (strtolower($status)) {
            case 'accepted':
            case 'completed':
            case 'active':
            case 'approved':
            case 'hired':
                $badge_class = 'badge bg-success';
                break;
            case 'rescheduled':
            case 'pending':
            case 'in progress':
            case 'scheduled':
                $badge_class = 'badge bg-warning';
                break;
            case 'rejected':
            case 'canceled':
            case 'expired':
                $badge_class = 'badge bg-danger';
                break;
            case 'interview':
                $badge_class = 'badge bg-primary';
                break;
            default:
                $badge_class = 'badge bg-secondary';
        }
        
        return '<span class="' . $badge_class . '">' . $status . '</span>';
    }
}

if (!function_exists('getFacultyName')) {
    function getFacultyName($faculty_name) {
        // ไม่จำเป็นต้องคิวรีเพราะฟิลด์ faculty_name เก็บข้อมูลโดยตรงในตาราง students
        return $faculty_name ?? 'Unknown Faculty';
    }
}

// Format currency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2);
    }
}

// Get dashboard statistics
if (!function_exists('getDashboardStats')) {
    function getDashboardStats($db) {
        $stats = array();
        
        // Get total companies
        $sql = "SELECT COUNT(*) as total FROM companies";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_companies'] = $result['total'] ?? 0;
        
        // Get total students
        $sql = "SELECT COUNT(*) as total FROM students";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_students'] = $result['total'] ?? 0;
        
        // Get total active job posts
        $sql = "SELECT COUNT(*) as total FROM jobs_posts WHERE is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_jobs'] = $result['total'] ?? 0;
        
        // Get total applications
        $sql = "SELECT COUNT(*) as total FROM applications";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_applications'] = $result['total'] ?? 0;
        
        // Get upcoming interviews (next 7 days)
        $sql = "SELECT COUNT(*) as total FROM interviews WHERE updated_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['upcoming_interviews'] = $result['total'] ?? 0;
        
        // Get total employed students
        $sql = "SELECT COUNT(DISTINCT student_id) as total FROM employments WHERE status = 'accepted'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['employed_students'] = $result['total'] ?? 0;
        
        return $stats;
    }
}

// Log activity
if (!function_exists('logActivity')) {
    function logActivity($database, $admin_id, $action, $module, $record_id = null) {
        $sql = "INSERT INTO activity_log (admin_id, action, module, record_id, log_date) VALUES (?, ?, ?, ?, NOW())";
        $params = [$admin_id, $action, $module, $record_id];
        $database->execute($sql, $params); // เปลี่ยนจาก insert เป็น execute
    }
}

if (!function_exists('uploadProfileImage')) {
    function uploadProfileImage($file, $user_type, $user_id) {
        // กำหนดโฟลเดอร์เก็บรูปโปรไฟล์ตามประเภทผู้ใช้
        $upload_dir = BASE_PATH . '/uploads/' . $user_type . '/';
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // ตรวจสอบไฟล์ที่อัปโหลด
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            return [
                'success' => false,
                'message' => 'รูปแบบไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์รูปภาพเท่านั้น (JPEG, PNG, GIF)'
            ];
        }
        
        if ($file['size'] > $max_size) {
            return [
                'success' => false,
                'message' => 'ขนาดไฟล์ใหญ่เกินไป กรุณาอัปโหลดไฟล์ขนาดไม่เกิน 5MB'
            ];
        }
        
        // สร้างชื่อไฟล์ใหม่เพื่อป้องกันการซ้ำกัน
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = $user_type . '_' . $user_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        // อัปโหลดไฟล์
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // คืนค่าเพื่อบันทึกพาธของไฟล์ลงฐานข้อมูล
            return [
                'success' => true,
                'file_path' => '/uploads/' . $user_type . '/' . $new_filename,
                'message' => 'อัปโหลดรูปโปรไฟล์สำเร็จ'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์ กรุณาลองใหม่อีกครั้ง'
            ];
        }
    }
}

// ฟังก์ชันลบรูปโปรไฟล์เก่า
if (!function_exists('deleteOldProfileImage')) {
    function deleteOldProfileImage($file_path) {
        if (!empty($file_path) && file_exists(BASE_PATH . $file_path)) {
            unlink(BASE_PATH . $file_path);
            return true;
        }
        return false;
    }
}

// ฟังก์ชันสำหรับแสดงรูปโปรไฟล์ (ถ้าไม่มีรูปจะแสดงรูป default)
if (!function_exists('getProfileImageUrl')) {
    function getProfileImageUrl($image_path, $user_type) {
        if (!empty($image_path) && file_exists(BASE_PATH . $image_path)) {
            return ROOT_URL . $image_path;
        } else {
            // รูปโปรไฟล์ default ตามประเภทผู้ใช้
            if ($user_type == 'student') {
                return ROOT_URL . '/assets/images/default-student.png';
            } else {
                return ROOT_URL . '/assets/images/default-company.png';
            }
        }
    }
}

// อัปเดตรูปโปรไฟล์ของนักศึกษา
if (!function_exists('updateStudentProfileImage')) {
    function updateStudentProfileImage($database, $student_id, $new_image_path) {
        $sql = "UPDATE students SET profile_image = ? WHERE student_id = ?";
        return $database->execute($sql, [$new_image_path, $student_id]);
    }
}

// อัปเดตโลโก้ของบริษัท
if (!function_exists('updateCompanyLogo')) {
    function updateCompanyLogo($database, $company_id, $new_logo_path) {
        $sql = "UPDATE companies SET logo_path = ? WHERE company_id = ?";
        $stmt = $database->prepare($sql);
        return $stmt->execute([$new_logo_path, $company_id]);
    }
}

// ดึงข้อมูลโปรไฟล์นักศึกษา
if (!function_exists('getStudentProfile')) {
    function getStudentProfile($database, $student_id) {
        $sql = "SELECT * FROM students WHERE student_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ดึงข้อมูลโปรไฟล์บริษัท
if (!function_exists('getCompanyProfile')) {
    function getCompanyProfile($database, $company_id) {
        $sql = "SELECT * FROM companies WHERE company_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$company_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getAdminProfile')) {
    function getAdminProfile($database, $admin_id) {
        $sql = "SELECT * FROM admins WHERE admin_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$admin_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getJobPost')) {
    function getJobPost($database, $post_id) {
        $sql = "SELECT j.*, c.company_name, c.logo_path 
                FROM jobs_posts j 
                JOIN companies c ON j.company_id = c.company_id 
                WHERE j.post_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$post_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getApplication')) {
    function getApplication($database, $application_id) {
        $sql = "SELECT a.*, s.first_name, s.last_name, s.student_code, s.email,
                j.job_title, c.company_name 
                FROM applications a
                JOIN students s ON a.student_id = s.student_id
                JOIN jobs_posts j ON a.post_id = j.post_id
                JOIN companies c ON j.company_id = c.company_id
                WHERE a.application_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$application_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('getInterview')) {
    function getInterview($database, $interview_id) {
        $sql = "SELECT i.*, a.student_id, a.post_id, s.first_name, s.last_name,
                j.job_title, c.company_name 
                FROM interviews i
                JOIN applications a ON i.application_id = a.application_id
                JOIN students s ON a.student_id = s.student_id
                JOIN jobs_posts j ON a.post_id = j.post_id
                JOIN companies c ON j.company_id = c.company_id
                WHERE i.interview_id = ?";
        $stmt = $database->prepare($sql);
        $stmt->execute([$interview_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('updateApplicationStatus')) {
    function updateApplicationStatus($database, $application_id, $new_status, $comment = '', $created_by = null, $changed_by_type = null) {
        // อัพเดทสถานะในตาราง applications
        $sql = "UPDATE applications SET status = ?, updated_at = NOW() WHERE application_id = ?";
        $stmt = $database->prepare($sql);
        $result = $stmt->execute([$new_status, $application_id]);
        
        if ($result) {
            // บันทึกประวัติการเปลี่ยนสถานะ
            $sql = "INSERT INTO application_status_history (application_id, status, comment, created_at, created_by, changed_by_type) 
                    VALUES (?, ?, ?, NOW(), ?, ?)";
            $stmt = $database->prepare($sql);
            return $stmt->execute([$application_id, $new_status, $comment, $created_by, $changed_by_type]);
        }
        
        return false;
    }
}

/**
 * ตรวจสอบว่านักศึกษามีงานแล้วหรือไม่
 */
if (!function_exists('hasActiveJob')) {
    function hasActiveJob($database, $student_id) {
        $query = "SELECT COUNT(*) as count FROM applications 
                  WHERE student_id = ? AND status = 'accepted'";
        $stmt = $database->prepare($query);
        $stmt->execute([$student_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
}

/**
 * เปลี่ยนสถานะนักศึกษาเป็นว่างงาน
 */
if (!function_exists('setStudentJobStatusToUnemployed')) {
    function setStudentJobStatusToUnemployed($database, $student_id) {
        // อัปเดตสถานะการสมัครงานเป็น 'completed' หรือสถานะอื่นที่ไม่ใช่ 'accepted'
        $query = "UPDATE applications SET status = 'completed' 
                  WHERE student_id = ? AND status = 'accepted'";
        $stmt = $database->prepare($query);
        return $stmt->execute([$student_id]);
    }
}
// Validate password complexity
if (!function_exists('validatePassword')) {
    function validatePassword($password) {
        $errors = [];
        
        // Check minimum length
        if (strlen($password) < 8) {
            $errors[] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        }
        
        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่至少 1 ตัว';
        }
        
        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีตัวอักษรพิมพ์เล็ก至少 1 ตัว';
        }
        
        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'รหัสผ่านต้องมีอักขระพิเศษ 1 ตัว';
        }
        
        return $errors;
    }
}
?>