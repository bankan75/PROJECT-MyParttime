<?php
// Keep the existing PHP code at the top unchanged
include('../../includes/functions.php');
// เตรียมส่วนหัวของหน้า
include('../../layouts/header.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบเป็นบริษัทหรือไม่
if (!$auth->isLoggedIn() || !$auth->isCompany()) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีบริษัท";
    header("Location: " . ROOT_URL . "/login.php");
    exit;
}

$company_id = $_SESSION['user_id'];

// ดึงข้อมูลนักศึกษาที่ทำงานกับบริษัทอยู่
$employee_sql = "SELECT s.student_id, s.first_name, s.last_name, s.profile_image, s.student_code, 
                s.faculty_name, s.major_name, s.year, s.gpa, s.employment_status,
                e.employment_id, e.position, e.start_date, e.salary, e.status
                FROM employments e
                JOIN students s ON e.student_id = s.student_id
                WHERE e.company_id = ? AND e.status = 'accepted'
                ORDER BY e.start_date DESC";
$employee_stmt = $db->prepare($employee_sql);
$employee_stmt->execute([$company_id]);
$employees = $employee_stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบจำนวนผู้สมัครที่ผ่านการคัดเลือกแต่ยังไม่ได้อยู่ในตาราง employments
$pending_sql = "SELECT COUNT(*) as pending_count
                FROM applications a
                JOIN jobs_posts j ON a.post_id = j.post_id
                WHERE a.status = 'accepted' 
                AND j.company_id = ?
                AND NOT EXISTS (
                    SELECT 1 FROM employments e 
                    WHERE e.student_id = a.student_id 
                    AND e.company_id = j.company_id
                    AND e.status = 'accepted'
                )";
$pending_stmt = $db->prepare($pending_sql);
$pending_stmt->execute([$company_id]);
$pending_result = $pending_stmt->fetch(PDO::FETCH_ASSOC);
$pending_count = $pending_result['pending_count'];
?>



