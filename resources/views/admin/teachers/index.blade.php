@extends('layouts.admin')
@section('title', 'Danh mục Giáo viên')

@section('content')
<div x-data="{ searchQuery: '' }" class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
    
    <div class="p-6 md:p-8 border-b border-slate-50 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        <div>
            <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách cán bộ giảng dạy</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Quản lý hồ sơ và định mức tiết dạy</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-72 lg:w-80">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input x-model="searchQuery" type="text" placeholder="Tìm tên hoặc mã GV..." 
                       class="w-full bg-slate-50 border-none rounded-2xl pl-11 pr-5 py-3.5 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner outline-none transition-all placeholder:font-medium">
                
                <button x-show="searchQuery !== ''" @click="searchQuery = ''" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
            
            <a href="{{ route('teachers.create') }}" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all shrink-0">
                <span class="material-symbols-outlined text-[16px]">person_add</span> Thêm giáo viên
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    <th class="px-8 py-6">Mã định danh</th>
                    <th class="px-8 py-6">Họ và tên</th>
                    <th class="px-8 py-6 text-center">Định mức/Tuần</th>
                    <th class="px-8 py-6 text-center">Số lớp đang dạy</th>
                    <th class="px-8 py-6 text-center">Trạng thái nghỉ</th>
                    <th class="px-8 py-6 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                @forelse($teachers as $t)
                <tr x-show="searchQuery === '' || `{{ $t->name }}`.toLowerCase().includes(searchQuery.toLowerCase()) || `{{ $t->code }}`.toLowerCase().includes(searchQuery.toLowerCase())" 
                    class="hover:bg-blue-50/10 transition-all group">
                    
                    <td class="px-8 py-5 font-black text-blue-600 uppercase">{{ $t->code }}</td>
                    <td class="px-8 py-5 font-bold text-slate-700">{{ $t->name }}</td>
                    
                    <td class="px-8 py-5 text-center">
                        <span class="font-black text-slate-600">{{ $t->max_slots_week }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">Tiết</span>
                    </td>
                    
                    <td class="px-8 py-5 text-center">
                        <span class="bg-blue-50 text-blue-600 px-4 py-1.5 rounded-xl font-black text-[10px] uppercase border border-blue-100">
                            {{ $t->assignments_count ?? 0 }} Lớp
                        </span>
                    </td>
                    
                    <td class="px-8 py-5 text-center">
                        <div class="flex justify-center gap-1">
                            @if($t->off_days)
                                @foreach($t->off_days as $day)
                                    <span class="size-5 rounded-full bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-[8px] font-black shadow-sm">T{{ $day }}</span>
                                @endforeach
                            @else
                                <span class="text-[9px] font-bold text-slate-300 uppercase italic">Chưa đăng ký</span>
                            @endif
                        </div>
                    </td>
                    
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('teachers.edit', $t->id) }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-blue-500 font-bold text-[10px] uppercase tracking-widest hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all shadow-sm">
                                <span class="material-symbols-outlined text-[14px]">edit_note</span> Sửa
                            </a>
                            <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa hồ sơ giáo viên này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-red-400 font-bold text-[10px] uppercase tracking-widest hover:bg-red-500 hover:text-white hover:border-red-500 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">delete</span> Xóa
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-8 py-20 text-center">
                        <span class="material-symbols-outlined text-6xl text-slate-200 mb-4 block">group_off</span>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Chưa có dữ liệu giáo viên</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection