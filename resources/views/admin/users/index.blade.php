@extends('layouts.admin')
@section('title', 'Quản lý Nhân viên')

@section('content')

@php
    // Từ điển dịch tên quyền sang Tiếng Việt có dấu
    $tenQuyenTiengViet = [
        'quan_ly_giao_vien' => 'Quản lý Giáo viên',
        'quan_ly_mon_hoc' => 'Quản lý Môn học',
        'quan_ly_lop_hoc' => 'Quản lý Lớp học',
        'quan_ly_xep_lich' => 'Quản lý Xếp lịch',
        'quan_ly_giam_thi' => 'Quản lý Giám thị',
        'quan_ly_cai_dat' => 'Quản lý Cài đặt'
    ];
@endphp

<div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Danh sách Người dùng & Phân quyền</h2>
        <p class="text-sm text-gray-500 font-medium mt-1">Hệ thống / <span class="text-[#886cc0]">Người dùng</span></p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    
    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="relative w-full md:w-80">
            <span class="material-symbols-outlined absolute left-3 top-2.5 text-gray-400">search</span>
            <input type="text" placeholder="Tìm kiếm người dùng..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#886cc0]/50 transition">
        </div>
        
        <div class="flex w-full md:w-auto">
            <a href="{{ route('users.create') }}" class="px-4 py-2 bg-[#1e9d8b] hover:bg-[#167d6f] text-white rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-md shadow-teal-500/20">
                <span class="material-symbols-outlined text-[18px]">add</span> Thêm người dùng
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="px-6 py-4 font-bold">Người dùng</th>
                    <th class="px-6 py-4 font-bold">Email đăng nhập</th>
                    <th class="px-6 py-4 font-bold">Quyền hạn (Menu)</th>
                    <th class="px-6 py-4 font-bold text-center">Trạng thái</th>
                    <th class="px-6 py-4 font-bold text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="text-sm divide-y divide-gray-100">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-purple-100 text-[#886cc0] flex items-center justify-center font-bold">
                                {{ mb_substr($user->name, 0, 1) }}
                            </div>
                            <span class="font-semibold text-gray-800">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            @if($user->hasRole('Super Admin'))
                                <span class="px-2.5 py-1 bg-red-50 text-red-600 border border-red-100 rounded-md text-[11px] font-bold tracking-wide">TẤT CẢ QUYỀN (GIÁM ĐỐC)</span>
                            @elseif($user->permissions->count() > 0)
                                @foreach($user->permissions as $perm)
                                    <span class="px-2 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-md text-[11px] font-semibold">
                                        {{ $tenQuyenTiengViet[$perm->name] ?? $perm->name }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-gray-400 italic text-xs">Chưa cấp quyền</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Hoạt động
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition" title="Xóa tài khoản">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
                
                @if($users->isEmpty())
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">
                        Chưa có nhân viên nào được tạo.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection