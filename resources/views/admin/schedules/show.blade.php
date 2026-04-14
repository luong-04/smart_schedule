@extends('layouts.admin')
@section('title', 'TKB Lớp ' . $classroom->name)

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="space-y-6 max-w-[1100px] mx-auto">
    <div class="bg-white p-4 rounded-[2rem] border border-slate-200 shadow-sm flex justify-between items-center no-print">
        <a href="{{ route('schedules.list') }}" class="flex items-center gap-2 text-slate-500 font-bold text-xs uppercase hover:text-blue-600 bg-slate-50 px-5 py-3 rounded-xl transition-colors">
            <span class="material-symbols-outlined text-sm">arrow_back</span> Quay lại danh sách
        </a>
        
        <div class="flex gap-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-6 py-3 rounded-xl text-[11px] font-black uppercase flex items-center gap-2 hover:bg-slate-900 transition-all shadow-md">
                <span class="material-symbols-outlined text-sm">print</span> In Nhanh
            </button>
            <button onclick="exportSingleWord()" class="bg-indigo-600 text-white px-6 py-3 rounded-xl text-[11px] font-black uppercase flex items-center gap-2 hover:bg-indigo-700 transition-all shadow-md">
                <span class="material-symbols-outlined text-sm">article</span> Tải File Word
            </button>
            <button onclick="downloadPDF()" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-[11px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md">
                <span class="material-symbols-outlined text-sm">download</span> Tải PDF
            </button>
        </div>
    </div>

    <div id="pdf-content" class="bg-white p-10 rounded-2xl shadow-sm border border-slate-200">
        
        <div class="text-center mb-8 relative border-b border-slate-200 pb-6">
            <div class="absolute left-0 top-0 text-left">
                <p class="text-[11px] font-black text-slate-800 uppercase">{{ $settings['school_name'] ?? 'TRƯỜNG CHƯA CÀI ĐẶT' }}</p>
                <p class="text-[10px] font-bold text-slate-500 mt-1">Năm học: {{ $settings['school_year'] ?? '2024-2025' }}</p>
            </div>
            
            <h1 class="text-xl font-black text-slate-800 uppercase tracking-widest">THỜI KHÓA BIỂU LỚP {{ $classroom->name }}</h1>
            <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">Khối {{ $classroom->grade }} ({{ $classroom->block ?? 'Cơ bản' }}) • {{ $classroom->shift == 'morning' ? 'Ca Sáng' : 'Ca Chiều' }} • GVCN: {{ $classroom->homeroom_teacher ?? 'Chưa phân công' }}</p>
        </div>

        <table class="w-full text-center border-collapse">
            <thead>
                <tr>
                    <th class="border border-slate-300 bg-slate-100 p-3 text-[11px] font-black text-slate-700 uppercase w-20">Tiết</th>
                    @for($d=2; $d<=7; $d++)
                        <th class="border border-slate-300 bg-slate-100 p-3 text-[11px] font-black text-slate-700 uppercase w-[15%]">Thứ {{ $d }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @php
                    $shiftStr = strtolower($classroom->shift ?? 'morning');
                    $fDay = $settings[$shiftStr.'_flag_day'] ?? 2;
                    $fPer = $settings[$shiftStr.'_flag_period'] ?? ($shiftStr == 'morning' ? 1 : 10);
                    $mDay = $settings[$shiftStr.'_meeting_day'] ?? 7;
                    $mPer = $settings[$shiftStr.'_meeting_period'] ?? ($shiftStr == 'morning' ? 5 : 10);
                @endphp

                @for($p=1; $p<=10; $p++)
                    @if($p == 6)
                        <tr>
                            <td colspan="7" class="border border-slate-300 bg-slate-50 p-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                                Nghỉ trưa / Đổi ca
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="border border-slate-300 bg-slate-50 p-3 text-[11px] font-black text-slate-600">Tiết {{ $p }}</td>
                        @for($d=2; $d<=7; $d++)
                            @php
                                $isFlagSalute = ($d == $fDay && $p == $fPer);
                                $isClassMeeting = ($d == $mDay && $p == $mPer);
                                $current = $schedules->where('day_of_week', $d)->where('period', $p)->first();
                            @endphp

                            <td class="border border-slate-300 p-3 h-20 relative align-middle">
                                @if($isFlagSalute)
                                    <span class="text-xs font-black text-rose-600 uppercase tracking-widest block">CHÀO CỜ</span>
                                    @if($classroom->homeroom_teacher && ($settings['assign_gvcn_flag_salute'] ?? 0))
                                        <span class="text-[9px] font-bold text-rose-500 mt-1 block">{{ $classroom->homeroom_teacher }}</span>
                                    @endif
                                @elseif($isClassMeeting)
                                    <span class="text-xs font-black text-emerald-600 uppercase tracking-widest block">SINH HOẠT</span>
                                    @if($classroom->homeroom_teacher && ($settings['assign_gvcn_class_meeting'] ?? 0))
                                        <span class="text-[9px] font-bold text-emerald-500 mt-1 block">{{ $classroom->homeroom_teacher }}</span>
                                    @endif
                                @elseif($current)
                                    <span class="text-[11px] font-black text-blue-700 uppercase block mb-1">{{ $current->assignment->subject->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-600 block">{{ $current->assignment->teacher->name }}</span>
                                    @if($current->room_id)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 bg-orange-100 text-orange-700 text-[8px] font-bold rounded">P: {{ $current->room->name }}</span>
                                    @endif
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="mt-8 flex justify-between items-end px-10">
            <div class="text-center">
                <p class="text-xs font-black text-slate-600 uppercase">Hiệu Phó Chuyên Môn</p>
                <p class="text-sm font-bold text-slate-800 mt-16">{{ $settings['vice_principal_name'] ?? '...............................' }}</p>
            </div>
            <div>
                <p class="text-xs font-black text-slate-600 uppercase">Hiệu Trưởng</p>
                <p class="text-sm font-bold text-slate-800 mt-16">{{ $settings['principal_name'] ?? '...............................' }}</p>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .no-print { display: none !important; }
        #pdf-content { box-shadow: none !important; margin: 0 !important; width: 100% !important; border: none !important; }
    }
</style>

@extends('layouts.admin')
@section('title', 'Danh sách Thời khóa biểu')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div x-data="{ viewMode: 'class', activeGrade: 10, expandedClass: null, expandedTeacher: null, searchTeacher: '' }" class="space-y-6 max-w-7xl mx-auto">
    
    <div class="flex gap-4 mb-6 no-print">
        <button @click="viewMode = 'class'" :class="viewMode === 'class' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-500 hover:bg-slate-50'" class="px-8 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all border border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">meeting_room</span> Xem theo Lớp Học
        </button>
        <button @click="viewMode = 'teacher'" :class="viewMode === 'teacher' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-500 hover:bg-slate-50'" class="px-8 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all border border-slate-100 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">person</span> Xem theo Giáo Viên
        </button>
    </div>

    <div x-show="viewMode === 'class'" x-transition>
        <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2 shadow-sm no-print mb-6">
            @foreach([10, 11, 12] as $grade)
            <button @click="activeGrade = {{ $grade }}; expandedClass = null" 
                    :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
                Khối lớp {{ $grade }}
            </button>
            @endforeach
        </div>

        @foreach([10, 11, 12] as $grade)
        <div x-show="activeGrade === {{ $grade }}" class="space-y-4">
            
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

            @php $classGroup = $groupedClasses->get($grade) ?? collect(); @endphp
            @forelse($classGroup as $class)
            <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:border-blue-300 transition-colors">
                
                <div @click="expandedClass = expandedClass === {{ $class->id }} ? null : {{ $class->id }}" 
                     class="p-5 flex justify-between items-center cursor-pointer bg-white hover:bg-blue-50/30 transition-colors no-print">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center font-black text-lg">{{ $class->name }}</div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase">LỚP {{ $class->name }} ({{ $class->block ?? 'Cơ bản' }})</h3>
                            <p class="text-[11px] text-slate-500 font-bold">GVCN: <span class="teacher-name-data">{{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}</span></p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-slate-400 transition-transform" :class="expandedClass === {{ $class->id }} ? 'rotate-180' : ''">expand_more</span>
                </div>

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

                <div id="pdf-content-class-{{ $class->id }}" class="grade-{{ $grade }}-content bg-white p-6 md:p-10" :class="expandedClass === {{ $class->id }} ? '' : 'hidden print:block'">
                    <div class="text-center mb-6 border-b-2 border-slate-800 pb-4 print-header-pdf" style="text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px;">
                        <h2 style="font-size: 14px; font-weight: 900; color: #4b5563; margin: 0; text-transform: uppercase;">{{ $settings['school_name'] ?? 'TRƯỜNG CHƯA CÀI ĐẶT' }}</h2>
                        <h1 style="font-size: 24px; font-weight: 900; color: #1d4ed8; margin: 5px 0; text-transform: uppercase;">THỜI KHÓA BIỂU</h1>
                        <p style="font-size: 12px; font-weight: 700; color: #1f2937; margin: 5px 0;">
                            LỚP: <span class="class-name-data">{{ $class->name }}</span> ({{ $class->block ?? 'Cơ bản' }}) | NĂM HỌC: {{ $settings['school_year'] ?? '2024 - 2025' }}
                        </p>
                        <p style="font-size: 12px; font-weight: 700; color: #4b5563; margin: 0; text-transform: uppercase;">GVCN: {{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}</p>
                    </div>

                    <table class="w-full border-collapse border-2 border-black print-table" style="width: 100%; border-collapse: collapse; border: 2px solid black;">
                        <thead>
                            <tr class="bg-slate-100" style="background-color: #f1f5f9;">
                                <th style="border: 1px solid black; padding: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; width: 60px; text-align: center;">Tiết</th>
                                @for($d=2; $d<=7; $d++)
                                <th style="border: 1px solid black; padding: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; text-align: center;">Thứ {{ $d }}</th>
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
                                <tr style="height: 25px;"><td colspan="7" style="border: 1px solid black; background-color: #f8f9fa; text-align: center; font-size: 11px; font-weight: 900; text-transform: uppercase; font-style: italic;">Nghỉ trưa / Chuyển ca</td></tr>
                                @endif
                                <tr style="height: 50px;">
                                    <td style="border: 1px solid black; text-align: center; font-weight: 900; font-size: 13px; background-color: #f8f9fa;">{{ $p }}</td>
                                    @for($d=2; $d<=7; $d++)
                                        @php
                                            $isFixed = ($d == $fDay && $p == $fPer) || ($d == $mDay && $p == $mPer);
                                            $fixedLabel = ($d == $fDay && $p == $fPer) ? 'CHÀO CỜ' : 'SINH HOẠT';
                                            $cell = $schedules->where('assignment.class_id', $class->id)->where('day_of_week', $d)->where('period', $p)->first();
                                        @endphp
                                        <td style="border: 1px solid black; text-align: center; vertical-align: middle; background-color: {{ $isFixed ? '#f8f9fa' : '#ffffff' }};">
                                            @if($isFixed)
                                                <span style="font-size: 11px; font-weight: 900; color: #6b7280;">{{ $fixedLabel }}</span>
                                            @elseif($cell)
                                                <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                                    <span style="font-size: 12px; font-weight: 900; color: #1e40af; text-transform: uppercase;">{{ $cell->assignment->subject->name }}</span>
                                                    <span style="font-size: 10px; font-weight: 700; color: #4b5563;">{{ $cell->assignment->teacher->name }}</span>
                                                    @if($cell->room_id)
                                                        <span style="font-size: 9px; font-weight: 900; color: #c2410c;">P: {{ $cell->room->name }}</span>
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
            @empty
            <div class="py-10 text-center bg-white rounded-[2rem] border border-slate-200 no-print">
                <p class="text-slate-400 font-bold uppercase text-[10px]">Chưa có dữ liệu lớp học</p>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>
</div>

<script>
    // --- FIX LỖI TẢI WORD ---
    function exportWord(classId, className) {
        const element = document.getElementById(`pdf-content-class-${classId}`);
        if (!element) return alert("Không tìm thấy nội dung!");
        
        const content = element.innerHTML;
        const finalHtml = generateWordTemplate(content);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `TKB_Lop_${className}.doc`;
        link.click();
    }

    function exportGradeWord(grade) {
        const classContents = document.querySelectorAll(`.grade-${grade}-content`);
        if (classContents.length === 0) return alert("Không tìm thấy dữ liệu khối " + grade);

        let combinedHtml = "";
        classContents.forEach((el, index) => {
            combinedHtml += `
                <div class="word-page">${el.innerHTML}</div>
                ${index < classContents.length - 1 ? '<br clear="all" style="mso-special-character:line-break; page-break-before:always">' : ''}
            `;
        });

        const finalHtml = generateWordTemplate(combinedHtml);
        const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `TKB_Khoi_${grade}.doc`;
        link.click();
    }

    function generateWordTemplate(content) {
        return `
            <html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
            <head><meta charset="utf-8"><style>
                @page WordSection1 { size: 841.9pt 595.3pt; mso-page-orientation: landscape; margin: 0.5in 0.5in 0.5in 0.5in; }
                div.WordSection1 { page: WordSection1; }
                table { border-collapse: collapse; width: 100%; border: 1pt solid black !important; }
                th, td { border: 1pt solid black !important; padding: 5px; text-align: center; font-family: 'Arial', sans-serif; }
            </style></head>
            <body><div class="WordSection1">${content}</div></body></html>
        `;
    }

    // --- FIX LỖI TRANG TRẮNG KHI IN ---
    function exportNative(elementId) {
        const content = document.getElementById(elementId).innerHTML;
        const newWindow = window.open('', '_blank');
        newWindow.document.write(`
            <html>
            <head>
                <meta charset="UTF-8">
                <title>In Thời Khóa Biểu</title>
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    @page { size: A4 landscape; margin: 0; }
                    body { background: white; -webkit-print-color-adjust: exact !important; padding: 10mm; font-family: sans-serif; }
                    .print-table th, .print-table td { border: 1px solid black !important; }
                </style>
            </head>
            <body>${content}</body>
            </html>
        `);
        newWindow.document.close();
        
        // Chờ 1 giây để CSS kịp nhận diện rồi mới mở hộp thoại in
        setTimeout(() => {
            newWindow.focus();
            newWindow.print();
            newWindow.close();
        }, 1000);
    }

    function exportGradeNative(grade) {
        const classContents = document.querySelectorAll(`.grade-${grade}-content`);
        if (classContents.length === 0) return alert("Không tìm thấy dữ liệu khối " + grade);

        const newWindow = window.open('', '_blank');
        let fullHtml = "";
        classContents.forEach((el, index) => {
            fullHtml += `<div style="${index < classContents.length - 1 ? 'page-break-after: always;' : ''} padding: 5mm; background:white;">${el.innerHTML}</div>`;
        });

        newWindow.document.write(`
            <html>
            <head>
                <meta charset="UTF-8">
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    @page { size: A4 landscape; margin: 0; }
                    body { background: white; -webkit-print-color-adjust: exact !important; font-family: sans-serif; }
                    .print-table th, .print-table td { border: 1px solid black !important; }
                    .hidden { display: block !important; }
                </style>
            </head>
            <body>${fullHtml}</body>
            </html>
        `);
        newWindow.document.close();
        setTimeout(() => {
            newWindow.focus();
            newWindow.print();
            newWindow.close();
        }, 1200);
    }
</script>
@endsection
@endsection