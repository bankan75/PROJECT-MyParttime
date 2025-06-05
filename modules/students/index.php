<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Myparttime/includes/config.php';

// ตั้งค่าชื่อหน้า
$page_title = "รายชื่อนักศึกษาทั้งหมด";
include(BASE_PATH . '/layouts/header.php');


// รับค่าหน้าปัจจุบันสำหรับการแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = RECORDS_PER_PAGE; // 10 จาก config.php
$offset = ($page - 1) * $records_per_page;

// รับค่าตัวกรอง
$search = isset($_GET['search']) ? $_GET['search'] : '';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : '';
$year_filter = isset($_GET['year']) ? $_GET['year'] : '';
$major_filter = isset($_GET['major']) ? $_GET['major'] : '';

// สร้าง query
$params = [];
$where_clause = " WHERE 1=1";

if (!empty($search)) {
    $where_clause .= " AND (student_code LIKE ? OR email LIKE ? OR phone LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($faculty_filter)) {
    $where_clause .= " AND faculty_name = ?";
    $params[] = $faculty_filter;
}

if (!empty($year_filter)) {
    $where_clause .= " AND year = ?";
    $params[] = $year_filter;
}

if (!empty($major_filter)) {
    $where_clause .= " AND major_name = ?";
    $params[] = $major_filter;
}

// ดึงข้อมูลคณะและสาขาเพื่อสร้างตัวกรอง
$faculty_sql = "SELECT DISTINCT faculty_name FROM students ORDER BY faculty_name";
$faculties = $database->getRows($faculty_sql);

$major_sql = "SELECT DISTINCT major_name FROM students ORDER BY major_name";
$majors = $database->getRows($major_sql);

$year_sql = "SELECT DISTINCT year FROM students ORDER BY year";
$years = $database->getRows($year_sql);

// นับจำนวนข้อมูลทั้งหมดสำหรับการแบ่งหน้า
$count_sql = "SELECT COUNT(*) as total FROM students" . $where_clause;
$count_result = $database->getRow($count_sql, $params);
$total_records = $count_result['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// ดึงข้อมูลนักศึกษาด้วยการแบ่งหน้า
$sql = "SELECT * FROM students" . $where_clause . " ORDER BY year ASC, faculty_name ASC, major_name ASC LIMIT " . $offset . ", " . $records_per_page;

try {
    $students = $database->getRows($sql, $params);
} catch (PDOException $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $students = [];
}

// สรุปข้อมูลนักศึกษาตามชั้นปีและคณะ/สาขา
$summary_sql = "SELECT year, faculty_name, major_name, COUNT(*) as count FROM students GROUP BY year, faculty_name, major_name ORDER BY year ASC, faculty_name ASC, major_name ASC";
$summary = $database->getRows($summary_sql);
?>

<!-- หัวเรื่อง -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $page_title; ?></h1>
    <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="fas fa-home me-1"></i> หน้าหลัก
            </a>
        </div>
</div>

<!-- แสดงสรุปจำนวนนักศึกษา -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-chart-bar me-1"></i> สรุปจำนวนนักศึกษา
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>ชั้นปี</th>
                        <th>คณะ</th>
                        <th>สาขา</th>
                        <th>จำนวน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $item): ?>
                    <tr>
                        <td><?php echo $item['year']; ?></td>
                        <td><?php echo htmlspecialchars($item['faculty_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['major_name']); ?></td>
                        <td><?php echo $item['count']; ?> คน</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ช่องค้นหาและกรอง -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="ค้นหา..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-2">
    <select class="form-select" name="faculty" id="faculty-filter">
        <option value="">-- เลือกคณะ --</option>
        <?php foreach ($faculties as $faculty): ?>
        <option value="<?php echo htmlspecialchars($faculty['faculty_name']); ?>" <?php echo ($faculty_filter == $faculty['faculty_name']) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($faculty['faculty_name']); ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="col-md-2">
    <select class="form-select" name="major" id="major-filter">
        <option value="">-- เลือกสาขา --</option>
        <?php if (!empty($major_filter)): ?>
            <?php foreach ($majors as $major): ?>
                <?php if (empty($faculty_filter) || $faculty_filter == ""): ?>
                <option value="<?php echo htmlspecialchars($major['major_name']); ?>" <?php echo ($major_filter == $major['major_name']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($major['major_name']); ?>
                </option>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>
            <div class="col-md-2">
                <select class="form-select" name="year">
                    <option value="">-- เลือกชั้นปี --</option>
                    <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year['year']; ?>" <?php echo ($year_filter == $year['year']) ? 'selected' : ''; ?>>
                        ชั้นปีที่ <?php echo $year['year']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i> รีเซ็ต
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ตารางนักศึกษา -->
<div class="card">
    <div class="card-header bg-info text-white">
        <i class="fas fa-table me-1"></i> รายชื่อนักศึกษา
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="10%">รหัสนักศึกษา</th>
                        <th width="15%">ชื่อ-นามสกุล</th>
                        <th width="15%">อีเมล</th>
                        <th width="10%">เบอร์โทร</th>
                        <th width="10%">ชั้นปี</th>
                        <th width="15%">คณะ</th>
                        <th width="15%">สาขา</th>
                        <th width="5%">ตัวเลือก</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $index => $student): ?>
                            <tr>
                                <td><?php echo $offset + $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                                <td><?php echo htmlspecialchars($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($student['year']); ?></td>
                                <td><?php echo htmlspecialchars($student['faculty_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['major_name']); ?></td>
                                <td class="text-center">
                                    <a href="view.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">ไม่พบข้อมูลนักศึกษา</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- การแบ่งหน้า -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>&major=<?php echo urlencode($major_filter); ?>&year=<?php echo urlencode($year_filter); ?>">Previous</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>&major=<?php echo urlencode($major_filter); ?>&year=<?php echo urlencode($year_filter); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&faculty=<?php echo urlencode($faculty_filter); ?>&major=<?php echo urlencode($major_filter); ?>&year=<?php echo urlencode($year_filter); ?>">Next</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_URL . '/layouts/footer.php';
?>