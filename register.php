<?php
// Include configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// ถ้าล็อกอินแล้วให้ redirect ไปที่หน้า dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// ตรวจสอบการส่ง form
$errors = [];
$success = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจาก form
    $formData['company_name'] = trim($_POST['company_name'] ?? '');
    $formData['tax_id'] = trim($_POST['tax_id'] ?? '');
    $formData['username'] = trim($_POST['username'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['confirm_password'] = $_POST['confirm_password'] ?? '';
    $formData['business_type'] = trim($_POST['business_type'] ?? '');
    $formData['company_type'] = trim($_POST['company_type'] ?? '');
    $formData['business_sector'] = trim($_POST['business_sector'] ?? '');
    $formData['company_desc'] = trim($_POST['company_desc'] ?? '');
    $formData['address'] = trim($_POST['address'] ?? '');
    $formData['province'] = trim($_POST['province'] ?? '');
    $formData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $formData['website'] = trim($_POST['website'] ?? '');
    $formData['contact_person'] = trim($_POST['contact_person'] ?? '');
    $formData['contact_email'] = trim($_POST['contact_email'] ?? '');
    $formData['contact_phone'] = trim($_POST['contact_phone'] ?? '');
    $formData['pdpa_consent'] = isset($_POST['pdpa_consent']) ? true : false;
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($formData['company_name'])) {
        $errors[] = 'กรุณาระบุชื่อบริษัท';
    }
    
    if (empty($formData['tax_id'])) {
        $errors[] = 'กรุณาระบุเลขจดทะเบียนบริษัท';
    } elseif (!preg_match('/^\d{13}$/', $formData['tax_id'])) {
        $errors[] = 'เลขจดทะเบียนบริษัท 13 หลัก';
    }
    
    if (empty($formData['username'])) {
        $errors[] = 'กรุณาระบุชื่อผู้ใช้';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,50}$/', $formData['username'])) {
        $errors[] = 'ชื่อผู้ใช้ต้องประกอบด้วยตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ และมีความยาว 4-50 ตัวอักษร';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'กรุณาระบุรหัสผ่าน';
    } else {
        $passwordErrors = validatePassword($formData['password']);
        $errors = array_merge($errors, $passwordErrors);
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    }
    
    if (empty($formData['business_type'])) {
        $errors[] = 'กรุณาระบุประเภทธุรกิจ';
    }
    
    if (empty($formData['company_type'])) {
        $errors[] = 'กรุณาระบุรูปแบบบริษัท';
    }
    
    if (empty($formData['company_desc'])) {
        $errors[] = 'กรุณาระบุรายละเอียดบริษัท';
    }
    
    if (empty($formData['address'])) {
        $errors[] = 'กรุณาระบุที่อยู่';
    }
    
    if (empty($formData['province'])) {
        $errors[] = 'กรุณาระบุจังหวัด';
    }
    
    if (empty($formData['postal_code'])) {
        $errors[] = 'กรุณาระบุรหัสไปรษณีย์';
    } elseif (!preg_match('/^\d{5}$/', $formData['postal_code'])) {
        $errors[] = 'รหัสไปรษณีย์ต้องเป็นตัวเลข 5 หลัก';
    }
    
    if (empty($formData['contact_person'])) {
        $errors[] = 'กรุณาระบุชื่อผู้ติดต่อ';
    }
    
    if (empty($formData['contact_email'])) {
        $errors[] = 'กรุณาระบุอีเมลติดต่อ';
    } elseif (!filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    
    if (empty($formData['contact_phone'])) {
        $errors[] = 'กรุณาระบุเบอร์โทรศัพท์ติดต่อ';
    } elseif (!preg_match('/^\d{9,10}$/', $formData['contact_phone'])) {
        $errors[] = 'เบอร์โทรศัพท์ต้องเป็นตัวเลข 9-10 หลัก';
    }
    
    if (!$formData['pdpa_consent']) {
        $errors[] = 'กรุณายอมรับนโยบายความเป็นส่วนตัวและเงื่อนไขการใช้บริการ';
    }
    
    // ตรวจสอบว่าชื่อผู้ใช้ อีเมล หรือเลขจดทะเบียนธุรกิจซ้ำหรือไม่
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT company_id FROM companies WHERE username = ? OR contact_email = ? OR tax_id = ?");
        $stmt->execute([$formData['username'], $formData['contact_email'], $formData['tax_id']]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'ชื่อผู้ใช้ อีเมล หรือเลขจดทะเบียนธุรกิจนี้มีอยู่ในระบบแล้ว';
        }
    }

    // บันทึกข้อมูลถ้าไม่มีข้อผิดพลาด
    if (empty($errors)) {
        try {
            // เข้ารหัสรหัสผ่าน
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO companies 
                (company_name, tax_id, username, password, business_type, company_type, business_sector, 
                company_desc, address, province, postal_code, website, contact_person, contact_email, contact_phone, pdpa_consent) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $formData['company_name'],
                $formData['tax_id'],
                $formData['username'],
                $hashedPassword,
                $formData['business_type'],
                $formData['company_type'],
                $formData['business_sector'],
                $formData['company_desc'],
                $formData['address'],
                $formData['province'],
                $formData['postal_code'],
                $formData['website'],
                $formData['contact_person'],
                $formData['contact_email'],
                $formData['contact_phone'],
                $formData['pdpa_consent'] ? 1 : 0
            ]);
            
            $success = true;
            $formData = []; // ล้างข้อมูลฟอร์มหลังจากลงทะเบียนสำเร็จ
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาดในการลงทะเบียน: ' . $e->getMessage();
        }
    }
}

