@extends('layouts.admin')
@section('title', 'Hồ sơ & Phân công Giáo viên')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <form action="{{ route('teachers.update', $teacher->id) }}" method="POST" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
        @csrf @method('PUT')
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">person_edit</span> Chỉnh sửa hồ sơ
            </h3>
            <button type="submit" class="text-blue-600 font-black text-[10px] uppercase hover:underline">Cập nhật thông tin</button>
        </div>

        <div class="grid grid-cols-12 gap-6 mb-8">
            <div class="col-span-12 md:col-span-4">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Họ và tên</label>
                <input type="text" name="name" value="{{ $teacher->name }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>
            <div class="col-span-12 md:col-span-4">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Mã định danh</label>
                <input type="text" name="code" value="{{ $teacher->code }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner uppercase">
            </div>
            <div class="col-span-12 md:col-span-4">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Định mức/Tuần</label>
                <input type="number" name="max_slots_week" value="{{ $teacher->max_slots_week }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>
        </div>

        <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
            <h4 class="text-[10px] font-black text-slate-400 uppercase mb-4 tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">event_busy</span> Đăng ký lịch nghỉ
            </h4>
            <div class="flex flex-wrap gap-3">
                @foreach([2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5', 6 => 'Thứ 6', 7 => 'Thứ 7'] as $val => $label)
                <label class="flex-1 min-w-[100px] flex items-center justify-center gap-2 p-3 bg-white rounded-xl border-2 border-transparent cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50 transition-all shadow-sm">
                    <input type="checkbox" name="off_days[]" value="{{ $val }}" 
                           {{ in_array($val, $teacher->off_days ?? []) ? 'checked' : '' }} class="sr-only">
                    <span class="text-[10px] font-black uppercase text-slate-500">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </form>

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 h-fit">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-6">Thêm phân công</h3>
            <form action="{{ route('assignments.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                <div>
                    <select name="class_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn lớp học --</option>
                        @foreach($classrooms as $c) <option value="{{ $c->id }}">Lớp {{ $c->name }} (Khối {{ $c->grade }})</option> @endforeach
                    </select>
                </div>
                <div>
                    <select name="subject_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-xs font-bold focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn môn dạy --</option>
                        @foreach($subjects as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-slate-200">
                    Gán phân công
                </button>
            </form>
        </div>

        <div class="col-span-12 md:col-span-8 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-6">Lớp học đang phụ trách</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($teacher->assignments as $as)
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center group">
                    <div class="flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-xs">{{ $as->classroom->name }}</div>
                        <div>
                            <p class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ $as->subject->name }}</p>
                            <p class="text-[9px] text-slate-400 font-bold uppercase">Khối {{ $as->classroom->grade }}</p>
                        </div>
                    </div>
                    <form action="{{ route('assignments.destroy', $as->id) }}" method="POST" onsubmit="return confirm('Hủy phân công?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-slate-300 hover:text-red-500 transition-colors">
                            <span class="material-symbols-outlined text-sm">cancel</span>
                        </button>
                    </form>
                </div>
                @empty
                <div class="col-span-2 text-center py-12 opacity-30">
                    <span class="material-symbols-outlined text-4xl">inventory_2</span>
                    <p class="text-[10px] font-black uppercase tracking-widest mt-2">Chưa gán phân công nào</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection