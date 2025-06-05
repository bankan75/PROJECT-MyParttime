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


// Add export functionality handling
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'])) {
    // Get companies statistics
    // Total companies
    $sql = "SELECT COUNT(*) as count FROM companies";
    $total_result = $database->getRow($sql);
    $total_companies = ($total_result !== false) ? $total_result['count'] : 0;

    // Active companies
    $sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 1";
    $active_result = $database->getRow($sql);
    $active_companies = ($active_result !== false) ? $active_result['count'] : 0;

    // Pending companies
    $sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 0";
    $pending_result = $database->getRow($sql);
    $pending_companies = ($pending_result !== false) ? $pending_result['count'] : 0;
    
    // Rejected companies - เพิ่มการดึงข้อมูลบริษัทที่ไม่ผ่านการพิจารณา
    $sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 2";
    $rejected_result = $database->getRow($sql);
    $rejected_companies = ($rejected_result !== false) ? $rejected_result['count'] : 0;

    // Companies by business type
    $sql = "SELECT business_type, COUNT(*) as count FROM companies GROUP BY business_type ORDER BY count DESC";
    $business_types = $database->getRows($sql);

    // Companies registered by month
    $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM companies 
            GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
            ORDER BY month DESC 
            LIMIT 12";
    $monthly_registrations = $database->getRows($sql);

    // Export as Excel
    if ($_GET['export'] == 'excel') {
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="รายงานข้อมูลบริษัท-' . date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        
        // Generate excel content
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<title>รายงานข้อมูลบริษัท</title>';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
        echo 'th { background-color: #f2f2f2; }';
        echo '.header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }';
        echo '.subheader { font-size: 14px; font-weight: bold; margin: 15px 0 5px 0; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<div class="header">รายงานสรุปข้อมูลบริษัท ณ วันที่ ' . date('d/m/Y') . '</div>';
        
        // Summary statistics
        echo '<div class="subheader">สรุปภาพรวม</div>';
        echo '<table>';
        echo '<tr><th>รายการ</th><th>จำนวน</th><th>ร้อยละ</th></tr>';
        echo '<tr><td>บริษัททั้งหมด</td><td>' . $total_companies . '</td><td>100%</td></tr>';
        echo '<tr><td>บริษัทที่พร้อมทำงาน</td><td>' . $active_companies . '</td><td>' . ($total_companies > 0 ? round(($active_companies / $total_companies) * 100, 1) : 0) . '%</td></tr>';
        echo '<tr><td>บริษัทที่รอพิจารณา</td><td>' . $pending_companies . '</td><td>' . ($total_companies > 0 ? round(($pending_companies / $total_companies) * 100, 1) : 0) . '%</td></tr>';
        echo '<tr><td>บริษัทที่ไม่ผ่านการพิจารณา</td><td>' . $rejected_companies . '</td><td>' . ($total_companies > 0 ? round(($rejected_companies / $total_companies) * 100, 1) : 0) . '%</td></tr>';
        echo '</table>';
        
        // Business types
        echo '<div class="subheader">สัดส่วนประเภทธุรกิจ</div>';
        echo '<table>';
        echo '<tr><th>ประเภทธุรกิจ</th><th>จำนวน</th><th>ร้อยละ</th></tr>';
        if (count($business_types) > 0) {
            foreach ($business_types as $type) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($type['business_type']) . '</td>';
                echo '<td>' . $type['count'] . '</td>';
                echo '<td>' . ($total_companies > 0 ? round(($type['count'] / $total_companies * 100), 1) : 0) . '%</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">ไม่พบข้อมูลประเภทธุรกิจ</td></tr>';
        }
        echo '</table>';
        
        // Monthly registration
        echo '<div class="subheader">การลงทะเบียนบริษัทรายเดือน</div>';
        echo '<table>';
        echo '<tr><th>เดือน</th><th>จำนวนบริษัทที่ลงทะเบียน</th></tr>';
        if (count($monthly_registrations) > 0) {
            foreach (array_reverse($monthly_registrations) as $month) {
                $date = new DateTime($month['month'] . '-01');
                echo '<tr>';
                echo '<td>' . $date->format('M Y') . '</td>';
                echo '<td>' . $month['count'] . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="2">ไม่พบข้อมูลการลงทะเบียนรายเดือน</td></tr>';
        }
        echo '</table>';
        
        echo '</body>';
        echo '</html>';
        exit;
    }
    
    // Export as PDF
    if ($_GET['export'] == 'pdf') {
        // Redirect back if html2pdf.js script hasn't run yet
        if (!isset($_POST['html_content'])) {
            exit;
        }
        
        // Get the HTML content from the POST data
        $html_content = $_POST['html_content'];
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment;filename="รายงานข้อมูลบริษัท-' . date('Y-m-d') . '.pdf"');
        
        // Output the PDF data (this is actually handled by the javascript html2pdf library)
        echo $html_content;
        exit;
    }
}

$page_title = "รายงานสรุปข้อมูลบริษัท";
include(BASE_PATH . '/layouts/header.php');

// Get companies statistics
// Total companies
$sql = "SELECT COUNT(*) as count FROM companies";
$total_result = $database->getRow($sql);
$total_companies = ($total_result !== false) ? $total_result['count'] : 0;

// Active companies
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 1";
$active_result = $database->getRow($sql);
$active_companies = ($active_result !== false) ? $active_result['count'] : 0;

// Pending companies
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 0";
$pending_result = $database->getRow($sql);
$pending_companies = ($pending_result !== false) ? $pending_result['count'] : 0;

// Rejected companies - เพิ่มการดึงข้อมูลบริษัทที่ไม่ผ่านการพิจารณา
$sql = "SELECT COUNT(*) as count FROM companies WHERE is_verified = 2";
$rejected_result = $database->getRow($sql);
$rejected_companies = ($rejected_result !== false) ? $rejected_result['count'] : 0;

// Companies by business type
$sql = "SELECT business_type, COUNT(*) as count FROM companies GROUP BY business_type ORDER BY count DESC";
$business_types = $database->getRows($sql);

// Companies registered by month
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM companies 
        GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
        ORDER BY month DESC 
        LIMIT 12";
$monthly_registrations = $database->getRows($sql);

// Companies by verification status - เพิ่มสถานะไม่ผ่านการพิจารณา
$verification_data = [
    ['name' => 'พร้อมทำงาน', 'value' => $active_companies],
    ['name' => 'รอพิจารณา', 'value' => $pending_companies],
    ['name' => 'ไม่ผ่านการพิจารณา', 'value' => $rejected_companies]
];

// Recently registered companies
$sql = "SELECT * FROM companies ORDER BY created_at DESC LIMIT 5";
$recent_companies = $database->getRows($sql);

?>

      
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">รายงานสรุปข้อมูลบริษัท</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-outline-success" id="exportExcel">
                <i class="fas fa-file-excel me-1"></i> Excel
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="exportPDF">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="printTable()">
            <i class="fas fa-print me-1"></i> พิมพ์รายงาน
        </button>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left me-1"></i> กลับไปหน้าหลัก
        </a>
    </div>
</div>
<div id="printableReport">
    <!-- Overview Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">บริษัททั้งหมด</h6>
                            <h2 class="display-4"><?php echo $total_companies; ?></h2>
                        </div>
                        <i class="fas fa-building fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">บริษัทที่พร้อมทำงาน</h6>
                            <h2 class="display-4"><?php echo $active_companies; ?></h2>
                            <small class="opacity-75"><?php echo ($total_companies > 0) ? round(($active_companies / $total_companies) * 100) : 0; ?>% ของทั้งหมด</small>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">บริษัทที่รอพิจารณา</h6>
                            <h2 class="display-4"><?php echo $pending_companies; ?></h2>
                            <small class="opacity-75"><?php echo ($total_companies > 0) ? round(($pending_companies / $total_companies) * 100) : 0; ?>% ของทั้งหมด</small>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">ไม่ผ่านการพิจารณา</h6>
                            <h2 class="display-4"><?php echo $rejected_companies; ?></h2>
                            <small class="opacity-75"><?php echo ($total_companies > 0) ? round(($rejected_companies / $total_companies) * 100) : 0; ?>% ของทั้งหมด</small>
                        </div>
                        <i class="fas fa-times-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Company Types Chart -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>สัดส่วนประเภทธุรกิจ</h5>
                </div>
                <div class="card-body">
                    <?php if (count($business_types) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>ประเภทธุรกิจ</th>
                                        <th class="text-center">จำนวน</th>
                                        <th>สัดส่วน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($business_types as $type): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($type['business_type']); ?></td>
                                            <td class="text-center"><?php echo $type['count']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: <?php echo ($total_companies > 0) ? ($type['count'] / $total_companies * 100) : 0; ?>%" 
                                                         aria-valuenow="<?php echo $type['count']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="<?php echo $total_companies; ?>">
                                                         <?php echo ($total_companies > 0) ? round(($type['count'] / $total_companies * 100), 1) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">ไม่พบข้อมูลประเภทธุรกิจ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>สถานะบริษัท</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 230px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Monthly Registration Trend -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>แนวโน้มการลงทะเบียนบริษัทรายเดือน</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 250px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>บริษัทที่ลงทะเบียนล่าสุด</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (count($recent_companies) > 0): ?>
                            <?php foreach ($recent_companies as $company): ?>
                                <a href="view.php?id=<?php echo $company['company_id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($company['company_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($company['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($company['business_type']); ?></p>
                                    <small class="text-<?php 
                                        if ($company['is_verified'] == 1) {
                                            echo 'success';
                                        } elseif ($company['is_verified'] == 0) {
                                            echo 'warning';
                                        } else {
                                            echo 'danger';
                                        }
                                    ?>">
                                        <i class="fas fa-<?php 
                                            if ($company['is_verified'] == 1) {
                                                echo 'check-circle';
                                            } elseif ($company['is_verified'] == 0) {
                                                echo 'clock';
                                            } else {
                                                echo 'times-circle';
                                            }
                                        ?> me-1"></i>
                                        <?php 
                                            if ($company['is_verified'] == 1) {
                                                echo 'พร้อมทำงาน';
                                            } elseif ($company['is_verified'] == 0) {
                                                echo 'รอพิจารณา';
                                            } else {
                                                echo 'ไม่ผ่านการพิจารณา';
                                            }
                                        ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="list-group-item">ไม่พบข้อมูลบริษัทล่าสุด</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form for PDF generation -->
<form id="pdfForm" action="report.php?export=pdf" method="post" style="display: none;">
    <input type="hidden" name="html_content" id="htmlContent">
</form>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<!-- Load html2pdf.js for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
// Store chart instances to be able to destroy them later
let statusChart = null;
let monthlyChart = null;

// Function to initialize charts
function initCharts() {
    // Destroy existing charts if they exist
    if (statusChart) {
        statusChart.destroy();
    }
    if (monthlyChart) {
        monthlyChart.destroy();
    }
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['พร้อมทำงาน', 'รอพิจารณา', 'ไม่ผ่านการพิจารณา'],
            datasets: [{
                data: [<?php echo $active_companies; ?>, <?php echo $pending_companies; ?>, <?php echo $rejected_companies; ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Monthly Registration Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                $labels = [];
                foreach (array_reverse($monthly_registrations) as $month) {
                    $date = new DateTime($month['month'] . '-01');
                    $labels[] = "'" . $date->format('M Y') . "'";
                }
                echo implode(', ', $labels);
                ?>
            ],
            datasets: [{
                label: 'จำนวนบริษัทที่ลงทะเบียน',
                data: [
                    <?php 
                    $counts = [];
                    foreach (array_reverse($monthly_registrations) as $month) {
                        $counts[] = $month['count'];
                    }
                    echo implode(', ', $counts);
                    ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Print Report Function
function printReport() {
    const printContents = document.getElementById('printableReport').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div class="container p-4">
            <h1 class="text-center mb-4">${SITE_NAME} - รายงานสรุปข้อมูลบริษัท</h1>
            <p class="text-end">วันที่ออกรายงาน: ${new Date().toLocaleDateString('th-TH')}</p>
            ${printContents}
        </div>
    `;
    
    window.print();
    
    // Restore original content and reinitialize charts
    document.body.innerHTML = originalContents;
    
    // We need to wait for DOM to be fully restored
    setTimeout(() => {
        initCharts();
        addExportEventListeners();
    }, 100);
}

// Export to Excel Function
function exportToExcel() {
    window.location.href = 'report.php?export=excel';
}

// Export to PDF Function
function exportToPDF() {
    // Get the report content
    const element = document.getElementById('printableReport');
    
    // Set options for PDF generation
    const options = {
        margin: 10,
        filename: 'รายงานข้อมูลบริษัท-' + new Date().toISOString().slice(0, 10) + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    // Generate PDF
    html2pdf().set(options).from(element).save();
}

// Add event listeners for export buttons
function addExportEventListeners() {
    // Export to Excel button
    document.getElementById('exportExcel').addEventListener('click', exportToExcel);
    
    // Export to PDF button
    document.getElementById('exportPDF').addEventListener('click', exportToPDF);
    
   
}
function printTable() {
    window.print();
}

// Initialize everything when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initCharts();
    
    // Add event listeners
    addExportEventListeners();
    
    // Handle window resize to redraw charts
    window.addEventListener('resize', function() {
        // Debounce the resize event
        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
        }
        this.resizeTimeout = setTimeout(function() {
            initCharts();
        }, 200);
    });
});
</script>

<?php
 include $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
 ?>