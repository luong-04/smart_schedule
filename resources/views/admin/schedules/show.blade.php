@extends('layouts.admin')
@section('title', 'TKB Lớp ' . $classroom->name)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center no-print">
        <a href="{{ route('schedules.list') }}" class="flex items-center gap-2 text-slate-400 font-bold text-xs uppercase hover:text-blue-600 transition-colors">
            <span class="material-symbols-outlined">arrow_back</span> Quay lại
        </a>
        <button onclick="window.print()" class="bg-slate-900 text-white px-8 py-4 rounded-2xl text-[10px] font-black uppercase flex items-center gap-2 shadow-xl shadow-slate-200">
            <span class="material-symbols-outlined text-sm">print</span> In Thời khóa biểu
        </button>
    </div>

    <div class="bg-white rounded-[3rem] border border-slate-200 p-12 shadow-sm print:p-0 print:border-none print:shadow-none">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-slate-900 uppercase tracking-tighter">Thời khóa biểu</h1>
            <p class="text-xl font-bold text-blue-600 uppercase mt-2">Lớp {{ $classroom->name }} — Học kỳ 1</p>
            <div class="w-24 h-1.5 bg-blue-600 mx-auto mt-6 rounded-full"></div>
        </div>

        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-50">
                    <th class="border border-slate-200 p-6 text-[10px] font-black uppercase text-slate-400 w-20">Tiết</th>
                    @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'] as $day)
                    <th class="border border-slate-200 p-6 text-[10px] font-black uppercase text-slate-700 tracking-widest">{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($p = 1; $p <= 5; $p++)
                <tr>
                    <td class="border border-slate-200 p-6 text-center font-black text-slate-300">{{ $p }}</td>
                    @for($d = 2; $d <= 7; $d++)
                        @php $cell = $schedules->where('day_of_week', $d)->where('period', $p)->first(); @endphp
                        <td class="border border-slate-200 p-6 text-center min-h-[100px] align-middle">
                            @if($cell)
                                <p class="text-sm font-black text-slate-800 uppercase leading-tight">{{ $cell->assignment->subject->name }}</p>
                                <p class="text-[9px] text-slate-400 font-bold mt-2 uppercase tracking-tighter">{{ $cell->assignment->teacher->name }}</p>
                            @else
                                <span class="text-slate-100 material-symbols-outlined">close</span>
                            @endif
                        </td>
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
        
        <div class="mt-12 flex justify-between items-end hidden print:flex">
            <div class="text-center italic text-slate-500 text-xs">
                Ngày xuất bản: {{ date('d/m/Y') }}
            </div>
            <div class="text-center">
                <p class="font-black uppercase text-xs mb-16 tracking-widest text-slate-800">Hiệu trưởng phê duyệt</p>
                <div class="w-48 border-b border-slate-200 mx-auto"></div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        aside { display: none !important; }
        main { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        body { background: white !important; }
        .bg-[#f6f6f8] { background: white !important; }
    }
</style>
@endsection