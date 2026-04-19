document.addEventListener('DOMContentLoaded', function () {
    // Lấy các tham số cấu hình từ global window object (do server-side nạp vào)
    const MAX_CONSECUTIVE = window.ScheduleData.maxConsecutive; // Số tiết liên tiếp tối đa cho phép
    const MAX_DAYS_PER_WEEK = window.ScheduleData.maxDaysPerWeek; // Số ngày dạy tối đa trong tuần
    const CHECK_TEACHER_CONFLICT = window.ScheduleData.checkTeacherConflict; // Cờ kiểm tra trùng lịch giáo viên
    const CHECK_ROOM_CONFLICT = window.ScheduleData.checkRoomConflict; // Cờ kiểm tra trùng phòng học

    const allRooms = window.ScheduleData.allRooms; // Danh sách toàn bộ phòng học chuyên dụng
    const teacherBusySlots = window.ScheduleData.teacherBusySlots; // Các slot bận của giáo viên ở lớp khác
    const teacherOtherDays = window.ScheduleData.teacherOtherDays; // Các ngày giáo viên đã có lịch ở lớp khác
    const roomBusySlots = window.ScheduleData.roomBusySlots; // Các slot bận của phòng học ở lớp khác

    let teacherSlots = {}; // Theo dõi số tiết còn lại của từng giáo viên trong phiên này
    let subjectSlots = {}; // Theo dõi số tiết còn lại của từng môn học (phân công)
    let pendingItem = null; // Lưu trữ phần tử đang chờ xử lý chọn phòng học
    let pendingTargetDay = null;
    let pendingTargetPeriod = null;

    /**
     * TÔ MÀU XUNG ĐỘT THỜI GIAN THỰC (Real-time Conflict Highlighting)
     * Đánh dấu đỏ các ô mà giáo viên hoặc phòng học đang truyền vào bận ở các lớp khác.
     * 
     * @param {number} teacherId ID Giáo viên
     * @param {number|null} roomId ID Phòng học
     * @param {boolean} active Trạng thái kích hoạt (bật/tắt highlight)
     */
    function updateConflictHighlights(teacherId, roomId, active) {
        // Xóa highlight cũ trên toàn bộ lưới
        document.querySelectorAll('.drop-zone').forEach(zone => {
            zone.classList.remove('conflict-zone');
        });

        if (!active || !teacherId) return;

        const busySlots = teacherBusySlots[teacherId] || [];
        const roomBusy = roomId ? (roomBusySlots[roomId] || []) : [];

        document.querySelectorAll('.drop-zone').forEach(zone => {
            const day = zone.dataset.day;
            const period = zone.dataset.period;
            const slotKey = `${day}-${period}`;

            // Nếu slot này trùng với lịch bận của GV hoặc phòng học thì tô màu đỏ
            if (busySlots.includes(slotKey) || roomBusy.includes(slotKey)) {
                zone.classList.add('conflict-zone');
            }
        });
    }

    // Khởi tạo trạng thái số tiết từ Sidebar khi trang vừa nạp xong
    document.querySelectorAll('.sidebar-item').forEach(el => {
        let tid = el.dataset.teacherId;
        let asId = el.dataset.id;

        if (tid && teacherSlots[tid] === undefined) {
            teacherSlots[tid] = parseInt(el.dataset.teacherRemaining) || 0;
        }
        if (asId) {
            subjectSlots[asId] = parseInt(el.dataset.subjectRemaining) || 0;
        }
    });

    /**
     * Cập nhật giao diện Sidebar (badges số tiết, trạng thái mờ đi khi hết tiết).
     */
    function updateSidebarUI() {
        document.querySelectorAll('.sidebar-item').forEach(el => {
            let tid = el.dataset.teacherId;
            let asId = el.dataset.id;

            let tSlots = teacherSlots[tid] || 0;
            let sSlots = subjectSlots[asId] || 0;

            let tBadge = el.querySelector('.teacher-badge');
            let sBadge = el.querySelector('.subject-badge');
            let mainBadge = el.querySelector('.slot-badge');

            if (tBadge) {
                tBadge.innerText = tSlots;
                tBadge.className = `teacher-badge text-xs font-black ${tSlots <= 0 ? 'text-rose-500' : 'text-blue-600'}`;
            }
            if (sBadge) {
                sBadge.innerText = sSlots;
                sBadge.className = `subject-badge text-xs font-black ${sSlots <= 0 ? 'text-rose-500' : 'text-emerald-600'}`;
            }

            let minSlots = Math.min(tSlots, sSlots);
            if (mainBadge) {
                mainBadge.innerText = minSlots > 0 ? (minSlots < 10 ? "0" + minSlots : minSlots) : "HẾT";
                mainBadge.className = `slot-badge text-xs font-black ${minSlots <= 0 ? 'text-rose-500' : 'text-emerald-500'}`;
            }

            // Mờ item nếu không còn tiết để xếp
            if (tSlots <= 0 || sSlots <= 0) {
                el.classList.add('opacity-50', 'bg-slate-50');
            } else {
                el.classList.remove('opacity-50', 'bg-slate-50');
            }
        });
    }

    /**
     * Gắn sự kiện Double-click để xóa môn học khỏi ma trận.
     */
    function attachDoubleClickEvent(item) {
        if (!item) return;
        item.addEventListener('dblclick', function () {
            let asId = this.dataset.id;
            let tid = this.dataset.teacherId;

            // Hoàn lại số tiết cho cả môn học và giáo viên
            if (asId && subjectSlots[asId] !== undefined) subjectSlots[asId]++;
            if (tid && teacherSlots[tid] !== undefined) teacherSlots[tid]++;

            this.remove(); // Xóa khỏi giao diện ma trận
            updateSidebarUI(); // Cập nhật Sidebar ngay lập tức
        });
    }

    // Gán sự kiện dblclick cho các item đã có sẵn trên ma trận (khi load trang)
    document.querySelectorAll('.matrix-item').forEach(item => { attachDoubleClickEvent(item); });

    /**
     * Tính số tiết dạy liên tiếp tối đa của một giáo viên trong cùng một ngày.
     * 
     * @param {number} teacherId 
     * @param {number} day 
     * @param {number} targetPeriod Tiết đang dự định xếp vào.
     */
    function getConsecutiveSlotsCount(teacherId, day, targetPeriod) {
        let periods = [];
        document.querySelectorAll(`.drop-zone[data-day="${day}"]`).forEach(box => {
            let item = box.querySelector(`.matrix-item[data-teacher-id="${teacherId}"]`);
            if (item) periods.push(parseInt(box.dataset.period));
        });
        if (targetPeriod !== undefined) periods.push(parseInt(targetPeriod));
        if (periods.length === 0) return 0;

        periods = [...new Set(periods)].sort((a, b) => a - b);
        let maxCons = 1, currentCons = 1;
        for (let i = 1; i < periods.length; i++) {
            if (periods[i] === periods[i - 1] + 1) {
                currentCons++;
                maxCons = Math.max(maxCons, currentCons);
            } else {
                currentCons = 1;
            }
        }
        return maxCons;
    }

    /**
     * Tính tổng số ngày giáo viên này đã có lịch dạy trong tuần.
     * Kiểm tra cả trong ma trận hiện tại và các lớp học khác đã xếp xong.
     */
    function getTeacherTotalDays(teacherId, targetDayToAdd) {
        let daysInMatrix = new Set();

        document.querySelectorAll(`.matrix-item[data-teacher-id="${teacherId}"]`).forEach(el => {
            let box = el.closest('.drop-zone');
            if (box && box.dataset.day) {
                daysInMatrix.add(parseInt(box.dataset.day));
            }
        });

        if (targetDayToAdd) {
            daysInMatrix.add(parseInt(targetDayToAdd));
        }

        let otherDays = teacherOtherDays[teacherId] || [];
        otherDays.forEach(d => daysInMatrix.add(parseInt(d)));

        return daysInMatrix.size;
    }

    /**
     * Đóng Modal chọn phòng học.
     */
    function closeModal() {
        const modal = document.getElementById('roomModal');
        const content = document.getElementById('roomModalContent');
        if (!modal) return;
        modal.classList.add('opacity-0');
        content.classList.replace('scale-100', 'scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // Sự kiện hủy chọn phòng học
    const btnCancelRoom = document.getElementById('btnCancelRoom');
    if (btnCancelRoom) {
        btnCancelRoom.addEventListener('click', function () {
            if (pendingItem) {
                let asId = pendingItem.dataset.id;
                let tid = pendingItem.dataset.teacherId;
                if (asId) subjectSlots[asId]++;
                if (tid) teacherSlots[tid]++;
                updateSidebarUI();
                pendingItem.remove(); // Hủy bỏ dragging item
                pendingItem = null;
            }
            closeModal();
        });
    }

    // Sự kiện xác nhận chọn phòng học cho môn thực hành
    const btnConfirmRoom = document.getElementById('btnConfirmRoom');
    if (btnConfirmRoom) {
        btnConfirmRoom.addEventListener('click', function () {
            const select = document.getElementById('roomSelect');
            if (!select) return;
            const roomId = select.value;
            const roomName = select.options[select.selectedIndex].text;

            // Kiểm tra xung đột phòng học nếu cấu hình yêu cầu
            if (CHECK_ROOM_CONFLICT == 1) {
                let slotKey = pendingTargetDay + '-' + pendingTargetPeriod;
                if (roomBusySlots[roomId] && roomBusySlots[roomId].includes(slotKey)) {
                    showToast(`[${roomName}] đã bận vào Thứ ${pendingTargetDay} - Tiết ${pendingTargetPeriod}!`, 'error');
                    return;
                }
            }

            if (pendingItem) {
                pendingItem.dataset.roomId = roomId;

                const subjectName = pendingItem.dataset.subjectName || (pendingItem.querySelector('.subject-name') ? pendingItem.querySelector('.subject-name').innerText : 'Môn học');
                const teacherName = pendingItem.dataset.teacherName || (pendingItem.querySelector('.teacher-name') ? pendingItem.querySelector('.teacher-name').innerText : 'Giáo viên');

                // Cập nhật giao diện item trong ma trận (chế độ thu gọn)
                pendingItem.className = "matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 transition-all overflow-hidden";

                pendingItem.innerHTML = `
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary shrink-0"></div>
                    <div class="w-full flex flex-col items-center justify-center px-1 min-w-0 overflow-hidden">
                        <span class="text-[9px] font-black uppercase text-primary text-center leading-tight whitespace-normal break-words w-full block" title="${subjectName}">${subjectName}</span>
                        <span class="text-[8px] font-semibold text-slate-600 text-center leading-tight whitespace-normal break-words w-full block mt-0.5" title="${teacherName}">${teacherName}</span>
                        <span class="text-[7px] font-bold text-orange-700 bg-orange-100 px-1 rounded mt-0.5 max-w-[95%] whitespace-normal break-words block room-tag" title="P: ${roomName}">P: ${roomName}</span>
                    </div>
                `;

                attachDoubleClickEvent(pendingItem);
                pendingItem.style.display = 'flex';
                pendingItem = null;
            }
            closeModal();
        });
    }

    // Khởi tạo SortableJS cho toàn bộ các ô trong ma trận (Drop Zones)
    document.querySelectorAll('.drop-zone').forEach(el => {
        new Sortable(el, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onStart: function (evt) {
                const item = evt.item;
                if (!item) return;
                let offDays = [];
                try {
                    const rawOffDays = item.dataset.offDays || '[]';
                    offDays = JSON.parse(rawOffDays).map(Number);
                } catch (e) { }

                // Khóa các ngày giáo viên nghỉ khi bắt đầu kéo
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    const day = parseInt(zone.dataset.day);
                    if (offDays.includes(day)) {
                        zone.dataset.disabled = "true";
                    } else {
                        zone.dataset.disabled = "false";
                    }
                });

                const tid = item.dataset.teacherId;
                const rid = item.dataset.roomId || null;
                if (tid) {
                    updateConflictHighlights(tid, rid, true);
                }
            },
            onEnd: function (evt) {
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    zone.classList.remove('bg-slate-200', 'opacity-50', 'conflict-zone');
                    zone.style.cursor = '';
                    zone.dataset.disabled = "false";
                });
                updateConflictHighlights(null, null, false);
            },
            onAdd: function (evt) {
                const item = evt.item;
                const targetDay = evt.to.dataset.day;
                const targetPeriod = evt.to.dataset.period;
                const needsTransformation = item && item.classList && item.classList.contains('sidebar-item');

                /**
                 * Đưa môn học/giáo viên về trạng thái trước đó nếu không thể thả vào ô này.
                 */
                function bounceBack() {
                    if (needsTransformation) {
                        item.remove(); // Xóa bản sao đang được kéo
                    } else {
                        if (evt.from) {
                            evt.from.appendChild(item); // Trả về ô cũ
                        } else {
                            item.remove();
                        }
                    }
                    updateSidebarUI();
                }

                // 1. KIỂM TRA Ô ĐÃ CÓ MÔN CHƯA
                const existingItems = Array.from(evt.to.children).filter(child => child !== item && (child.classList.contains('matrix-item') || child.classList.contains('sidebar-item')));
                
                if (existingItems.length > 0) {
                    showToast('Ô này đã có môn học!', 'error');
                    bounceBack();
                    return;
                }

                const asId = item.dataset.id;
                const tid = item.dataset.teacherId;
                const reqRoomType = item.dataset.roomTypeId;

                updateConflictHighlights(null, null, false);

                // 2. KIỂM TRA NGÀY NGHỈ CỦA GIÁO VIÊN
                let offDays = [];
                try {
                    const rawOffDays = item.dataset.offDays || '[]';
                    offDays = JSON.parse(rawOffDays).map(Number);
                } catch (e) { offDays = []; }

                if (offDays.includes(parseInt(targetDay))) {
                    showToast(`GV đã đăng ký NGHỈ vào Thứ ${targetDay}!`, 'error');
                    bounceBack();
                    return;
                }

                // 3. KIỂM TRA QUỸ TIẾT DẠY
                if (needsTransformation) {
                    if (teacherSlots[tid] <= 0) {
                        showToast('Giáo viên đã hết tiết trong tuần!', 'error');
                        bounceBack();
                        return;
                    }
                    if (subjectSlots[asId] <= 0) {
                        showToast('Môn này đã đủ số tiết!', 'error');
                        bounceBack();
                        return;
                    }
                }

                // 4. KIỂM TRA TRÙNG LỊCH BẬN (LỚP KHÁC)
                if (CHECK_TEACHER_CONFLICT == 1) {
                    let slotKey = targetDay + '-' + targetPeriod;
                    if (teacherBusySlots[tid] && teacherBusySlots[tid].includes(slotKey)) {
                        showToast(`GV bận dạy lớp khác vào Thứ ${targetDay} - Tiết ${targetPeriod}!`, 'error');
                        bounceBack();
                        return;
                    }
                }

                // 5. KIỂM TRA SỐ NGÀY DẠY TỐI ĐA
                let totalDays = getTeacherTotalDays(tid, targetDay);
                if (totalDays > MAX_DAYS_PER_WEEK) {
                    showToast(`GV vượt quá ${MAX_DAYS_PER_WEEK} ngày dạy/tuần!`, 'error');
                    bounceBack();
                    return;
                }

                // 6. KIỂM TRA TIẾT LIÊN TIẾP
                let currentConsecutive = getConsecutiveSlotsCount(tid, targetDay, targetPeriod);
                if (currentConsecutive > MAX_CONSECUTIVE) {
                    showToast(`GV dạy quá ${MAX_CONSECUTIVE} tiết liên tiếp!`, 'error');
                    bounceBack();
                    return;
                }

                // Nếu hợp lệ, xử lý nốt logic biến hình item (nếu kéo từ sidebar)
                if (needsTransformation) {
                    subjectSlots[asId]--;
                    teacherSlots[tid]--;

                    const subjectType = item.dataset.subjectType;

                    // Nếu là môn thực hành cần có phòng học chuyên dụng
                    if (subjectType === 'practice' && reqRoomType && reqRoomType !== "" && reqRoomType !== "null") {
                        const filteredRooms = allRooms.filter(r => r.room_type_id == reqRoomType);

                        if (filteredRooms.length === 0) {
                            showToast('Hệ thống chưa có loại phòng này!', 'error');
                            subjectSlots[asId]++;
                            teacherSlots[tid]++;
                            bounceBack();
                            return;
                        }

                        // Hiển thị Modal để người dùng chọn phòng cụ thể
                        const select = document.getElementById('roomSelect');
                        if (select) {
                            select.innerHTML = '';
                            filteredRooms.forEach(r => {
                                select.innerHTML += `<option value="${r.id}">${r.name}</option>`;
                            });
                        }

                        pendingItem = item;
                        pendingTargetDay = targetDay;
                        pendingTargetPeriod = targetPeriod;
                        item.style.display = 'none'; // Tạm ẩn item cho đến khi chọn xong phòng

                        const modal = document.getElementById('roomModal');
                        const content = document.getElementById('roomModalContent');
                        if (modal && content) {
                            modal.classList.remove('hidden');
                            setTimeout(() => {
                                modal.classList.remove('opacity-0');
                                content.classList.replace('scale-95', 'scale-100');
                            }, 10);
                        }

                        updateSidebarUI();
                        return;
                    }

                    // Xử lý biến hình cho Môn Lý thuyết hoặc Thực hành không yêu cầu phòng cụ thể
                    try {
                        const subjectName = (item.dataset.subjectName || 'Môn học').trim();
                        const teacherName = (item.dataset.teacherName || 'Giáo viên').trim();

                        // Xóa sạch style sidebar và thay bằng giao diện ma trận thu gọn
                        item.className = "matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden";
                        item.style.padding = "0";
                        item.style.background = "";
                        
                        item.innerHTML = `
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary shrink-0"></div>
                            <div class="w-full flex flex-col items-center justify-center px-1 min-w-0 overflow-hidden">
                                <span class="text-[9px] font-black uppercase text-primary text-center leading-tight whitespace-normal break-words w-full block" title="${subjectName}">${subjectName}</span>
                                <span class="text-[8px] font-semibold text-slate-600 text-center leading-tight whitespace-normal break-words w-full block mt-0.5" title="${teacherName}">${teacherName}</span>
                            </div>
                        `;

                        attachDoubleClickEvent(item);
                    } catch (e) {
                        console.error('[Matrix] Transformation Error:', e);
                        item.innerHTML = '<div class="text-[9px] font-bold text-red-500">Lỗi hiển thị</div>';
                    }
                }
                updateSidebarUI();
            }
        });
    });

    // Khởi tạo kéo thả cho Sidebar (Nguồn dữ liệu)
    const externalEvents = document.getElementById('external-events');
    if (externalEvents) {
        new Sortable(externalEvents, {
            group: { name: 'shared', pull: 'clone', put: false },
            sort: false,
            animation: 150,
            onStart: function (evt) {
                const item = evt.item;
                if (!item) return;
                let offDays = [];
                try {
                    const rawOffDays = item.dataset.offDays || '[]';
                    offDays = JSON.parse(rawOffDays).map(Number);
                } catch (e) { }

                document.querySelectorAll('.drop-zone').forEach(zone => {
                    const day = parseInt(zone.dataset.day);
                    if (offDays.includes(day)) {
                        zone.dataset.disabled = "true";
                    } else {
                        zone.dataset.disabled = "false";
                    }
                });

                const tid = item.dataset.teacherId;
                const rid = item.dataset.roomId || null;
                if (tid) {
                    updateConflictHighlights(tid, rid, true);
                }
            },
            onEnd: function (evt) {
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    zone.classList.remove('bg-slate-200', 'opacity-50', 'conflict-zone');
                    zone.style.cursor = '';
                    zone.dataset.disabled = "false";
                });
                updateConflictHighlights(null, null, false);
            },
            onMove: function (evt) {
                // Ngăn chặn thả vào các ngày GV nghỉ
                if (evt.to && evt.to.dataset.disabled === "true") {
                    return false;
                }
            }
        });
    }

    // Xử lý bộ lọc tìm kiếm giáo viên và môn học ở Sidebar
    const searchTeacher = document.getElementById('search-teacher');
    if (searchTeacher) {
        searchTeacher.addEventListener('input', function (e) {
            const text = e.target.value.toLowerCase();
            document.querySelectorAll('.sidebar-item').forEach(item => {
                const tName = item.querySelector('.teacher-name').innerText.toLowerCase();
                const sName = item.querySelector('.subject-name').innerText.toLowerCase();
                item.style.display = (tName.includes(text) || sName.includes(text)) ? 'block' : 'none';
            });
        });
    }

    /**
     * Hiển thị thông báo (Toast) cho người dùng.
     * 
     * @param {string} message 
     * @param {string} type 'success' hoặc 'error'
     */
    function showToast(message, type = 'success') {
        const toast = document.getElementById('success-toast');
        const container = document.getElementById('toast-container');
        const icon = document.getElementById('toast-icon');
        const msgEl = document.getElementById('toast-message');
        
        if (!toast || !container || !icon || !msgEl) return;

        msgEl.innerText = message;
        
        if (type === 'error') {
            container.classList.replace('bg-emerald-500', 'bg-rose-500');
            icon.innerText = 'error';
        } else {
            container.classList.replace('bg-rose-500', 'bg-emerald-500');
            icon.innerText = 'check_circle';
        }

        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.remove('-translate-y-20');
        }, 10);
        
        setTimeout(() => {
            toast.classList.add('-translate-y-20');
            setTimeout(() => toast.classList.add('hidden'), 500);
        }, 5000);
    }

    // XỬ LÝ LƯU THỜI KHÓA BIỂU
    let isSaving = false;
    window.saveSchedule = function () {
        // NGĂN CHẶN CLICK NHIỀU LẦN (Race condition)
        if (isSaving) return;
        isSaving = true;

        const overlay = document.getElementById('saving-overlay');
        const saveBtn = document.querySelector('button[onclick="saveSchedule()"]');
        
        if (overlay) overlay.classList.remove('hidden');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">sync</span> ĐANG LƯU...';
        }

        try {
            const data = [];
            const seenSlots = new Set();
            let hasDuplicate = false;

            // Thu thập dữ liệu từ toàn bộ lưới ma trận
            document.querySelectorAll('.drop-zone').forEach(box => {
                const item = box.querySelector('.matrix-item') || box.querySelector('.sidebar-item');
                if (item) {
                    const day = box.dataset.day;
                    const period = box.dataset.period;
                    const slotKey = `${day}-${period}`;

                    if (seenSlots.has(slotKey)) {
                        hasDuplicate = true;
                        return;
                    }
                    seenSlots.add(slotKey);

                    data.push({
                        assignment_id: item.dataset.id,
                        day_of_week: day,
                        period: period,
                        room_id: item.dataset.roomId || null
                    });
                }
            });

            if (hasDuplicate) {
                if (overlay) overlay.classList.add('hidden');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Lưu Phiên bản';
                }
                isSaving = false;
                showToast('Phát hiện trùng môn trong cùng một tiết!', 'error');
                return;
            }

            // Gửi dữ liệu về Backend qua AJAX/Fetch
            fetch(window.ScheduleData.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.ScheduleData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    schedules: data,
                    class_id: window.ScheduleData.selectedClassId,
                    applies_from: document.getElementById('applies_from').value,
                    applies_to: document.getElementById('applies_to').value,
                    last_updated_at: window.ScheduleData.lastUpdatedAt
                })
            })
            .then(async res => {
                const isJson = res.headers.get('content-type')?.includes('application/json');
                const responseData = isJson ? await res.json() : null;

                if (!res.ok) {
                    throw new Error(responseData?.message || `Lỗi máy chủ (${res.status})`);
                }
                return responseData;
            })
            .then(res => {
                if (res.status === 'success') {
                    if (res.last_updated_at) {
                        window.ScheduleData.lastUpdatedAt = res.last_updated_at;
                    }
                    showToast('Lưu bản cập nhật thành công!');
                    // Tải lại trang sau khi lưu thành công để đồng bộ lại dữ liệu slot bận của giáo viên
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else {
                    throw new Error(res.message || 'Có lỗi xảy ra khi lưu lịch.');
                }
            })
            .catch(err => {
                console.error('Save error:', err);
                if (overlay) overlay.classList.add('hidden');
                showToast(err.message, 'error');
                
                // Giải phóng lock để người dùng có thể thử lại
                isSaving = false;
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<span class="material-symbols-outlined text-sm">save</span> Lưu Phiên bản';
                }
            });

        } catch (e) {
            console.error('Critical JS Error:', e);
            isSaving = false;
            if (overlay) overlay.classList.add('hidden');
        }
    };
});
