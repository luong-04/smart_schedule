@extends('layouts.admin')
@section('title', 'Thêm Nhân Viên')

@section('content')

@php
    // Từ điển dịch tên quyền sang Tiếng Việt có dấu
    $tenQuyenTiengViet = [
        'quan_ly_giao_vien' => 'Quản lý Giáo viên',
        'quan_ly_mon_hoc' => 'Quản lý Môn học',
        'quan_ly_lop_hoc' => 'Quản lý Lớp học',
        'quan_ly_xep_lich' => 'Quản lý Xếp lịch',
        'quan_ly_giam_thi' => 'Quản lý Giám thị',
        'quan_ly_cai_dat' => 'Quản lý Cài đặt hệ thống'
    ];
@endphp

<div class="mb-6">
    <a href="{{ route('users.index') }}" class="text-sm font-semibold text-gray-500 hover:text-[#886cc0] flex items-center gap-1 w-fit mb-2 transition">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Quay lại danh sách
    </a>
    <h2 class="text-2xl font-bold text-gray-800">Thêm Nhân Viên Mới</h2>
</div>

<form action="{{ route('users.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <div class="xl:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#886cc0]">person</span> Thông tin đăng nhập
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Họ và Tên <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Nhập tên nhân viên..." 
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#886cc0]/50 focus:border-[#886cc0] outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required placeholder="example@school.edu.vn" 
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#886cc0]/50 focus:border-[#886cc0] outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Mật khẩu <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required placeholder="Tối thiểu 8 ký tự" minlength="8"
                               class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:ring-2 focus:ring-[#886cc0]/50 focus:border-[#886cc0] outline-none transition">
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-full">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[#1e9d8b]">shield_person</span> Phân quyền danh mục
                    </h3>
                    <span class="text-xs font-semibold text-gray-400 bg-gray-100 px-2 py-1 rounded-md">Lựa chọn nhiều quyền</span>
                </div>
                
                <p class="text-sm text-gray-500 mb-6">Tích chọn các ô bên dưới để cho phép nhân viên này nhìn thấy và quản lý các danh mục tương ứng trên Menu.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($permissions as $perm)
                        <label class="relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-purple-50 hover:border-purple-200 transition-all group">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->name }}" class="peer sr-only">
                            
                            <div class="w-6 h-6 rounded border-2 border-gray-300 mr-3 peer-checked:bg-[#886cc0] peer-checked:border-[#886cc0] flex items-center justify-center transition">
                                <span class="material-symbols-outlined text-white text-[16px] opacity-0 peer-checked:opacity-100 transition-opacity">check</span>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-gray-800 group-hover:text-[#886cc0] transition-colors">
                                    {{ $tenQuyenTiengViet[$perm->name] ?? $perm->name }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">Cho phép truy cập menu này</p>
                            </div>
                            
                            <div class="absolute inset-0 border-2 border-transparent peer-checked:border-[#886cc0] rounded-xl pointer-events-none transition-all"></div>
                        </label>
                    @endforeach
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                    <button type="reset" class="px-6 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-50 transition">
                        Làm lại
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-[#886cc0] hover:bg-[#725ab0] text-white rounded-xl text-sm font-bold shadow-lg shadow-purple-500/30 transition flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">save</span> Lưu tài khoản
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>
@endsection