<?php
// เริ่มต้น session และตรวจสอบการล็อกอิน
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// นำเข้าไฟล์ที่จำเป็น
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// สร้างอ็อบเจ็กต์ฐานข้อมูลและการยืนยันตัวตน
$db = new Database();
$auth = new Auth($db->getConnection());

// ตรวจสอบการล็อกอิน
$auth->requireLogin();

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะบริษัทเท่านั้น)
if (!$auth->isAdmin()) {
    // ถ้าไม่ใช่บริษัท ให้เปลี่ยนเส้นทาง
    header("Location: ../../index.php");
    exit;
}
// Ensure config is loaded first
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';



$page_title = "จัดการข้อมูลบริษัท";
include(BASE_PATH . '/layouts/header.php');

// Get all companies
$sql = "SELECT * FROM companies ORDER BY company_id DESC";
$companies = $database->getRows($sql);

// Count total companies
$total_companies = count($companies);

// Count active companies
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 1";
$active_result = $database->getRow($sql, []);
$active_companies = ($active_result !== false) ? $active_result['count'] : 0;

// Count pending companies
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 0";
$pending_result = $database->getRow($sql, []);
$pending_companies = ($pending_result !== false) ? $pending_result['count'] : 0;

// Count rejected companies
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 2";
$rejected_result = $database->getRow($sql, []);
$rejected_companies = ($rejected_result !== false) ? $rejected_result['count'] : 0;

?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">จัดการบริษัท</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- <a href="all_companies.php" class="btn btn-primary me-2">
            <i class="fas fa-list"></i> รายละเอียดบริษัททั้งหมด
        </a> -->
        <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
        <!-- <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> เพิ่มบริษัทใหม่
        </a> -->
    </div>
</div>

