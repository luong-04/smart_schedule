@extends('layouts.admin')
@section('title', 'Hồ sơ & Phân công Giáo viên')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    <form action="{{ route('teachers.update', $teacher->id) }}" method="POST" class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
        @csrf @method('PUT')
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-slate-50 pb-6">
            <div>
                <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                    <span class="material-symbols-outlined text-blue-600">person_edit</span> Chỉnh sửa hồ sơ giáo viên
                </h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Thông tin định danh và định mức tiết dạy</p>
            </div>
        </div>

        <div class="mb-8 bg-amber-50 border border-amber-100 rounded-2xl p-5 flex gap-4 items-start shadow-inner">
            <div class="p-2 bg-amber-100 text-amber-600 rounded-xl shrink-0">
                <span class="material-symbols-outlined text-xl">lightbulb</span>
            </div>
            <div>
                <p class="text-[11px] font-black text-amber-800 uppercase tracking-widest mb-1">Lưu ý quan trọng</p>
                <p class="text-xs text-amber-700 font-medium leading-relaxed">
                    Mọi thay đổi về <b>Họ tên, Mã GV, Tổ, Định mức</b> hoặc <b>Lịch nghỉ</b> chỉ có hiệu lực sau khi bạn nhấn nút <b class="text-amber-800 uppercase">"Lưu hồ sơ giáo viên"</b>. 
                    <br><i>(Phần gán lớp học ở phía dưới sẽ được lưu tự động theo từng dòng riêng biệt).</i>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-6 mb-8">
            <div class="col-span-12 md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Họ và tên</label>
                <input type="text" name="name" value="{{ $teacher->name }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner transition-all">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Mã định danh</label>
                <input type="text" name="code" value="{{ $teacher->code }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner uppercase transition-all">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tổ chuyên môn</label>
                <input type="text" name="department" value="{{ $teacher->department }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner transition-all">
            </div>
            <div class="col-span-12 md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Định mức tiết/tuần</label>
                <input type="number" name="max_slots_week" value="{{ $teacher->max_slots_week }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner transition-all">
            </div>
        </div>

        <div class="p-6 md:p-8 bg-slate-50 rounded-3xl border border-slate-100 mb-8 shadow-inner">
            <h4 class="text-[10px] font-black text-slate-500 uppercase mb-5 tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-sm text-red-400">event_busy</span> Đăng ký các buổi nghỉ cố định
            </h4>
            <div class="flex flex-wrap gap-3">
                @foreach([2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5', 6 => 'Thứ 6', 7 => 'Thứ 7'] as $val => $label)
                <label class="flex-1 min-w-[110px] flex items-center justify-center gap-2 p-3 bg-white rounded-xl border-2 border-transparent cursor-pointer has-[:checked]:border-red-500 has-[:checked]:bg-red-50 transition-all shadow-sm hover:shadow-md">
                    <input type="checkbox" name="off_days[]" value="{{ $val }}" 
                           {{ in_array($val, $teacher->off_days ?? []) ? 'checked' : '' }} class="sr-only">
                    <span class="text-[11px] font-black uppercase text-slate-600">{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end pt-6 border-t border-slate-100">
            <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest shadow-xl shadow-blue-200 hover:bg-blue-700 hover:scale-105 transition-all flex items-center gap-2 group">
                <span class="material-symbols-outlined text-[18px] group-hover:rotate-12 transition-transform">save</span> 
                Lưu hồ sơ giáo viên
            </button>
        </div>
    </form>

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-4 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 h-fit">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-6">Thêm phân công mới</h3>
            <form action="{{ route('assignments.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2 mb-1 block">Chọn Lớp học</label>
                    <select name="class_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn lớp học --</option>
                        @foreach($classrooms as $c) <option value="{{ $c->id }}">Lớp {{ $c->name }} (Khối {{ $c->grade }})</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[9px] font-black text-slate-400 uppercase ml-2 mb-1 block">Chọn Môn dạy</label>
                    <select name="subject_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Chọn môn dạy --</option>
                        @foreach($subjects as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-slate-800 text-white py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-slate-200 hover:bg-slate-900 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">add_task</span> Lưu phân công mới
                </button>
            </form>
        </div>

        <div class="col-span-12 md:col-span-8 bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-6">Danh sách lớp học đang phụ trách</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($teacher->assignments as $as)
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex justify-between items-center group hover:border-blue-200 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-black text-xs">{{ $as->classroom->name }}</div>
                        <div>
                            <p class="text-[11px] font-black text-slate-700 uppercase tracking-tight">{{ $as->subject->name }}</p>
                            <p class="text-[9px] text-slate-500 font-bold uppercase mt-0.5">Khối {{ $as->classroom->grade }}</p>
                        </div>
                    </div>
                    <form action="{{ route('assignments.destroy', $as->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy phân công lớp này không?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-slate-300 hover:text-rose-500 transition-colors" title="Xóa phân công">
                            <span class="material-symbols-outlined text-sm">cancel</span>
                        </button>
                    </form>
                </div>
                @empty
                <div class="col-span-2 text-center py-12 opacity-40">
                    <span class="material-symbols-outlined text-5xl">inventory_2</span>
                    <p class="text-[10px] font-black uppercase tracking-widest mt-3 text-slate-500">Giáo viên này chưa được phân công lớp nào</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection