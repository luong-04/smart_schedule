@extends('layouts.admin')

@section('title', 'Hồ sơ & Phân công Giáo viên')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-blue-50">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest mb-6">Thông tin cán bộ</h3>
        <form action="{{ route('teachers.update', $teacher->id) }}" method="POST" class="grid grid-cols-12 gap-6">
            @csrf @method('PUT')
            <div class="col-span-4">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block">Họ và tên</label>
                <input type="text" name="name" value="{{ $teacher->name }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block">Mã định danh</label>
                <input type="text" name="code" value="{{ $teacher->code }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 uppercase">
            </div>
            <div class="col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-1 block">Tiết tối đa/Tuần</label>
                <input type="number" name="max_slots_week" value="{{ $teacher->max_slots_week }}" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="col-span-2 flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">Lưu</button>
            </div>
        </form>
    </div>

    <div class="bg-[#F0F7FF] p-8 rounded-3xl border border-blue-100 shadow-sm">
        <h3 class="text-xs font-black text-blue-700 uppercase tracking-widest mb-6 flex items-center gap-2">
            <span class="w-2 h-4 bg-blue-600 rounded-full"></span>
            Phân công lớp và môn dạy
        </h3>

        <form action="{{ route('assignments.store') }}" method="POST" class="flex gap-4 mb-8 bg-white p-4 rounded-2xl shadow-sm border border-blue-50">
            @csrf
            <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
            
            <div class="flex-1">
                <select name="class_id" required class="w-full bg-slate-50 border-none rounded-xl text-xs py-3">
                    <option value="">Chọn lớp học...</option>
                    @foreach($classrooms as $class)
                        <option value="{{ $class->id }}">Lớp {{ $class->name }} (Khối {{ $class->grade }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1">
                <select name="subject_id" required class="w-full bg-slate-50 border-none rounded-xl text-xs py-3">
                    <option value="">Chọn môn học...</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-blue-600 text-white font-black px-8 py-2 rounded-xl text-[10px] uppercase tracking-widest hover:bg-blue-700 transition-all">
                Gán nhiệm vụ
            </button>
        </form>

        <div class="grid grid-cols-1 gap-3">
            @forelse($teacher->assignments as $as)
            <div class="bg-white p-5 rounded-2xl flex justify-between items-center shadow-sm border border-blue-50 group transition-all hover:border-blue-300">
                <div class="flex items-center gap-8">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Lớp học</span>
                        <span class="text-sm font-black text-slate-700 uppercase">{{ $as->classroom->name }}</span>
                    </div>
                    <div class="flex flex-col border-l border-slate-100 pl-8">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Môn giảng dạy</span>
                        <span class="text-sm font-bold text-blue-600">{{ $as->subject->name }}</span>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <span class="text-[9px] font-black text-slate-300 uppercase italic">Theo định mức khối</span>
                    <form action="{{ route('assignments.destroy', $as->id) }}" method="POST" onsubmit="return confirm('Hủy phân công này?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-red-300 hover:text-red-500 transition-all opacity-0 group-hover:opacity-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="text-center py-8 bg-white/50 rounded-2xl border-2 border-dashed border-blue-100">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest italic">Chưa có dữ liệu phân công giảng dạy</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection