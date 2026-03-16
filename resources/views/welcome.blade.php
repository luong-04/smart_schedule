@php
    use App\Models\Setting;
    use App\Models\Classroom;
    use App\Models\Teacher;
    use App\Models\Schedule;

    // Lấy thông tin cấu hình từ Admin
    $schoolName = Setting::getVal('school_name', 'TRƯỜNG CHƯA CÀI ĐẶT');
    $schoolYear = Setting::getVal('school_year', '2024 - 2025');
    $principal = Setting::getVal('principal_name', 'Đang cập nhật');
    
    // Thuật toán tra cứu thông minh (Tự nhận diện Lớp hoặc Giáo viên)
    $searchQuery = request('q');
    $classroom = null;
    $teacher = null;
    $schedules = collect();
    $gvcnClasses = collect();

    $assignFlag = Setting::getVal('assign_gvcn_flag_salute', 0);
    $assignMeeting = Setting::getVal('assign_gvcn_class_meeting', 0);

    if ($searchQuery) {
        // 1. Thử tìm kiếm theo Tên Lớp trước
        $classroom = Classroom::where('name', $searchQuery)->first();
        
        if ($classroom) {
            $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
                ->whereHas('assignment', function($query) use ($classroom) {
                    $query->where('class_id', $classroom->id);
                })
                ->with(['assignment.subject', 'assignment.teacher', 'room'])
                ->get();
                
            $shiftStr = strtolower($classroom->shift ?? 'morning');
            $fDay = Setting::getVal($shiftStr.'_flag_day', 2);
            $fPer = Setting::getVal($shiftStr.'_flag_period', ($shiftStr == 'morning' ? 1 : 10));
            $mDay = Setting::getVal($shiftStr.'_meeting_day', 7);
            $mPer = Setting::getVal($shiftStr.'_meeting_period', ($shiftStr == 'morning' ? 5 : 10));
            
            $gvcnName = $classroom->homeroom_teacher;
        } else {
            // 2. Nếu không phải Lớp, thử tìm theo Mã Giáo viên hoặc Tên Giáo viên
            $teacher = Teacher::where('code', $searchQuery)
                              ->orWhere('name', 'LIKE', '%' . $searchQuery . '%')
                              ->first();
                              
            if ($teacher) {
                $schedules = Schedule::where('schedule_name', 'Học kỳ 1')
                    ->whereHas('assignment', function($query) use ($teacher) {
                        $query->where('teacher_id', $teacher->id);
                    })
                    ->with(['assignment.subject', 'assignment.classroom', 'room'])
                    ->get();
                
                // Tìm xem giáo viên này đang làm chủ nhiệm lớp nào để hiển thị tiết Chào cờ/Sinh hoạt
                $gvcnClasses = Classroom::where('homeroom_teacher', $teacher->name)->get();
            }
        }
    }
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu TKB - {{ $schoolName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>body { font-family: 'Lexend', sans-serif; }</style>
</head>
<body class="bg-[#F0F7FF] min-h-screen flex flex-col">
    <header class="p-6 flex justify-between items-center bg-white shadow-sm border-b border-blue-50">
        <div>
            <h1 class="text-2xl font-black text-blue-700 uppercase tracking-tight">{{ $schoolName }}</h1>
            <p class="text-xs font-bold text-slate-400 uppercase mt-1">Niên khóa: {{ $schoolYear }}</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="bg-slate-800 text-white px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest shadow-lg shadow-slate-200 hover:bg-slate-900 transition-all flex items-center gap-2">
            Đăng nhập Quản lý
        </a>
    </header>

    <main class="max-w-6xl mx-auto pt-10 md:pt-16 px-4 flex-1 w-full">
        <div class="text-center mb-10">
            <h2 class="text-3xl md:text-4xl font-black text-slate-800 mb-6">Tra cứu Thời khóa biểu</h2>
            
            <form action="{{ url('/') }}" method="GET" class="relative max-w-2xl mx-auto group">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nhập tên lớp (10A1) hoặc Mã GV (GV01)..." 
                    class="w-full p-5 md:p-6 pl-8 pr-32 md:pr-40 bg-white rounded-[2rem] shadow-xl shadow-blue-100/50 border border-blue-50 focus:border-blue-300 focus:ring-4 focus:ring-blue-100 text-base md:text-lg font-bold text-slate-700 transition-all outline-none">
                <button type="submit" class="absolute right-3 top-3 bottom-3 bg-blue-600 text-white px-6 md:px-8 rounded-[1.5rem] text-sm font-black uppercase tracking-widest hover:bg-blue-700 transition-colors shadow-md shadow-blue-200 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg hidden md:block">search</span> Tra cứu
                </button>
            </form>
        </div>

        @if(request()->has('q'))
            @if($classroom || $teacher)
                
                @php
                    // Xác định đang xem dạng Lớp hay dạng Giáo viên để đổ ID ra HTML
                    $viewType = $classroom ? 'class' : 'teacher';
                    $targetId = $classroom ? $classroom->id : $teacher->id;
                    $targetName = $classroom ? $classroom->name : $teacher->name;
                    $targetExtra = $classroom ? ($gvcnName ?? 'Chưa cập nhật') : $teacher->code;
                @endphp

                <div class="flex justify-end gap-3 mb-4 max-w-5xl mx-auto no-print">
                    <button onclick="exportExcel('{{ $viewType }}-{{ $targetId }}', '{{ $targetName }}', '{{ $schoolYear }}', '{{ $targetExtra }}', '{{ $viewType }}')" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-emerald-700 transition-all shadow-md shadow-emerald-500/30">
                        <span class="material-symbols-outlined text-sm">table_view</span> Tải Excel
                    </button>
                    <button onclick="exportNative('{{ $viewType }}-{{ $targetId }}', '{{ $targetName }}', '{{ $viewType }}')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30">
                        <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                    </button>
                </div>

                <div id="pdf-content-{{ $viewType }}-{{ $targetId }}" class="print-area bg-white rounded-[2rem] shadow-sm p-6 md:p-8 border border-blue-50 max-w-5xl mx-auto">
                     
                     <div class="text-center mb-6 border-b-2 border-slate-800 pb-5 print-header">
                        <h2 class="text-sm font-black text-slate-600 uppercase">{{ $schoolName }}</h2>
                        <h1 class="text-xl md:text-2xl font-black text-blue-700 uppercase tracking-widest mt-1.5">THỜI KHÓA BIỂU {{ $teacher ? 'GIẢNG DẠY' : '' }}</h1>
                        
                        <div class="flex justify-center gap-5 mt-2 text-[11px] font-bold text-slate-800 uppercase">
                            <p>{{ $classroom ? 'Lớp:' : 'Giáo viên:' }} <span class="text-blue-700 text-xs">{{ $targetName }}</span></p>
                            <p>Năm học: {{ $schoolYear }}</p>
                        </div>
                        <p class="text-[10px] font-bold text-slate-600 mt-1.5 uppercase">
                            {{ $classroom ? 'Giáo viên chủ nhiệm:' : 'Mã giáo viên:' }} 
                            <span class="text-blue-700">{{ $targetExtra }}</span>
                        </p>
                    </div>
                     
                     <table class="w-full border-collapse border-2 border-slate-800 print-table">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-800 p-2 w-12 md:w-16 text-center text-[10px] font-black text-slate-600 uppercase">Tiết</th>
                                @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'] as $thu)
                                    <th class="border border-slate-800 p-2 text-center text-[11px] font-black text-slate-700 uppercase w-[15%]">{{ $thu }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for($p=1; $p<=10; $p++)
                                @if($p == 6)
                                <tr>
                                    <td colspan="7" class="border-y-2 border-slate-800 bg-slate-50 p-1.5 text-center text-[9px] font-black text-slate-500 uppercase tracking-[0.3em]">Nghỉ trưa / Đổi ca</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="border border-slate-300 p-1.5 text-center font-black text-slate-600 bg-slate-50/50 text-[11px]">{{ $p }}</td>
                                    
                                    @for($d=2; $d<=7; $d++)
                                        @php
                                            $cell = $schedules->where('day_of_week', $d)->where('period', $p)->first();
                                            
                                            // Xử lý riêng cho LỚP HỌC
                                            $isFixedClass = false;
                                            $fixedLabelClass = '';
                                            $showGvcnClass = false;

                                            if ($classroom) {
                                                $isFlagSalute = ($d == $fDay && $p == $fPer);
                                                $isClassMeeting = ($d == $mDay && $p == $mPer);
                                                $isFixedClass = $isFlagSalute || $isClassMeeting;
                                                $fixedLabelClass = $isFlagSalute ? 'CHÀO CỜ' : 'SINH HOẠT';
                                                $showGvcnClass = ($isFlagSalute && $assignFlag) || ($isClassMeeting && $assignMeeting);
                                            }

                                            // Xử lý riêng cho GIÁO VIÊN (Tìm tiết Cố định của các lớp họ làm GVCN)
                                            $gvcnSlotTeacher = null;
                                            if ($teacher && !$cell && $gvcnClasses->count() > 0) {
                                                foreach($gvcnClasses as $c) {
                                                    $sShift = strtolower($c->shift ?? 'morning');
                                                    $tfDay = Setting::getVal($sShift.'_flag_day', 2);
                                                    $tfPer = Setting::getVal($sShift.'_flag_period', ($sShift == 'morning' ? 1 : 10));
                                                    $tmDay = Setting::getVal($sShift.'_meeting_day', 7);
                                                    $tmPer = Setting::getVal($sShift.'_meeting_period', ($sShift == 'morning' ? 5 : 10));
                                                    
                                                    if ($assignFlag && $d == $tfDay && $p == $tfPer) {
                                                        $gvcnSlotTeacher = ['label' => 'CHÀO CỜ', 'class' => $c->name];
                                                        break;
                                                    }
                                                    if ($assignMeeting && $d == $tmDay && $p == $tmPer) {
                                                        $gvcnSlotTeacher = ['label' => 'SINH HOẠT', 'class' => $c->name];
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <td class="border border-slate-300 p-1 text-center h-[60px] align-middle {{ ($isFixedClass || $gvcnSlotTeacher) ? 'bg-slate-50' : 'bg-white' }}">
                                            
                                            @if($classroom)
                                                @if($isFixedClass)
                                                    <div class="text-[10px] font-black text-slate-400 tracking-widest">{{ $fixedLabelClass }}</div>
                                                    @if($showGvcnClass && !empty($gvcnName))
                                                        <div class="text-[8px] font-bold text-slate-400 mt-0.5">{{ $gvcnName }}</div>
                                                    @endif
                                                @elseif($cell)
                                                    <div class="text-[11px] font-black text-blue-700 uppercase subject-txt">{{ $cell->assignment->subject->name }}</div>
                                                    <div class="text-[9px] text-slate-600 font-bold mt-0.5 teacher-txt">GV: {{ $cell->assignment->teacher->name }}</div>
                                                    @if($cell->room_id)
                                                        <div class="text-[8px] text-orange-700 bg-orange-100 font-bold uppercase inline-block px-1 py-0.5 rounded mt-0.5 room-tag">P: {{ $cell->room->name }}</div>
                                                    @endif
                                                @endif
                                            
                                            @elseif($teacher)
                                                @if($cell)
                                                    <div class="text-[11px] font-black text-blue-700 uppercase subject-txt">{{ $cell->assignment->subject->name }}</div>
                                                    <div class="text-[9px] text-slate-600 font-bold mt-0.5 teacher-txt">Lớp: {{ $cell->assignment->classroom->name }}</div>
                                                    @if($cell->room_id)
                                                        <div class="text-[8px] text-orange-700 bg-orange-100 font-bold uppercase inline-block px-1 py-0.5 rounded mt-0.5 room-tag">P: {{ $cell->room->name }}</div>
                                                    @endif
                                                @elseif($gvcnSlotTeacher)
                                                    <div class="text-[10px] font-black text-slate-400 tracking-widest">{{ $gvcnSlotTeacher['label'] }}</div>
                                                    <div class="text-[8px] font-bold text-slate-400 mt-0.5">Lớp: {{ $gvcnSlotTeacher['class'] }}</div>
                                                @endif
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endfor
                        </tbody>
                     </table>
                </div>
            @else
                <div class="bg-white rounded-[2.5rem] shadow-sm p-12 border border-rose-100 text-center max-w-2xl mx-auto mt-10">
                    <span class="material-symbols-outlined text-6xl text-rose-200 mb-4 block">search_off</span>
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-widest mb-2">Không tìm thấy dữ liệu</h3>
                    <p class="text-sm font-medium text-slate-500">Hệ thống không tìm thấy Lớp hoặc Giáo viên nào mang mã "<span class="text-rose-500 font-bold">{{ request('q') }}</span>". Vui lòng kiểm tra lại!</p>
                </div>
            @endif
        @endif
    </main>

    <footer class="mt-20 p-8 bg-white border-t border-slate-200 text-center mt-auto">
        <p class="font-black text-slate-400 uppercase tracking-widest text-[11px]">
            Hệ thống tra cứu Thời khóa biểu {{ $schoolName }}. <br>
            Hiệu trưởng: {{ $principal }}
        </p>
    </footer>

    <script>
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
                        .rounded-\\[2rem\\] { border-radius: 0 !important; border: none !important; }
                        .p-6, .md\\:p-8 { padding: 0 !important; }
                        .print-header { margin-bottom: 10px !important; padding-bottom: 10px !important; }
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
            
            setTimeout(() => {
                newWindow.focus();
                newWindow.print();
                setTimeout(() => { newWindow.close(); }, 500);
            }, 1000);
        }

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

            let schoolName = "{{ $schoolName }}";
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
</body>
</html>