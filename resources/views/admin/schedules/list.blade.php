@extends('layouts.admin')

@section('title', 'Danh sách Thời khóa biểu')

@section('content')
{{-- Nạp thư viện hỗ trợ xuất PDF nếu cần (Hiện tại đang dùng giải pháp In trình duyệt tối ưu hơn) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    /**
     * Hàm hỗ trợ loại bỏ dấu Tiếng Việt để tìm kiếm không dấu hiệu quả.
     */
    function removeVietnameseTones(str) {
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a"); 
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e"); 
        str = str.replace(/ì|í|ị|ỉ|ĩ/g,"i"); 
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o"); 
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u"); 
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y"); 
        str = str.replace(/đ/g,"d");
        str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g,"A");
        str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g,"E");
        str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g,"I");
        str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g,"O");
        str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g,"U");
        str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g,"Y");
        str = str.replace(/Đ/g,"D");
        return str.toLowerCase().trim();
    }
</script>

{{-- Container chính sử dụng Alpine.js để quản lý trạng thái giao diện --}}
<div x-data="{ 
    viewMode: 'class',           // Chế độ xem: 'class' (Lớp) hoặc 'teacher' (Giáo viên)
    activeGrade: 10,             // Khối lớp đang hiển thị (10, 11, 12)
    activeBlock: 'all',          // Tổ hợp môn (KHTN, KHXH...)
    expandedClass: null,         // Lớp đang được mở rộng chi tiết
    expandedTeacher: null,       // Giáo viên đang được mở rộng chi tiết
    searchTeacher: '',           // Từ khóa tìm kiếm giáo viên
    matchTeacher(name) {         // Hàm filter giáo viên theo tên (không dấu)
        if (!this.searchTeacher) return true;
        let s = removeVietnameseTones(this.searchTeacher);
        let n = removeVietnameseTones(name);
        return n.includes(s);
    }
}" class="space-y-6 max-w-7xl mx-auto">
    
    {{-- PHẦN 1: BỘ CHỌN PHIÊN BẢN (VERSION PICKER) --}}
    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm mb-6 no-print">
        <form action="{{ route('schedules.list') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Chọn phiên bản TKB</label>
                <div class="relative">
                    <select name="date" onchange="this.form.submit()" 
                            class="w-full pl-4 pr-10 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring focus:ring-blue-200 outline-none bg-white text-sm font-bold text-slate-700 cursor-pointer appearance-none bg-none">
                        @foreach($historyRanges as $range)
                            @php 
                                $rangeStart = $range->applies_from->toDateString();
                                $isSelected = ($appliesFrom == $rangeStart);
                            @endphp
                            <option value="{{ $rangeStart }}" {{ $isSelected ? 'selected' : '' }}>
                                Phiên bản: {{ $range->applies_from->format('d/m/Y') }} - {{ $range->applies_to->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                </div>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Xem ngày cụ thể</label>
                <input type="date" name="lookup_date" value="{{ request('lookup_date', $appliesFrom) }}" onchange="this.form.submit()"
                       class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring focus:ring-blue-200 outline-none text-sm font-bold text-slate-700">
            </div>

            <div class="bg-blue-50 border border-blue-100 px-6 py-3 rounded-xl flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-600">event_available</span>
                <div>
                    <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest leading-none mb-1">Đang hiển thị tuần</p>
                    <p class="text-xs font-bold text-blue-700">{{ \Illuminate\Support\Carbon::parse($appliesFrom)->format('d/m/Y') }} - {{ \Illuminate\Support\Carbon::parse($appliesTo)->format('d/m/Y') }}</p>
                </div>
            </div>
        </form>
    </div>

    {{-- PHẦN 2: CHỌN CHẾ ĐỘ XEM (CLASS VS TEACHER) --}}
    <div class="flex gap-4 mb-6 no-print">
        <button @click="viewMode = 'class'" :class="viewMode === 'class' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-500 hover:bg-slate-50'" class="px-8 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all border border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">meeting_room</span> Xem theo Lớp Học
        </button>
        <button @click="viewMode = 'teacher'" :class="viewMode === 'teacher' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-500 hover:bg-slate-50'" class="px-8 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all border border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">person</span> Xem theo Giáo Viên
        </button>
    </div>

    {{-- PHẦN 3: HIỂN THỊ THEO LỚP HỌC --}}
    <div x-show="viewMode === 'class'" x-transition>
        {{-- Bộ lọc Khối lớp --}}
        <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2 shadow-sm no-print mb-6">
            @foreach([10, 11, 12] as $grade)
            <button @click="activeGrade = {{ $grade }}; expandedClass = null" 
                    :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
                Khối lớp {{ $grade }}
            </button>
            @endforeach
        </div>

        {{-- Bộ lọc Tổ hợp môn (Khối A, B...) --}}
        <div class="flex flex-wrap gap-2 mb-6 no-print">
            @foreach(['all' => 'Tất cả Lớp', 'KHTN' => 'Tổ hợp KHTN', 'KHXH' => 'Tổ hợp KHXH', 'Cơ bản' => 'Cơ bản'] as $key => $label)
            <button @click="activeBlock = '{{ $key }}'" 
                    :class="activeBlock === '{{ $key }}' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                    class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
                {{ $label }}
            </button>
            @endforeach
        </div>

        @foreach([10, 11, 12] as $grade)
        <div x-show="activeGrade === {{ $grade }}" class="space-y-4">
            
            {{-- Tiêu đề khối & Nút xuất file hàng loạt --}}
            <div class="flex justify-between items-end px-6 mb-2 no-print">
                <h2 class="text-xl font-black text-slate-800 uppercase tracking-tight">Khối {{ $grade }}</h2>
                <div class="flex gap-2">
                    <button onclick="exportGradeWord({{ $grade }})" class="bg-blue-50 text-blue-700 border border-blue-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-100 transition-all">
                        <span class="material-symbols-outlined text-sm">description</span> Xuất Word Khối {{ $grade }}
                    </button>
                    <button onclick="exportGradeNative({{ $grade }})" class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-indigo-100 transition-all">
                        <span class="material-symbols-outlined text-sm">print</span> In Khối {{ $grade }} (PDF)
                    </button>
                </div>
            </div>

            {{-- Danh sách các lớp trong khối --}}
            @php $classGroup = $groupedClasses->get($grade) ?? collect(); @endphp
            @forelse($classGroup as $class)
            <div x-show="activeBlock === 'all' || activeBlock === '{{ $class->block_name }}'" class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:border-blue-300 transition-colors">
                
                {{-- Header lớp (Click để mở rộng TKB) --}}
                <div @click="expandedClass = expandedClass === {{ $class->id }} ? null : {{ $class->id }}" 
                     class="p-5 flex justify-between items-center cursor-pointer bg-white hover:bg-blue-50/30 transition-colors no-print">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center font-black text-lg">{{ $class->name }}</div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase">LỚP {{ $class->name }} ({{ $class->block_name }})</h3>
                            <p class="text-[11px] text-slate-500 font-bold">GVCN: <span class="teacher-name-data">{{ $class->homeroomTeacher?->name ?? 'Chưa cập nhật' }}</span></p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 transition-transform" :class="expandedClass === {{ $class->id }} ? 'rotate-180' : ''">expand_more</span>
                </div>

                {{-- Nút điều khiển xuất file cho từng lớp --}}
                <div x-show="expandedClass === {{ $class->id }}" x-transition class="border-t border-slate-100 bg-[#f8f9fa] p-6 no-print">
                    <div class="flex justify-end gap-3 mb-6">
                        <button onclick="exportWord({{ $class->id }}, '{{ $class->name }}')" class="bg-blue-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-800 shadow-lg shadow-blue-500/20">
                            <span class="material-symbols-outlined text-sm">article</span> Tải File Word
                        </button>
                        <button onclick="exportNative('pdf-content-class-{{ $class->id }}')" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-black shadow-lg">
                            <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                        </button>
                    </div>
                </div>

                {{-- VÙNG HIỂN THỊ TKB (Sẽ được dùng để in/xuất file) --}}
                <div id="pdf-content-class-{{ $class->id }}" class="grade-{{ $grade }}-content bg-white p-6 md:p-10" :class="expandedClass === {{ $class->id }} ? '' : 'hidden print:block'">
                    {{-- Header khi in (ẩn trên web, hiện khi xuất file) --}}
                    <div class="print-header-pdf" style="text-align: center; border-bottom: 1.5px solid black; padding-bottom: 8px; margin-bottom: 12px; display: none;">
                        <h2 style="font-size: 13px; font-weight: 900; color: #4b5563; margin: 0; text-transform: uppercase;">{{ $settings['school_name'] ?? '' }}</h2>
                        <h1 style="font-size: 28px; font-weight: 900; color: #1d4ed8; margin: 2px 0; text-transform: uppercase;">THỜI KHÓA BIỂU</h1>
                        <p style="font-size: 13px; font-weight: 900; color: #1f2937; margin: 4px 0;">
                            LỚP: <span class="class-name-data">{{ $class->name }}</span> ({{ $class->block_name }}) 
                            @if(isset($appliesFrom) && isset($appliesTo))
                                | ÁP DỤNG: {{ \Carbon\Carbon::parse($appliesFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($appliesTo)->format('d/m/Y') }}
                            @endif
                            | NĂM HỌC: {{ $settings['school_year'] ?? '2024 - 2025' }}
                        </p>
                        <p style="font-size: 13px; font-weight: 900; color: #4b5563; margin: 0; text-transform: uppercase;">GVCN: {{ $class->homeroomTeacher?->name ?? 'Chưa cập nhật' }}</p>
                    </div>

                    {{-- Bảng thời khóa biểu --}}
                    <table style="width: 100%; border-collapse: collapse; border: 2px solid black; table-layout: fixed;">
                        <thead>
                            <tr class="bg-slate-100" style="background-color: #f1f5f9;">
                                <th style="border: 1px solid black; padding: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase; width: 35px; text-align: center;">T</th>
                                @for($d=2; $d<=7; $d++)
                                <th style="border: 1px solid black; padding: 4px; font-size: 10px; font-weight: 900; text-transform: uppercase; text-align: center; width: 16%;">Thứ {{ $d }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $shiftStr = strtolower($class->shift ?? 'morning');
                                $fDay = $settings[$shiftStr.'_flag_day'] ?? 2;
                                $fPer = $settings[$shiftStr.'_flag_period'] ?? ($shiftStr == 'morning' ? 1 : 10);
                                $mDay = $settings[$shiftStr.'_meeting_day'] ?? 7;
                                $mPer = $settings[$shiftStr.'_meeting_period'] ?? ($shiftStr == 'morning' ? 5 : 10);
                            @endphp
                            @for($p=1; $p<=10; $p++)
                                @if($p == 6)
                                <tr style="height: 20px;"><td colspan="7" style="border: 1px solid black; background-color: #f8f9fa; text-align: center; font-size: 10px; font-weight: 900; text-transform: uppercase; font-style: italic;">Nghỉ trưa / Chuyển ca</td></tr>
                                @endif
                                <tr style="height: 48px;">
                                    <td style="border: 1px solid black; text-align: center; font-weight: 900; font-size: 11px; background-color: #f8f9fa;">{{ $p }}</td>
                                    @for($d=2; $d<=7; $d++)
                                        @php
                                            $isFixed = ($d == $fDay && $p == $fPer) || ($d == $mDay && $p == $mPer);
                                            $fixedLabel = ($d == $fDay && $p == $fPer) ? 'CHÀO CỜ' : 'SINH HOẠT';
                                            $cell = $classSchedules[$class->id][$d][$p] ?? null;
                                        @endphp
                                        <td style="border: 1px solid black; text-align: center; vertical-align: middle; background-color: {{ $isFixed ? '#f8f9fa' : '#ffffff' }}; height: 46px; overflow: hidden; padding: 1px;">
                                            @if($isFixed)
                                                <span style="font-size: 11px; font-weight: 900; color: #6b7280; line-height: 1;">{{ $fixedLabel }}</span>
                                            @elseif($cell)
                                                @php
                                                    $sName = $cell->assignment->subject->name;
                                                    // Viết tắt tên môn cho gọn khi in
                                                    $abAbbreviations = [
                                                        'Giáo dục thể chất' => 'GD Thể chất', 'Giáo dục quốc phòng và an ninh' => 'GDQP-AN',
                                                        'GD Kinh tế và Pháp luật' => 'GDKT-PL', 'Hoạt động trải nghiệm, hướng nghiệp' => 'HĐTN-HN',
                                                        'Nội dung giáo dục địa phương' => 'GD Địa phương', 'Tiếng Anh' => 'T.Anh'
                                                    ];
                                                    $displayName = $abAbbreviations[$sName] ?? $sName;
                                                    
                                                    $tName = $cell->assignment->teacher->name;
                                                    $tParts = explode(' ', $tName);
                                                    $tShort = count($tParts) > 1 ? end($tParts) : $tName; 
                                                @endphp
                                                <div style="line-height: 1.1; padding: 1px; height: 46px; display: block; overflow: hidden;">
                                                    <div style="font-size: 12px; font-weight: 900; color: #1e40af; text-transform: uppercase; display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin-bottom: 1px;">{{ $displayName }}</div>
                                                    <div style="font-size: 10px; font-weight: 700; color: #4b5563; margin-bottom: 0px;">GV: {{ $tShort }}</div>
                                                    @if($cell->room_id)
                                                        <div style="font-size: 10px; font-weight: 900; color: #c2410c;">P: {{ $cell->room->name }}</div>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
                <div class="page-break"></div>
            </div>
            @empty
            <div class="py-10 text-center bg-white rounded-[2rem] border border-slate-200 no-print">
                <p class="text-slate-400 font-bold uppercase text-[10px]">Chưa có dữ liệu lớp học</p>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>
    
    {{-- PHẦN 4: HIỂN THỊ THEO GIÁO VIÊN --}}
    <div x-show="viewMode === 'teacher'" x-transition x-cloak>
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm mb-6 no-print">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" x-model="searchTeacher" placeholder="Tìm kiếm giáo viên (Tên, Bộ môn)..." 
                       class="w-full pl-12 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring focus:ring-blue-200 outline-none transition-all text-sm font-bold text-slate-700">
            </div>
        </div>

        <div class="flex justify-end mb-6 no-print">
            <div class="flex gap-2">
                <button onclick="exportAllTeachersWord()" class="bg-blue-50 text-blue-700 border border-blue-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-100 transition-all">
                    <span class="material-symbols-outlined text-sm">description</span> Xuất Word Tất cả GV
                </button>
                <button onclick="exportAllTeachersNative()" class="bg-indigo-50 text-indigo-700 border border-indigo-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-indigo-100 transition-all">
                    <span class="material-symbols-outlined text-sm">print</span> In Tất cả GV (PDF)
                </button>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($teachers as $teacher)
            <div x-show="matchTeacher('{{ $teacher->name }}')" 
                 class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:border-blue-300 transition-colors">
                
                <div @click="expandedTeacher = expandedTeacher === {{ $teacher->id }} ? null : {{ $teacher->id }}" 
                     class="p-5 flex justify-between items-center cursor-pointer bg-white hover:bg-blue-50/30 transition-colors no-print">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center font-black text-lg">
                            <span class="material-symbols-outlined">person</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase">{{ $teacher->name }}</h3>
                            <p class="text-[11px] text-slate-500 font-bold">Bộ môn: {{ $teacher->subject->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 transition-transform" :class="expandedTeacher === {{ $teacher->id }} ? 'rotate-180' : ''">expand_more</span>
                </div>

                {{-- Nút điều khiển xuất file cho từng giáo viên --}}
                <div x-show="expandedTeacher === {{ $teacher->id }}" x-transition class="border-t border-slate-100 bg-[#f8f9fa] p-6 no-print">
                    <div class="flex justify-end gap-3 mb-6">
                        <button onclick="exportTeacherWord({{ $teacher->id }}, '{{ $teacher->name }}')" class="bg-blue-700 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-800 shadow-lg shadow-blue-500/20">
                            <span class="material-symbols-outlined text-sm">article</span> Tải File Word
                        </button>
                        <button onclick="exportNative('pdf-content-teacher-{{ $teacher->id }}')" class="bg-slate-800 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-black shadow-lg">
                            <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                        </button>
                    </div>
                </div>

                <div id="pdf-content-teacher-{{ $teacher->id }}" class="teacher-content bg-white p-6 md:p-10" :class="expandedTeacher === {{ $teacher->id }} ? '' : 'hidden print:block'">
                    {{-- Header khi in cho giáo viên --}}
                    <div class="print-header-pdf" style="text-align: center; border-bottom: 1.5px solid black; padding-bottom: 8px; margin-bottom: 12px; display: none;">
                        <h2 style="font-size: 13px; font-weight: 900; color: #4b5563; margin: 0; text-transform: uppercase;">{{ $settings['school_name'] ?? '' }}</h2>
                        <h1 style="font-size: 28px; font-weight: 900; color: #1d4ed8; margin: 2px 0; text-transform: uppercase;">THỜI KHÓA BIỂU GIÁO VIÊN</h1>
                        <p style="font-size: 13px; font-weight: 900; color: #1f2937; margin: 4px 0;">
                            HỌ TÊN: <span class="teacher-name-data uppercase font-black">{{ $teacher->name }}</span> 
                            @if(isset($appliesFrom) && isset($appliesTo))
                                | ÁP DỤNG: {{ \Carbon\Carbon::parse($appliesFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($appliesTo)->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>

                    {{-- Bảng TKB giáo viên --}}
                    <table style="width: 100%; border-collapse: collapse; border: 2px solid black; table-layout: fixed;">
                        <thead>
                            <tr class="bg-slate-100" style="background-color: #f1f5f9;">
                                <th style="border: 1px solid black; padding: 4px; font-size: 11px; font-weight: 900; text-transform: uppercase; width: 35px; text-align: center;">T</th>
                                @for($d=2; $d<=7; $d++)
                                <th style="border: 1px solid black; padding: 4px; font-size: 11px; font-weight: 900; text-transform: uppercase; text-align: center; width: 16%;">Thứ {{ $d }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $homeroomClass = $homeroomMap->get($teacher->id);
                                if($homeroomClass) {
                                    $sh = strtolower($homeroomClass->shift ?? 'morning');
                                    $fDay = $settings[$sh.'_flag_day'] ?? 2;
                                    $fPer = $settings[$sh.'_flag_period'] ?? ($sh == 'morning' ? 1 : 10);
                                    $mDay = $settings[$sh.'_meeting_day'] ?? 7;
                                    $mPer = $settings[$sh.'_meeting_period'] ?? ($sh == 'morning' ? 5 : 10);
                                } else { $fDay = -1; $fPer = -1; $mDay = -1; $mPer = -1; }
                            @endphp

                            @for($p=1; $p<=10; $p++)
                                @if($p == 6)
                                <tr style="height: 20px;"><td colspan="7" style="border: 1px solid black; background-color: #f8f9fa; text-align: center; font-size: 10px; font-weight: 900; text-transform: uppercase; font-style: italic;">Nghỉ trưa</td></tr>
                                @endif
                                <tr style="height: 48px;">
                                    <td style="border: 1px solid black; text-align: center; font-weight: 900; font-size: 11px; background-color: #f8f9fa;">{{ $p }}</td>
                                    @for($d=2; $d<=7; $d++)
                                    @php
                                        $isFixed = ($d == $fDay && $p == $fPer) || ($d == $mDay && $p == $mPer);
                                        $fixedLabel = ($d == $fDay && $p == $fPer) ? 'CHÀO CỜ' : 'SINH HOẠT';
                                        $cell = $teacherSchedules[$teacher->id][$d][$p] ?? null;
                                    @endphp
                                    <td style="border: 1px solid black; text-align: center; vertical-align: middle; background-color: {{ $isFixed ? '#f8f9fa' : '#ffffff' }}; height: 46px; overflow: hidden; padding: 1px;">
                                        @if($isFixed && $homeroomClass)
                                            <div style="line-height: 1.1;">
                                                <div style="font-size: 11px; font-weight: 900; color: #6b7280;">{{ $fixedLabel }}</div>
                                                <div style="font-size: 10px; font-weight: 700; color: #4b5563;">Lớp {{ $homeroomClass->name }}</div>
                                            </div>
                                        @elseif($cell)
                                            <div style="line-height: 1.1; padding: 1px; height: 46px; display: block; overflow: hidden;">
                                                <div style="font-size: 12px; font-weight: 900; color: #1e40af; text-transform: uppercase; margin-bottom: 1px;">Lớp {{ $cell->assignment->classroom->name }}</div>
                                                <div style="font-size: 10px; font-weight: 700; color: #4b5563;">{{ $cell->assignment->subject->name }}</div>
                                                @if($cell->room_id)
                                                    <div style="font-size: 10px; font-weight: 900; color: #c2410c;">P: {{ $cell->room->name }}</div>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- LOGIC XUẤT FILE (WORD & NATIVE PRINT) --}}
<script>
    /**
     * Xuất Word cho từng giáo viên
     */
    function exportTeacherWord(teacherId, teacherName) {
        const element = document.getElementById(`pdf-content-teacher-${teacherId}`);
        if (!element) return alert("Không tìm thấy nội dung!");
        
        const content = element.innerHTML;
        const finalHtml = generateWordTemplate(content);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `TKB_GiaoVien_${teacherName}.doc`;
        link.click();
    }

    /**
     * Xuất Word hàng loạt cho tất cả giáo viên
     */
    function exportAllTeachersWord() {
        const container = document.createElement('div');
        document.querySelectorAll(`.teacher-content`).forEach((el, index) => {
            let clone = el.cloneNode(true);
            const header = clone.querySelector('.print-header-pdf');
            if (header) header.style.display = 'block';
            
            container.innerHTML += `
                <div class="word-page">${clone.innerHTML}</div>
                ${index < document.querySelectorAll(`.teacher-content`).length - 1 ? '<br clear="all" style="mso-special-character:line-break; page-break-before:always">' : ''}
            `;
        });

        const finalHtml = generateWordTemplate(container.innerHTML);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `TKB_TatCa_GiaoVien.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * In hàng loạt tất cả giáo viên (Mở cửa sổ in của trình duyệt)
     */
    function exportAllTeachersNative() {
        const classContents = document.querySelectorAll(`.teacher-content`);
        if (classContents.length === 0) return alert("Không tìm thấy dữ liệu giáo viên.");

        const newWindow = window.open('', '_blank');
        let fullHtml = "";
        classContents.forEach((el, index) => {
            let clone = el.cloneNode(true);
            const header = clone.querySelector('.print-header-pdf');
            if (header) header.style.display = 'block';
            fullHtml += `<div style="${index < classContents.length - 1 ? 'page-break-after: always;' : ''} padding: 5mm; background:white;">${clone.innerHTML}</div>`;
        });

        newWindow.document.write(`
            <html><head><meta charset="UTF-8"><title>In Tất Cả Giáo Viên</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <style>@page { size: A4 landscape; margin: 5mm; } body { background: white; -webkit-print-color-adjust: exact !important; font-family: 'Segoe UI', sans-serif; }</style>
            </head><body>${fullHtml}</body></html>
        `);
        newWindow.document.close();
        setTimeout(() => { newWindow.focus(); newWindow.print(); newWindow.close(); }, 1200);
    }

    /**
     * Xuất Word cho một lớp cụ thể
     */
    function exportWord(classId, className) {
        const element = document.getElementById(`pdf-content-class-${classId}`);
        if (!element) return alert("Không tìm thấy nội dung!");
        
        let clone = element.cloneNode(true);
        const header = clone.querySelector('.print-header-pdf');
        if (header) header.style.display = 'block';

        const finalHtml = generateWordTemplate(clone.innerHTML);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `TKB_Lop_${className.replace(/\s+/g, '_')}.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * Xuất Word cho toàn bộ các lớp thuộc một khối
     */
    function exportGradeWord(grade) {
        const classContents = document.querySelectorAll(`.grade-${grade}-content`);
        if (classContents.length === 0) return alert("Không tìm thấy dữ liệu khối " + grade);

        let combinedHtml = "";
        classContents.forEach((el, index) => {
            let clone = el.cloneNode(true);
            const header = clone.querySelector('.print-header-pdf');
            if (header) header.style.display = 'block';
            combinedHtml += `<div class="word-page">${clone.innerHTML}</div>${index < classContents.length - 1 ? '<br clear="all" style="mso-special-character:line-break; page-break-before:always">' : ''}`;
        });

        const finalHtml = generateWordTemplate(combinedHtml);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `TKB_Khoi_${grade}.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * Template HTML chuẩn để MS Word nhận diện định dạng (Bao gồm hướng giấy ngang - Landscape)
     */
    function generateWordTemplate(content) {
        return `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><meta charset="utf-8"><style>
                @page WordSection1 { size: 841.9pt 595.3pt; mso-page-orientation: landscape; margin: 0.5in 0.5in 0.5in 0.5in; }
                div.WordSection1 { page: WordSection1; }
                table { border-collapse: collapse; width: 100%; border: 1.5pt solid black !important; table-layout: fixed; }
                th, td { border: 1.2pt solid black !important; padding: 2px; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; vertical-align: middle; }
            </style></head>
            <body style="font-family: 'Segoe UI', sans-serif;"><div class="WordSection1" style="width: 100%; margin: 0 auto; text-align: center;">${content}</div></body></html>
        `;
    }

    /**
     * In một phần nội dung cụ thể bằng hộp thoại in trình duyệt
     */
    function exportNative(elementId) {
        const element = document.getElementById(elementId);
        let clone = element.cloneNode(true);
        const header = clone.querySelector('.print-header-pdf');
        if (header) header.style.display = 'block';

        const newWindow = window.open('', '_blank');
        newWindow.document.write(`
            <html><head><meta charset="UTF-8"><title>In Thời Khóa Biểu</title>
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    @page { size: A4 landscape; margin: 5mm !important; }
                    body { background: white; -webkit-print-color-adjust: exact !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
                    table { border-collapse: collapse; width: 100%; border: 1.5pt solid black !important; table-layout: fixed; }
                    th, td { border: 1.2pt solid black !important; padding: 2px; text-align: center; vertical-align: middle; }
                </style>
            </head><body><div style="width: 100%; max-width: 297mm; margin: 0 auto;">${clone.innerHTML}</div></body></html>
        `);
        newWindow.document.close();
        setTimeout(() => { newWindow.focus(); newWindow.print(); newWindow.close(); }, 1000);
    }

    /**
     * In hàng loạt tất cả các lớp trong một khối
     */
    function exportGradeNative(grade) {
        const classContents = document.querySelectorAll(`.grade-${grade}-content`);
        if (classContents.length === 0) return alert("Không tìm thấy dữ liệu khối " + grade);

        const newWindow = window.open('', '_blank');
        let fullHtml = "";
        classContents.forEach((el, index) => {
            let clone = el.cloneNode(true);
            const header = clone.querySelector('.print-header-pdf');
            if (header) header.style.display = 'block';
            fullHtml += `<div style="${index < classContents.length - 1 ? 'page-break-after: always;' : ''} padding: 5mm; background:white;">${clone.innerHTML}</div>`;
        });

        newWindow.document.write(`
            <html><head><meta charset="UTF-8"><title>In Khối ${grade}</title>
            <script src="https://cdn.tailwindcss.com"><\/script>
            <style>@page { size: A4 landscape; margin: 5mm; } body { background: white; -webkit-print-color-adjust: exact !important; font-family: 'Segoe UI', sans-serif; } .print-table th, .print-table td { border: 1px solid black !important; }</style>
            </head><body>${fullHtml}</body></html>
        `);
        newWindow.document.close();
        setTimeout(() => { newWindow.focus(); newWindow.print(); newWindow.close(); }, 1200);
    }
</script>
@endsection