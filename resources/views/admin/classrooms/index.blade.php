@extends('layouts.admin')
@section('title', 'Quản lý Lớp học')
@section('content')
<div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách lớp học</h3>
        <a href="{{ route('classrooms.create') }}" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200">
            Thêm lớp mới
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    <th class="px-6 py-4">Tên lớp</th>
                    <th class="px-6 py-4 text-center">Khối</th>
                    <th class="px-6 py-4 text-center">Ca học</th>
                    <th class="px-6 py-4">GV Chủ nhiệm</th>
                    <th class="px-6 py-4 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                @foreach($classrooms as $c)
                <tr class="hover:bg-blue-50/20 transition-all">
                    <td class="px-6 py-4 font-bold text-blue-600 uppercase">Lớp {{ $c->name }}</td>
                    <td class="px-6 py-4 text-center font-bold text-slate-500">Khối {{ $c->grade }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase {{ $c->shift == 'morning' ? 'bg-orange-100 text-orange-600' : 'bg-indigo-100 text-indigo-600' }}">
                            {{ $c->shift == 'morning' ? 'Sáng' : 'Chiều' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-slate-600 italic">
                        {{ $c->homeroom_teacher ?? 'Chưa phân công' }} </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-3">
                        <a href="{{ route('classrooms.edit', $c->id) }}" class="text-blue-500 font-bold">Sửa</a>
                        <form action="{{ route('classrooms.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa?')">
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