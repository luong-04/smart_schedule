@extends('layouts.admin')
@section('title', 'Cấu hình hệ thống')

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
    @csrf

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex items-center gap-3">
            <span class="material-symbols-outlined text-blue-600">account_balance</span>
            <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Thông tin đơn vị & Niên khóa</h3>
        </div>
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Tên trường học</label>
                <input type="text" name="school_name" value="{{ $settings['school_name'] ?? '' }}" 
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner" placeholder="VD: THPT Chuyên ABC">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Niên khóa / Học kỳ</label>
                <input type="text" name="school_year" value="{{ $settings['school_year'] ?? '' }}" 
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner" placeholder="VD: 2024 - 2025 (Học kỳ 1)">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Hiệu trưởng</label>
                <input type="text" name="principal" value="{{ $settings['principal'] ?? '' }}" 
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Hiệu phó chuyên môn</label>
                <input type="text" name="vice_principal" value="{{ $settings['vice_principal'] ?? '' }}" 
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex items-center gap-3">
            <span class="material-symbols-outlined text-indigo-600">lock_clock</span>
            <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Cấu hình Tiết học cố định (Không thể dời)</h3>
        </div>
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4 p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Thứ 2 (Tiết 1 & Tiết 10)</p>
                <div class="space-y-3">
                    <select name="fixed_monday_type" class="w-full bg-white border-none rounded-xl px-4 py-3 text-xs font-bold shadow-sm">
                        <option value="text" {{ ($settings['fixed_monday_type'] ?? '') == 'text' ? 'selected' : '' }}>Hiển thị văn bản cố định</option>
                        <option value="gvcn" {{ ($settings['fixed_monday_type'] ?? '') == 'gvcn' ? 'selected' : '' }}>Gán Giáo viên chủ nhiệm</option>
                        <option value="null" {{ ($settings['fixed_monday_type'] ?? '') == 'null' ? 'selected' : '' }}>Để trống (Cho phép xếp lịch)</option>
                    </select>
                    <input type="text" name="fixed_monday_text" value="{{ $settings['fixed_monday_text'] ?? 'CHÀO CỜ' }}" 
                        class="w-full bg-white border-none rounded-xl px-4 py-3 text-xs font-bold shadow-inner" placeholder="Nội dung hiển thị (VD: Chào cờ)">
                </div>
            </div>

            <div class="space-y-4 p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Thứ 7 (Tiết 5 & Tiết 10)</p>
                <div class="space-y-3">
                    <select name="fixed_saturday_type" class="w-full bg-white border-none rounded-xl px-4 py-3 text-xs font-bold shadow-sm">
                        <option value="text" {{ ($settings['fixed_saturday_type'] ?? '') == 'text' ? 'selected' : '' }}>Hiển thị văn bản cố định</option>
                        <option value="gvcn" {{ ($settings['fixed_saturday_type'] ?? '') == 'gvcn' ? 'selected' : '' }}>Gán Giáo viên chủ nhiệm</option>
                        <option value="null" {{ ($settings['fixed_saturday_type'] ?? '') == 'null' ? 'selected' : '' }}>Để trống (Cho phép xếp lịch)</option>
                    </select>
                    <input type="text" name="fixed_saturday_text" value="{{ $settings['fixed_saturday_text'] ?? 'SINH HOẠT LỚP' }}" 
                        class="w-full bg-white border-none rounded-xl px-4 py-3 text-xs font-bold shadow-inner" placeholder="Nội dung hiển thị (VD: Sinh hoạt)">
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex items-center gap-3">
            <span class="material-symbols-outlined text-orange-500">settings_suggest</span>
            <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Ràng buộc & Kiểm soát Ma trận</h3>
        </div>
        
        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-6">
                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Số tiết tối đa liên tiếp</label>
                    <div class="flex items-center gap-4">
                        <input name="max_consecutive_slots" type="number" value="{{ $settings['max_consecutive_slots'] ?? 3 }}" 
                            class="w-24 px-4 py-3 rounded-xl border-none shadow-inner text-center font-black text-blue-600">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Tiết / Buổi</span>
                    </div>
                </div>

                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Số ngày dạy tối đa trong tuần</label>
                    <div class="flex items-center gap-4">
                        <input name="max_days_per_week" type="number" value="{{ $settings['max_days_per_week'] ?? 5 }}" 
                            class="w-24 px-4 py-3 rounded-xl border-none shadow-inner text-center font-black text-orange-600">
                        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Ngày / Tuần</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-6 bg-blue-50/50 rounded-3xl border border-blue-100">
                    <div>
                        <p class="text-xs font-black text-slate-700 uppercase tracking-tight">Trùng lịch Giáo viên</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase">Cảnh báo khi 1 GV dạy 2 lớp cùng tiết</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="check_teacher_conflict" value="1" {{ ($settings['check_teacher_conflict'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-6 bg-orange-50/50 rounded-3xl border border-orange-100">
                    <div>
                        <p class="text-xs font-black text-slate-700 uppercase tracking-tight">Trùng Phòng học (Thực hành)</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase">Kiểm soát xung đột phòng chức năng</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="check_room_conflict" value="1" {{ ($settings['check_room_conflict'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-orange-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-red-500">event_busy</span>
                <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Cấu hình Xin nghỉ & Ưu tiên giáo viên</h3>
            </div>
            <a href="{{ route('teachers.index') }}" class="text-[10px] font-black text-blue-600 uppercase border-b-2 border-blue-100">Đi tới danh sách giáo viên</a>
        </div>
        <div class="p-8">
            <div class="p-6 bg-red-50/30 border border-red-100 rounded-3xl">
                <p class="text-xs font-bold text-red-600 leading-relaxed italic">
                    Lưu ý: Để cấu hình chi tiết ngày nghỉ cho từng giáo viên, vui lòng truy cập vào mục **"Giáo viên"** và nhấn **"Sửa"**. Hệ thống Ma trận TKB sẽ tự động đọc lịch nghỉ của từng cá nhân để ngăn chặn việc xếp tiết sai quy định.
                </p>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-4 pb-12">
        <button type="submit" class="bg-blue-600 text-white px-16 py-5 rounded-[2rem] font-black uppercase text-xs tracking-[0.2em] shadow-2xl shadow-blue-200 hover:scale-105 transition-all">
            Áp dụng cấu hình hệ thống
        </button>
    </div>
</form>
@endsection