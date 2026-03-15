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
            <button onclick="downloadPDF()" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-[11px] font-black uppercase flex items-center gap-2 hover:bg-blue-700 transition-all shadow-md shadow-blue-500/30">
                <span class="material-symbols-outlined text-sm">download</span> Tải bản PDF
            </button>
        </div>
    </div>

    <div id="pdf-content" class="bg-white rounded-[2rem] border border-slate-200 p-8 md:p-12 shadow-sm print:p-0 print:border-none print:shadow-none">
        
        <div class="flex justify-between items-start mb-10 border-b-2 border-slate-800 pb-6">
            <div class="text-left">
                <h2 class="text-sm font-black text-slate-800 uppercase">{{ $settings['school_name'] ?? 'TÊN TRƯỜNG CHƯA CÀI ĐẶT' }}</h2>
                <p class="text-xs font-bold text-slate-500 mt-1">Năm học: {{ $settings['school_year'] ?? '2024 - 2025' }}</p>
            </div>
            <div class="text-right">
                <h1 class="text-2xl font-black text-blue-700 uppercase tracking-wider">THỜI KHÓA BIỂU</h1>
                <p class="text-sm font-black text-slate-700 uppercase mt-1 bg-blue-50 inline-block px-4 py-1 rounded-lg">Lớp: {{ $classroom->name }} (Khối {{ $classroom->grade }})</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border-2 border-slate-800">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-50/50">
                        <th class="border-b-2 border-r border-slate-800 p-3 text-xs font-black uppercase text-blue-900 w-16 text-center">Tiết</th>
                        @for($d=2; $d<=7; $d++)
                        <th class="border-b-2 border-r last:border-r-0 border-slate-800 p-3 text-xs font-black uppercase text-blue-900 text-center w-[15%]">Thứ {{ $d }}</th>
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
                            <td colspan="7" class="border-y-2 border-slate-800 bg-slate-100 p-2 text-center text-[10px] font-black text-slate-500 uppercase tracking-[0.3em]">
                                Nghỉ Trưa / Chuyển Ca
                            </td>
                        </tr>
                        @endif

                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="border-b border-r border-slate-300 p-3 text-center font-black text-slate-600 bg-slate-50/50">{{ $p }}</td>
                            
                            @for($d=2; $d<=7; $d++)
                                @php
                                    $isFixed = ($d == $fDay && $p == $fPer) || ($d == $mDay && $p == $mPer);
                                    $fixedLabel = ($d == $fDay && $p == $fPer) ? 'CHÀO CỜ' : 'SINH HOẠT';
                                    $cell = $schedules->where('day_of_week', $d)->where('period', $p)->first();
                                @endphp
                                
                                <td class="border-b border-r last:border-r-0 border-slate-300 p-2 text-center h-20 align-middle {{ $isFixed ? 'bg-slate-100/50' : 'bg-white' }}">
                                    @if($isFixed)
                                        <span class="text-xs font-black text-slate-400 tracking-widest">{{ $fixedLabel }}</span>
                                    @elseif($cell)
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-[13px] font-black text-blue-700 uppercase leading-tight">{{ $cell->assignment->subject->name }}</span>
                                            <span class="text-[11px] font-bold text-slate-600 mt-1 bg-slate-100 px-2 py-0.5 rounded">{{ $cell->assignment->teacher->name }}</span>
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="mt-12 flex justify-between items-center text-center">
            <div>
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
        #pdf-content { box-shadow: none !important; margin: 0 !important; width: 100% !important; }
    }
</style>

<script>
    function downloadPDF() {
        const element = document.getElementById('pdf-content');
        
        // Tùy chỉnh cấu hình xuất PDF A4 Ngang (Landscape)
        const opt = {
            margin:       0.3,
            filename:     'TKB_Lop_{{ $classroom->name }}.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        // Chạy lệnh tải
        html2pdf().set(opt).from(element).save();
    }
</script>
@endsection