<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">พนักงานปัจจุบัน</h1>
        <div class="btn-group float-end" role="group">
            <a href="/Myparttime/dashboard.php" class="btn btn-primary">
                <i class="me-1"></i> หน้าหลัก
            </a>
            <a href="<?php echo ROOT_URL; ?>/modules/jobs/index.php" class="btn btn-primary">
                <i class="fas fa-list-alt me-1"></i> จัดการประกาศงาน
            </a>
            
            <?php if ($pending_count > 0): ?>
            <a href="<?php echo ROOT_URL; ?>/modules/companies/transfer_accepted_applications.php" class="btn btn-success">
                <i class="fas fa-user-plus me-1"></i> โอนย้ายนักศึกษาที่ผ่านการคัดเลือก (<?php echo $pending_count; ?>)
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['info_message'])): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php 
            echo $_SESSION['info_message'];
            unset($_SESSION['info_message']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- รายการพนักงาน -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-users me-1"></i>
            รายชื่อพนักงานปัจจุบัน
        </div>
        <div class="card-body">
            <?php if (count($employees) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="employeesTable" width="100%" cellspacing="0">
                <thead class="table-light">
    <tr>
        <th>ชื่อนักศึกษา</th>
        <th>รหัสนักศึกษา</th>
        <th>ตำแหน่งงาน</th>
        <th>คณะ/สาขา</th>
        <th>วันที่เริ่มงาน</th>
        <th>เงินเดือน (บาท)</th>
        <th>สถานะการจ้างงาน</th>
        <th>จัดการ</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($employees as $employee): ?>
    <tr>
        <td>
            <div class="d-flex align-items-center">
                <?php 
                    $profile_image = !empty($employee['profile_image']) ? ROOT_URL . '/' . $employee['profile_image'] : ROOT_URL . '/assets/images/default-profile.png';
                ?>
                <img src="<?php echo $profile_image; ?>" class="rounded-circle me-2"
                    style="width: 40px; height: 40px; object-fit: cover;">
                <div>
                    <span class="fw-bold"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></span>
                    <br>
                    <small class="text-muted">GPA: <?php echo htmlspecialchars($employee['gpa']); ?></small>
                </div>
            </div>
        </td>
        <td><?php echo htmlspecialchars($employee['student_code']); ?></td>
        <td><?php echo htmlspecialchars($employee['position']); ?></td>
        <td><?php echo htmlspecialchars($employee['faculty_name']) . ' / ' . htmlspecialchars($employee['major_name']) . ' ชั้นปีที่ ' . htmlspecialchars($employee['year']); ?></td>
        <td><?php echo date('d/m/Y', strtotime($employee['start_date'])); ?></td>
        <td><?php echo number_format($employee['salary'], 2); ?></td>
        <td><?php echo htmlspecialchars($employee['employment_status']); ?></td>
        <td class="text-center">
            <div class="btn-group">
                <a href="<?php echo ROOT_URL; ?>/modules/students/view.php?id=<?php echo $employee['student_id']; ?>"
                    class="btn btn-sm btn-info">
                    <i class="fas fa-eye"></i> โปรไฟล์
                </a>
                <!-- เปลี่ยนจาก direct URL เป็นใช้ terminate-btn class -->
                <button 
                   class="btn btn-sm btn-danger terminate-btn"
                   data-employment-id="<?php echo intval($employee['employment_id']); ?>"
                   data-student-name="<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>"
                   data-position="<?php echo htmlspecialchars($employee['position']); ?>">
                    <i class="fas fa-user-times"></i> ยกเลิกการจ้างงาน
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>ไม่พบข้อมูลพนักงานที่กำลังทำงานอยู่ในขณะนี้
                <?php if ($pending_count > 0): ?>
                <br><br>
                <div class="text-center">
                    <a href="<?php echo ROOT_URL; ?>/modules/companies/transfer_accepted_applications.php" class="btn btn-success">
                        <i class="fas fa-user-plus me-1"></i> โอนย้ายนักศึกษาที่ผ่านการคัดเลือก (<?php echo $pending_count; ?> คน)
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- สรุปข้อมูลพนักงาน -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-bar me-1"></i>
                    สรุปข้อมูลพนักงาน
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // นับจำนวนพนักงานทั้งหมด
                        $total_employees = count($employees);
                        
                        // คำนวณค่าเฉลี่ยเงินเดือน
                        $total_salary = 0;
                        foreach ($employees as $emp) {
                            $total_salary += $emp['salary'];
                        }
                        $avg_salary = $total_employees > 0 ? $total_salary / $total_employees : 0;
                        
                        // ดึงพนักงานที่ทำงานมานานที่สุด
                        $oldest_employee = null;
                        $max_days = 0;
                        foreach ($employees as $emp) {
                            $start_date = new DateTime($emp['start_date']);
                            $now = new DateTime();
                            $days = $start_date->diff($now)->days;
                            if ($days > $max_days) {
                                $max_days = $days;
                                $oldest_employee = $emp;
                            }
                        }
                        ?>

                        <div class="col-md-4">
                            <div class="card text-center bg-primary bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_employees; ?></h5>
                                    <p class="card-text">จำนวนพนักงานปัจจุบัน</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card text-center bg-success bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo number_format($avg_salary, 2); ?></h5>
                                    <p class="card-text">เงินเดือนเฉลี่ย (บาท)</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card text-center bg-info bg-opacity-25 mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $total_salary > 0 ? number_format($total_salary, 2) : 0; ?></h5>
                                    <p class="card-text">ค่าใช้จ่ายรวมต่อเดือน (บาท)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal ยกเลิกการจ้างงาน -->
<div class="modal fade" id="terminateModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="terminateModalLabel">ยืนยันการยกเลิกการจ้างงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo ROOT_URL; ?>/modules/companies/terminate_employment.php" id="terminateForm">
                <div class="modal-body">
                    <p>คุณกำลังจะยกเลิกการจ้างงานของ <strong id="studentName"></strong> ในตำแหน่ง <strong id="positionDisplay"></strong></p>
                    <p class="text-danger">การดำเนินการนี้จะเปลี่ยนสถานะการทำงานของนักศึกษาเป็น "ว่างงาน" และไม่สามารถย้อนกลับได้</p>
                    
                    <!-- employmentId input -->
                    <input type="hidden" name="employment_id" id="employmentId">
                    
                    <div class="mb-3">
                        <label for="termination_reason" class="form-label">เหตุผลในการยกเลิกการจ้างงาน</label>
                        <select name="termination_reason" class="form-select" required>
                            <option value="">เลือกเหตุผล</option>
                            <option value="สิ้นสุดสัญญา">สิ้นสุดสัญญา</option>
                            <option value="ลาออก">ลาออก</option>
                            <option value="ผลการทำงานไม่เป็นไปตามเกณฑ์">ผลการทำงานไม่เป็นไปตามเกณฑ์</option>
                            <option value="ปรับโครงสร้างองค์กร">ปรับโครงสร้างองค์กร</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">รายละเอียดเพิ่มเติม</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="termination_date" class="form-label">วันที่สิ้นสุดการจ้างงาน</label>
                        <input type="date" name="termination_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยันการยกเลิกการจ้างงาน</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // กำหนดข้อมูลสำหรับ Modal ยกเลิกการจ้างงาน
    const terminateBtns = document.querySelectorAll('.terminate-btn');
    terminateBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // ป้องกันการทำงานเริ่มต้นของปุ่ม
            e.preventDefault();
            
            const employmentId = this.getAttribute('data-employment-id');
            const studentName = this.getAttribute('data-student-name');
            const position = this.getAttribute('data-position');
            
            // แสดงข้อมูลในคอนโซลเพื่อดีบัก
            console.log("Employment ID:", employmentId);
            console.log("Student Name:", studentName);
            console.log("Position:", position);
            
            // ตรวจสอบว่าค่า employment_id มีค่าหรือไม่
            if (!employmentId || employmentId == '0') {
                console.error("ไม่พบ employment_id หรือค่าเป็น 0");
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่พบรหัสการจ้างงาน กรุณาลองใหม่อีกครั้ง',
                    icon: 'error'
                });
                return false;
            }
            
            // เซ็ตค่าให้กับฟอร์ม
            document.getElementById('employmentId').value = employmentId;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('positionDisplay').textContent = position;
            
            // แสดง Modal โดยใช้ Bootstrap
            const terminateModal = new bootstrap.Modal(document.getElementById('terminateModal'));
            terminateModal.show();
        });
    });
    
    // เพิ่มการตรวจสอบก่อนส่งฟอร์ม
    document.getElementById('terminateForm').addEventListener('submit', function(e) {
        const empId = document.getElementById('employmentId').value;
        if (!empId || empId.trim() === '' || empId === '0') {
            e.preventDefault();
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่พบรหัสการจ้างงาน กรุณาลองใหม่อีกครั้ง',
                icon: 'error'
            });
            return false;
        }
        console.log("Form submitting with employment ID:", empId);
        return true;
    });
    
    // กำหนด DataTable
    if (document.getElementById('employeesTable')) {
        $('#employeesTable').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            }
        });
    }
});

// ฟังก์ชันสำหรับการแสดง SweetAlert2 แทนการใช้ confirm เดิม
function confirmDelete(postId) {
    Swal.fire({
        title: 'ยืนยันการลบ',
        text: 'คุณแน่ใจหรือไม่ที่จะยกเลิกการ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete.php?id=${postId}`;
        }
    });
}
</script>

<?php
// Include footer
include('../../layouts/footer.php');
?>