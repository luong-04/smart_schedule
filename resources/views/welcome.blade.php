<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu TKB - {{ $schoolName }}</title>
    
    <!-- Meta Tags cho SEO -->
    <meta name="description" content="Hệ thống tra cứu Thời khóa biểu của {{ $schoolName }} năm học {{ $schoolYear }}. Hiện đại, nhanh chóng và chính xác.">
    <meta name="keywords" content="thời khóa biểu, tra cứu tkb, {{ $schoolName }}, giáo dục">
    <meta property="og:title" content="Tra cứu TKB - {{ $schoolName }}">
    <meta property="og:description" content="Tra cứu lịch học và giảng dạy nhanh nhất.">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-soft: rgba(37, 99, 235, 0.1);
            --accent: #10b981;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
        }

        body { 
            display: flex;
            flex-direction: column;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            color: #1e293b;
            margin: 0;
        }

        /* Đảm bảo chân trang không bị nhảy lên */
        main {
            flex: 1 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 16px 0 rgba(31, 38, 135, 0.04);
        }

        .gradient-text {
            background: linear-gradient(to right, #2563eb, #1e40af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .search-container {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .search-container:focus-within {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.1), 0 10px 10px -5px rgba(37, 99, 235, 0.04);
        }

        @media print {
            .no-print { display: none !important; }
            .print-area { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; margin: 0 !important; }
            body { background: white !important; }
            .glass-card { background: white !important; backdrop-filter: none !important; border: none !important; }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* TKB Table Styling - CHUẨN ADMIN */
        .print-table { border-collapse: collapse; border: 2px solid black; }
        .print-table th { background: #f1f5f9; border: 1px solid black; padding: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; text-align: center; }
        .print-table td { border: 1px solid black; text-align: center; vertical-align: middle; padding: 4px; }
    </style>
</head>
<body class="selection:bg-blue-100">
    
    <!-- Header -->
    <header class="sticky top-0 z-50 glass-card px-6 py-4 border-b border-blue-100 flex justify-between items-center no-print">
        <div class="flex items-center gap-3">
            <div class="bg-blue-600 rounded-2xl p-2.5 text-white shadow-lg shadow-blue-200">
                 <span class="material-symbols-outlined text-2xl">school</span>
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight text-blue-900 uppercase leading-tight">{{ $schoolName }}</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $semester }} • Niên khóa {{ $schoolYear }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="group relative px-6 py-2.5 rounded-2xl bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest overflow-hidden transition-all hover:pr-10">
                <span class="relative z-10">Quản trị viên</span>
                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-all text-sm">arrow_forward</span>
            </a>
        </div>
    </header>

    <main class="w-full max-w-6xl mx-auto px-4 py-8 md:py-12 flex-grow">
        
        <!-- Hero Section -->
        <div class="text-center mb-10 fade-in">
            <h2 class="text-3xl md:text-5xl font-black text-slate-800 mb-6 tracking-tight leading-tight">
                Hệ Thống <span class="gradient-text">Tra Cứu TKB</span>
            </h2>

            <form action="{{ url('/') }}" method="GET" class="search-container relative max-w-2xl mx-auto group no-print">
                <input type="text" name="q" value="{{ $searchQuery }}" placeholder="Nhập tên lớp (10A1) hoặc tên Giáo viên..." 
                    class="w-full p-5 pl-14 pr-40 bg-white rounded-[2.5rem] shadow-xl shadow-blue-100/40 border-2 border-transparent focus:border-blue-300 outline-none text-base font-semibold text-slate-700 placeholder:text-slate-300 transition-all">
                <span class="material-symbols-outlined absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-500 transition-colors">search</span>
                <button type="submit" class="absolute right-2.5 top-2.5 bottom-2.5 bg-blue-600 text-white px-8 rounded-full text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg active:scale-95">
                    Tìm Kiếm
                </button>
            </form>
        </div>

        @if($searchQuery)
            <div class="fade-in">
                @if($classroom || $teacher)
                    @php
                        $targetType = $classroom ? 'class' : 'teacher';
                        $targetId   = $classroom ? $classroom->id : $teacher->id;
                        $targetName = $classroom ? $classroom->name : $teacher->name;
                        $targetFull = $classroom ? "LỚP $classroom->name" : "GV: $teacher->name";
                        $extraInfo  = $classroom ? "GVCN: $gvcnName" : "Mã GV: " . ($teacher->code ?? 'N/A');
                    @endphp

                    <!-- UI Controls -->
                    <div class="flex justify-between items-end mb-6 max-w-5xl mx-auto no-print">
                        <div class="flex items-center gap-3">
                            <span class="bg-blue-600 text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">KẾT QUẢ</span>
                            <h3 class="text-xl font-black text-slate-800 uppercase">{{ $targetName }}</h3>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="exportWord('{{ $targetType }}-{{ $targetId }}', '{{ $targetName }}')" class="bg-blue-700 text-white px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-blue-800 transition-all shadow-lg">
                                <span class="material-symbols-outlined text-sm">article</span> Tải File Word
                            </button>
                            <button onclick="exportNative('pdf-content-{{ $targetType }}-{{ $targetId }}')" class="bg-slate-800 text-white px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 hover:bg-black transition-all shadow-lg">
                                <span class="material-symbols-outlined text-sm">print</span> In / Tải PDF
                            </button>
                        </div>
                    </div>

                    <!-- Result Card - MẪU CHUẨN ADMIN -->
                    <div id="pdf-content-{{ $targetType }}-{{ $targetId }}" class="print-area bg-white rounded-[2rem] p-6 md:p-10 border border-slate-200 max-w-5xl mx-auto mb-10 shadow-sm overflow-x-auto">
                        
                        <!-- Header identical to admin -->
                        <div class="print-header-pdf" style="text-align: center; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px;">
                            <h2 style="font-size: 14px; font-weight: 900; color: #4b5563; margin: 0; text-transform: uppercase;">{{ $schoolName }}</h2>
                            <h1 style="font-size: 24px; font-weight: 900; color: #1d4ed8; margin: 5px 0; text-transform: uppercase;">THỜI KHÓA BIỂU{{ $teacher ? ' GIÁO VIÊN' : '' }}</h1>
                            <p style="font-size: 12px; font-weight: 700; color: #1f2937; margin: 5px 0;">
                                <span style="background-color: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 10px;">PHIÊN BẢN HIỆN TẠI</span>
                                {{ $classroom ? 'LỚP: ' .  $targetName : 'HỌ TÊN: ' . $targetName }} 
                                @if($appliesFromDate && $appliesToDate)
                                    | ÁP DỤNG: {{ $appliesFromDate->format('d/m/Y') }} - {{ $appliesToDate->format('d/m/Y') }}
                                @endif
                                | NĂM HỌC: {{ $schoolYear }}
                            </p>
                            <p style="font-size: 12px; font-weight: 700; color: #4b5563; margin: 0; text-transform: uppercase;">
                                {{ $extraInfo }} 
                                @if($teacher && $teacher->subject) | MÔN: {{ $teacher->subject->name }} @endif
                            </p>
                        </div>

                        <table class="w-full print-table" style="width: 100%; border-collapse: collapse; border: 2px solid black;">
                            <thead>
                                <tr style="background-color: #f1f5f9;">
                                    <th style="border: 1px solid black; padding: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; width: 60px; text-align: center;">Tiết</th>
                                    @for($d=2; $d<=7; $d++)
                                    <th style="border: 1px solid black; padding: 8px; font-size: 12px; font-weight: 900; text-transform: uppercase; text-align: center;">Thứ {{ $d }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @for($p=1; $p<=10; $p++)
                                    @if($p == 6)
                                    <tr style="height: 25px;"><td colspan="7" style="border: 1px solid black; background-color: #f8f9fa; text-align: center; font-size: 11px; font-weight: 900; text-transform: uppercase; font-style: italic;">Nghỉ trưa / Chuyển ca</td></tr>
                                    @endif
                                    <tr style="height: 50px;">
                                        <td style="border: 1px solid black; text-align: center; font-weight: 900; font-size: 13px; background-color: #f8f9fa;">{{ $p }}</td>
                                        @for($d=2; $d<=7; $d++)
                                            @php
                                                $cell = $schedules->where('day_of_week', $d)->where('period', $p)->first();
                                                
                                                // Xử lý Chào cờ / Sinh hoạt
                                                $isFixed = false;
                                                $fixedLabel = '';
                                                if ($classroom) {
                                                    $isFlagSalute = ($d == $shiftVars['fDay'] && $p == $shiftVars['fPer']);
                                                    $isClassMeeting = ($d == $shiftVars['mDay'] && $p == $shiftVars['mPer']);
                                                    $isFixed = $isFlagSalute || $isClassMeeting;
                                                    $fixedLabel = $isFlagSalute ? 'CHÀO CỜ' : 'SINH HOẠT';
                                                }

                                                // Xử lý GV chủ nhiệm (khi xem TKB Giáo viên)
                                                $isHomeroomFixed = false;
                                                $homeroomLabel = '';
                                                $homeroomClassName = '';
                                                if ($teacher && !$cell && $gvcnClasses->isNotEmpty()) {
                                                    foreach($gvcnClasses as $c) {
                                                        $sShift = strtolower($c->shift ?? 'morning');
                                                        $tfDay = Setting::getVal($sShift.'_flag_day', 2);
                                                        $tfPer = Setting::getVal($sShift.'_flag_period', ($sShift == 'morning' ? 1 : 10));
                                                        $tmDay = Setting::getVal($sShift.'_meeting_day', 7);
                                                        $tmPer = Setting::getVal($sShift.'_meeting_period', ($sShift == 'morning' ? 5 : 10));

                                                        if ($assignFlag && $d == $tfDay && $p == $tfPer) { $isHomeroomFixed = true; $homeroomLabel = 'CHÀO CỜ'; $homeroomClassName = $c->name; break; }
                                                        if ($assignMeeting && $d == $tmDay && $p == $tmPer) { $isHomeroomFixed = true; $homeroomLabel = 'SINH HOẠT'; $homeroomClassName = $c->name; break; }
                                                    }
                                                }
                                            @endphp

                                            <td style="border: 1px solid black; text-align: center; vertical-align: middle; background-color: {{ ($isFixed || $isHomeroomFixed) ? '#f8f9fa' : '#ffffff' }}; padding: 4px;">
                                                @if($isFixed)
                                                    <span style="font-size: 11px; font-weight: 900; color: #6b7280;">{{ $fixedLabel }}</span>
                                                @elseif($isHomeroomFixed)
                                                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                                                        <span style="font-size: 11px; font-weight: 900; color: #6b7280;">{{ $homeroomLabel }}</span>
                                                        <span style="font-size: 10px; font-weight: 700; color: #4b5563;">Lớp {{ $homeroomClassName }}</span>
                                                    </div>
                                                @elseif($cell)
                                                    <div style="display: flex; flex-direction: column; line-height: 1.1;">
                                                        <span style="font-size: 12px; font-weight: 900; color: #1e40af; text-transform: uppercase;">{{ $classroom ? $cell->assignment->subject->name : "LỚP " . $cell->assignment->classroom->name }}</span>
                                                        <span style="font-size: 10px; font-weight: 700; color: #4b5563;">{{ $classroom ? $cell->assignment->teacher->name : $cell->assignment->subject->name }}</span>
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
                @else
                    <!-- No results -->
                    <div class="bg-white rounded-[3rem] p-16 border-2 border-dashed border-slate-200 text-center max-w-2xl mx-auto shadow-sm">
                        <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="material-symbols-outlined text-4xl">calendar_today</span>
                        </div>
                        <h4 class="text-xl font-black text-slate-800 mb-2 uppercase">Không tìm thấy lịch áp dụng</h4>
                        <p class="text-slate-500 font-medium leading-relaxed">
                            Rất tiếc, hệ thống không tìm thấy thời khóa biểu nào đang áp dụng cho ngày hôm nay ({{ now()->format('d/m/Y') }}).<br>
                            Vui lòng liên hệ bộ phận học vụ hoặc thử lại sau.
                        </p>
                        <a href="{{ url('/') }}" class="inline-block mt-8 text-blue-600 font-bold text-sm">Quay lại trang chủ</a>
                    </div>
                @endif
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="w-full bg-white border-t border-slate-200 py-10 px-6 no-print flex-shrink-0">
        <div class="max-w-6xl mx-auto text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-loose">
                &copy; {{ date('Y') }} {{ $schoolName }} <br>
                Hiệu trưởng: {{ $principal }} | Hiệu phó: {{ $vicePrincipal }}
            </p>
        </div>
    </footer>

    <script>
        function exportWord(elementId, targetName) {
            const element = document.getElementById(`pdf-content-${elementId}`);
            if (!element) return alert("Không tìm thấy nội dung!");
            
            const content = element.innerHTML;
            const finalHtml = generateWordTemplate(content);
            
            const blob = new Blob(['\ufeff', finalHtml], { type: 'application/msword' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = `TKB_${targetName}.doc`;
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
                <body>
                    ${content}
                </body>
                </html>
            `);
            newWindow.document.close();
            
            setTimeout(() => {
                newWindow.focus();
                newWindow.print();
                newWindow.close();
            }, 1000);
        }
    </script>
</body>
</html>