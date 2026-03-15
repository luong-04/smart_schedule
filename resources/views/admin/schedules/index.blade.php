@extends('layouts.admin')
@section('title', 'Ma trận Xếp lịch')

@section('content')
<style>
    /* Nhúng CSS tùy chỉnh từ bản thiết kế của bạn */
    :root { --primary: #135bec; }
    .text-primary { color: var(--primary) !important; }
    .bg-primary { background-color: var(--primary) !important; }
    .border-primary { border-color: var(--primary) !important; }
    
    .schedule-grid {
        display: grid;
        grid-template-columns: 80px repeat(6, 1fr);
    }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Làm mờ thẻ gốc khi đang kéo đi */
    .sortable-ghost { opacity: 0.3; }
    /* Định dạng thẻ đang được chuột giữ */
    .sortable-drag { cursor: grabbing !important; box-shadow: 0 10px 25px -5px rgba(19, 91, 236, 0.3); }
</style>

<div class="flex flex-col h-[calc(100vh-100px)]">
    <div class="bg-white p-4 rounded-t-[2rem] border-b border-slate-200 flex justify-between items-center shrink-0">
        <div class="flex items-center gap-4">
            <div class="bg-blue-50/50 p-2 rounded-xl text-primary flex items-center justify-center border border-blue-100">
                <span class="material-symbols-outlined">grid_view</span>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800 tracking-tight">Xếp thời khóa biểu</h2>
                <select onchange="window.location.href='?class_id='+this.value" class="bg-transparent border-none p-0 text-xs font-black text-slate-500 uppercase tracking-widest focus:ring-0 cursor-pointer outline-none hover:text-primary transition-colors">
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>Lớp {{ $class->name }} - Khối {{ $class->grade }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button onclick="saveSchedule()" class="bg-primary text-white px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">save</span> Lưu Ma trận
        </button>
    </div>

    <div class="flex flex-1 overflow-hidden bg-white rounded-b-[2rem] shadow-sm border border-t-0 border-slate-200">
        
        <section class="w-[30%] border-r border-slate-200 bg-[#f8f9fa] flex flex-col shrink-0">
            <div class="p-5 border-b border-slate-200 bg-white">
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">person_search</span>
                    <input class="w-full pl-10 pr-4 py-2.5 text-xs font-medium border-slate-200 bg-slate-50 rounded-xl focus:ring-primary focus:border-primary transition-all" placeholder="Tìm giáo viên, môn..." type="text" id="search-teacher"/>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 scrollbar-hide flex flex-col gap-3" id="external-events">
                <div class="flex justify-between items-center mb-1">
                    <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest">Danh sách phân công</h3>
                    <span class="bg-slate-200 text-slate-600 text-[9px] px-2 py-1 rounded font-bold">{{ count($assignments) }} GV</span>
                </div>

                @foreach($assignments as $as)
                <div class="sidebar-item bg-white p-3 rounded-xl border border-slate-200 shadow-sm cursor-move hover:border-primary/50 transition-all group relative" 
                     data-id="{{ $as->id }}" 
                     data-teacher-id="{{ $as->teacher_id }}"
                     data-off-days="{{ json_encode($as->teacher->off_days ?? []) }}"
                     data-remaining="{{ $as->teacher->remaining_slots }}">
                    
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="teacher-name text-sm font-bold group-hover:text-primary transition-colors">{{ $as->teacher->name }}</p>
                            <p class="subject-name text-[11px] text-slate-500 font-medium mt-0.5 uppercase">{{ $as->subject->name }}</p>
                        </div>
                        <span class="bg-slate-50 text-slate-400 border border-slate-100 text-[9px] px-2 py-0.5 rounded-lg font-bold">ID: {{ $as->teacher->code ?? 'GV' }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-50">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Tiết khả dụng</span>
                        <span class="slot-badge text-xs font-black {{ $as->teacher->remaining_slots <= 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                            {{ $as->teacher->remaining_slots > 0 ? sprintf("%02d", $as->teacher->remaining_slots) : 'HẾT' }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="p-4 border-t border-slate-200 text-center bg-white">
                <p class="text-[10px] text-slate-400 font-medium italic"><span class="material-symbols-outlined text-[12px] align-middle">mouse</span> Nhấp đúp vào môn trên lưới để xóa.</p>
            </div>
        </section>

        <section class="flex-1 flex flex-col bg-white overflow-hidden">
            <div class="flex-1 overflow-auto p-6">
                <div class="min-w-[700px] border border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-white">
                    
                    <div class="schedule-grid bg-slate-50 border-b border-slate-200">
                        <div class="p-3 flex items-center justify-center border-r border-slate-200">
                            <span class="material-symbols-outlined text-slate-400">schedule</span>
                        </div>
                        @for($d=2; $d<=7; $d++)
                        <div class="p-3 text-center border-r last:border-r-0 border-slate-200 font-black text-xs text-slate-600 uppercase tracking-widest">Thứ {{ $d }}</div>
                        @endfor
                    </div>

                    <div class="divide-y divide-slate-100 bg-[#f8f9fa]">
                        @php
                            $shiftStr = strtolower($classroom->shift ?? 'morning');
                            $fDay = $settings[$shiftStr.'_flag_day'] ?? 2;
                            $fPer = $settings[$shiftStr.'_flag_period'] ?? ($shiftStr == 'morning' ? 1 : 10);
                            $mDay = $settings[$shiftStr.'_meeting_day'] ?? 7;
                            $mPer = $settings[$shiftStr.'_meeting_period'] ?? ($shiftStr == 'morning' ? 5 : 10);
                        @endphp

                        @for($p=1; $p<=10; $p++)
                        @if($p == 6)
                        <div class="schedule-grid bg-slate-50/50 border-y border-slate-200 relative overflow-hidden">
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMjBMMjAgMEgxNkwwIDE2djRaTTIwIDE2djRMMTYgMjBMMjAgMTZ6IiBmaWxsPSIjZTFlNWU5IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=')] opacity-[0.05]"></div>
                            <div class="p-2 border-r border-slate-200 relative z-10"></div>
                            <div class="col-span-6 flex items-center justify-center h-8 relative z-10">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] bg-slate-50/80 px-4 rounded-full">Nghỉ trưa / Đổi ca</span>
                            </div>
                        </div>
                        @endif

                        <div class="schedule-grid group hover:bg-slate-50/50 transition-colors">
                            <div class="p-2 flex flex-col items-center justify-center border-r border-slate-200 bg-white group-hover:bg-slate-50 transition-colors">
                                <span class="text-[10px] font-black text-slate-400">TIẾT {{ $p }}</span>
                            </div>
                            
                            @for($d=2; $d<=7; $d++)
                                @php
                                    $isFlagSalute = ($d == $fDay && $p == $fPer);
                                    $isClassMeeting = ($d == $mDay && $p == $mPer);
                                    $isFixed = $isFlagSalute || $isClassMeeting;
                                    $fixedLabel = $isFlagSalute ? 'CHÀO CỜ' : 'SINH HOẠT';
                                    $current = $schedules->where('day_of_week', $d)->where('period', $p)->where('assignment.class_id', $selectedClassId)->first();
                                    
                                    // KIỂM TRA HIỂN THỊ GVCN VÀO Ô CỐ ĐỊNH
                                    $assignFlag = $settings['assign_gvcn_flag_salute'] ?? 0;
                                    $assignMeeting = $settings['assign_gvcn_class_meeting'] ?? 0;
                                    $gvcnName = $classroom->homeroom_teacher;
                                    $showGvcn = ($isFlagSalute && $assignFlag) || ($isClassMeeting && $assignMeeting);

                                    // MÀU SẮC ĐẶC TRƯNG CHO CHÀO CỜ VÀ SINH HOẠT
                                    $fixedBg = $isFlagSalute ? 'bg-rose-50 border-rose-200' : 'bg-emerald-50 border-emerald-200';
                                    $fixedText = $isFlagSalute ? 'text-rose-600' : 'text-emerald-600';
                                    $fixedGvcnBg = $isFlagSalute ? 'bg-rose-100/80 text-rose-800' : 'bg-emerald-100/80 text-emerald-800';
                                @endphp
                                
                                <div class="p-1.5 border-r last:border-r-0 border-slate-200 h-[85px] flex items-center justify-center relative bg-white">
                                    @if($isFixed)
                                        <div class="w-full h-full rounded-xl flex flex-col items-center justify-center border {{ $fixedBg }} pointer-events-none select-none relative overflow-hidden" 
                                             data-day="{{ $d }}" data-period="{{ $p }}">
                                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMjBMMjAgMEgxNkwwIDE2djRaTTIwIDE2djRMMTYgMjBMMjAgMTZ6IiBmaWxsPSIjZTFlNWU5IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=')] opacity-[0.03]"></div>
                                            
                                            <span class="relative z-10 text-[11px] font-black tracking-widest {{ $fixedText }}">{{ $fixedLabel }}</span>
                                            
                                            @if($showGvcn && !empty($gvcnName))
                                                <span class="relative z-10 text-[9px] font-bold mt-1 px-2 py-0.5 truncate max-w-[95%] rounded {{ $fixedGvcnBg }}">{{ $gvcnName }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="drop-zone w-full h-full rounded-xl flex items-center justify-center overflow-hidden transition-all border-2 border-dashed border-slate-200 hover:border-primary hover:bg-blue-50/20 cursor-pointer relative" 
                                             data-day="{{ $d }}" data-period="{{ $p }}">
                                            
                                            @if($current)
                                                <div class="matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden" 
                                                     data-id="{{ $current->assignment_id }}"
                                                     data-teacher-id="{{ $current->assignment->teacher_id }}"
                                                     data-off-days="{{ json_encode($current->assignment->teacher->off_days ?? []) }}">
                                                    
                                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
                                                    
                                                    <span class="text-[10px] font-black uppercase text-primary text-center leading-tight truncate w-full px-2">{{ $current->assignment->subject->name }}</span>
                                                    <span class="text-[9px] font-semibold text-slate-600 text-center truncate w-full mt-1 px-2">{{ $current->assignment->teacher->name }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500 font-medium">
                <div class="flex items-center gap-5">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-slate-100 border border-slate-300"></span> Ô trống</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-rose-50 border border-rose-200"></span> Chào cờ</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-50 border border-emerald-200"></span> Sinh hoạt lớp</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-primary/20 border border-primary/40"></span> Đã xếp lịch</span>
                </div>
                <div>Lưu ý: Chỉ những thẻ nằm trong lưới ma trận mới được lưu.</div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // 1. STATE MANAGEMENT
    const MAX_CONSECUTIVE = {{ $settings['max_consecutive_slots'] ?? 3 }};
    let teacherSlots = {};
    
    document.querySelectorAll('.sidebar-item').forEach(el => {
        let tid = el.dataset.teacherId;
        teacherSlots[tid] = parseInt(el.dataset.remaining);
    });

    function updateSidebarUI() {
        document.querySelectorAll('.sidebar-item').forEach(el => {
            let tid = el.dataset.teacherId;
            let slots = teacherSlots[tid];
            let badge = el.querySelector('.slot-badge');
            
            if(slots <= 0) {
                badge.innerText = "HẾT";
                badge.className = "slot-badge text-xs font-black text-rose-500";
                el.classList.add('opacity-50', 'bg-slate-50');
            } else {
                badge.innerText = slots < 10 ? "0" + slots : slots;
                badge.className = "slot-badge text-xs font-black text-emerald-500";
                el.classList.remove('opacity-50', 'bg-slate-50');
            }
        });
    }

    function attachDoubleClickEvent(item) {
        item.addEventListener('dblclick', function() {
            let tid = this.dataset.teacherId;
            teacherSlots[tid]++;
            updateSidebarUI();
            this.remove();
        });
    }

    document.querySelectorAll('.matrix-item').forEach(item => { attachDoubleClickEvent(item); });

    // ==========================================
    // 2. THUẬT TOÁN ĐẾM SỐ TIẾT
    // ==========================================
    function getConsecutiveSlotsCount(teacherId, day, targetPeriod) {
        let periods = [];
        document.querySelectorAll(`.drop-zone[data-day="${day}"]`).forEach(box => {
            let item = box.querySelector(`.matrix-item[data-teacher-id="${teacherId}"]`);
            if (item) periods.push(parseInt(box.dataset.period));
        });
        
        if (targetPeriod !== undefined) {
            periods.push(parseInt(targetPeriod));
        }
        
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

    // ==========================================
    // 3. TẠO DRAG & DROP KHÔNG BỊ GIẬT/PHÓNG TO
    // ==========================================
    document.querySelectorAll('.drop-zone').forEach(el => {
        new Sortable(el, {
            group: 'shared',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onAdd: function (evt) {
                const item = evt.item;
                const tid = item.dataset.teacherId;
                const targetDay = evt.to.dataset.day;
                const targetPeriod = evt.to.dataset.period;
                const isFromSidebar = evt.from.id === 'external-events';

                if (isFromSidebar && teacherSlots[tid] <= 0) {
                    alert("⚠️ HỆ THỐNG CHẶN: Môn này đã được xếp hết số tiết khả dụng!");
                    item.remove();
                    return;
                }

                let currentConsecutive = getConsecutiveSlotsCount(tid, targetDay, targetPeriod);
                if (currentConsecutive > MAX_CONSECUTIVE) {
                    alert(`⚠️ HỆ THỐNG CHẶN: Giáo viên này bị giới hạn dạy tối đa ${MAX_CONSECUTIVE} tiết liên tiếp!`);
                    if (isFromSidebar) item.remove();
                    else evt.from.appendChild(item); 
                    return;
                }

                Array.from(evt.to.children).forEach(child => {
                    if (child !== item) {
                        if (child.dataset.teacherId) teacherSlots[child.dataset.teacherId]++;
                        child.remove();
                    }
                });

                if (isFromSidebar) {
                    teacherSlots[tid]--;
                    
                    const offDays = JSON.parse(item.dataset.offDays || '[]');
                    if (offDays.includes(parseInt(targetDay))) {
                        alert("⚠️ Cảnh báo: Giáo viên này đã đăng ký nghỉ vào Thứ " + targetDay);
                        item.classList.add('ring-2', 'ring-rose-500');
                    }

                    const subjectName = item.querySelector('.subject-name').innerText;
                    const teacherName = item.querySelector('.teacher-name').innerText;
                    
                    // Vẽ lại Giao diện Thẻ Môn trong lưới (Không bị phình to)
                    item.className = "matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden";
                    item.innerHTML = `
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
                        <span class="text-[10px] font-black uppercase text-primary text-center leading-tight truncate w-full px-2">${subjectName}</span>
                        <span class="text-[9px] font-semibold text-slate-600 text-center truncate w-full mt-1 px-2">${teacherName}</span>
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
        animation: 150
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
                data.push({ assignment_id: item.dataset.id, day_of_week: box.dataset.day, period: box.dataset.period });
            }
        });

        fetch('{{ route("admin.schedules.save") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ schedules: data, class_id: {{ $selectedClassId }} })
        }).then(res => res.json()).then(res => {
            if (res.status === 'success') {
                alert('🎉 Tuyệt vời! Đã lưu thời khóa biểu thành công.');
                window.location.reload();
            } else {
                alert('⚠️ ' + res.message);
            }
        }).catch(err => alert('Lỗi hệ thống!'));
    }
</script>
@endsection