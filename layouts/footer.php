</main>
</div>
</div>

<!-- รวม script ทั้งหมดไว้ที่ footer -->
<script src="<?php echo ROOT_URL; ?>/assets/js/jquery.min.js"></script>
<script src="<?php echo ROOT_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo ROOT_URL; ?>/assets/js/sweetalert2@11.js"></script>
<script src="<?php echo ROOT_URL; ?>/assets/js/html2pdf.bundle.min.js"></script>
<script src="<?php echo ROOT_URL; ?>/assets/js/admin.js"></script>
<script src="<?php echo ROOT_URL; ?>/assets/js/thai-font.js"></script>
<!-- <script>alert('test')</script> -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const facultyFilter = document.getElementById('faculty-filter');
        const majorFilter = document.getElementById('major-filter');

        if (facultyFilter && majorFilter) {
            facultyFilter.addEventListener('change', function() {
                const faculty = this.value;

                // เคลียร์ตัวเลือกสาขาเดิม
                while (majorFilter.firstChild) {
                    majorFilter.removeChild(majorFilter.firstChild);
                }

                // เพิ่มตัวเลือกเริ่มต้น
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- เลือกสาขา --';
                majorFilter.appendChild(defaultOption);

                if (!faculty) {
                    loadAllMajors();
                    return;
                }

                fetch('get_majors.php?faculty=' + encodeURIComponent(faculty))
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        data.forEach(major => {
                            const option = document.createElement('option');
                            option.value = sanitize(major.major_name); // กรองข้อมูลจากเซิร์ฟเวอร์
                            option.textContent = sanitize(major.major_name); // ใช้ textContent แทน innerHTML
                            majorFilter.appendChild(option);
                        });

                        const major_filter = '<?php echo sanitize(isset($major_filter) ? $major_filter : ""); ?>';
                        if (major_filter) {
                            majorFilter.value = major_filter;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching major data:', error);
                    });
            });

            function loadAllMajors() {
                fetch('get_majors.php')
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(major => {
                            const option = document.createElement('option');
                            option.value = sanitize(major.major_name); // กรองข้อมูลจากเซิร์ฟเวอร์
                            option.textContent = sanitize(major.major_name);
                            majorFilter.appendChild(option);
                        });

                        const major_filter = '<?php echo sanitize(isset($major_filter) ? $major_filter : ""); ?>';
                        if (major_filter) {
                            majorFilter.value = major_filter;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching all majors:', error);
                    });
            }

            if (facultyFilter.value) {
                facultyFilter.dispatchEvent(new Event('change'));
            } else {
                loadAllMajors();
            }
        }
    });
</script>
</body>
</html>
<?php
// ส่ง output buffer และปิด
ob_end_flush();
?>