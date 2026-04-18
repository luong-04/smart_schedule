document.addEventListener('DOMContentLoaded', function () {
    const MAX_CONSECUTIVE = window.ScheduleData.maxConsecutive;
    const MAX_DAYS_PER_WEEK = window.ScheduleData.maxDaysPerWeek;
    const CHECK_TEACHER_CONFLICT = window.ScheduleData.checkTeacherConflict;
    const CHECK_ROOM_CONFLICT = window.ScheduleData.checkRoomConflict;

    const allRooms = window.ScheduleData.allRooms;
    const teacherBusySlots = window.ScheduleData.teacherBusySlots;
    const teacherOtherDays = window.ScheduleData.teacherOtherDays;
    const roomBusySlots = window.ScheduleData.roomBusySlots;

    let teacherSlots = {};
    let subjectSlots = {};
    let pendingItem = null; // Fix: Khai báo biến đang chờ xử lý phòng học
    let pendingTargetDay = null;
    let pendingTargetPeriod = null;

    /**
     * TÔ MÀU XUNG ĐỘT TRỰC TIẾP (Real-time Conflict Highlighting)
     * Highlight các ô mà giáo viên hoặc phòng học đang bận ở lớp khác.
     */
    function updateConflictHighlights(teacherId, roomId, active) {
        // Xóa highlight cũ
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

            if (busySlots.includes(slotKey) || roomBusy.includes(slotKey)) {
                zone.classList.add('conflict-zone');
            }
        });
    }

    // Khởi tạo số tiết còn lại từ Sidebar
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

            if (tSlots <= 0 || sSlots <= 0) {
                el.classList.add('opacity-50', 'bg-slate-50');
            } else {
                el.classList.remove('opacity-50', 'bg-slate-50');
            }
        });
    }

    function attachDoubleClickEvent(item) {
        if (!item) return;
        item.addEventListener('dblclick', function () {
            let asId = this.dataset.id;
            let tid = this.dataset.teacherId;

            // Trả lại tiết cho cả phân công và giáo viên
            if (asId && subjectSlots[asId] !== undefined) subjectSlots[asId]++;
            if (tid && teacherSlots[tid] !== undefined) teacherSlots[tid]++;

            // Xóa phần tử khỏi ma trận và cập nhật lại Sidebar ngay lập tức
            this.remove();
            updateSidebarUI();
        });
    }

    document.querySelectorAll('.matrix-item').forEach(item => { attachDoubleClickEvent(item); });

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
     * Tính tổng số ngày giáo viên này đã có lịch (trong ma trận + các lớp khác).
     * Dùng để kiểm tra giới hạn MAX_DAYS_PER_WEEK.
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

    function closeModal() {
        const modal = document.getElementById('roomModal');
        const content = document.getElementById('roomModalContent');
        if (!modal) return;
        modal.classList.add('opacity-0');
        content.classList.replace('scale-100', 'scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    const btnCancelRoom = document.getElementById('btnCancelRoom');
    if (btnCancelRoom) {
        btnCancelRoom.addEventListener('click', function () {
            if (pendingItem) {
                let asId = pendingItem.dataset.id;
                let tid = pendingItem.dataset.teacherId;
                if (asId) subjectSlots[asId]++;
                if (tid) teacherSlots[tid]++;
                updateSidebarUI();
                pendingItem.remove();
                pendingItem = null;
            }
            closeModal();
        });
    }

    const btnConfirmRoom = document.getElementById('btnConfirmRoom');
    if (btnConfirmRoom) {
        btnConfirmRoom.addEventListener('click', function () {
            const select = document.getElementById('roomSelect');
            if (!select) return;
            const roomId = select.value;
            const roomName = select.options[select.selectedIndex].text;

            if (CHECK_ROOM_CONFLICT == 1) {
                let slotKey = pendingTargetDay + '-' + pendingTargetPeriod;
                if (roomBusySlots[roomId] && roomBusySlots[roomId].includes(slotKey)) {
                    alert(`⚠️ HỆ THỐNG CHẶN: [${roomName}] đã được lớp khác sử dụng vào Thứ ${pendingTargetDay} - Tiết ${pendingTargetPeriod}! Vui lòng chọn phòng khác.`);
                    return;
                }
            }

            if (pendingItem) {
                pendingItem.dataset.roomId = roomId;

                const subjectName = pendingItem.dataset.subjectName || (pendingItem.querySelector('.subject-name') ? pendingItem.querySelector('.subject-name').innerText : 'Môn học');
                const teacherName = pendingItem.dataset.teacherName || (pendingItem.querySelector('.teacher-name') ? pendingItem.querySelector('.teacher-name').innerText : 'Giáo viên');

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

    document.querySelectorAll('.drop-zone').forEach(el => {
        new Sortable(el, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onStart: function (evt) {
                const item = evt.item;
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
            onAdd: function (evt) {
                const item = evt.item;
                const targetDay = evt.to.dataset.day;
                const targetPeriod = evt.to.dataset.period;
                const needsTransformation = item.classList.contains('sidebar-item');

                // HELPER: Đưa môn về chỗ cũ nếu lỗi
                function bounceBack() {
                    if (needsTransformation) {
                        item.remove(); // Xóa bản sao từ sidebar (thẻ thật vẫn ở sidebar)
                    } else {
                        if (evt.from) {
                            evt.from.appendChild(item); // Trả về ô cũ trong lưới
                        } else {
                            item.remove();
                        }
                    }
                    updateSidebarUI();
                }

                // 1. KIỂM TRA Ô ĐÃ CÓ MÔN CHƯA (Bounce back if occupied)
                const existingItems = Array.from(evt.to.children).filter(child => child !== item && (child.classList.contains('matrix-item') || child.classList.contains('sidebar-item')));
                
                if (existingItems.length > 0) {
                    alert(`⚠️ Ô này đã có môn học! Vui lòng xóa môn cũ trước khi xếp môn mới.`);
                    bounceBack();
                    return;
                }

                const asId = item.dataset.id;
                const tid = item.dataset.teacherId;
                const reqRoomType = item.dataset.roomTypeId;

                updateConflictHighlights(null, null, false);

                let offDays = [];
                try {
                    const rawOffDays = item.dataset.offDays || '[]';
                    offDays = JSON.parse(rawOffDays).map(Number);
                } catch (e) { offDays = []; }

                if (offDays.includes(parseInt(targetDay))) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên này đã đăng ký NGHỈ CỐ ĐỊNH vào Thứ ${targetDay}!`);
                    bounceBack();
                    return;
                }

                if (needsTransformation) {
                    if (teacherSlots[tid] <= 0) {
                        alert("⚠️ HỆ THỐNG CHẶN: Giáo viên này đã giảng dạy hết số tiết trong tuần!");
                        bounceBack();
                        return;
                    }
                    if (subjectSlots[asId] <= 0) {
                        alert("⚠️ HỆ THỐNG CHẶN: Môn này đã được xếp đủ số tiết cho lớp!");
                        bounceBack();
                        return;
                    }
                }

                if (CHECK_TEACHER_CONFLICT == 1) {
                    let slotKey = targetDay + '-' + targetPeriod;
                    if (teacherBusySlots[tid] && teacherBusySlots[tid].includes(slotKey)) {
                        alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên bị TRÙNG LỊCH! (Đã có tiết dạy ở lớp khác vào Thứ ${targetDay} - Tiết ${targetPeriod})`);
                        bounceBack();
                        return;
                    }
                }

                let totalDays = getTeacherTotalDays(tid, targetDay);
                if (totalDays > MAX_DAYS_PER_WEEK) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên này vượt quá số ngày dạy tối đa (Tối đa ${MAX_DAYS_PER_WEEK} ngày/tuần)!`);
                    bounceBack();
                    return;
                }

                let currentConsecutive = getConsecutiveSlotsCount(tid, targetDay, targetPeriod);
                if (currentConsecutive > MAX_CONSECUTIVE) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên bị giới hạn dạy tối đa ${MAX_CONSECUTIVE} tiết liên tiếp!`);
                    bounceBack();
                    return;
                }

                // Dọn dẹp ô (Safety)
                Array.from(evt.to.children).forEach(child => {
                    if (child !== item && (child.classList.contains('matrix-item') || child.classList.contains('sidebar-item'))) {
                        if (child.dataset.id) {
                            subjectSlots[child.dataset.id]++;
                            teacherSlots[child.dataset.teacherId]++;
                        }
                        child.remove();
                    }
                });

                if (needsTransformation) {
                    subjectSlots[asId]--;
                    teacherSlots[tid]--;

                    const subjectType = item.dataset.subjectType;
                    console.log(`[Matrix] Transform started for: ${item.dataset.subjectName} (${subjectType})`);

                    if (subjectType === 'practice' && reqRoomType && reqRoomType !== "" && reqRoomType !== "null") {
                        const filteredRooms = allRooms.filter(r => r.room_type_id == reqRoomType);

                        if (filteredRooms.length === 0) {
                            alert("⚠️ LỖI: Hệ thống chưa có phòng học nào thuộc loại phòng yêu cầu cho môn này!");
                            subjectSlots[asId]++;
                            teacherSlots[tid]++;
                            bounceBack();
                            return;
                        }

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
                        item.style.display = 'none';

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

                    // Transformation for Theory subjects or Practice that didn't need a room
                    try {
                        const subjectName = (item.dataset.subjectName || 'Môn học').trim();
                        const teacherName = (item.dataset.teacherName || 'Giáo viên').trim();

                        // Wipe ALL sidebar specific classes and styling
                        item.className = "matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden";
                        item.style.padding = "0"; // Force override sidebar padding
                        item.style.background = ""; // Clear sidebar bg
                        
                        item.innerHTML = `
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary shrink-0"></div>
                            <div class="w-full flex flex-col items-center justify-center px-1 min-w-0 overflow-hidden">
                                <span class="text-[9px] font-black uppercase text-primary text-center leading-tight whitespace-normal break-words w-full block" title="${subjectName}">${subjectName}</span>
                                <span class="text-[8px] font-semibold text-slate-600 text-center leading-tight whitespace-normal break-words w-full block mt-0.5" title="${teacherName}">${teacherName}</span>
                            </div>
                        `;

                        attachDoubleClickEvent(item);
                        console.log(`[Matrix] Transform complete for: ${subjectName}`);
                    } catch (e) {
                        console.error('[Matrix] Transformation Error:', e);
                        item.innerHTML = '<div class="text-[9px] font-bold text-red-500">Lỗi hiển thị</div>';
                    }
                }
                updateSidebarUI();
            }
        });
    });

    const externalEvents = document.getElementById('external-events');
    if (externalEvents) {
        new Sortable(externalEvents, {
            group: { name: 'shared', pull: 'clone', put: false },
            sort: false,
            animation: 150,
            onStart: function (evt) {
                const item = evt.item;
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
                if (evt.to && evt.to.dataset.disabled === "true") {
                    return false;
                }
            }
        });
    }

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
        }, 5000); // 5 seconds for visibility
    }

    // EXPOSE TO WINDOW: Đảm bảo button onclick="saveSchedule()" trong HTML có thể gọi được
    let isSaving = false;
    window.saveSchedule = function () {
        // 1. NGĂN CHẶN CONCURRENCY NGAY LẬP TỨC
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
                alert('⚠️ LỖI: Phát hiện có nhiều môn học trong cùng một tiết! Vui lòng kiểm tra lại ma trận trước khi lưu.');
                return;
            }

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
                
                // Giải phóng lock để người dùng có thể sửa lỗi và lưu lại
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