<!-- Company Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary h-75">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-building fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">บริษัททั้งหมด</h5>
                    <h2 class="display-4"><?php echo $total_companies; ?></h2>
                </div>
            </div>

        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success h-75">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-check-circle fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">บริษัทที่พร้อมทำงาน</h5>
                    <h2 class="display-4"><?php echo $active_companies; ?></h2>
                </div>
            </div>

        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning h-75">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-clock fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">บริษัทที่รอพิจารณา</h5>
                    <h2 class="display-4"><?php echo $pending_companies; ?></h2>
                </div>
            </div>

        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger h-75">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-times-circle fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">บริษัทที่ไม่ผ่าน</h5>
                    <h2 class="display-4"><?php echo $rejected_companies; ?></h2>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">ค้นหาและกรองข้อมูล</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาบริษัท...">
            </div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-select">
                    <option value="">-- สถานะทั้งหมด --</option>
                    <option value="พร้อมทำงาน">พร้อมทำงาน</option>
                    <option value="รอพิจารณา">รอพิจารณา</option>
                    <option value="ไม่ผ่านการพิจารณา">ไม่ผ่านการพิจารณา</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="businessTypeFilter" class="form-select">
                    <option value="">-- ประเภทธุรกิจทั้งหมด --</option>
                    <?php
                    // Get unique business types
                    $business_types = [];
                    foreach ($companies as $company) {
                        if (!empty($company['business_type']) && !in_array($company['business_type'], $business_types)) {
                            $business_types[] = $company['business_type'];
                        }
                    }
                    sort($business_types);
                    
                    foreach ($business_types as $type) {
                        echo "<option value=\"" . htmlspecialchars($type) . "\">" . htmlspecialchars($type) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <button id="resetFilter" class="btn btn-primary w-100">รีเซ็ต</button>
            </div>
        </div>
    </div>
</div>

<!-- Companies Table Card -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>รายการบริษัท</h5>
            <!-- <div>
                <button type="button" class="btn btn-sm btn-outline-light" onclick="exportToExcel()">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-light ms-2" onclick="printTable()">
                    <i class="fas fa-print me-1"></i> พิมพ์
                </button>
            </div> -->
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="companiesTable">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">ลำดับ</th>
                        <th>ชื่อบริษัท</th>
                        <th>บุคคลที่ติดต่อ</th>
                        <th>อีเมล</th>
                        <th>เบอร์</th>
                        <th class="text-center">สถานะ</th>
                        <th class="text-center">วันที่สร้าง</th>
                        <th class="text-center">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($companies && count($companies) > 0) {
                        foreach ($companies as $company) {
                            // แปลงค่า is_verified เป็นข้อความสถานะ
                            if (isset($company['is_verified'])) {
                                if ($company['is_verified'] == 1) {
                                    $status_text = 'พร้อมทำงาน';
                                    $status_class = 'bg-success';
                                } else if ($company['is_verified'] == 2) {
                                    $status_text = 'ไม่ผ่านการพิจารณา';
                                    $status_class = 'bg-danger';
                                } else {
                                    $status_text = 'รอพิจารณา';
                                    $status_class = 'bg-warning';
                                }
                            } else {
                                $status_text = 'รอพิจารณา';
                                $status_class = 'bg-warning';
                            }
                            
                            // จัดรูปแบบวันที่สร้าง
                            $created_date = date('d/m/Y', strtotime($company['created_at']));
                    ?>
                    <tr data-status="<?php echo $status_text; ?>" data-business-type="<?php echo htmlspecialchars($company['business_type']); ?>">
                        <td class="text-center"><?php echo $company['company_id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="company-icon me-2 rounded-circle d-flex align-items-center justify-content-center bg-light" style="width:40px;height:40px;">
                                    <i class="fas fa-building text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($company['company_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($company['business_type']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($company['contact_email']); ?>">
                                <?php echo htmlspecialchars($company['contact_email']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($company['contact_phone']); ?></td>
                        <td class="text-center">
                            <span class="badge rounded-pill <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $created_date; ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="view.php?id=<?php echo $company['company_id']; ?>" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <!-- <a href="edit.php?id=<?php echo $company['company_id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="แก้ไขข้อมูล">
                                    <i class="fas fa-edit"></i>
                                </a> -->
                                <!-- <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $company['company_id']; ?>)" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="ลบบริษัท">
                                    <i class="fas fa-trash"></i>
                                </a> -->
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                    ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <p>ไม่พบข้อมูลบริษัท</p>
                            </div>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Actions Card -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>การดำเนินการด่วน</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- <div class="col-md-3">
                <a href="add.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center py-3">
                    <i class="fas fa-plus-circle me-2"></i> เพิ่มบริษัทใหม่
                </a>
            </div> -->
            <!-- <div class="col-md-3">
                <a href="approval.php" class="btn btn-outline-warning w-100 d-flex align-items-center justify-content-center py-3">
                    <i class="fas fa-tasks me-2"></i> อนุมัติบริษัทรอพิจารณา
                </a>
            </div> -->
            <div class="col-md-3">
                <a href="report.php" class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center py-3">
                    <i class="fas fa-chart-bar me-2"></i> รายงานสรุป
                </a>
            </div>
            <!-- <div class="col-md-3">
                <a href="all_companies.php" class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center py-3">
                    <i class="fas fa-list-alt me-2"></i> รายละเอียดทั้งหมด
                </a>
            </div> -->
        </div>
    </div>
</div>

<!-- Hidden table for export -->
<div id="exportTable" style="display:none;">
    <h2><?php echo SITE_NAME; ?> - รายงานข้อมูลบริษัท</h2>
    <p>วันที่ออกรายงาน: <?php echo date('d/m/Y H:i'); ?></p>
    <table class="table table-bordered">
        <thead class="table-dark"> 
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อบริษัท</th>
                <th>ประเภทธุรกิจ</th>
                <th>บุคคลที่ติดต่อ</th>
                <th>อีเมล</th>
                <th>เบอร์</th>
                <th>สถานะ</th>
                <th>วันที่สร้าง</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($companies && count($companies) > 0) {
                foreach ($companies as $company) {
                    if (isset($company['is_verified'])) {
                        if ($company['is_verified'] == 1) {
                            $status_text = 'พร้อมทำงาน';
                        } else if ($company['is_verified'] == 2) {
                            $status_text = 'ไม่ผ่านการพิจารณา';
                        } else {
                            $status_text = 'รอพิจารณา';
                        }
                    } else {
                        $status_text = 'รอพิจารณา';
                    }
                    
                    $created_date = date('d/m/Y', strtotime($company['created_at']));
                    ?>
            <tr>
                <td><?php echo $company['company_id']; ?></td>
                <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                <td><?php echo htmlspecialchars($company['business_type']); ?></td>
                <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                <td><?php echo htmlspecialchars($company['contact_email']); ?></td>
                <td><?php echo htmlspecialchars($company['contact_phone']); ?></td>
                <td><?php echo $status_text; ?></td>
                <td><?php echo $created_date; ?></td>
            </tr>
            <?php
                }
            } else {
                ?>
            <tr>
                <td colspan="8" class="text-center">ไม่พบข้อมูลบริษัท</td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Filter functions
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const businessTypeFilter = document.getElementById('businessTypeFilter');
    const resetButton = document.getElementById('resetFilter');
    const tableRows = document.querySelectorAll('#companiesTable tbody tr');
    
    // Search function
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const businessType = businessTypeFilter.value;
        
        tableRows.forEach(row => {
            const rowData = row.textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            const rowBusinessType = row.getAttribute('data-business-type');
            
            const matchSearch = rowData.includes(searchTerm);
            const matchStatus = statusValue === '' || status === statusValue;
            const matchBusinessType = businessType === '' || rowBusinessType === businessType;
            
            if (matchSearch && matchStatus && matchBusinessType) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Event listeners
    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
    businessTypeFilter.addEventListener('change', filterTable);
    
    // Reset filters
    resetButton.addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        businessTypeFilter.value = '';
        tableRows.forEach(row => {
            row.style.display = '';
        });
    });
});

// Export to Excel function using SheetJS library
function exportToExcel() {
    // Create a loading indication
    Swal.fire({
        title: 'กำลังส่งออกข้อมูล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Load SheetJS library dynamically
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js';
    script.onload = function() {
        // Get table data
        const table = document.getElementById('companiesTable');
        const ws = XLSX.utils.table_to_sheet(table, {raw: true});
        
        // Create workbook and add worksheet
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Companies");
        
        // Generate file name with current date
        const fileName = 'companies_report_' + new Date().toISOString().split('T')[0] + '.xlsx';
        
        // Write and download
        XLSX.writeFile(wb, fileName);
        
        // Close the loading dialog
        Swal.close();
        
        // Success message
        Swal.fire({
            icon: 'success',
            title: 'ส่งออกเสร็จสิ้น',
            text: 'ส่งออกข้อมูลเป็นไฟล์ Excel เรียบร้อยแล้ว',
            timer: 2000,
            showConfirmButton: false
        });
    };
    
    script.onerror = function() {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลด SheetJS library ได้'
        });
    };
    
    document.body.appendChild(script);
}

// Function to print the table
function printTable() {
    window.print();
}

// Function to confirm delete
function confirmDelete(companyId) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณต้องการลบบริษัทนี้ใช่หรือไม่? การดำเนินการนี้ไม่สามารถย้อนกลับได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + companyId;
        }
    });
}
</script>
<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';

?>