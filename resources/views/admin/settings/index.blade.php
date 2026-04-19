@extends('layouts.admin')
@section('title', 'Cấu hình hệ thống')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
            @csrf

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
                    <span class="material-symbols-outlined text-blue-600">school</span>
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Thông tin đơn vị & Ban giám hiệu
                    </h3>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Tên trường học</label>
                        <input type="text" name="school_name" value="{{ $settings['school_name'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner"
                            placeholder="VD: THPT Chuyên ABC">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Niên khóa</label>
                        <input type="text" name="school_year" value="{{ $settings['school_year'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner"
                            placeholder="VD: 2024-2025">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Học kỳ hiện tại</label>
                        <select name="semester"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner font-bold">
                            <option value="Học kỳ 1" {{ ($settings['semester'] ?? 'Học kỳ 1') == 'Học kỳ 1' ? 'selected' : '' }}>Học kỳ 1</option>
                            <option value="Học kỳ 2" {{ ($settings['semester'] ?? '') == 'Học kỳ 2' ? 'selected' : '' }}>Học
                                kỳ 2</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Hiệu trưởng (Ký tên)</label>
                        <input type="text" name="principal_name" value="{{ $settings['principal_name'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner"
                            placeholder="Họ và tên Hiệu trưởng">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Hiệu phó chuyên môn</label>
                        <input type="text" name="vice_principal_name" value="{{ $settings['vice_principal_name'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner"
                            placeholder="Họ và tên Hiệu phó">
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Thông điệp Chào mừng (Dashboard)</label>
                        <input type="text" name="dashboard_welcome_message" value="{{ $settings['dashboard_welcome_message'] ?? 'Chào mừng đến với phần mềm sắp xếp lịch giảng dạy!' }}"
                            class="w-full bg-blue-50/50 border border-blue-100 rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 font-bold text-blue-700 shadow-sm"
                            placeholder="VD: Chào mừng đến với phần mềm sắp xếp lịch giảng dạy!">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
                    <span class="material-symbols-outlined text-emerald-600">contact_mail</span>
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Thông tin liên hệ</h3>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Địa chỉ trường</label>
                        <input type="text" name="school_address" value="{{ $settings['school_address'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 shadow-inner"
                            placeholder="VD: 123 Đường ABC, TP. HCM">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Số điện thoại liên hệ</label>
                        <input type="text" name="school_phone" value="{{ $settings['school_phone'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 shadow-inner"
                            placeholder="VD: 0123 456 789">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Email hỗ trợ</label>
                        <input type="email" name="school_email" value="{{ $settings['school_email'] ?? '' }}"
                            class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-emerald-500 shadow-inner"
                            placeholder="VD: contact@truong.edu.vn">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-indigo-600">lock_clock</span>
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Thiết lập Tiết Cố định (Chào
                            cờ & Sinh hoạt)</h3>
                    </div>
                </div>

                <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="space-y-6 p-8 bg-blue-50/30 rounded-[2.5rem] border border-blue-100 relative">
                        <span class="absolute top-4 right-6 text-[10px] font-black text-blue-300 uppercase">Khối Sáng</span>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="space-y-2 text-center bg-white p-4 rounded-3xl shadow-sm">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Chào cờ (Thứ)</label>
                                <select name="morning_flag_day" class="w-full border-none text-xs font-bold text-center">
                                    @for($i = 2; $i <= 7; $i++)
                                        <option value="{{$i}}" {{($settings['morning_flag_day'] ?? 2) == $i ? 'selected' : ''}}>Thứ
                                    {{$i}}</option> @endfor
                                </select>
                                <div class="h-[1px] bg-slate-100 my-2"></div>
                                <label class="text-[9px] font-black text-slate-400 uppercase">Tiết</label>
                                <input type="number" name="morning_flag_period"
                                    value="{{$settings['morning_flag_period'] ?? 1}}" min="1" max="5"
                                    class="w-full border-none text-xs font-black text-center text-blue-600">
                            </div>

                            <div class="space-y-2 text-center bg-white p-4 rounded-3xl shadow-sm">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Sinh hoạt (Thứ)</label>
                                <select name="morning_meeting_day" class="w-full border-none text-xs font-bold text-center">
                                    @for($i = 2; $i <= 7; $i++)
                                        <option value="{{$i}}" {{($settings['morning_meeting_day'] ?? 7) == $i ? 'selected' : ''}}>Thứ
                                    {{$i}}</option> @endfor
                                </select>
                                <div class="h-[1px] bg-slate-100 my-2"></div>
                                <label class="text-[9px] font-black text-slate-400 uppercase">Tiết</label>
                                <input type="number" name="morning_meeting_period"
                                    value="{{$settings['morning_meeting_period'] ?? 5}}" min="1" max="5"
                                    class="w-full border-none text-xs font-black text-center text-blue-600">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 p-8 bg-orange-50/30 rounded-[2.5rem] border border-orange-100 relative">
                        <span class="absolute top-4 right-6 text-[10px] font-black text-orange-300 uppercase">Khối
                            Chiều</span>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="space-y-2 text-center bg-white p-4 rounded-3xl shadow-sm">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Chào cờ (Thứ)</label>
                                <select name="afternoon_flag_day" class="w-full border-none text-xs font-bold text-center">
                                    @for($i = 2; $i <= 7; $i++)
                                        <option value="{{$i}}" {{($settings['afternoon_flag_day'] ?? 2) == $i ? 'selected' : ''}}>Thứ
                                    {{$i}}</option> @endfor
                                </select>
                                <div class="h-[1px] bg-slate-100 my-2"></div>
                                <label class="text-[9px] font-black text-slate-400 uppercase">Tiết</label>
                                <input type="number" name="afternoon_flag_period"
                                    value="{{$settings['afternoon_flag_period'] ?? 10}}" min="6" max="10"
                                    class="w-full border-none text-xs font-black text-center text-orange-600">
                            </div>

                            <div class="space-y-2 text-center bg-white p-4 rounded-3xl shadow-sm">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Sinh hoạt (Thứ)</label>
                                <select name="afternoon_meeting_day"
                                    class="w-full border-none text-xs font-bold text-center">
                                    @for($i = 2; $i <= 7; $i++)
                                        <option value="{{$i}}" {{($settings['afternoon_meeting_day'] ?? 7) == $i ? 'selected' : ''}}>Thứ
                                    {{$i}}</option> @endfor
                                </select>
                                <div class="h-[1px] bg-slate-100 my-2"></div>
                                <label class="text-[9px] font-black text-slate-400 uppercase">Tiết</label>
                                <input type="number" name="afternoon_meeting_period"
                                    value="{{$settings['afternoon_meeting_period'] ?? 10}}" min="6" max="10"
                                    class="w-full border-none text-xs font-black text-center text-orange-600">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 border-t border-slate-100">
                    <h4 class="text-sm font-black text-purple-700 uppercase tracking-tight mb-4">Tự động gán GVCN vào tiết
                        Cố định</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <div
                            class="flex items-center justify-between p-6 bg-purple-50/50 rounded-3xl border border-purple-100 shadow-sm">
                            <div>
                                <p class="text-xs font-black text-purple-800 uppercase">Tiết Chào Cờ</p>
                                <p class="text-[10px] text-slate-500 font-medium mt-1">Gán GVCN và <span
                                        class="text-rose-500 font-bold">trừ 1 tiết</span></p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="assign_gvcn_flag_salute" value="1" {{ ($settings['assign_gvcn_flag_salute'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-purple-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white shadow-inner">
                                </div>
                            </label>
                        </div>

                        <div
                            class="flex items-center justify-between p-6 bg-purple-50/50 rounded-3xl border border-purple-100 shadow-sm">
                            <div>
                                <p class="text-xs font-black text-purple-800 uppercase">Tiết Sinh Hoạt Lớp</p>
                                <p class="text-[10px] text-slate-500 font-medium mt-1">Gán GVCN và <span
                                        class="text-rose-500 font-bold">trừ 1 tiết</span></p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="assign_gvcn_class_meeting" value="1" {{ ($settings['assign_gvcn_class_meeting'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-purple-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white shadow-inner">
                                </div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
                    <span class="material-symbols-outlined text-orange-500">settings_suggest</span>
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Ràng buộc & Kiểm soát Ma trận
                    </h3>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <label class="text-xs font-black text-slate-700 uppercase">Số tiết dạy liên tiếp tối đa
                                (GV)</label>
                            <input name="max_consecutive_slots" type="number"
                                value="{{ $settings['max_consecutive_slots'] ?? 3 }}"
                                class="w-16 p-2 rounded-xl text-center font-black text-blue-600 border-none shadow-sm">
                        </div>
                        <div class="flex items-center justify-between p-6 bg-slate-50 rounded-3xl border border-slate-100">
                            <label class="text-xs font-black text-slate-700 uppercase">Số ngày dạy tối đa / tuần</label>
                            <input name="max_days_per_week" type="number" value="{{ $settings['max_days_per_week'] ?? 5 }}"
                                class="w-16 p-2 rounded-xl text-center font-black text-orange-600 border-none shadow-sm">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-6 bg-blue-50/50 rounded-3xl border border-blue-100">
                            <p class="text-xs font-black text-blue-700 uppercase">Chặn trùng lịch Giáo viên</p>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="check_teacher_conflict" value="1" {{ ($settings['check_teacher_conflict'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                        <div
                            class="flex items-center justify-between p-6 bg-orange-50/50 rounded-3xl border border-orange-100">
                            <p class="text-xs font-black text-orange-700 uppercase">Chặn trùng Phòng thực hành</p>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="check_room_conflict" value="1" {{ ($settings['check_room_conflict'] ?? 0) ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:bg-orange-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full">
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 pb-12">
                <button type="submit"
                    class="bg-blue-600 text-white px-16 py-5 rounded-full font-black uppercase text-xs tracking-widest shadow-2xl shadow-blue-200 hover:scale-105 transition-all active:scale-95">
                    Áp dụng cấu hình hệ thống
                </button>
            </div>
        </form>
    </div>
@endsection