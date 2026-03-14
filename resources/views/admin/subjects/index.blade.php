@extends('layouts.admin')
@section('title', 'Danh mục Môn học')
@section('content')
<div class="bg-white rounded-3xl shadow-sm border border-blue-50 overflow-hidden">
    <div class="p-6 border-b border-slate-50 flex justify-between items-center">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách môn học</h3>
        <a href="{{ route('subjects.create') }}" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-200">
            Thêm môn học mới
        </a>
    </div>
    <table class="w-full text-left">
        <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
            <tr>
                <th class="px-6 py-4">Tên môn học</th>
                <th class="px-6 py-4 text-center">Phân loại</th>
                <th class="px-6 py-4 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 text-sm">
            @foreach($subjects as $s)
            <tr class="hover:bg-blue-50/20 transition-all">
                <td class="px-6 py-4 font-bold text-slate-700">{{ $s->name }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase {{ $s->type == 'theory' ? 'bg-green-100 text-green-600' : 'bg-purple-100 text-purple-600' }}">
                        {{ $s->type == 'theory' ? 'Lý thuyết' : 'Thực hành' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right flex justify-end gap-3">
                    <a href="{{ route('subjects.edit', $s->id) }}" class="text-blue-500 font-bold">Sửa</a>
                    <form action="{{ route('subjects.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 font-bold">Xóa</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection