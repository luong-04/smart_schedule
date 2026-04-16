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
    let pendingItem = null; 
    let pendingTargetDay = null;
    let pendingTargetPeriod = null;

    document.querySelectorAll('.sidebar-item').forEach(el => {
        let tid = el.dataset.teacherId;
        let asId = el.dataset.id;
        
        if (teacherSlots[tid] === undefined) {
            teacherSlots[tid] = parseInt(el.dataset.teacherRemaining);
        }
        subjectSlots[asId] = parseInt(el.dataset.subjectRemaining);
    });

    function updateSidebarUI() {
        document.querySelectorAll('.sidebar-item').forEach(el => {
            let tid = el.dataset.teacherId;
            let asId = el.dataset.id;
            
            let tSlots = teacherSlots[tid];
            let sSlots = subjectSlots[asId];
            
            let tBadge = el.querySelector('.teacher-badge');
            let sBadge = el.querySelector('.subject-badge');
            let mainBadge = el.querySelector('.slot-badge');
            
            if(tBadge) {
                tBadge.innerText = tSlots;
                tBadge.className = `teacher-badge text-xs font-black ${tSlots <= 0 ? 'text-rose-500' : 'text-blue-600'}`;
            }
            if(sBadge) {
                sBadge.innerText = sSlots;
                sBadge.className = `subject-badge text-xs font-black ${sSlots <= 0 ? 'text-rose-500' : 'text-emerald-600'}`;
            }
            
            let minSlots = Math.min(tSlots, sSlots);
            if(mainBadge) {
                mainBadge.innerText = minSlots > 0 ? (minSlots < 10 ? "0"+minSlots : minSlots) : "HẾT";
                mainBadge.className = `slot-badge text-xs font-black ${minSlots <= 0 ? 'text-rose-500' : 'text-emerald-500'}`;
            }

            if(tSlots <= 0 || sSlots <= 0) {
                el.classList.add('opacity-50', 'bg-slate-50');
            } else {
                el.classList.remove('opacity-50', 'bg-slate-50');
            }
        });
    }

    function attachDoubleClickEvent(item) {
        item.addEventListener('dblclick', function() {
            let asId = this.dataset.id;
            let tid = this.dataset.teacherId;
            
            // Trả lại tiết cho cả phân công và giáo viên
            if(subjectSlots[asId] !== undefined) subjectSlots[asId]++;
            if(teacherSlots[tid] !== undefined) teacherSlots[tid]++;
            
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
        
        periods = [...new Set(periods)].sort((a,b) => a - b);
        let maxCons = 1, currentCons = 1;
        for(let i = 1; i < periods.length; i++) {
            if (periods[i] === periods[i-1] + 1) {
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
        
        // Lấy các ngày đang có trên lưới (excluding the item being moved if necessary, 
        // but Sortable moves it before onAdd, so we just count unique days from DOM)
        document.querySelectorAll(`.matrix-item[data-teacher-id="${teacherId}"]`).forEach(el => {
            let box = el.closest('.drop-zone');
            if (box && box.dataset.day) {
                daysInMatrix.add(parseInt(box.dataset.day));
            }
        });
        
        // Luôn add targetDay vào để kiểm tra xem nếu đặt vào đó thì có vượt ngưỡng không
        if (targetDayToAdd) {
            daysInMatrix.add(parseInt(targetDayToAdd));
        }

        // Cộng các ngày bận ở các lớp khác (đã load từ server)
        let otherDays = teacherOtherDays[teacherId] || [];
        otherDays.forEach(d => daysInMatrix.add(parseInt(d)));

        return daysInMatrix.size;
    }

    function closeModal() {
        const modal = document.getElementById('roomModal');
        const content = document.getElementById('roomModalContent');
        modal.classList.add('opacity-0');
        content.classList.replace('scale-100', 'scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    document.getElementById('btnCancelRoom').addEventListener('click', function() {
        if(pendingItem) {
            let asId = pendingItem.dataset.id;
            let tid = pendingItem.dataset.teacherId;
            subjectSlots[asId]++; 
            teacherSlots[tid]++; 
            updateSidebarUI();
            pendingItem.remove(); 
            pendingItem = null;
        }
        closeModal();
    });

    document.getElementById('btnConfirmRoom').addEventListener('click', function() {
        const select = document.getElementById('roomSelect');
        const roomId = select.value;
        const roomName = select.options[select.selectedIndex].text;

        if (CHECK_ROOM_CONFLICT == 1) {
            let slotKey = pendingTargetDay + '-' + pendingTargetPeriod;
            if (roomBusySlots[roomId] && roomBusySlots[roomId].includes(slotKey)) {
                alert(`⚠️ HỆ THỐNG CHẶN: [${roomName}] đã được lớp khác sử dụng vào Thứ ${pendingTargetDay} - Tiết ${pendingTargetPeriod}! Vui lòng chọn phòng khác.`);
                return;
            }
        }

        if(pendingItem) {
            pendingItem.dataset.roomId = roomId;
            
            const subjectName = pendingItem.querySelector('.subject-name') ? pendingItem.querySelector('.subject-name').innerText : pendingItem.querySelector('span[title]').title;
            const teacherName = pendingItem.querySelector('.teacher-name') ? pendingItem.querySelector('.teacher-name').innerText : pendingItem.querySelectorAll('span[title]')[1].title;
            
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
                } catch(e) { }
                
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    const day = parseInt(zone.dataset.day);
                    if (offDays.includes(day)) {
                        zone.classList.add('bg-slate-200', 'opacity-50');
                        zone.style.cursor = 'not-allowed';
                        zone.dataset.disabled = "true";
                    } else {
                        zone.dataset.disabled = "false";
                    }
                });
            },
            onEnd: function (evt) {
                document.querySelectorAll('.drop-zone').forEach(zone => {
                    zone.classList.remove('bg-slate-200', 'opacity-50');
                    zone.style.cursor = '';
                    zone.dataset.disabled = "false";
                });
            },
            onMove: function (evt) {
                if (evt.to && evt.to.dataset.disabled === "true") {
                    return false;
                }
            },
            onAdd: function (evt) {
                const item = evt.item;
                const asId = item.dataset.id;
                const tid = item.dataset.teacherId;
                const reqRoomType = item.dataset.roomTypeId; 
                const targetDay = evt.to.dataset.day;
                const targetPeriod = evt.to.dataset.period;
                const isFromSidebar = evt.from.id === 'external-events';

                let offDays = [];
                try {
                    const rawOffDays = item.dataset.offDays || '[]';
                    offDays = JSON.parse(rawOffDays).map(Number);
                } catch(e) { offDays = []; }

                if (offDays.includes(parseInt(targetDay))) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên này đã đăng ký NGHỈ CỐ ĐỊNH vào Thứ ${targetDay}!`);
                    if (isFromSidebar) item.remove(); else evt.from.appendChild(item);
                    return; 
                }

                if (isFromSidebar) {
                    if (teacherSlots[tid] <= 0) {
                        alert("⚠️ HỆ THỐNG CHẶN: Giáo viên này đã giảng dạy hết số tiết trong tuần!");
                        item.remove();
                        return;
                    }
                    if (subjectSlots[asId] <= 0) {
                        alert("⚠️ HỆ THỐNG CHẶN: Môn này đã được xếp đủ số tiết cho lớp!");
                        item.remove();
                        return;
                    }
                }

                if (CHECK_TEACHER_CONFLICT == 1) {
                    let slotKey = targetDay + '-' + targetPeriod;
                    if (teacherBusySlots[tid] && teacherBusySlots[tid].includes(slotKey)) {
                        alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên bị TRÙNG LỊCH! (Đã có tiết dạy ở lớp khác vào Thứ ${targetDay} - Tiết ${targetPeriod})`);
                        if (isFromSidebar) item.remove(); else evt.from.appendChild(item);
                        return;
                    }
                }

                let totalDays = getTeacherTotalDays(tid, targetDay);
                if (totalDays > MAX_DAYS_PER_WEEK) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên này vượt quá số ngày dạy tối đa (Tối đa ${MAX_DAYS_PER_WEEK} ngày/tuần)!`);
                    if (isFromSidebar) item.remove(); else evt.from.appendChild(item);
                    return;
                }

                let currentConsecutive = getConsecutiveSlotsCount(tid, targetDay, targetPeriod);
                if (currentConsecutive > MAX_CONSECUTIVE) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên bị giới hạn dạy tối đa ${MAX_CONSECUTIVE} tiết liên tiếp!`);
                    if (isFromSidebar) item.remove(); else evt.from.appendChild(item); 
                    return;
                }

                Array.from(evt.to.children).forEach(child => {
                    if (child !== item) {
                        if (child.dataset.id) {
                            subjectSlots[child.dataset.id]++;
                            teacherSlots[child.dataset.teacherId]++;
                        }
                        child.remove();
                    }
                });

                if (isFromSidebar) {
                    subjectSlots[asId]--;
                    teacherSlots[tid]--;

                    if (reqRoomType && reqRoomType !== "" && reqRoomType !== "null") {
                        const filteredRooms = allRooms.filter(r => r.room_type_id == reqRoomType);
                        
                        if(filteredRooms.length === 0) {
                            alert("⚠️ LỖI: Hệ thống chưa có phòng học nào thuộc loại phòng yêu cầu cho môn này!");
                            subjectSlots[asId]++; 
                            teacherSlots[tid]++; 
                            item.remove();
                            updateSidebarUI();
                            return;
                        }

                        const select = document.getElementById('roomSelect');
                        select.innerHTML = '';
                        filteredRooms.forEach(r => {
                            select.innerHTML += `<option value="${r.id}">${r.name}</option>`;
                        });

                        pendingItem = item;
                        pendingTargetDay = targetDay;
                        pendingTargetPeriod = targetPeriod;
                        item.style.display = 'none'; 
                        
                        const modal = document.getElementById('roomModal');
                        const content = document.getElementById('roomModalContent');
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modal.classList.remove('opacity-0');
                            content.classList.replace('scale-95', 'scale-100');
                        }, 10);
                        
                        updateSidebarUI();
                        return; 
                    }

                    const subjectName = item.querySelector('.subject-name').innerText;
                    const teacherName = item.querySelector('.teacher-name').innerText;
                    
                    item.className = "matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden";
                    
                    item.innerHTML = `
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary shrink-0"></div>
                        <div class="w-full flex flex-col items-center justify-center px-1 min-w-0 overflow-hidden">
                            <span class="text-[9px] font-black uppercase text-primary text-center leading-tight whitespace-normal break-words w-full block" title="${subjectName}">${subjectName}</span>
                            <span class="text-[8px] font-semibold text-slate-600 text-center leading-tight whitespace-normal break-words w-full block mt-0.5" title="${teacherName}">${teacherName}</span>
                        </div>
                    `;
                    
                    attachDoubleClickEvent(item);
                }
                updateSidebarUI();
            }
        });
    });

    new Sortable(document.getElementById('external-events'), {
        group: { name: 'shared', pull: 'clone', put: false },
        sort: false,
        animation: 150,
        onStart: function (evt) {
            const item = evt.item;
            let offDays = [];
            try {
                const rawOffDays = item.dataset.offDays || '[]';
                offDays = JSON.parse(rawOffDays).map(Number);
            } catch(e) { }
            
            document.querySelectorAll('.drop-zone').forEach(zone => {
                const day = parseInt(zone.dataset.day);
                if (offDays.includes(day)) {
                    zone.classList.add('bg-slate-200', 'opacity-50');
                    zone.style.cursor = 'not-allowed';
                    zone.dataset.disabled = "true";
                } else {
                    zone.dataset.disabled = "false";
                }
            });
        },
        onEnd: function (evt) {
            document.querySelectorAll('.drop-zone').forEach(zone => {
                zone.classList.remove('bg-slate-200', 'opacity-50');
                zone.style.cursor = '';
                zone.dataset.disabled = "false";
            });
        },
        onMove: function (evt) {
            if (evt.to && evt.to.dataset.disabled === "true") {
                return false;
            }
        }
    });

    document.getElementById('search-teacher').addEventListener('input', function(e) {
        const text = e.target.value.toLowerCase();
        document.querySelectorAll('.sidebar-item').forEach(item => {
            const tName = item.querySelector('.teacher-name').innerText.toLowerCase();
            const sName = item.querySelector('.subject-name').innerText.toLowerCase();
            item.style.display = (tName.includes(text) || sName.includes(text)) ? 'block' : 'none';
        });
    });

    function saveSchedule() {
        const data = [];
        document.querySelectorAll('.drop-zone').forEach(box => {
            const item = box.querySelector('.matrix-item');
            if (item) {
                data.push({ 
                    assignment_id: item.dataset.id, 
                    day_of_week: box.dataset.day, 
                    period: box.dataset.period,
                    room_id: item.dataset.roomId || null
                });
            }
        });

        fetch(window.ScheduleData.saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ScheduleData.csrfToken },
            body: JSON.stringify({ schedules: data, class_id: window.ScheduleData.selectedClassId })
        }).then(res => res.json()).then(res => {
            if (res.status === 'success') {
                alert('🎉 Tuyệt vời! Đã lưu thời khóa biểu thành công.');
                window.location.reload();
            } else {
                alert('⚠️ ' + res.message);
            }
        }).catch(err => alert('Lỗi kết nối với máy chủ!'));
    }
