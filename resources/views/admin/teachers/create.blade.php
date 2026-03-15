@extends('layouts.admin')
@section('title', 'Hồ sơ Giáo viên mới')
@section('content')
<div class="max-w-5xl mx-auto">
    <form action="{{ route('teachers.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="grid grid-cols-12 gap-6">
            <div class="col-span-12 md:col-span-8 space-y-6">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-8 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-600">badge</span> Thông tin hồ sơ
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Mã giáo viên</label>
                            <input type="text" name="code" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner uppercase">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Định mức tiết/Tuần</label>
                            <input type="number" name="max_slots_week" value="18" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Họ và tên giáo viên</label>
                            <input type="text" name="name" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-500">event_busy</span> Đăng ký lịch nghỉ cố định
                    </h3>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                        @foreach([2 => 'T2', 3 => 'T3', 4 => 'T4', 5 => 'T5', 6 => 'T6', 7 => 'T7'] as $val => $label)
                        <label class="flex flex-col items-center p-3 bg-slate-50 rounded-2xl border-2 border-transparent cursor-pointer hover:bg-red-50 has-[:checked]:border-red-500 has-[:checked]:bg-red-50/50 transition-all">
                            <input type="checkbox" name="off_days[]" value="{{ $val }}" class="sr-only">
                            <span class="text-[10px] font-black text-slate-400 uppercase">{{ $label }}</span>
                            <span class="material-symbols-outlined mt-1 text-slate-300">block</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-span-12 md:col-span-4 space-y-6">
                <div class="bg-blue-600 p-8 rounded-[2.5rem] shadow-xl shadow-blue-200 text-white">
                    <h3 class="text-xs font-black uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="material-symbols-outlined">assignment_ind</span> Phân công ban đầu
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[9px] font-black uppercase opacity-60 ml-2 mb-2 block">Chọn Lớp học</label>
                            <select name="class_id" class="w-full bg-white/10 border-none rounded-2xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-white">
                                <option value="" class="text-slate-800">-- Chưa gán lớp --</option>
                                @foreach($classrooms as $c) <option value="{{ $c->id }}" class="text-slate-800">Lớp {{ $c->name }}</option> @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[9px] font-black uppercase opacity-60 ml-2 mb-2 block">Chọn Môn dạy</label>
                            <select name="subject_id" class="w-full bg-white/10 border-none rounded-2xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-white">
                                <option value="" class="text-slate-800">-- Chưa gán môn --</option>
                                @foreach($subjects as $s) <option value="{{ $s->id }}" class="text-slate-800">{{ $s->name }}</option> @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white py-6 rounded-[2rem] font-black uppercase text-xs tracking-[0.2em] shadow-2xl hover:bg-blue-700 transition-all">
                    Lưu hồ sơ cán bộ
                </button>
                <a href="{{ route('teachers.index') }}" class="block text-center text-slate-400 text-[10px] font-black uppercase tracking-widest">Quay lại danh sách</a>
            </div>
        </div>
    </form>
</div>
@endsection