@extends('layouts.admin')
@section('title', 'Ma trận Xếp lịch')

@section('content')
<style>
    :root { --primary: #135bec; }
    .text-primary { color: var(--primary) !important; }
    .bg-primary { background-color: var(--primary) !important; }
    .border-primary { border-color: var(--primary) !important; }
    
    .schedule-grid { display: grid; grid-template-columns: 80px repeat(6, minmax(0, 1fr)); }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .sortable-ghost { opacity: 0.3; }
    .sortable-drag { cursor: grabbing !important; box-shadow: 0 10px 25px -5px rgba(19, 91, 236, 0.3); }

    .drop-zone { max-width: 100%; min-width: 0; overflow: hidden; }
    .matrix-item { width: 100%; max-width: 100%; min-width: 0; overflow: hidden; }
</style>

<div id="roomModal" class="fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center hidden opacity-0 transition-opacity backdrop-blur-sm">
    <div class="bg-white rounded-3xl p-6 w-[400px] shadow-2xl transform scale-95 transition-transform" id="roomModalContent">
        <div class="flex items-center gap-3 mb-4 border-b border-slate-100 pb-4">
            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">meeting_room</span>
            </div>
            <div>
                <h3 class="font-black text-slate-800 text-lg uppercase tracking-tight">Chọn phòng học</h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase">Môn học yêu cầu thực hành</p>
            </div>
        </div>
        
        <select id="roomSelect" class="w-full bg-slate-50 border border-slate-200 text-slate-700 font-black uppercase text-xs tracking-widest rounded-xl px-4 py-4 mb-6 focus:ring-primary focus:border-primary shadow-inner">
        </select>
        
        <div class="flex justify-end gap-3">
            <button id="btnCancelRoom" class="px-5 py-3 rounded-xl font-bold text-xs text-slate-500 hover:bg-slate-100 uppercase tracking-widest transition-colors">Hủy thao tác</button>
            <button id="btnConfirmRoom" class="px-5 py-3 rounded-xl font-black text-xs bg-primary text-white uppercase tracking-widest hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30">Xác nhận</button>
        </div>
    </div>
</div>

<div class="flex flex-col h-[calc(100vh-100px)]">
    <div class="bg-white p-4 rounded-t-[2rem] border-b border-slate-200 flex justify-between items-center shrink-0">
    <div class="flex items-center gap-4">
            <div class="bg-blue-50/50 p-2 rounded-xl text-primary flex items-center justify-center border border-blue-100">
                <span class="material-symbols-outlined">grid_view</span>
            </div>
            <div>
                <h2 class="text-sm font-bold text-slate-800 tracking-tight">Xếp thời khóa biểu</h2>
                <select onchange="window.location.href='?class_id='+this.value" class="bg-transparent border-none p-0 text-xs font-black text-slate-500 uppercase tracking-widest focus:ring-0 cursor-pointer outline-none hover:text-primary transition-colors">
                    @php
                        // Sắp xếp chuẩn: Khối (10->12) trước, Tên lớp (A->Z) sau
                        $sortedClasses = $classes->sortBy(function($c) {
                            return sprintf('%02d-%s', $c->grade, $c->name);
                        });
                    @endphp
                    @foreach($sortedClasses as $class)
                        <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                            Lớp {{ $class->name }} - Khối {{ $class->grade }} ({{ $class->block ?? 'Cơ bản' }})
                        </option>
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
                    <span class="bg-slate-200 text-slate-600 text-[9px] px-2 py-1 rounded font-bold">{{ count($assignments) }} Môn</span>
                </div>

                @forelse($assignments as $as)
                <div class="sidebar-item bg-white p-3 rounded-xl border border-slate-200 shadow-sm cursor-move hover:border-primary/50 transition-all group relative" 
                     data-id="{{ $as->id }}" 
                     data-teacher-id="{{ $as->teacher_id }}"
                     data-room-type-id="{{ $as->subject->room_type_id }}"
                     data-off-days="{{ is_array($as->teacher->off_days) ? json_encode($as->teacher->off_days) : $as->teacher->off_days ?? '[]' }}"
                     data-teacher-remaining="{{ $as->teacher_remaining }}"
                     data-subject-remaining="{{ $as->remaining_subject_slots }}">
                    
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-[75%]">
                            <p class="teacher-name text-sm font-bold group-hover:text-primary transition-colors truncate w-full" title="{{ $as->teacher->name }}">{{ $as->teacher->name }}</p>
                            <p class="subject-name text-[11px] text-slate-500 font-medium mt-0.5 uppercase truncate w-full" title="{{ $as->subject->name }}">{{ $as->subject->name }}</p>
                        </div>
                        <span class="bg-slate-50 text-slate-400 border border-slate-100 text-[9px] px-2 py-0.5 rounded-lg font-bold shrink-0">ID: {{ $as->teacher->code ?? 'GV' }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-50">
                        <div class="flex items-center gap-4">
                            <div class="flex flex-col items-center">
                                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-widest mb-0.5">Tiết GV</span>
                                <span class="teacher-badge text-xs font-black {{ $as->teacher_remaining <= 0 ? 'text-rose-500' : 'text-blue-600' }}">{{ $as->teacher_remaining }}</span>
                            </div>
                            <div class="w-px h-6 bg-slate-200"></div>
                            <div class="flex flex-col items-center">
                                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-widest mb-0.5">Tiết Môn</span>
                                <span class="subject-badge text-xs font-black {{ $as->remaining_subject_slots <= 0 ? 'text-rose-500' : 'text-emerald-600' }}">{{ $as->remaining_subject_slots }}</span>
                            </div>
                        </div>
                        <span class="slot-badge text-xs font-black {{ $as->actual_remaining <= 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                            {{ $as->actual_remaining > 0 ? sprintf("%02d", $as->actual_remaining) : 'HẾT' }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 opacity-50">
                    <span class="material-symbols-outlined text-4xl">inventory_2</span>
                    <p class="text-xs font-bold mt-2 uppercase">Lớp chưa có môn học định mức</p>
                </div>
                @endforelse
            </div>
            
            <div class="p-4 border-t border-slate-200 text-center bg-white shrink-0">
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
                            // DRY: Các biến này được tính 1 lần ở Controller, không cần tính lại ở đây
                            $fDay = $shiftVars['flagDay'];
                            $fPer = $shiftVars['flagPeriod'];
                            $mDay = $shiftVars['meetDay'];
                            $mPer = $shiftVars['meetPeriod'];
                            // DRY: Tính 1 lần trước vòng lặp — không cần lặp 60 lần (6 ngày × 10 tiết)
                            $assignFlag    = $settings['assign_gvcn_flag_salute']   ?? 0;
                            $assignMeeting = $settings['assign_gvcn_class_meeting'] ?? 0;
                            $gvcnName      = $classroom->homeroom_teacher;
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
                                    $isFlagSalute   = ($d == $fDay && $p == $fPer);
                                    $isClassMeeting = ($d == $mDay && $p == $mPer);
                                    $isFixed        = $isFlagSalute || $isClassMeeting;
                                    $fixedLabel     = $isFlagSalute ? 'CHÀO CỜ' : 'SINH HOẠT';
                                    $current        = $schedules->where('day_of_week', $d)->where('period', $p)->where('assignment.class_id', $selectedClassId)->first();
                                    // $assignFlag, $assignMeeting, $gvcnName đã được khai báo bên ngoài vòng lặp
                                    $showGvcn       = ($isFlagSalute && $assignFlag) || ($isClassMeeting && $assignMeeting);

                                    $fixedBg     = $isFlagSalute ? 'bg-rose-50 border-rose-200'       : 'bg-emerald-50 border-emerald-200';
                                    $fixedText   = $isFlagSalute ? 'text-rose-600'                    : 'text-emerald-600';
                                    $fixedGvcnBg = $isFlagSalute ? 'bg-rose-100/80 text-rose-800'     : 'bg-emerald-100/80 text-emerald-800';
                                @endphp
                                
                                <div class="p-1.5 border-r last:border-r-0 border-slate-200 h-[85px] flex items-center justify-center relative bg-white">
                                    @if($isFixed)
                                        <div class="w-full h-full rounded-xl flex flex-col items-center justify-center border {{ $fixedBg }} pointer-events-none select-none relative overflow-hidden" 
                                             data-day="{{ $d }}" data-period="{{ $p }}">
                                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMjBMMjAgMEgxNkwwIDE2djRaTTIwIDE2djRMMTYgMjBMMjAgMTZ6IiBmaWxsPSIjZTFlNWU5IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=')] opacity-[0.03]"></div>
                                            <span class="relative z-10 text-[11px] font-black tracking-widest {{ $fixedText }}">{{ $fixedLabel }}</span>
                                            @if($showGvcn && !empty($gvcnName))
                                                <span class="relative z-10 text-[9px] font-bold mt-1 px-2 py-0.5 truncate max-w-[95%] rounded {{ $fixedGvcnBg }}" title="{{ $gvcnName }}">{{ $gvcnName }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="drop-zone w-full h-full rounded-xl flex items-center justify-center overflow-hidden transition-all border-2 border-dashed border-slate-200 hover:border-primary hover:bg-blue-50/20 cursor-pointer relative" 
                                             data-day="{{ $d }}" data-period="{{ $p }}">
                                            
                                            @if($current)
                                                <div class="matrix-item group relative w-full h-full rounded-xl flex flex-col items-center justify-center bg-primary/10 border-2 border-primary/20 cursor-move hover:border-primary/50 hover:shadow-md hover:shadow-primary/10 transition-all overflow-hidden" 
                                                     data-id="{{ $current->assignment_id }}"
                                                     data-teacher-id="{{ $current->assignment->teacher_id }}"
                                                     data-room-id="{{ $current->room_id }}"
                                                     data-room-type-id="{{ $current->assignment->subject->room_type_id }}"
                                                     data-off-days="{{ is_array($current->assignment->teacher->off_days) ? json_encode($current->assignment->teacher->off_days) : $current->assignment->teacher->off_days ?? '[]' }}">
                                                    
                                                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary shrink-0"></div>
                                                    
                                                    <div class="w-full flex flex-col items-center justify-center px-1 min-w-0 overflow-hidden">
                                                        <span class="text-[9px] font-black uppercase text-primary text-center leading-tight whitespace-normal break-words w-full block" title="{{ $current->assignment->subject->name }}">
                                                            {{ $current->assignment->subject->name }}
                                                        </span>
                                                        <span class="text-[8px] font-semibold text-slate-600 text-center leading-tight whitespace-normal break-words w-full block mt-0.5" title="{{ $current->assignment->teacher->name }}">
                                                            {{ $current->assignment->teacher->name }}
                                                        </span>
                                                        
                                                        @if($current->room_id)
                                                            <span class="text-[7px] font-bold text-orange-700 bg-orange-100 px-1 rounded mt-0.5 max-w-[95%] whitespace-normal break-words block room-tag" title="P: {{ $current->room->name }}">
                                                                P: {{ $current->room->name }}
                                                            </span>
                                                        @endif
                                                    </div>
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
            
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-500 font-medium shrink-0">
                <div class="flex items-center gap-5">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-slate-100 border border-slate-300"></span> Ô trống</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-rose-50 border border-rose-200"></span> Chào cờ</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-50 border border-emerald-200"></span> Sinh hoạt lớp</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-primary/20 border border-primary/40"></span> Đã xếp lịch</span>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const MAX_CONSECUTIVE = {{ $settings['max_consecutive_slots'] ?? 3 }};
    const MAX_DAYS_PER_WEEK = {{ $settings['max_days_per_week'] ?? 6 }};
    const CHECK_TEACHER_CONFLICT = {{ $settings['check_teacher_conflict'] ?? 0 }};
    const CHECK_ROOM_CONFLICT = {{ $settings['check_room_conflict'] ?? 0 }};
    
    const allRooms = @json($rooms ?? []);
    const teacherBusySlots = @json($teacherBusySlots ?? []);
    const teacherOtherDays = @json($teacherOtherDays ?? []);
    const roomBusySlots = @json($roomBusySlots ?? []);
    
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
            
            if(subjectSlots[asId] !== undefined) subjectSlots[asId]++;
            if(teacherSlots[tid] !== undefined) teacherSlots[tid]++;
            
            updateSidebarUI();
            this.remove();
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

    function getTeacherTotalDays(teacherId, targetDayToAdd) {
        let daysInMatrix = new Set();
        document.querySelectorAll(`.matrix-item[data-teacher-id="${teacherId}"]`).forEach(el => {
            let box = el.closest('.drop-zone');
            if (box) daysInMatrix.add(parseInt(box.dataset.day));
        });
        if (targetDayToAdd) daysInMatrix.add(parseInt(targetDayToAdd));

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
                data.push({ 
                    assignment_id: item.dataset.id, 
                    day_of_week: box.dataset.day, 
                    period: box.dataset.period,
                    room_id: item.dataset.roomId || null
                });
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
        }).catch(err => alert('Lỗi kết nối với máy chủ!'));
    }
</script>
@endsection