@extends('layouts.admin')
@section('title', 'Danh mục Giáo viên')
@section('content')
<div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-8 border-b border-slate-50 flex justify-between items-center">
        <div>
            <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách cán bộ giảng dạy</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Quản lý hồ sơ và định mức tiết dạy</p>
        </div>
        <a href="{{ route('teachers.create') }}" class="bg-blue-600 text-white px-8 py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">
            + Thêm giáo viên mới
        </a>
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
                @foreach($teachers as $t)
                <tr class="hover:bg-blue-50/10 transition-all group">
                    <td class="px-8 py-5 font-black text-blue-600 uppercase">{{ $t->code }}</td>
                    <td class="px-8 py-5 font-bold text-slate-700">{{ $t->name }}</td>
                    <td class="px-8 py-5 text-center">
                        <span class="font-black text-slate-600">{{ $t->max_slots_week }}</span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">Tiết</span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <span class="bg-blue-50 text-blue-600 px-4 py-1.5 rounded-xl font-black text-[10px] uppercase">
                            {{ $t->assignments_count }} Lớp
                        </span>
                    </td>
                    <td class="px-8 py-5 text-center">
                        <div class="flex justify-center gap-1">
                            @if($t->off_days)
                                @foreach($t->off_days as $day)
                                    <span class="size-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-[8px] font-black">T{{ $day }}</span>
                                @endforeach
                            @else
                                <span class="text-[9px] font-bold text-slate-300 uppercase italic">Chưa đăng ký</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-3">
                            <a href="{{ route('teachers.edit', $t->id) }}" class="p-2 bg-slate-50 rounded-xl text-blue-500 hover:bg-blue-600 hover:text-white transition-all shadow-sm">
                                <span class="material-symbols-outlined text-sm">edit_note</span>
                            </a>
                            <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa hồ sơ giáo viên này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 bg-slate-50 rounded-xl text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection