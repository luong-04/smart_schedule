@extends('layouts.admin')
@section('title', 'Ma trận Xếp lịch')
@section('body_attrs', 'hx-boost="false" hx-history="false"')

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
    
    /* Real-time Conflict Highlighting */
    .conflict-zone { 
        background-color: #fef2f2 !important; 
        border: 2px solid #ef4444 !important; 
        border-style: solid !important;
        position: relative;
    }
    .conflict-zone::after {
        content: "BẬN";
        position: absolute;
        top: 2px;
        right: 4px;
        font-size: 8px;
        font-weight: 900;
        color: #ef4444;
        letter-spacing: 1px;
    }

    /* Prevent text selection during drag to avoid "bôi đen" issue */
    .sortable-drag * {
        user-select: none !important;
        -webkit-user-select: none !important;
    }
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
<div id="saving-overlay" class="fixed inset-0 bg-slate-900/40 z-[300] flex items-center justify-center hidden backdrop-blur-[2px]">
    <div class="bg-white rounded-3xl p-8 shadow-2xl flex flex-col items-center gap-4 animate-in fade-in zoom-in duration-300">
        <div class="size-16 border-4 border-slate-100 border-t-primary rounded-full animate-spin"></div>
        <p class="text-xs font-black text-slate-800 uppercase tracking-[0.2em]">Đang lưu dữ liệu...</p>
    </div>
</div>

<div id="success-toast" class="fixed top-10 left-1/2 -translate-x-1/2 z-[400] transform -translate-y-20 transition-all duration-500 hidden">
    <div id="toast-container" class="bg-emerald-500 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-3 min-w-[300px] justify-center">
        <span id="toast-icon" class="material-symbols-outlined animate-bounce">check_circle</span>
        <span id="toast-message" class="font-black uppercase tracking-widest text-xs">Lưu bản cập nhật thành công!</span>
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
                            Lớp {{ $class->name }} - Khối {{ $class->grade }} ({{ $class->block_name }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center bg-slate-100/50 rounded-xl px-3 py-1.5 border border-slate-200">
                <span class="material-symbols-outlined text-slate-400 text-sm mr-2">calendar_month</span>
                <input type="date" id="applies_from" value="{{ $appliesFrom }}" class="bg-transparent border-none p-0 text-[10px] font-black text-slate-700 focus:ring-0 uppercase">
                <span class="mx-2 text-slate-400 text-[10px] fon-black">→</span>
                <input type="date" id="applies_to" value="{{ $appliesTo }}" class="bg-transparent border-none p-0 text-[10px] font-black text-slate-700 focus:ring-0 uppercase">
            </div>

            @if($historyRanges->count() > 0)
            <div class="relative group">
                <select onchange="window.location.href='?class_id={{ $selectedClassId }}&date='+this.value" class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-[10px] font-black text-slate-500 uppercase tracking-widest focus:ring-primary focus:border-primary cursor-pointer outline-none hover:bg-slate-50 transition-all appearance-none pr-10 shadow-sm">
                    <option value="">Lịch sử phiên bản</option>
                    @foreach($historyRanges as $range)
                        <option value="{{ $range->applies_from->toDateString() }}" {{ $appliesFrom == $range->applies_from->toDateString() ? 'selected' : '' }}>
                            {{ $range->applies_from->format('d/m/Y') }} - {{ $range->applies_to->format('d/m/Y') }}
                        </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">history</span>
            </div>
            @endif

            <button onclick="saveSchedule()" hx-boost="false" class="bg-primary text-white px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">save</span> Lưu Phiên bản
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden bg-white rounded-b-[2rem] shadow-sm border border-t-0 border-slate-200" hx-disable>
        
        <section class="w-[30%] border-r border-slate-200 bg-[#f8f9fa] flex flex-col shrink-0" hx-disable>
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
                     data-subject-name="{{ $as->subject->name }}"
                     data-teacher-name="{{ $as->teacher->name }}"
                     data-room-type-id="{{ $as->subject->room_type_id }}"
                     data-subject-type="{{ $as->subject->type }}"
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
                        <span class="slot-badge text-[10px] font-black {{ $as->actual_remaining <= 0 ? 'text-rose-500 bg-rose-50 px-2 py-1 rounded-lg border border-rose-100' : 'text-emerald-500' }}">
                            @if($as->actual_remaining > 0)
                                {{ sprintf("%02d", $as->actual_remaining) }}
                            @else
                                @if($as->bottleneck == 'teacher') HẾT ĐM GV
                                @elseif($as->bottleneck == 'subject') HẾT TIẾT MÔN
                                @else HẾT TIẾT
                                @endif
                            @endif
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

        <section class="flex-1 flex flex-col bg-white overflow-hidden" id="matrix-section" hx-disable>
            <div class="flex-1 overflow-auto p-6 scroll-smooth">
                @php
                    $isClassMorning = strtolower($classroom->shift) === 'morning';
                @endphp
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
                            // Check shift
                            $isClassMorning = strtolower($classroom->shift ?? 'morning') === 'morning';

                            // DRY: Các biến này được tính 1 lần ở Controller, không cần tính lại ở đây
                            $fDay = $shiftVars['flagDay'];
                            $fPer = $shiftVars['flagPeriod'];
                            $mDay = $shiftVars['meetDay'];
                            $mPer = $shiftVars['meetPeriod'];
                            // DRY: Tính 1 lần trước vòng lặp — không cần lặp 60 lần (6 ngày × 10 tiết)
                            $assignFlag    = $settings['assign_gvcn_flag_salute']   ?? 0;
                            $assignMeeting = $settings['assign_gvcn_class_meeting'] ?? 0;
                            $gvcnName      = $classroom->homeroomTeacher?->name ?? '';
                        @endphp

                        @for($p=1; $p<=10; $p++)
                        @if($p == 6)
                        <div class="schedule-grid bg-slate-100/80 border-y border-slate-200 relative overflow-hidden">
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMjBMMjAgMEgxNkwwIDE2djRaTTIwIDE2djRMMTYgMjBMMjAgMTZ6IiBmaWxsPSIjZTFlNWU5IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiLz48L3N2Zz4=')] opacity-[0.1]"></div>
                            <div class="p-2 border-r border-slate-200 relative z-10"></div>
                            <div class="col-span-6 flex items-center justify-center h-8 relative z-10">
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] bg-white/80 px-4 rounded-full shadow-sm">Nghỉ trưa / Đổi ca</span>
                            </div>
                        </div>
                        @endif

                        @php
                            $isCurrentShift = ($isClassMorning && $p <= 5) || (!$isClassMorning && $p >= 6);
                            $rowOpacity = $isCurrentShift ? 'bg-white' : 'bg-slate-50/50 opacity-60';
                        @endphp

                        <div class="schedule-grid group hover:bg-slate-50/50 transition-colors {{ $rowOpacity }}">
                            <div class="p-2 flex flex-col items-center justify-center border-r border-slate-200 {{ $isCurrentShift ? 'bg-white' : 'bg-slate-100/50' }} group-hover:bg-slate-50 transition-colors">
                                <span class="text-[10px] font-black {{ $isCurrentShift ? 'text-primary' : 'text-slate-400' }}">TIẾT {{ $p }}</span>
                            </div>
                            
                            @for($d=2; $d<=7; $d++)
                                @php
                                    $isFlagSalute   = ($d == $fDay && $p == $fPer);
                                    $isClassMeeting = ($d == $mDay && $p == $mPer);
                                    $isFixed        = $isFlagSalute || $isClassMeeting;
                                    $fixedLabel     = $isFlagSalute ? 'CHÀO CỜ' : 'SINH HOẠT';
                                    
                                    // O(1) Lookup - Toàn bộ N+1 và loop in loop đã được triệt tiêu ở Controller
                                    $current = $schedules["{$d}-{$p}"] ?? null;
                                    
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
                                                     data-subject-type="{{ $current->assignment->subject->type }}"
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
    window.ScheduleData = {
        maxConsecutive: {{ $settings['max_consecutive_slots'] ?? 3 }},
        maxDaysPerWeek: {{ $settings['max_days_per_week'] ?? 6 }},
        checkTeacherConflict: {{ $settings['check_teacher_conflict'] ?? 0 }},
        checkRoomConflict: {{ $settings['check_room_conflict'] ?? 0 }},
        allRooms: @json($rooms ?? []),
        teacherBusySlots: @json($teacherBusySlots ?? []),
        teacherOtherDays: @json($teacherOtherDays ?? []),
        roomBusySlots: @json($roomBusySlots ?? []),
        selectedClassId: {{ $selectedClassId }},
        appliesFrom: "{{ $appliesFrom }}",
        appliesTo: "{{ $appliesTo }}",
        lastUpdatedAt: "{{ $classroom->updated_at->toDateTimeString() }}",
        saveUrl: "{{ route('admin.schedules.save') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
@vite('resources/js/admin/schedule-matrix.js')
@endsection