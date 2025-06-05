<?php
// Include header
include(__DIR__ . '/../layouts/header.php');
// ตรวจสอบว่าเป็นบริษัทที่ล็อกอินแล้ว
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

$company_id = $_SESSION['user_id'];
$company = getCompanyProfile($db, $company_id);

if (!$company) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลบริษัท';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

// จัดการการอัปโหลดโลโก้
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadProfileImage($_FILES['logo'], 'company', $company_id);
        
        if ($upload_result['success']) {
            // ลบรูปเก่าถ้ามี
            if (!empty($company['logo_path'])) {
                deleteOldProfileImage($company['logo_path']);
            }
            
            // อัปเดตพาธรูปใหม่ในฐานข้อมูล
            if (updateCompanyLogo($db, $company_id, $upload_result['file_path'])) {
                $_SESSION['success_message'] = 'อัปโหลดโลโก้บริษัทสำเร็จ';
                // อัปเดตข้อมูลที่แสดง
                $company['logo_path'] = $upload_result['file_path'];
            } else {
                $_SESSION['error_message'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลโลโก้';
            }
        } else {
            $_SESSION['error_message'] = $upload_result['message'];
        }
        
        // Redirect เพื่อรีเฟรชหน้า
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$logo_url = getProfileImageUrl($company['logo_path'], 'company');
$job_count_query = "SELECT COUNT(*) FROM jobs_posts WHERE company_id = ? AND status = 'เปิดรับสมัคร'";
$job_count_stmt = $database->prepare($job_count_query);
$job_count_stmt->execute([$company_id]);
$job_count = $job_count_stmt->fetchColumn();
?>


<div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
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
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="<?php echo $logo_url; ?>" alt="โลโก้บริษัท" class="img-fluid" style="max-height: 150px;">
                    <h5 class="my-3"><?php echo $company['company_name']; ?></h5>
                    <p class="text-muted mb-1"><?php echo $company['business_type']; ?></p>
                    <p class="text-muted mb-4"><?php echo $company['province']; ?></p>
                    <div class="alert alert-info mb-3">
    <i class="fas fa-briefcase me-2"></i>สถานะ: <strong>มีตำแหน่งงานเปิดรับ <?php echo $job_count; ?> ตำแหน่ง</strong>
</div>
                    <!-- ปุ่มเปลี่ยนโลโก้ -->
                    <button type="button" class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#logoModal">
                        <i class="fas fa-camera me-2"></i>เปลี่ยนโลโก้บริษัท
                    </button>
                    
                    <!-- ปุ่มแก้ไขโปรไฟล์ -->
                    <a href="edit_company_profile.php" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>แก้ไขข้อมูลบริษัท
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลบริษัท</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ชื่อบริษัท</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['company_name']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เลขจดทะเบียนบริษัท</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['tax_id']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ประเภทธุรกิจ</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['business_type']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">รูปแบบบริษัท</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['company_type']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">กลุ่มอุตสาหกรรม</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['business_sector']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">รายละเอียดบริษัท</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">คำอธิบายบริษัท</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['company_desc']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ที่อยู่</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['address']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">จังหวัด</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['province']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">รหัสไปรษณีย์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['postal_code']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เว็บไซต์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0">
                                <?php if (!empty($company['website'])): ?>
                                <a href="<?php echo $company['website']; ?>" target="_blank"><?php echo $company['website']; ?></a>
                                <?php else: ?>
                                ไม่ระบุ
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลติดต่อ</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">ผู้ติดต่อ</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['contact_person']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">อีเมล</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['contact_email']; ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3">
                            <p class="mb-0">เบอร์โทรศัพท์</p>
                        </div>
                        <div class="col-sm-9">
                            <p class="text-muted mb-0"><?php echo $company['contact_phone']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal อัปโหลดโลโก้ -->
<div class="modal fade" id="logoModal" tabindex="-1" aria-labelledby="logoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoModalLabel">อัปโหลดโลโก้บริษัท</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="logo" class="form-label">เลือกรูปภาพ (สูงสุด 5MB)</label>
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg,image/png,image/gif" required>
                        <div class="form-text">รองรับไฟล์ภาพประเภท JPEG, PNG, GIF</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">อัปโหลด</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>