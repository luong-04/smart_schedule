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

<div x-data="{ searchQuery: '', selectedUsers: [] }" class="space-y-6">
    
    <form action="{{ route('users.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden" hx-boost="false">
        @csrf @method('DELETE')
        <template x-for="id in selectedUsers" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Người dùng & Phân quyền</h2>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1 tracking-widest">Hệ thống / <span class="text-blue-600">Nhân viên</span></p>
        </div>
        
        <div class="flex items-center gap-3">
            <button x-show="selectedUsers.length > 0" 
                    @click="if(confirm('CẢNH BÁO: Bạn sắp xóa ' + selectedUsers.length + ' tài khoản nhân viên. Thao tác này không thể hoàn tác. Tiếp tục?')) document.getElementById('bulkDeleteForm').requestSubmit()"
                    x-transition
                    class="bg-red-500 text-white px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-red-200 hover:bg-red-600 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Xóa (<span x-text="selectedUsers.length"></span>)
            </button>

            <a href="{{ route('users.create') }}" class="px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 shadow-xl shadow-emerald-200">
                <span class="material-symbols-outlined text-[18px]">add</span> Thêm nhân viên
            </a>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden">
        
        <div class="p-6 border-b border-slate-50 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/30">
            <div class="relative w-full md:w-80">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input x-model="searchQuery" type="text" placeholder="Tìm tên hoặc email..." 
                       class="w-full bg-white border-none rounded-2xl pl-11 pr-5 py-3.5 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-sm outline-none transition-all placeholder:font-medium">
            </div>
            
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Tổng số: {{ $users->count() }} tài khoản
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white text-slate-400 text-[10px] font-black uppercase tracking-widest border-b border-slate-100">
                        @php 
                            $allIdsJson = $users->pluck('id')->toJson();
                        @endphp
                        <th class="px-6 py-5 w-12 text-center">
                            <input type="checkbox" 
                                @change="
                                    let allIds = {{ $allIdsJson }}.map(id => String(id));
                                    if($event.target.checked) {
                                        selectedUsers = [...new Set([...selectedUsers, ...allIds])];
                                    } else {
                                        selectedUsers = selectedUsers.filter(id => !allIds.includes(String(id)));
                                    }
                                "
                                :checked="{{ $users->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedUsers.includes(String(id)))"
                                class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-5 font-black whitespace-nowrap">Nhân viên</th>
                        <th class="px-6 py-5 font-black whitespace-nowrap">Email đăng nhập</th>
                        <th class="px-6 py-5 font-black">Quyền hạn hệ thống</th>
                        <th class="px-6 py-5 font-black text-center whitespace-nowrap">Trạng thái</th>
                        <th class="px-8 py-5 font-black text-right whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-50">
                    @foreach($users as $user)
                    <tr x-show="searchQuery === '' || `{{ $user->name }}`.toLowerCase().includes(searchQuery.toLowerCase()) || `{{ $user->email }}`.toLowerCase().includes(searchQuery.toLowerCase())"
                        class="hover:bg-blue-50/20 transition-all group"
                        :class="selectedUsers.includes('{{ $user->id }}') ? 'bg-blue-50/40' : ''">
                        
                        <td class="px-6 py-5 text-center">
                            <input type="checkbox" value="{{ $user->id }}" x-model="selectedUsers" 
                                   class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-black text-xs uppercase shadow-inner">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-bold text-slate-700 tracking-tight">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500 font-medium">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @if($user->hasRole('Super Admin'))
                                    <span class="px-3 py-1 bg-red-50 text-red-600 border border-red-100 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm">Super Admin</span>
                                @elseif($user->permissions->count() > 0)
                                    @foreach($user->permissions as $perm)
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-[9px] font-black uppercase shadow-sm">
                                            {{ $tenQuyenTiengViet[$perm->name] ?? $perm->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-slate-400 italic text-xs font-medium">Chưa cấp quyền</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Hoạt động
                            </span>
                        </td>
                        <td class="px-8 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all">
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                    class="inline" hx-boost="false" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                        class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all" title="Xóa tài khoản">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    
                    @if($users->isEmpty())
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <span class="material-symbols-outlined text-5xl text-slate-200 mb-3 block">person_off</span>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Không tìm thấy tài khoản nhân viên</p>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection