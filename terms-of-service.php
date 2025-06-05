<?php
// Include configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เงื่อนไขการใช้บริการ - <?php echo SITE_NAME; ?></title>
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
                <i class="fas fa-file-contract policy-icon"></i>
                <h4 class="mb-0">เงื่อนไขการใช้บริการ <?php echo SITE_NAME; ?></h4>
            </div>
            <div class="card-body">
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
        </div>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> v<?php echo SITE_VERSION; ?>
        </div>
    </div>
    
    <script src="<?php echo ROOT_URL; ?>assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>