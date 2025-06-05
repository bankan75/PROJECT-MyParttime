<?php
// Generate OTP code (6 digits)
function generateOTP() {
    return sprintf("%06d", mt_rand(1, 999999));
}

// Create OTP record in database

function createOTP($database, $user_type, $user_id, $verification_type, $verification_value) {
    // ลบ OTP เก่าของผู้ใช้นี้สำหรับการยืนยันประเภทเดียวกัน
    $sql = "DELETE FROM otp_verifications WHERE user_type = ? AND user_id = ? AND verification_type = ?";
    $stmt = $database->prepare($sql);
    $stmt->execute([$user_type, $user_id, $verification_type]);
    
    
    // สร้าง OTP ใหม่
    $otp_code = generateOTP();
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP หมดอายุใน 15 นาที
    
    $sql = "INSERT INTO otp_verifications (user_type, user_id, verification_type, verification_value, otp_code, expires_at) 
    VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $database->prepare($sql);
$params = [$user_type, $user_id, $verification_type, $verification_value, $otp_code, $expires_at];

if ($stmt->execute($params)) {
return $otp_code;
}
return false;
}
// Send OTP via email
function sendEmailOTP($email, $otp) {
    // For development only - just display the OTP and return true
    $_SESSION['debug_email_otp'] = $otp;
    echo "<div style='background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>
          รหัสยืนยันที่ส่งไปยัง $email คือ: <strong>$otp</strong>
          </div>";
    
    return true;
    
    // Real email sending code would go here for production
}

// Send OTP via SMS (for now, just display on web)
function sendPhoneOTP($phone, $otp) {
    // For development only - just display the OTP and return true
    $_SESSION['debug_otp'] = $otp;
    echo "<div style='background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; margin: 10px 0; border-radius: 5px;'>
          รหัสยืนยันที่ส่งไปยัง $phone คือ: <strong>$otp</strong>
          </div>";
    
    return true;
    
    // Real SMS sending code would go here
}
// Verify OTP with improved debugging and error handling
function verifyOTP($database, $user_type, $user_id, $verification_type, $otp_code) {
    // แปลงให้เป็น string และตัดช่องว่าง
    $otp_code = trim((string)$otp_code);
    
    // ดึงข้อมูล OTP ที่ตรงกับเงื่อนไขโดยยังไม่ตรวจสอบหมดอายุและการยืนยัน
    $sql = "SELECT * FROM otp_verifications 
            WHERE user_type = ? 
            AND user_id = ? 
            AND verification_type = ?
            AND otp_code = ?";
    
    $stmt = $database->prepare($sql);
    $stmt->execute([$user_type, $user_id, $verification_type, $otp_code]);
    $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ถ้าไม่พบข้อมูล OTP
    if (!$otp_record) {
        error_log("OTP verification failed: No matching OTP found for user $user_type:$user_id");
        return false;
    }
    
    // ตรวจสอบว่า OTP หมดอายุหรือยัง
    if (strtotime($otp_record['expires_at']) < time()) {
        error_log("OTP verification failed: OTP expired for user $user_type:$user_id");
        return false;
    }
    
    // ตรวจสอบว่า OTP ถูกใช้ไปแล้วหรือยัง
    if ($otp_record['is_verified'] == 1) {
        error_log("OTP verification failed: OTP already verified for user $user_type:$user_id");
        return false;
    }
    
    // ถ้าผ่านทุกเงื่อนไข ให้ทำการอัปเดตสถานะเป็น verified
    $update_sql = "UPDATE otp_verifications SET is_verified = 1 WHERE id = ?";
    $update_stmt = $database->prepare($update_sql);
    $update_result = $update_stmt->execute([$otp_record['id']]);
    
    if (!$update_result) {
        error_log("OTP verification failed: Could not update verification status for user $user_type:$user_id");
        return false;
    }
    
    return true;
}

// Update user email after verification
function updateUserEmail($database, $user_type, $user_id, $new_email) {
    $table = '';
    $id_field = '';
    
    switch ($user_type) {
        case 'student':
            $table = 'students';
            $id_field = 'student_id';
            break;
        case 'company':
            $table = 'companies';
            $id_field = 'company_id';
            $new_email = ['contact_email' => $new_email];
            break;
        case 'admin':
            $table = 'admins';
            $id_field = 'admin_id';
            break;
        default:
            return false;
    }
    
    $sql = "UPDATE {$table} SET email = ? WHERE {$id_field} = ?";
    return $database->execute($sql, [$new_email, $user_id]);
}

// Update user phone after verification
function updateUserPhone($database, $user_type, $user_id, $new_phone) {
    $table = '';
    $id_field = '';
    $phone_field = 'phone';
    
    switch ($user_type) {
        case 'student':
            $table = 'students';
            $id_field = 'student_id';
            break;
        case 'company':
            $table = 'companies';
            $id_field = 'company_id';
            $phone_field = 'contact_phone';
            break;
        default:
            return false;
    }
    
    $sql = "UPDATE {$table} SET {$phone_field} = ? WHERE {$id_field} = ?";
    return $database->execute($sql, [$new_phone, $user_id]);
}
function getOTPVerification($database, $user_type, $user_id, $verification_type) {
    $sql = "SELECT * FROM otp_verifications 
            WHERE user_type = ? AND user_id = ? AND verification_type = ?
            ORDER BY created_at DESC LIMIT 1";
    $stmt = $database->prepare($sql);
    $stmt->execute([$user_type, $user_id, $verification_type]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generateNewOTP($database, $user_type, $user_id, $verification_type, $verification_value) {
    // สร้าง OTP 6 หลัก
    $otp_code = mt_rand(100000, 999999);
    
    // กำหนดเวลาหมดอายุ 15 นาที
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // บันทึกลงฐานข้อมูล
    $sql = "INSERT INTO otp_verifications (user_type, user_id, verification_type, verification_value, otp_code, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $result = $stmt->execute([$user_type, $user_id, $verification_type, $verification_value, $otp_code, $expires_at]);
    
    if ($result) {
        return [
            'success' => true,
            'otp_code' => $otp_code,
            'expires_at' => $expires_at
        ];
    }
    
    return [
        'success' => false,
        'message' => 'ไม่สามารถสร้าง OTP ได้'
    ];
}
?>