// ข้อมูลตัวเลือกสำหรับ dropdown
$businessTypes = [
    'ธุรกิจบริการ', 'ธุรกิจการผลิต', 'ธุรกิจการค้า', 'ธุรกิจเกษตรกรรม', 'ธุรกิจอสังหาริมทรัพย์', 'อื่นๆ'
];

$companyTypes = [
    'บริษัทจำกัด', 'บริษัทมหาชนจำกัด', 'ห้างหุ้นส่วนจำกัด', 'ห้างหุ้นส่วนสามัญนิติบุคคล', 'กิจการเจ้าของคนเดียว', 'อื่นๆ'
];

$businessSectors = [
    'เทคโนโลยีสารสนเทศ', 'การเงินและการธนาคาร', 'การศึกษา', 'สุขภาพและการแพทย์', 'การท่องเที่ยวและการบริการ',
    'อาหารและเครื่องดื่ม', 'ค้าปลีกและค้าส่ง', 'อสังหาริมทรัพย์และการก่อสร้าง', 'การผลิตและอุตสาหกรรม', 'อื่นๆ'
];

$provinces = [
    'กรุงเทพมหานคร', 'กระบี่', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา',
    'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด', 'ตาก', 'นครนายก',
    'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน',
    'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา',
    'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร',
    'แม่ฮ่องสอน', 'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน',
    'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว',
    'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู',
    'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชธานี'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนบริษัท - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
    body {
        background-color: #f8f9fa;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
    .register-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .card {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .card-header {
        background-color: #007bff;
        color: white;
        text-align: center;
        padding: 1.5rem;
        border-radius: 0.25rem 0.25rem 0 0 !important;
    }
    .btn-primary {
        width: 100%;
        padding: 0.6rem;
    }
    .form-label {
        font-weight: 500;
        color: #000000;
    }
    .form-section-title {
        border-bottom: 1px solid #000000;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
        color: #000000;
    }
    .form-text {
        color: #000000;
    }
    .register-footer {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.875rem;
        color: #000000;
    }
    .register-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .required-field::after {
        content: ' *';
        color: red;
    }
    .form-section {
        margin-bottom: 2rem;
    }
    .alert a {
        color: #000000;
    }
    .form-control,
    .form-select {
        color: #000000;
    }
    .pdpa-consent label {
        font-size: 0.9rem;
        color: #000000;
    }
    .pdpa-consent .policy-icon {
        color: #007bff;
        cursor: pointer;
        margin-left: 10px;
        font-size: 1.2rem;
    }
    .modal-content {
        color: #000000;
    }
    .modal-header {
        background-color: #007bff;
        color: white;
    }
    .modal-body {
        max-height: 400px;
        overflow-y: auto;
    }
    .policy-section {
        margin-bottom: 1rem;
    }
    .policy-section h5 {
        color: #000000;
        border-bottom: 1px solid #000000;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .policy-section p, .policy-section ul {
        font-size: 0.9rem;
    }
    .policy-section ul {
        padding-left: 1.5rem;
    }
    .password-requirements {
        margin-top: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
    }
    .password-requirements ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-bottom: 0;
    }
    .password-requirements .met {
        color: #28a745; /* Bootstrap green */
    }
    .toggle-password {
        cursor: pointer;
        padding: 0 10px;
        display: flex;
        align-items: center;
        color: #6c757d;
    }
    .toggle-password:hover {
        color: #007bff;
    }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="card mb-4">
            <div class="card-header">
                <img src="/myparttime/assets/images/logo-U-thon1.png" alt="Thonburi University Logo" style="width: 100px; height: auto; display: block; margin: 0 auto 10px;">
                <h4 class="mb-0">ลงทะเบียนบริษัท <?php echo SITE_NAME; ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <h4 class="alert-heading">ลงทะเบียนสำเร็จ!</h4>
                        <p>ขอบคุณที่ลงทะเบียนกับเรา ระบบจะดำเนินการตรวจสอบข้อมูลของท่านโดยเร็วที่สุด</p>
                        <hr>
                        <p class="mb-0">ท่านสามารถ <a href="login.php" class="alert-link">เข้าสู่ระบบ</a> เพื่อเริ่มใช้งานได้ทันที</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="" novalidate>
                        <div class="form-section">
                            <h5 class="form-section-title"><i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="company_name" class="form-label required-field">ชื่อบริษัท</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($formData['company_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tax_id" class="form-label required-field">เลขจดทะเบียนธุรกิจ</label>
                                    <input type="text" class="form-control" id="tax_id" name="tax_id" maxlength="13" value="<?php echo htmlspecialchars($formData['tax_id'] ?? ''); ?>" required>
                                    <div class="form-text">ตัวเลข 13 หลัก</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_type" class="form-label required-field">ประเภทธุรกิจ</label>
                                    <select class="form-select" id="business_type" name="business_type" required>
                                        <option value="" disabled selected>-- เลือกประเภทธุรกิจ --</option>
                                        <?php foreach ($businessTypes as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php echo (isset($formData['business_type']) && $formData['business_type'] === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="company_type" class="form-label required-field">รูปแบบบริษัท</label>
                                    <select class="form-select" id="company_type" name="company_type" required>
                                        <option value="" disabled selected>-- เลือกรูปแบบบริษัท --</option>
                                        <?php foreach ($companyTypes as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php echo (isset($formData['company_type']) && $formData['company_type'] === $type) ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="business_sector" class="form-label">ภาคธุรกิจ</label>
                                    <select class="form-select" id="business_sector" name="business_sector">
                                        <option value="" disabled selected>-- เลือกภาคธุรกิจ --</option>
                                        <?php foreach ($businessSectors as $sector): ?>
                                            <option value="<?php echo $sector; ?>" <?php echo (isset($formData['business_sector']) && $formData['business_sector'] === $sector) ? 'selected' : ''; ?>><?php echo $sector; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="website" class="form-label">เว็บไซต์</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($formData['website'] ?? ''); ?>">
                                    <div class="form-text">โปรดระบุรวม http:// หรือ https://</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="company_desc" class="form-label required-field">รายละเอียดบริษัท</label>
                                <textarea class="form-control" id="company_desc" name="company_desc" rows="4" required><?php echo htmlspecialchars($formData['company_desc'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i>ที่อยู่</h5>
                            <div class="mb-3">
                                <label for="address" class="form-label required-field">ที่อยู่</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="province" class="form-label required-field">จังหวัด</label>
                                    <select class="form-select" id="province" name="province" required>
                                        <option value="" disabled selected>-- เลือกจังหวัด --</option>
                                        <?php foreach ($provinces as $province): ?>
                                            <option value="<?php echo $province; ?>" <?php echo (isset($formData['province']) && $formData['province'] === $province) ? 'selected' : ''; ?>><?php echo $province; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="postal_code" class="form-label required-field">รหัสไปรษณีย์</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" maxlength="5" value="<?php echo htmlspecialchars($formData['postal_code'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="form-section-title"><i class="fas fa-user me-2"></i>ข้อมูลติดต่อ</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="contact_person" class="form-label required-field">ชื่อผู้ติดต่อ</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($formData['contact_person'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contact_email" class="form-label required-field">อีเมลติดต่อ</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($formData['contact_email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="contact_phone" class="form-label required-field">เบอร์โทรศัพท์ติดต่อ</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($formData['contact_phone'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="form-section-title"><i class="fas fa-lock me-2"></i>ข้อมูลเข้าสู่ระบบ</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="username" class="form-label required-field">ชื่อผู้ใช้</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required>
                                    <div class="form-text">ประกอบด้วยตัวอักษรภาษาอังกฤษ ตัวเลข หรือ _ และมีความยาว 4-50 ตัวอักษร</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label required-field">รหัสผ่าน</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="toggle-icon-password"></i>
                                        </span>
                                    </div>
                                    <div class="password-requirements">
                                        <ul>
                                            <li id="req-length"><span>รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</span></li>
                                            <li id="req-uppercase"><span>รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว</span></li>
                                            <li id="req-lowercase"><span>รหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว</span></li>
                                            <li id="req-special"><span>รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว</span></li>
                                        </ul>
                                    </div>
                                    <div id="password-feedback-container"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label required-field">ยืนยันรหัสผ่าน</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="toggle-icon-confirm_password"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h5 class="form-section-title"><i class="fas fa-shield-alt me-2"></i>นโยบายความเป็นส่วนตัว</h5>
                            <div class="mb-3 pdpa-consent">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="pdpa_consent" name="pdpa_consent" <?php echo (isset($formData['pdpa_consent']) && $formData['pdpa_consent']) ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="pdpa_consent">
                                        ฉันยอมรับนโยบายความเป็นส่วนตัวและเงื่อนไขการใช้บริการ
                                        <i class="fas fa-shield-alt policy-icon" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal" title="ดูนโยบายความเป็นส่วนตัว"></i>
                                        <i class="fas fa-file-contract policy-icon" data-bs-toggle="modal" data-bs-target="#termsOfServiceModal" title="ดูเงื่อนไขการใช้บริการ"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <button type="submit" class="btn btn-primary">ลงทะเบียน</button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="login.php" class="btn btn-outline-secondary w-100">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="register-footer">
            © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo SITE_VERSION; ?>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyPolicyModalLabel">นโยบายความเป็นส่วนตัว</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="policy-section">
                        <h5>1. บทนำ</h5>
                        <p>นโยบายความเป็นส่วนตัวนี้ ("นโยบาย") อธิบายวิธีที่ <?php echo SITE_NAME; ?> ("เรา", "ของเรา") รวบรวม ใช้ เปิดเผย และปกป้องข้อมูลส่วนบุคคลของท่านตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) และกฎหมายอื่นที่เกี่ยวข้อง</p>
                    </div>
                    <div class="policy-section">
                        <h5>2. ข้อมูลส่วนบุคคลที่เรารวบรวม</h5>
                        <p>เราอาจรวบรวมข้อมูลส่วนบุคคลต่อไปนี้จากท่าน:</p>
                        <ul>
                            <li>ข้อมูลระบุตัวตน เช่น ชื่อบริษัท ชื่อผู้ติดต่อ อีเมล เบอร์โทรศัพท์ เลขจดทะเบียนธุรกิจ</li>
                            <li>ข้อมูลที่อยู่ เช่น ที่อยู่บริษัท จังหวัด รหัสไปรษณีย์</li>
                            <li>ข้อมูลการใช้งาน เช่น ชื่อผู้ใช้ รหัสผ่าน ข้อมูลการเข้าสู่ระบบ</li>
                            <li>ข้อมูลอื่น ๆ ที่ท่านให้ผ่านแบบฟอร์มหรือการติดต่อกับเรา</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>3. วัตถุประสงค์ในการใช้ข้อมูล</h5>
                        <p>เราใช้ข้อมูลส่วนบุคคลของท่านเพื่อ:</p>
                        <ul>
                            <li>ให้บริการและจัดการบัญชีของท่าน</li>
                            <li>ดำเนินการลงทะเบียนและยืนยันตัวตน</li>
                            <li>สื่อสารกับท่านเกี่ยวกับบริการหรือการอัปเดต</li>
                            <li>ปฏิบัติตามกฎหมายและข้อบังคับ</li>
                            <li>ปรับปรุงและพัฒนาบริการของเรา</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>4. การเปิดเผยข้อมูล</h5>
                        <p>เราจะไม่เปิดเผยข้อมูลส่วนบุคคลของท่านต่อบุคคลที่สาม ยกเว้น:</p>
                        <ul>
                            <li>เมื่อได้รับความยินยอมจากท่าน</li>
                            <li>เมื่อจำเป็นต้องปฏิบัติตามกฎหมาย</li>
                            <li>เมื่อจำเป็นต้องให้บริการ เช่น ผู้ให้บริการด้านเทคโนโลยีที่ได้รับอนุญาต</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>5. การปกป้องข้อมูล</h5>
                        <p>เราใช้มาตรการรักษาความปลอดภัยที่เหมาะสม เช่น การเข้ารหัสและการควบคุมการเข้าถึง เพื่อปกป้องข้อมูลส่วนบุคคลของท่านจากการเข้าถึงโดยไม่ได้รับอนุญาต การสูญหาย หรือการเปลี่ยนแปลง</p>
                    </div>
                    <div class="policy-section">
                        <h5>6. สิทธิของเจ้าของข้อมูล</h5>
                        <p>ท่านมีสิทธิในข้อมูลส่วนบุคคลของท่าน ดังนี้:</p>
                        <ul>
                            <li>สิทธิในการเข้าถึงและขอสำเนาข้อมูล</li>
                            <li>สิทธิในการแก้ไขข้อมูลที่ไม่ถูกต้อง</li>
                            <li>สิทธิในการขอให้ลบข้อมูล</li>
                            <li>สิทธิในการคัดค้านหรือจำกัดการประมวลผล</li>
                            <li>สิทธิในการถอนความยินยอม</li>
                        </ul>
                        <p>ท่านสามารถติดต่อเราเพื่อใช้สิทธิเหล่านี้ได้ที่ <a href="mailto:support@<?php echo SITE_NAME; ?>.com">support@<?php echo SITE_NAME; ?>.com</a></p>
                    </div>
                    <div class="policy-section">
                        <h5>7. การเปลี่ยนแปลงนโยบาย</h5>
                        <p>เราอาจปรับปรุงนโยบายนี้เป็นครั้งคราว การเปลี่ยนแปลงใด ๆ จะมีผลเมื่อเราเผยแพร่นโยบายฉบับปรับปรุงบนเว็บไซต์ของเรา</p>
                    </div>
                    <div class="policy-section">
                        <h5>8. การติดต่อเรา</h5>
                        <p>หากท่านมีคำถามเกี่ยวกับนโยบายนี้ โปรดติดต่อ:</p>
                        <ul>
                            <li>อีเมล: <a href="mailto:support@<?php echo SITE_NAME; ?>.com">support@<?php echo SITE_NAME; ?>.com</a></li>
                            <li>ที่อยู่: [ระบุที่อยู่บริษัท]</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms of Service Modal -->
    <div class="modal fade" id="termsOfServiceModal" tabindex="-1" aria-labelledby="termsOfServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsOfServiceModalLabel">เงื่อนไขการใช้บริการ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="policy-section">
                        <h5>1. บทนำ</h5>
                        <p>ยินดีต้อนรับสู่ <?php echo SITE_NAME; ?> เงื่อนไขการใช้บริการนี้ ("เงื่อนไข") ควบคุมการใช้งานเว็บไซต์และบริการของเรา การใช้บริการของเราถือว่าท่านยอมรับเงื่อนไขเหล่านี้</p>
                    </div>
                    <div class="policy-section">
                        <h5>2. การลงทะเบียนและบัญชีผู้ใช้</h5>
                        <p>ในการใช้บริการของเรา ท่านต้อง:</p>
                        <ul>
                            <li>ลงทะเบียนบัญชีด้วยข้อมูลที่ถูกต้องและครบถ้วน</li>
                            <li>รักษาความปลอดภัยของชื่อผู้ใช้และรหัสผ่าน</li>
                            <li>แจ้งให้เราทราบทันทีหากมีการใช้งานบัญชีโดยไม่ได้รับอนุญาต</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>3. การใช้งานที่ยอมรับได้</h5>
                        <p>ท่านตกลงว่าจะไม่:</p>
                        <ul>
                            <li>ใช้บริการของเราเพื่อวัตถุประสงค์ที่ผิดกฎหมาย</li>
                            <li>พยายามเข้าถึงระบบหรือข้อมูลโดยไม่ได้รับอนุญาต</li>
                            <li>อัปโหลดหรือแชร์เนื้อหาที่ละเมิดลิขสิทธิ์หรือเป็นอันตราย</li>
                            <li>รบกวนหรือขัดขวางการทำงานของบริการ</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>4. เนื้อหาของผู้ใช้</h5>
                        <p>เนื้อหาที่ท่านอัปโหลดหรือให้ไว้ผ่านบริการ:</p>
                        <ul>
                            <li>ต้องเป็นของท่านหรือท่านมีสิทธิ์ในการใช้งาน</li>
                            <li>เราอาจตรวจสอบหรือลบเนื้อหาที่ไม่เหมาะสม</li>
                            <li>ท่านยังคงเป็นเจ้าของเนื้อหา แต่ให้สิทธิ์เราในการใช้งานเพื่อให้บริการ</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>5. การยกเลิกและการระงับ</h5>
                        <p>เราสงวนสิทธิ์ในการ:</p>
                        <ul>
                            <li>ระงับหรือยกเลิกบัญชีของท่านหากละเมิดเงื่อนไข</li>
                            <li>หยุดให้บริการบางส่วนหรือทั้งหมดตามดุลยพินิจของเรา</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>6. ข้อจำกัดความรับผิด</h5>
                        <p>เราให้บริการ "ตามสภาพ" และไม่รับประกันว่า:</p>
                        <ul>
                            <li>บริการจะปราศจากข้อผิดพลาดหรือการหยุดชะงัก</li>
                            <li>เราจะไม่รับผิดชอบต่อความเสียหายทางอ้อมหรือพิเศษที่เกิดจากการใช้บริการ</li>
                        </ul>
                    </div>
                    <div class="policy-section">
                        <h5>7. การเปลี่ยนแปลงเงื่อนไข</h5>
                        <p>เราอาจปรับปรุงเงื่อนไขนี้เป็นครั้งคราว การเปลี่ยนแปลงจะมีผลเมื่อเราเผยแพร่เงื่อนไขฉบับปรับปรุงบนเว็บไซต์ของเรา</p>
                    </div>
                    <div class="policy-section">
                        <h5>8. การติดต่อเรา</h5>
                        <p>หากท่านมีคำถามเกี่ยวกับเงื่อนไขนี้ โปรดติดต่อ:</p>
                        <ul>
                            <li>อีเมล: <a href="mailto:support@<?php echo SITE_NAME; ?>.com">support@<?php echo SITE_NAME; ?>.com</a></li>
                            <li>ที่อยู่: [ระบุที่อยู่บริษัท]</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="<?php echo ROOT_URL; ?>/assets/css/bootstrap.bundle.min.js?v=<?php echo time(); ?>">
    <script>
        // Bootstrap form validation
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        
        // Real-time password validation with dynamic requirement styling
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const feedbackContainer = document.getElementById('password-feedback-container');
            const feedback = document.createElement('div');
            feedback.className = 'form-text text-danger';
            feedback.id = 'password-feedback';
            
            // Get requirement elements
            const reqLength = document.querySelector('#req-length span');
            const reqUppercase = document.querySelector('#req-uppercase span');
            const reqLowercase = document.querySelector('#req-lowercase span');
            const reqSpecial = document.querySelector('#req-special span');
            
            // Check each condition and update styling
            let errors = [];
            
            // Length requirement
            if (password.length < 8) {
                errors.push('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
                reqLength.classList.remove('met');
            } else {
                reqLength.classList.add('met');
            }
            
            // Uppercase requirement
            if (!/[A-Z]/.test(password)) {
                errors.push('รหัสผ่านต้องมีตัวอักษรพิมพ์ใหญ่อย่างน้อย 1 ตัว');
                reqUppercase.classList.remove('met');
            } else {
                reqUppercase.classList.add('met');
            }
            
            // Lowercase requirement
            if (!/[a-z]/.test(password)) {
                errors.push('รหัสผ่านต้องมีตัวอักษรพิมพ์เล็กอย่างน้อย 1 ตัว');
                reqLowercase.classList.remove('met');
            } else {
                reqLowercase.classList.add('met');
            }
            
            // Special character requirement
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                errors.push('รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว');
                reqSpecial.classList.remove('met');
            } else {
                reqSpecial.classList.add('met');
            }
            
            // Update feedback
            const existingFeedback = document.getElementById('password-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            if (errors.length > 0) {
                feedback.innerHTML = errors.join('<br>');
                feedbackContainer.appendChild(feedback);
            }
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById('toggle-icon-' + inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>