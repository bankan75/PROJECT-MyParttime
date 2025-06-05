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

$page_title = "รายละเอียดบริษัททั้งหมด";
include(BASE_PATH . '/layouts/header.php');


// Get all companies
$sql = "SELECT * FROM companies ORDER BY company_id DESC";
$companies = $database->getRows($sql);

// Count total companies
$total_companies = count($companies);

// Count active/ready companies
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
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> กลับไปที่บริษัท
        </a>
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
            <div class="card-footer bg-transparent border-top-0">
                <a href="all_companies.php?status=ไม่ผ่านการพิจารณา" class="text-white text-decoration-none">
                    <small>ดูรายละเอียด <i class="fas fa-arrow-right ms-1"></i></small>
                </a>
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

<!-- Companies Details Table -->
<div class="card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">รายละเอียดบริษัท</h5>
    </div>
    <div class="card-body">
    <div class="table-responsive">
    <table class="table table-striped table-bordered table-hover" id="companiesTable">
    <thead class="table-dark bg-dark text-white">    
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อบริษัท</th>
                <th>ประเภทธุรกิจ</th>
                <th>บุคคลที่ติดต่อ</th>
                <th>อีเมล</th>
                <th>เบอร์</th>
                <th>ที่อยู่</th>
                <th>สถานะ</th>
                <th>วันที่สร้าง</th>
                <th>ชื่อผู้ใช้</th>
                <th>การดำเนินการ</th>
            </tr>
        </thead>
                <tbody>
                    <?php
                    if ($companies && count($companies) > 0) {
                        foreach ($companies as $company) {
                            // แปลงค่า is_verified เป็นข้อความสถานะ
                            $status_text = '';
                            $status_class = '';
                            
                            switch ($company['is_verified']) {
                                case 0:
                                    $status_text = 'รอพิจารณา';
                                    $status_class = 'bg-warning';
                                    break;
                                case 1:
                                    $status_text = 'พร้อมทำงาน';
                                    $status_class = 'bg-success';
                                    break;
                                case 2:
                                    $status_text = 'ไม่ผ่านการพิจารณา';
                                    $status_class = 'bg-danger';
                                    break;
                                default:
                                    $status_text = 'รอพิจารณา';
                                    $status_class = 'bg-warning';
                            }
                            
                            // จัดรูปแบบวันที่สร้าง
                            $created_date = date('d/m/Y', strtotime($company['created_at']));
                            ?>
                    <tr data-status="<?php echo $status_text; ?>" data-business-type="<?php echo htmlspecialchars($company['business_type']); ?>">
                        <td><?php echo $company['company_id']; ?></td>
                        <td>
                            <div class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip"
                                title="<?php echo htmlspecialchars($company['company_name']); ?>">
                                <?php echo htmlspecialchars($company['company_name']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($company['business_type']); ?></td>
                        <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" data-bs-toggle="tooltip"
                                title="<?php echo htmlspecialchars($company['contact_email']); ?>">
                                <?php echo htmlspecialchars($company['contact_email']); ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($company['contact_phone']); ?></td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" data-bs-toggle="tooltip"
                                title="<?php echo htmlspecialchars($company['address']); ?>">
                                <?php echo htmlspecialchars($company['address']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td><?php echo $created_date; ?></td>
                        <td><?php echo htmlspecialchars($company['username']); ?></td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <a href="view.php?id=<?php echo $company['company_id']; ?>" class="btn btn-sm btn-info"
                                    data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <!-- <a href="edit.php?id=<?php echo $company['company_id']; ?>"
                                    class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="แก้ไขข้อมูล">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $company['company_id']; ?>)"
                                    class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="ลบบริษัท">
                                    <i class="fas fa-trash"></i> -->
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        ?>
                    <tr>
                        <td colspan="11" class="text-center">ไม่พบข้อมูลบริษัท</td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Export Options -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">ส่งออกข้อมูล</h5>
    </div>
    <div class="card-body">
        <p>ส่งออกข้อมูลบริษัทในรูปแบบต่างๆ:</p>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> ส่งออกไปยัง Excel
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> ส่งออกไปยัง PDF
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="printTable()">
                <i class="fas fa-print"></i> พิมพ์
            </button>
        </div>
    </div>
</div>

<!-- Hidden table for PDF export (without action buttons) -->
<div id="exportTable" style="display:none;">
    <h2><?php echo SITE_NAME; ?> - รายงานข้อมูลบริษัททั้งหมด</h2>
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
                    $status_text = (isset($company['is_verified']) && $company['is_verified'] == 1) ? 'ใช้งานอยู่' : 'รอพิจารณา';
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
console.log('Export table content:', element.innerHTML);
function exportToPDF() {
    // Create a loading indication
    Swal.fire({
        title: 'กำลังสร้างไฟล์ PDF...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const element = document.getElementById('exportTable');
    
    // Show the hidden table for HTML capture
    element.style.display = 'block';
    
    // ให้เวลาในการเรนเดอร์ตาราง
    setTimeout(() => {
        // PDF options

const opt = {
    margin: 10,
    filename: 'companies_report_' + new Date().toISOString().split('T')[0] + '.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, logging: true },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
    // เพิ่มการระบุ CSS ที่ใช้
    pagebreak: { avoid: ['tr', 'td'] }
};
        
        // Generate PDF
        html2pdf().set(opt).from(element).save().then(function() {
            // Hide the element again
            element.style.display = 'none';
            
            // Close the loading dialog
            Swal.close();
            
            // Success message
            Swal.fire({
                icon: 'success',
                title: 'ส่งออกเสร็จสิ้น',
                text: 'ส่งออกข้อมูลเป็นไฟล์ PDF เรียบร้อยแล้ว',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            console.error('PDF Export Error:', err);
            element.style.display = 'none';
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถสร้างไฟล์ PDF ได้'
            });
        });
    }, 500); // รอ 500ms ให้ DOM อัพเดต
}

// Function to print the table
function printTable() {
    window.print();
}

// // Function to confirm delete with SweetAlert2
// function confirmDelete(companyId) {
//     Swal.fire({
//         title: 'คุณแน่ใจหรือไม่?',
//         text: "คุณจะไม่สามารถย้อนกลับได้หลังจากลบ!",
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#d33',
//         cancelButtonColor: '#3085d6',
//         confirmButtonText: 'ใช่, ลบเลย!',
//         cancelButtonText: 'ยกเลิก'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             window.location.href = 'delete.php?id=' + companyId;
//         }
//     });
// }
</script>

<?php
include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';

?>