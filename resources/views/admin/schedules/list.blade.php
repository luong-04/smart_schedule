@extends('layouts.admin')
@section('title', 'Danh sách Thời khóa biểu')

@section('content')
<div x-data="{ activeGrade: 10, expandedClass: null }" class="space-y-6 max-w-7xl mx-auto">
    
    <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2 shadow-sm no-print">
        @foreach([10, 11, 12] as $grade)
        <button @click="activeGrade = {{ $grade }}; expandedClass = null" 
                :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                class="flex-1 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
            Khối lớp {{ $grade }}
        </button>
        @endforeach
    </div>

    @foreach([10, 11, 12] as $grade)
    <div x-show="activeGrade === {{ $grade }}" x-transition class="space-y-4 no-print">
        @php $classes = $groupedClasses->get($grade) ?? collect(); @endphp
        
        @forelse($classes as $class)
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
                    <button onclick="exportExcel('{{ $class->id }}', '{{ $class->name }}', '{{ $settings['school_year'] ?? '2024 - 2025' }}', '{{ $class->homeroom_teacher ?? 'Chưa cập nhật' }}')" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-emerald-700 transition-all shadow-md shadow-emerald-500/30">
                        <span class="material-symbols-outlined text-sm">table_view</span> Tải Excel
                    </button>
                    
                    <button onclick="exportNative('{{ $class->id }}', '{{ $class->name }}')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30">
                        <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                    </button>
                </div>

                <div id="pdf-content-{{ $class->id }}" class="print-area bg-white p-6 md:p-10 rounded-3xl shadow-sm border border-slate-200">
                    
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
                                        
                                        <td class="border border-slate-400 p-2 text-center h-[70px] align-middle {{ $isFixed ? 'bg-slate-100/50' : 'bg-white' }}">
                                            @if($isFixed)
                                                <span class="text-[11px] font-black text-slate-500 tracking-widest">{{ $fixedLabel }}</span>
                                            @elseif($cell)
                                                <div class="flex flex-col items-center justify-center">
                                                    <span class="text-[12px] font-black text-blue-700 uppercase leading-tight subject-txt">{{ $cell->assignment->subject->name }}</span>
                                                    <span class="text-[10px] font-bold text-slate-600 mt-1 teacher-txt">{{ $cell->assignment->teacher->name }}</span>
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

