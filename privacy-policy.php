<?php
// Include configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
   突
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นโยบายความเป็นส่วนตัว - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
    body {
        background-color: #f8f9fa;
        padding-top: 2rem;
        padding-bottom: 2rem;
    }
    .policy-container {
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
    .card-body {
        padding: 2rem;
    }
    .policy-section {
        margin-bottom: 2rem;
    }
    .policy-section h5 {
        color: #000000;
        border-bottom: 1px solid #000000;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .policy-section p, .policy-section ul {
        color: #000000;
        font-size: 0.9rem;
    }
    .policy-section ul {
        padding-left: 1.5rem;
    }
    .policy-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .footer {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.875rem;
        color: #000000;
    }
    a {
        color: #007bff;
        text-decoration: underline;
    }
    </style>
</head>
<body>
    <div class="container policy-container">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-shield-alt policy-icon"></i>
                <h4 class="mb-0">นโยบายความเป็นส่วนตัว <?php echo SITE_NAME; ?></h4>
            </div>
            <div class="card-body">
                <div class="policy-section">
                    <h5>1. บทนำ</h5>
                    <p>นโยบายความเป็นส่วนตัวนี้ ("นโยบาย") อธิบายวิธีที่ <?php echo SITE_NAME; ?> ("เรา", "ของเรา") รวบรวม ใช้ เปิดเผย และปกป้องข้อมูลส่วนบุคคลของท่านตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA) และกฎหมายอื่นที่เกี่ยวข้อง</p>
                </div>

                <div class="policy-section">
                    <h5>2. ข้อมูลส่วนบุคคลที่เรารวบรวม</h5>
                    <p>เราอาจรวบรวมข้อมูลส่วนบุคคลต่อไปนี้จากท่าน:</p>
                    <ul>
                        <li>ข้อมูลระบุตัวตน เช่น ชื่อบริษัท ชื่อผู้ติดต่อ อีเมล เบอร์โทรศัพท์</li>
                        <li>ข้อมูลทางการเงิน เช่น เลขประจำตัวผู้เสียภาษี</li>
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
                    <p>เราใช้มาตรการรักษาความปลอดภัยที่เหมาะสม เช่น การเข้ารหัสและการควบ–

คุมการเข้าถึง เพื่อปกป้องข้อมูลส่วนบุคคลของท่านจากการเข้าถึงโดยไม่ได้รับอนุญาต การสูญหาย หรือการเปลี่ยนแปลง</p>
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
        </div>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo SITE_VERSION; ?>
        </div>
    </div>
    <script src="<?php echo ROOT_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>