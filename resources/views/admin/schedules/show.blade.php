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
            <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">Khối {{ $classroom->grade }} ({{ $classroom->block_name }}) • {{ $classroom->shift == 'morning' ? 'Ca Sáng' : 'Ca Chiều' }} • GVCN: {{ $classroom->homeroomTeacher?->name ?? 'Chưa phân công' }}</p>
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
                                    @if($classroom->homeroom_teacher_id && ($settings['assign_gvcn_flag_salute'] ?? 0))
                                        <span class="text-[9px] font-bold text-rose-500 mt-1 block">{{ $classroom->homeroomTeacher?->name }}</span>
                                    @endif
                                @elseif($isClassMeeting)
                                    <span class="text-xs font-black text-emerald-600 uppercase tracking-widest block">SINH HOẠT</span>
                                    @if($classroom->homeroom_teacher_id && ($settings['assign_gvcn_class_meeting'] ?? 0))
                                        <span class="text-[9px] font-bold text-emerald-500 mt-1 block">{{ $classroom->homeroomTeacher?->name }}</span>
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
@endsection