@extends('layouts.admin')
@section('title', 'Thời khóa biểu đã sắp')

@section('content')
<div x-data="{ activeGrade: 10 }" class="space-y-6">
    <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2">
        @foreach([10, 11, 12] as $grade)
        <button @click="activeGrade = {{ $grade }}" 
                :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-500 hover:bg-slate-50'"
                class="flex-1 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all">
            Khối lớp {{ $grade }}
        </button>
        @endforeach
    </div>

    @foreach([10, 11, 12] as $grade)
    <div x-show="activeGrade === {{ $grade }}" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php $classes = $groupedClasses->get($grade) ?? collect(); @endphp
        @forelse($classes as $class)
        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 hover:shadow-xl hover:shadow-blue-600/5 transition-all group">
            <div class="flex justify-between items-start mb-6">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl">
                    <span class="material-symbols-outlined text-2xl">school</span>
                </div>
                <span class="text-[10px] font-black text-emerald-500 bg-emerald-50 px-3 py-1 rounded-full uppercase">Sẵn sàng</span>
            </div>
            
            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Lớp {{ $class->name }}</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">Phòng học: {{ $class->homeroom_teacher ?? 'Chưa gán' }}</p>
            
            <div class="mt-8 flex gap-2">
                <a href="{{ route('schedules.show', $class->id) }}" class="flex-1 flex items-center justify-center gap-2 py-4 bg-blue-600 text-white text-[10px] font-black uppercase rounded-2xl hover:bg-blue-700 transition-all">
                    <span class="material-symbols-outlined text-sm">visibility</span> Xem & In
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full py-20 text-center opacity-20">
            <span class="material-symbols-outlined text-6xl">drafts</span>
            <p class="text-xs font-black uppercase tracking-widest mt-2">Chưa có dữ liệu lớp học cho khối này</p>
        </div>
        @endforelse
    </div>
    @endforeach
</div>
@endsection