<script>
    // ==========================================
    // 1. CHỨC NĂNG IN VÀ TẢI PDF (MỘT TRANG A4)
    // ==========================================
    function exportNative(classId, className) {
        const content = document.getElementById('pdf-content-' + classId).innerHTML;
        const newWindow = window.open('', '_blank');

        newWindow.document.write(`
            <!DOCTYPE html>
            <html lang="vi">
            <head>
                <meta charset="UTF-8">
                <title>TKB_Lop_${className}</title>
                <script src="https://cdn.tailwindcss.com"><\/script>
                <style>
                    /* Ép lề siêu mỏng và khổ A4 Nằm ngang */
                    @page { size: A4 landscape; margin: 8mm; }
                    
                    /* Ép buộc in nền màu (không bị trắng bảng) */
                    body { 
                        background: white; 
                        -webkit-print-color-adjust: exact !important; 
                        print-color-adjust: exact !important; 
                        font-family: ui-sans-serif, system-ui, sans-serif; 
                    }
                    
                    /* Tắt đổ bóng và bo viền thừa để thu gọn diện tích */
                    .shadow-sm { box-shadow: none !important; }
                    .rounded-3xl { border-radius: 0 !important; }
                    .p-6, .md\\:p-10 { padding: 0 !important; border: none !important; }
                    
                    /* ÉP NHỎ CÁC KHOẢNG CÁCH ĐỂ VỪA 1 TRANG */
                    .print-header { margin-bottom: 10px !important; padding-bottom: 10px !important; }
                    .print-header h2 { font-size: 12px !important; margin: 0 !important;}
                    .print-header h1 { font-size: 20px !important; margin: 5px 0 !important;}
                    .print-header p { margin: 0 !important; font-size: 11px !important;}
                    
                    /* Ép bảng nhỏ lại */
                    .print-table th, .print-table td { padding: 4px !important; }
                    .h-\\[70px\\] { height: 45px !important; } /* Cứu tinh: Giảm chiều cao ô */
                    .text-\\[12px\\] { font-size: 11px !important; }
                    .text-\\[10px\\] { font-size: 10px !important; }
                    
                    /* Tránh bảng bị ngắt giữa chừng */
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

        // Mẹo: Cảnh báo hướng dẫn lưu PDF
        alert("💡 HƯỚNG DẪN:\n\n1. Màn hình IN sẽ hiện ra.\n2. Để tải PDF: Chọn máy in là 'Lưu dưới dạng PDF' (Save as PDF).\n3. Nhấn Lưu là xong, đảm bảo vừa khít 1 trang nét căng!");

        setTimeout(() => {
            newWindow.focus();
            newWindow.print();
            setTimeout(() => { newWindow.close(); }, 500);
        }, 1000);
    }

    // ==========================================
    // 2. CHỨC NĂNG XUẤT RA EXCEL (ĐÃ BỔ SUNG GVCN)
    // ==========================================
    function exportExcel(classId, className, schoolYear, gvcn) {
        // Copy bảng TKB gốc để không làm hỏng giao diện web
        let tableClone = document.querySelector('#pdf-content-' + classId + ' table').cloneNode(true);
        
        // Dọn dẹp lại bảng HTML cho Excel đọc chuẩn
        tableClone.querySelectorAll('td, th').forEach(cell => {
            cell.style.border = '1px solid #000000';
            cell.style.padding = '5px';
            cell.style.textAlign = 'center';
            cell.style.verticalAlign = 'middle';
            
            // Xử lý lấy text môn và text GV ghép lại, cách nhau bằng <br> mso
            let subject = cell.querySelector('.subject-txt');
            let teacher = cell.querySelector('.teacher-txt');
            
            if (subject && teacher) {
                // Lệnh br style mso này giúp Excel hiểu đây là 1 ô nhưng rớt 2 dòng
                cell.innerHTML = `<strong>${subject.innerText}</strong><br style="mso-data-placement:same-cell;"/>${teacher.innerText}`;
            } else {
                // Các ô cố định / nghỉ trưa
                let text = cell.innerText.trim();
                if(text !== '') cell.innerHTML = `<strong>${text}</strong>`;
            }
        });

        // Chuẩn bị Header của file Excel (Đã bao gồm GVCN và Năm học)
        let schoolName = document.querySelector('#pdf-content-' + classId + ' h2').innerText;
        let headerHtml = `
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr><td colspan="8" style="text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase;">${schoolName}</td></tr>
                <tr><td colspan="8" style="text-align: center; font-size: 22px; font-weight: bold; color: #1d4ed8;">THỜI KHÓA BIỂU LỚP ${className}</td></tr>
                <tr><td colspan="8" style="text-align: center; font-size: 13px; font-style: italic;">Năm học: ${schoolYear}</td></tr>
                <tr><td colspan="8" style="text-align: left; font-size: 13px; font-weight: bold; color: #b91c1c;">Giáo viên chủ nhiệm: ${gvcn}</td></tr>
                <tr><td colspan="8"></td></tr> </table>
        `;

        // Format định dạng XLS mso
        let uri = 'data:application/vnd.ms-excel;base64,';
        let template = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            </head>
        <body>
            ${headerHtml}
            ${tableClone.outerHTML}
        </body>
        </html>`;

        let base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) };
        let format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) };

        let ctx = { worksheet: 'Lop_' + className };
        
        // Kích hoạt Tải xuống
        let link = document.createElement("a");
        link.download = "TKB_Lop_" + className + ".xls";
        link.href = uri + base64(format(template, ctx));
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endsection