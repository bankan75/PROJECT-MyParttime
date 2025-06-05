<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// ตรวจสอบว่าเป็นบริษัทที่ล็อกอินแล้ว
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

$company_id = $_SESSION['user_id'];
$company = getCompanyProfile($database, $company_id);

if (!$company) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลบริษัท';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

// จัดการการส่งฟอร์มแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่าเป็นการแก้ไขข้อมูลทั่วไป
    if (isset($_POST['update_company'])) {
        $company_name = $_POST['company_name'] ?? '';
        $business_type = $_POST['business_type'] ?? '';
        $company_type = $_POST['company_type'] ?? '';
        $business_sector = $_POST['business_sector'] ?? '';
        $company_desc = $_POST['company_desc'] ?? '';
        $address = $_POST['address'] ?? '';
        $province = $_POST['province'] ?? '';
        $postal_code = $_POST['postal_code'] ?? '';
        $website = $_POST['website'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        
        // ตรวจสอบความถูกต้องของข้อมูล
        if (empty($company_name) || empty($business_type) || empty($company_type) || 
            empty($company_desc) || empty($address) || empty($province) || 
            empty($postal_code) || empty($contact_person)) {
            $_SESSION['error_message'] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
        } else {
            // อัปเดตข้อมูลในฐานข้อมูล
            $sql = "UPDATE companies SET 
                    company_name = ?, 
                    business_type = ?, 
                    company_type = ?,
                    business_sector = ?,
                    company_desc = ?,
                    address = ?,
                    province = ?,
                    postal_code = ?,
                    website = ?,
                    contact_person = ?,
                    updated_at = NOW()
                    WHERE company_id = ?";
            
            $params = [
                $company_name, $business_type, $company_type, $business_sector,
                $company_desc, $address, $province, $postal_code, $website,
                $contact_person, $company_id
            ];
            
            if ($database->execute($sql, $params)) {
                $_SESSION['success_message'] = 'อัปเดตข้อมูลบริษัทสำเร็จ';
                header('Location: company_profile.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
            }
        }
    }

// ตรวจสอบว่าเป็นการเริ่มกระบวนการเปลี่ยนอีเมล
if (isset($_POST['request_email_change'])) {
    $new_email = $_POST['new_email'] ?? '';
    
    if (empty($new_email)) {
        $_SESSION['error_message'] = 'กรุณากรอกอีเมลใหม่';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    } else {
        // สร้าง OTP และบันทึกลงฐานข้อมูล
        require_once(INCLUDES_PATH . '/otp_functions.php');
        $otp = createOTP($database, 'company', $company_id, 'email', $new_email);
            // ตรวจสอบว่าอีเมลใหม่ซ้ำกับบริษัทอื่นหรือไม่
$check_email_sql = "SELECT COUNT(*) FROM companies WHERE contact_email = ? AND company_id != ?";
$check_stmt = $database->prepare($check_email_sql);
$check_stmt->execute([$new_email, $company_id]);
$email_exists = $check_stmt->fetchColumn();

if ($email_exists > 0) {
    $_SESSION['error_message'] = 'อีเมลนี้มีการใช้งานโดยบริษัทอื่นแล้ว กรุณาใช้อีเมลอื่น';
    header('Location: edit_company_profile.php');
    exit;
}
        if ($otp) {
            // แสดง OTP บนหน้าเว็บ (ในความเป็นจริงควรส่งอีเมล)
            if (sendEmailOTP($new_email, $otp)) {
                $_SESSION['success_message'] = 'รหัสยืนยันของคุณคือ: ' . $otp . ' กรุณานำไปกรอกในหน้ายืนยัน';
                $_SESSION['verify_new_email'] = $new_email;
                header('Location: verify_email.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'ไม่สามารถส่งอีเมลยืนยันได้ กรุณาลองใหม่อีกครั้ง';
            }
        } else {
            $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการสร้างรหัสยืนยัน';
        }
    }
}
    
    // ตรวจสอบว่าเป็นการเริ่มกระบวนการเปลี่ยนเบอร์โทร
    if (isset($_POST['request_phone_change'])) {
        $new_phone = $_POST['new_phone'] ?? '';
        
        if (empty($new_phone)) {
            $_SESSION['error_message'] = 'กรุณากรอกเบอร์โทรศัพท์ใหม่';
        } elseif (!preg_match('/^[0-9]{10}$/', $new_phone)) {
            $_SESSION['error_message'] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (กรุณากรอกเฉพาะตัวเลข 10 หลัก)';
        } else {
            // สร้าง OTP และบันทึกลงฐานข้อมูล
            require_once(INCLUDES_PATH . '/otp_functions.php');
            $otp = createOTP($database, 'company', $company_id, 'phone', $new_phone);
                        // ตรวจสอบว่าอีเมลใหม่ซ้ำกับบริษัทอื่นหรือไม่
$check_phone_sql = "SELECT COUNT(*) FROM companies WHERE contact_phone = ? AND company_id != ?";
$check_stmt = $database->prepare($check_phone_sql);
$check_stmt->execute([$new_phone, $company_id]);
$phone_exists = $check_stmt->fetchColumn();

if ($phone_exists > 0) {
    $_SESSION['error_message'] = 'เบอร์นี้มีการใช้งานโดยบริษัทอื่นแล้ว กรุณาใช้อีเมลอื่น';
    header('Location: edit_company_profile.php');
    exit;
}
            if ($otp) {
                // แสดง OTP บนหน้าเว็บ (ในความเป็นจริงควรส่ง SMS)
                $_SESSION['success_message'] = 'รหัสยืนยันของคุณคือ: ' . $otp . ' กรุณานำไปกรอกในหน้ายืนยัน';
                $_SESSION['verify_new_phone'] = $new_phone;
                header('Location: verify_phone.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการสร้างรหัสยืนยัน';
            }
        }
    }
}
?>
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
<div class="container px-4 py-5">
    <div class="row">
        <div class="col-12 mb-4">
            <h2>แก้ไขข้อมูลบริษัท</h2>
            <p>แก้ไขข้อมูลบริษัทของคุณ หมายเหตุ: การเปลี่ยนอีเมลหรือเบอร์โทรศัพท์จำเป็นต้องยืนยันด้วยรหัส OTP</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลทั่วไป</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="company_name" class="form-label">ชื่อบริษัท *</label>
                            <input type="text" class="form-control" id="company_name" name="company_name"
                                value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="business_type" class="form-label">ประเภทธุรกิจ *</label>
                            <select class="form-select" id="business_type" name="business_type" required>
                                <option value="">เลือกประเภทธุรกิจ</option>
                                <option value="ธุรกิจบริการ"
                                    <?php echo ($company['business_type'] === 'ธุรกิจบริการ') ? 'selected' : ''; ?>>
                                    ธุรกิจบริการ</option>
                                <option value="ธุรกิจการผลิต"
                                    <?php echo ($company['business_type'] === 'ธุรกิจการผลิต') ? 'selected' : ''; ?>>
                                    ธุรกิจการผลิต</option>
                                <option value="ธุรกิจค้าปลีก"
                                    <?php echo ($company['business_type'] === 'ธุรกิจค้าปลีก') ? 'selected' : ''; ?>>
                                    ธุรกิจค้าปลีก</option>
                                <option value="ธุรกิจค้าส่ง"
                                    <?php echo ($company['business_type'] === 'ธุรกิจค้าส่ง') ? 'selected' : ''; ?>>
                                    ธุรกิจค้าส่ง</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="company_type" class="form-label">รูปแบบบริษัท *</label>
                            <select class="form-select" id="company_type" name="company_type" required>
                                <option value="">เลือกรูปแบบบริษัท</option>
                                <option value="บริษัทจำกัด"
                                    <?php echo ($company['company_type'] === 'บริษัทจำกัด') ? 'selected' : ''; ?>>
                                    บริษัทจำกัด</option>
                                <option value="บริษัทมหาชน"
                                    <?php echo ($company['company_type'] === 'บริษัทมหาชน') ? 'selected' : ''; ?>>
                                    บริษัทมหาชน</option>
                                <option value="ห้างหุ้นส่วนจำกัด"
                                    <?php echo ($company['company_type'] === 'ห้างหุ้นส่วนจำกัด') ? 'selected' : ''; ?>>
                                    ห้างหุ้นส่วนจำกัด</option>
                                <option value="กิจการเจ้าของคนเดียว"
                                    <?php echo ($company['company_type'] === 'กิจการเจ้าของคนเดียว') ? 'selected' : ''; ?>>
                                    กิจการเจ้าของคนเดียว</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="business_sector" class="form-label">กลุ่มอุตสาหกรรม</label>
                            <select class="form-select" id="business_sector" name="business_sector">
                                <option value="">เลือกกลุ่มอุตสาหกรรม</option>
                                <option value="เทคโนโลยีสารสนเทศ"
                                    <?php echo ($company['business_sector'] === 'เทคโนโลยีสารสนเทศ') ? 'selected' : ''; ?>>
                                    เทคโนโลยีสารสนเทศ</option>
                                <option value="การเงิน"
                                    <?php echo ($company['business_sector'] === 'การเงิน') ? 'selected' : ''; ?>>การเงิน
                                </option>
                                <option value="การท่องเที่ยว"
                                    <?php echo ($company['business_sector'] === 'การท่องเที่ยว') ? 'selected' : ''; ?>>
                                    การท่องเที่ยว</option>
                                <option value="อาหารและเครื่องดื่ม"
                                    <?php echo ($company['business_sector'] === 'อาหารและเครื่องดื่ม') ? 'selected' : ''; ?>>
                                    อาหารและเครื่องดื่ม</option>
                                <option value="การศึกษา"
                                    <?php echo ($company['business_sector'] === 'การศึกษา') ? 'selected' : ''; ?>>
                                    การศึกษา</option>
                                <option value="สุขภาพและการแพทย์"
                                    <?php echo ($company['business_sector'] === 'สุขภาพและการแพทย์') ? 'selected' : ''; ?>>
                                    สุขภาพและการแพทย์</option>
                                <option value="ค้าปลีกและค้าส่ง"
                                    <?php echo ($company['business_sector'] === 'ค้าปลีกและค้าส่ง') ? 'selected' : ''; ?>>
                                    ค้าปลีกและค้าส่ง</option>
                                <option value="โลจิสติกส์"
                                    <?php echo ($company['business_sector'] === 'โลจิสติกส์') ? 'selected' : ''; ?>>
                                    โลจิสติกส์</option>
                                <option value="อื่นๆ"
                                    <?php echo ($company['business_sector'] === 'อื่นๆ') ? 'selected' : ''; ?>>อื่นๆ
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="company_desc" class="form-label">รายละเอียดบริษัท *</label>
                            <textarea class="form-control" id="company_desc" name="company_desc" rows="4"
                                required><?php echo htmlspecialchars($company['company_desc'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">ที่อยู่ *</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="province" class="form-label">จังหวัด *</label>
                                <select class="form-select" id="province" name="province" required>
                                    <option value="">เลือกจังหวัด</option>
                                    <option value="กรุงเทพมหานคร"
                                        <?php echo ($company['province'] === 'กรุงเทพมหานคร') ? 'selected' : ''; ?>>
                                        กรุงเทพมหานคร</option>
                                    <option value="เชียงใหม่"
                                        <?php echo ($company['province'] === 'เชียงใหม่') ? 'selected' : ''; ?>>
                                        เชียงใหม่</option>
                                    <option value="นนทบุรี"
                                        <?php echo ($company['province'] === 'นนทบุรี') ? 'selected' : ''; ?>>นนทบุรี
                                    </option>
                                    <option value="ปทุมธานี"
                                        <?php echo ($company['province'] === 'ปทุมธานี') ? 'selected' : ''; ?>>ปทุมธานี
                                    </option>
                                    <option value="สมุทรปราการ"
                                        <?php echo ($company['province'] === 'สมุทรปราการ') ? 'selected' : ''; ?>>
                                        สมุทรปราการ</option>
                                    <!-- เพิ่มจังหวัดอื่นๆ ตามต้องการ -->
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">รหัสไปรษณีย์ *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code"
                                    value="<?php echo htmlspecialchars($company['postal_code'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="website" class="form-label">เว็บไซต์</label>
                            <input type="url" class="form-control" id="website" name="website"
                                value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="contact_person" class="form-label">ชื่อผู้ติดต่อ *</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person"
                                value="<?php echo htmlspecialchars($company['contact_person'] ?? ''); ?>" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="update_company" class="btn btn-primary">บันทึกข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- การเปลี่ยนอีเมล -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนอีเมล</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_email" class="form-label">อีเมลปัจจุบัน</label>
                            <input type="email" class="form-control" id="current_email"
                                value="<?php echo htmlspecialchars($company['contact_email'] ?? ''); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_email" class="form-label">อีเมลใหม่</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="request_email_change"
                                class="btn btn-warning">ขอเปลี่ยนอีเมล</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- การเปลี่ยนเบอร์โทรศัพท์ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนเบอร์โทรศัพท์</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_phone" class="form-label">เบอร์โทรศัพท์ปัจจุบัน</label>
                            <input type="text" class="form-control" id="current_phone"
                                value="<?php echo htmlspecialchars($company['contact_phone'] ?? ''); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="new_phone" class="form-label">เบอร์โทรศัพท์ใหม่</label>
                            <input type="text" class="form-control" id="new_phone" name="new_phone" pattern="[0-9]{10}"
                                placeholder="กรอกเบอร์โทรศัพท์ 10 หลัก" required>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="request_phone_change"
                                class="btn btn-warning">ขอเปลี่ยนเบอร์โทรศัพท์</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- การเปลี่ยนรหัสผ่าน -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เปลี่ยนรหัสผ่าน</h5>
                </div>
                <div class="card-body">
                    <p>หากต้องการเปลี่ยนรหัสผ่าน กรุณาคลิกที่ปุ่มด้านล่าง</p>
                    <div class="text-end">
                        <a href="change_password.php" class="btn btn-warning">เปลี่ยนรหัสผ่าน</a>
                    </div>
                </div>
            </div>
    
            <!-- โลโก้บริษัท -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">โลโก้บริษัท</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="<?php echo getProfileImageUrl($company['logo_path'] ?? '', 'company'); ?>"
                            class="img-thumbnail" alt="Company Logo" style="max-height: 150px;">
                    </div>
                    <p>หากต้องการเปลี่ยนโลโก้บริษัท กรุณาคลิกที่ปุ่มด้านล่าง</p>
                    <div class="text-end">
                        <a href="update_company_logo.php" class="btn btn-warning">อัปเดตโลโก้</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>