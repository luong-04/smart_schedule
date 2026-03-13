import Sortable from 'sortablejs';
import axios from 'axios';

document.addEventListener('alpine:init', () => {
    // Logic xử lý kéo thả
    const initMatrix = () => {
        // Cấu hình cho kho môn học (bên trái)
        const sourceList = document.querySelector('#source-subjects');
        if (sourceList) {
            new Sortable(sourceList, {
                group: { name: 'schedule', pull: 'clone', put: false },
                sort: false,
                animation: 150
            });
        }

        // Cấu hình cho từng ô tiết học trong Ma trận (bên phải)
        document.querySelectorAll('.slot-box').forEach(el => {
            new Sortable(el, {
                group: 'schedule',
                animation: 150,
                onAdd: function (evt) {
                    const assignmentId = evt.item.getAttribute('data-id');
                    const day = el.getAttribute('data-day');
                    const period = el.getAttribute('data-period');
                    
                    // Gửi API kiểm tra trùng lịch (GV, phòng, tiết)
                    checkAndSaveSchedule(assignmentId, day, period, evt.item);
                }
            });
        });
    };

    const checkAndSaveSchedule = (assignmentId, day, period, element) => {
        axios.post('/api/check-schedule', {
            assignment_id: assignmentId,
            day: day,
            period: period
        })
        .then(response => {
            if (!response.data.success) {
                alert(response.data.message); // Thông báo nếu trùng lịch GV hoặc phòng
                element.remove(); // Xóa thẻ nếu không hợp lệ
            }
        })
        .catch(error => console.error('Lỗi kết nối LAN:', error));
    };

    initMatrix();
});