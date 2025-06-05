<?php
// Include header
include(__DIR__ . '/../layouts/header.php');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ' . ROOT_URL . '/login.php');
    exit;
}

// Get company ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลบริษัท';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

$company_id = $_GET['id'];

// Get company data
$company = getCompanyProfile($database, $company_id);

if (!$company) {
    $_SESSION['error_message'] = 'ไม่พบข้อมูลบริษัท';
    header('Location: ' . ROOT_URL . '/dashboard.php');
    exit;
}

// Get company job posts
$job_posts = [];
$query = "SELECT * FROM jobs_posts WHERE company_id = ? ORDER BY update_date DESC";
$stmt = $database->prepare($query);
$stmt->execute([$company_id]);
$job_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-briefcase me-2"></i>สถานะ: <strong>มีตำแหน่งงานเปิดรับ <?php echo $job_count; ?> ตำแหน่ง</strong>
                    </div>
                    <p class="text-muted mb-1"><?php echo $company['business_type']; ?></p>
                    <p class="text-muted mb-4"><?php echo $company['province']; ?></p>

                    <!-- ปุ่มกลับไปหน้าก่อนหน้า -->
                    <a href="javascript:history.back()" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
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

            <?php if (!empty($job_posts)): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-briefcase me-2"></i>ตำแหน่งงานที่เปิดรับ
                            <span class="badge bg-primary ms-2"><?php echo count($job_posts); ?> ตำแหน่ง</span>
                        </h5>
                        <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php?company_id=<?php echo $company_id; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-1"></i>ดูงานทั้งหมด
                        </a>
                    </div>
                    <div class="card-body">
                        <?php foreach ($job_posts as $index => $job): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <i class="fas fa-briefcase fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1"><?php echo $job['job_title']; ?></h5>
                                    <p class="mb-0 small">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo $job['location']; ?> |
                                        <i class="fas fa-calendar-alt me-1"></i><?php echo $job['work_days']; ?> |
                                        <i class="fas fa-clock me-1"></i><?php echo $job['work_hours']; ?>
                                    </p>
                                    <a href="<?php echo ROOT_URL; ?>/modules/jobs/view.php?id=<?php echo $job['post_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-info-circle me-1"></i>ดูรายละเอียด
                                    </a>
                                </div>
                            </div>
                            <?php if ($index < count($job_posts) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>ตำแหน่งงานที่เปิดรับ</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted">ยังไม่มีตำแหน่งงานที่เปิดรับ</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>