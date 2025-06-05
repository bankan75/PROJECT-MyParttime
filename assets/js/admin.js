document.addEventListener('DOMContentLoaded', function() {
    console.log('โหลด Admin JS สำเร็จแล้ว');

    // ฟังก์ชันสำหรับการแสดง/ซ่อน Sidebar บนอุปกรณ์มือถือ
    const sidebarToggle = document.querySelector('.navbar-toggler');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // ปิด Sidebar เมื่อคลิกนอก Sidebar ในหน้าจอขนาดเล็ก
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = sidebarToggle.contains(event.target);

            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }

    // ปิดข้อความแจ้งเตือนอัตโนมัติหลังจาก 5 วินาที
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        alerts.forEach(alert => {
            setTimeout(() => {
                const closeButton = alert.querySelector('.btn-close');
                if (closeButton) {
                    closeButton.click();
                }
            }, 5000);
        });
    }
});