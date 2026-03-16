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
        <div x-show="activeGrade === {{ $grade }}" class="space-y-4 no-print">
            @php $classGroup = $groupedClasses->get($grade) ?? collect(); @endphp
            
            @forelse($classGroup as $class)
            <div class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:border-blue-300 transition-colors">
                
                <div @click="expandedClass = expandedClass === {{ $class->id }} ? null : {{ $class->id }}" 
                     class="p-5 flex justify-between items-center cursor-pointer bg-white hover:bg-blue-50/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center font-black text-lg">
                            {{ $class->name }}
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase">LỚP {{ $class->name }} (KHỐI {{ $class->grade }})</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-0.5">GVCN: <span class="text-blue-600">{{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1.5 rounded-xl uppercase border border-emerald-100">Đã xếp lịch</span>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expandedClass === {{ $class->id }} ? 'rotate-180' : ''">expand_more</span>
                    </div>
                </div>

                <div x-show="expandedClass === {{ $class->id }}" x-transition class="border-t border-slate-100 bg-[#f8f9fa] p-6 md:p-8">
                    <div class="flex justify-end gap-3 mb-6 no-print">
                        <button onclick="exportExcel('class-{{ $class->id }}', '{{ $class->name }}', '{{ $settings['school_year'] ?? '2024 - 2025' }}', '{{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}', 'class')" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-emerald-700 transition-all shadow-md shadow-emerald-500/30">
                            <span class="material-symbols-outlined text-sm">table_view</span> Tải Excel
                        </button>
                        <button onclick="exportNative('class-{{ $class->id }}', '{{ $class->name }}', 'class')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30">
                            <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                        </button>
                    </div>

                    <div id="pdf-content-class-{{ $class->id }}" class="print-area bg-white p-6 md:p-10 rounded-3xl shadow-sm border border-slate-200">
                        <div class="text-center mb-8 border-b-2 border-slate-800 pb-6 print-header">
                            <h2 class="text-sm font-black text-slate-600 uppercase">{{ $settings['school_name'] ?? 'TRƯỜNG CHƯA CÀI ĐẶT' }}</h2>
                            <h1 class="text-2xl font-black text-blue-700 uppercase tracking-widest mt-2">THỜI KHÓA BIỂU</h1>
                            <div class="flex justify-center gap-6 mt-3 text-xs font-bold text-slate-800 uppercase">
                                <p>Lớp: <span class="text-blue-700 text-sm">{{ $class->name }}</span></p>
                                <p>Năm học: {{ $settings['school_year'] ?? '2024 - 2025' }}</p>
                            </div>
                            <p class="text-xs font-bold text-slate-600 mt-2 uppercase">Giáo viên chủ nhiệm: <span class="text-blue-700">{{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}</span></p>
                        </div>

                        <table class="w-full border-collapse border-2 border-slate-800 print-table">
                            <thead>
                                <tr class="bg-slate-100">
                                    <th class="border border-slate-800 p-2 text-[11px] font-black uppercase w-16 text-center text-slate-700">Tiết</th>
                                    @for($d=2; $d<=7; $d++)
                                    <th class="border border-slate-800 p-3 text-[11px] font-black uppercase text-center w-[15%] text-slate-700">Thứ {{ $d }}</th>
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
                                    <tr>
                                        <td colspan="7" class="border-y-2 border-slate-800 bg-slate-50 p-2 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Nghỉ trưa / Chuyển ca</td>
                                    </tr>
                                    @endif

                                    <tr>
                                        <td class="border border-slate-400 p-2 text-center font-black text-slate-700 bg-slate-50">{{ $p }}</td>
                                        
                                        @for($d=2; $d<=7; $d++)
                                            @php
                                                $isFixed = ($d == $fDay && $p == $fPer) || ($d == $mDay && $p == $mPer);
                                                $fixedLabel = ($d == $fDay && $p == $fPer) ? 'CHÀO CỜ' : 'SINH HOẠT';
                                                $cell = $schedules->where('assignment.class_id', $class->id)->where('day_of_week', $d)->where('period', $p)->first();
                                            @endphp
                                            
                                            <td class="border border-slate-400 p-2 text-center h-[60px] align-middle {{ $isFixed ? 'bg-slate-100/50' : 'bg-white' }}">
                                                @if($isFixed)
                                                    <span class="text-[11px] font-black text-slate-500 tracking-widest">{{ $fixedLabel }}</span>
                                                @elseif($cell)
                                                    <div class="flex flex-col items-center justify-center">
                                                        <span class="text-[12px] font-black text-blue-700 uppercase leading-tight subject-txt">{{ $cell->assignment->subject->name }}</span>
                                                        <span class="text-[10px] font-bold text-slate-600 mt-1 teacher-txt">{{ $cell->assignment->teacher->name }}</span>
                                                        @if($cell->room_id)
                                                            <span class="text-[9px] text-orange-700 bg-orange-100 font-bold uppercase inline-block px-1.5 py-0.5 rounded mt-1 room-tag">P: {{ $cell->room->name }}</span>
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
            </div>
            @empty
            <div class="py-20 text-center bg-white rounded-[2rem] border border-slate-200">
                <span class="material-symbols-outlined text-6xl text-slate-200 mb-4">folder_off</span>
                <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Chưa có lớp nào trong khối này</p>
            </div>
            @endforelse
        </div>
        @endforeach
    </div>

    <div x-show="viewMode === 'teacher'" x-transition style="display: none;">
        
        <div class="mb-6 relative no-print">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
            <input x-model="searchTeacher" placeholder="Nhập tên giáo viên để tìm kiếm nhanh..." class="w-full bg-white border-slate-200 rounded-[2rem] pl-12 pr-6 py-4 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all font-bold text-slate-700">
        </div>

        <div class="space-y-4 no-print">
            @foreach($teachers as $teacher)
            @php
                $gvcnClasses = $classes->where('homeroom_teacher', $teacher->name);
            @endphp
            <div x-show="searchTeacher === '' || '{{ strtolower($teacher->name) }}'.includes(searchTeacher.toLowerCase())" class="bg-white rounded-[2rem] border border-slate-200 overflow-hidden shadow-sm hover:border-blue-300 transition-colors">
                
                <div @click="expandedTeacher = expandedTeacher === {{ $teacher->id }} ? null : {{ $teacher->id }}" 
                     class="p-5 flex justify-between items-center cursor-pointer bg-white hover:bg-blue-50/30 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center font-black text-lg">
                            <span class="material-symbols-outlined">person_play</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-800 uppercase">GV: {{ $teacher->name }}</h3>
                            <p class="text-[11px] text-slate-500 font-bold mt-0.5">Mã GV: <span class="text-purple-600">{{ $teacher->code }}</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        @if($gvcnClasses->count() > 0)
                            <span class="text-[9px] font-black text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg uppercase border border-amber-100">Chủ nhiệm: {{ $gvcnClasses->pluck('name')->join(', ') }}</span>
                        @endif
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expandedTeacher === {{ $teacher->id }} ? 'rotate-180' : ''">expand_more</span>
                    </div>
                </div>

                <div x-show="expandedTeacher === {{ $teacher->id }}" x-transition class="border-t border-slate-100 bg-[#f8f9fa] p-6 md:p-8">
                    
                    <div class="flex justify-end gap-3 mb-6 no-print">
                        <button onclick="exportExcel('teacher-{{ $teacher->id }}', '{{ $teacher->name }}', '{{ $settings['school_year'] ?? '2024 - 2025' }}', '{{ $teacher->code }}', 'teacher')" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-emerald-700 transition-all shadow-md shadow-emerald-500/30">
                            <span class="material-symbols-outlined text-sm">table_view</span> Tải Excel
                        </button>
                        <button onclick="exportNative('teacher-{{ $teacher->id }}', '{{ $teacher->name }}', 'teacher')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30">
                            <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                        </button>
                    </div>

                    <div id="pdf-content-teacher-{{ $teacher->id }}" class="print-area bg-white p-6 md:p-10 rounded-3xl shadow-sm border border-slate-200">
                        <div class="text-center mb-8 border-b-2 border-slate-800 pb-6 print-header">
                            <h2 class="text-sm font-black text-slate-600 uppercase">{{ $settings['school_name'] ?? 'TRƯỜNG CHƯA CÀI ĐẶT' }}</h2>
                            <h1 class="text-2xl font-black text-purple-700 uppercase tracking-widest mt-2">LỊCH GIẢNG DẠY</h1>
                            <div class="flex justify-center gap-6 mt-3 text-xs font-bold text-slate-800 uppercase">
                                <p>Giáo viên: <span class="text-purple-700 text-sm">{{ $teacher->name }}</span></p>
                                <p>Năm học: {{ $settings['school_year'] ?? '2024 - 2025' }}</p>
                            </div>
                            <p class="text-xs font-bold text-slate-600 mt-2 uppercase">Mã Giáo viên: <span class="text-purple-700">{{ $teacher->code }}</span></p>
                        </div>

                        <table class="w-full border-collapse border-2 border-slate-800 print-table">
                            <thead>
                                <tr class="bg-slate-100">
                                    <th class="border border-slate-800 p-2 text-[11px] font-black uppercase w-16 text-center text-slate-700">Tiết</th>
                                    @for($d=2; $d<=7; $d++)
                                    <th class="border border-slate-800 p-3 text-[11px] font-black uppercase text-center w-[15%] text-slate-700">Thứ {{ $d }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $assignFlag = $settings['assign_gvcn_flag_salute'] ?? 0;
                                    $assignMeeting = $settings['assign_gvcn_class_meeting'] ?? 0;
                                @endphp

                                @for($p=1; $p<=10; $p++)
                                    @if($p == 6)
                                    <tr>
                                        <td colspan="7" class="border-y-2 border-slate-800 bg-slate-50 p-2 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">Nghỉ trưa / Chuyển ca</td>
                                    </tr>
                                    @endif

                                    <tr>
                                        <td class="border border-slate-400 p-2 text-center font-black text-slate-700 bg-slate-50">{{ $p }}</td>
                                        
                                        @for($d=2; $d<=7; $d++)
                                            @php
                                                $cell = $schedules->where('assignment.teacher_id', $teacher->id)->where('day_of_week', $d)->where('period', $p)->first();
                                                
                                                $gvcnSlot = null;
                                                if (!$cell && $gvcnClasses->count() > 0) {
                                                    foreach($gvcnClasses as $c) {
                                                        $sShift = strtolower($c->shift ?? 'morning');
                                                        $tfDay = $settings[$sShift.'_flag_day'] ?? 2;
                                                        $tfPer = $settings[$sShift.'_flag_period'] ?? ($sShift == 'morning' ? 1 : 10);
                                                        $tmDay = $settings[$sShift.'_meeting_day'] ?? 7;
                                                        $tmPer = $settings[$sShift.'_meeting_period'] ?? ($sShift == 'morning' ? 5 : 10);
                                                        
                                                        if ($assignFlag && $d == $tfDay && $p == $tfPer) {
                                                            $gvcnSlot = ['label' => 'CHÀO CỜ', 'class' => $c->name];
                                                            break;
                                                        }
                                                        if ($assignMeeting && $d == $tmDay && $p == $tmPer) {
                                                            $gvcnSlot = ['label' => 'SINH HOẠT', 'class' => $c->name];
                                                            break;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            
                                            <td class="border border-slate-400 p-2 text-center h-[60px] align-middle {{ $gvcnSlot ? 'bg-slate-100/50' : 'bg-white' }}">
                                                @if($cell)
                                                    <div class="flex flex-col items-center justify-center">
                                                        <span class="text-[12px] font-black text-purple-700 uppercase leading-tight subject-txt">{{ $cell->assignment->subject->name }}</span>
                                                        <span class="text-[10px] font-bold text-slate-600 mt-1 teacher-txt">Lớp: {{ $cell->assignment->classroom->name }}</span>
                                                        @if($cell->room_id)
                                                            <span class="text-[9px] text-orange-700 bg-orange-100 font-bold uppercase inline-block px-1.5 py-0.5 rounded mt-1 room-tag">P: {{ $cell->room->name }}</span>
                                                        @endif
                                                    </div>
                                                @elseif($gvcnSlot)
                                                    <div class="flex flex-col items-center justify-center">
                                                        <span class="text-[11px] font-black text-slate-500 tracking-widest">{{ $gvcnSlot['label'] }}</span>
                                                        <span class="text-[9px] font-bold text-slate-500 mt-1">Lớp: {{ $gvcnSlot['class'] }}</span>
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
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
    // Hàm In & PDF Dùng chung cho cả Lớp và Giáo Viên
    function exportNative(elementId, targetName, viewType) {
        const content = document.getElementById('pdf-content-' + elementId).innerHTML;
        const newWindow = window.open('', '_blank');
        const title = viewType === 'class' ? `TKB_Lop_${targetName}` : `TKB_GV_${targetName}`;

        newWindow.document.write(`
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    @page { size: A4 landscape; margin: 8mm; }
                    body { 
                        background: white; 
                        -webkit-print-color-adjust: exact !important; 
                        print-color-adjust: exact !important; 
                        font-family: ui-sans-serif, system-ui, sans-serif; 
                    }
                    .shadow-sm { box-shadow: none !important; }
                    .rounded-3xl { border-radius: 0 !important; }
                    .p-6, .md\\:p-10 { padding: 0 !important; border: none !important; }
                    .print-header { margin-bottom: 10px !important; padding-bottom: 10px !important; }
                    .print-header h2 { font-size: 12px !important; margin: 0 !important;}
                    .print-header h1 { font-size: 20px !important; margin: 5px 0 !important;}
                    .print-header p { margin: 0 !important; font-size: 11px !important;}
                    .print-table th, .print-table td { padding: 4px !important; }
                    .h-\\[60px\\] { height: 40px !important; }
                    table { page-break-inside: avoid; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                </style>
            </head>
            <body>
                ${content}
            </body>
            </html>
        `);

        newWindow.document.close();

        alert("💡 HƯỚNG DẪN:\n\n1. Màn hình IN sẽ hiện ra.\n2. Để tải PDF: Chọn máy in là 'Lưu dưới dạng PDF' (Save as PDF).\n3. Nhấn Lưu là xong, đảm bảo vừa khít 1 trang nét căng!");

        setTimeout(() => {
            newWindow.focus();
            newWindow.print();
            setTimeout(() => { newWindow.close(); }, 500);
        }, 1000);
    }

    // Hàm Xuất Excel Dùng chung cho cả Lớp và Giáo Viên
    function exportExcel(elementId, targetName, schoolYear, extraInfo, viewType) {
        let tableClone = document.querySelector('#pdf-content-' + elementId + ' table').cloneNode(true);
        
        tableClone.querySelectorAll('td, th').forEach(cell => {
            cell.style.border = '1px solid #000000';
            cell.style.padding = '5px';
            cell.style.textAlign = 'center';
            cell.style.verticalAlign = 'middle';
            
            let subject = cell.querySelector('.subject-txt');
            let teacherClass = cell.querySelector('.teacher-txt');
            let room = cell.querySelector('.room-tag');
            
            if (subject && teacherClass) {
                let htmlContent = `<strong>${subject.innerText}</strong><br style="mso-data-placement:same-cell;"/>${teacherClass.innerText}`;
                if(room) {
                    htmlContent += `<br style="mso-data-placement:same-cell;"/><span style="color:#c2410c;">${room.innerText}</span>`;
                }
                cell.innerHTML = htmlContent;
            } else {
                let text = cell.innerText.trim();
                if(text !== '') cell.innerHTML = `<strong>${text}</strong>`;
            }
        });

        let schoolName = "{{ $settings['school_name'] ?? 'TRƯỜNG CHƯA CÀI ĐẶT' }}";
        let mainTitle = viewType === 'class' ? `THỜI KHÓA BIỂU LỚP ${targetName}` : `THỜI KHÓA BIỂU GIẢNG DẠY - GV: ${targetName}`;
        let subTitle = viewType === 'class' ? `Giáo viên chủ nhiệm: ${extraInfo}` : `Mã giáo viên: ${extraInfo}`;

        let headerHtml = `
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr><td colspan="8" style="text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase;">${schoolName}</td></tr>
                <tr><td colspan="8" style="text-align: center; font-size: 22px; font-weight: bold; color: #1d4ed8;">${mainTitle}</td></tr>
                <tr><td colspan="8" style="text-align: center; font-size: 13px; font-style: italic;">Năm học: ${schoolYear}</td></tr>
                <tr><td colspan="8" style="text-align: left; font-size: 13px; font-weight: bold; color: #b91c1c;">${subTitle}</td></tr>
                <tr><td colspan="8"></td></tr>
            </table>
        `;

        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head><meta charset="UTF-8"></head>
        <body>${headerHtml}${tableClone.outerHTML}</body>
        </html>`;

        let base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
        let format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };

        let fileName = viewType === 'class' ? `TKB_Lop_${targetName}.xls` : `TKB_GV_${targetName}.xls`;
        let ctx = { worksheet: 'TKB' };
        let link = document.createElement("a");
        link.download = fileName;
        link.href = uri + base64(format(template, ctx));
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection