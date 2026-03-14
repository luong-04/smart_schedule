@extends('layouts.admin')
@section('title', 'Danh mục Giáo viên')
@section('content')
<div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách cán bộ giảng dạy</h3>
        <a href="{{ route('teachers.create') }}" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">
            Thêm giáo viên mới
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    <th class="px-6 py-4">Mã GV</th>
                    <th class="px-6 py-4">Họ và tên</th>
                    <th class="px-6 py-4 text-center">Định mức/Tuần</th>
                    <th class="px-6 py-4 text-center">Số lớp đang dạy</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                @foreach($teachers as $t)
                <tr class="hover:bg-blue-50/20 transition-all">
                    <td class="px-6 py-4 font-bold text-blue-600">{{ $t->code }}</td>
                    <td class="px-6 py-4 font-bold text-slate-700">{{ $t->name }}</td>
                    <td class="px-6 py-4 text-center font-bold text-slate-500">{{ $t->max_slots_week }} tiết</td>
                    <td class="px-6 py-4 text-center">
                        <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg font-black text-[10px]">{{ $t->assignments_count }} lớp</span>
                    </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-3">
                        <a href="{{ route('teachers.edit', $t->id) }}" class="text-blue-500 font-bold hover:underline">Sửa & Phân công</a>
                        <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 font-bold">Xóa</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection