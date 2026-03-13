@extends('layouts.admin')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h3 class="text-2xl font-black text-slate-800">Danh mục Giáo viên</h3>
        <p class="text-sm text-slate-500">Quản lý thông tin và phân công giảng dạy</p>
    </div>
    <button class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:scale-105 transition-all">
        + Thêm Giáo viên mới
    </button>
</div>

<div class="overflow-x-auto">
    <table class="w-full border-separate border-spacing-y-3">
        <thead>
            <tr class="text-slate-400 text-[11px] uppercase tracking-widest">
                <th class="px-6 py-2 text-left">Mã GV</th>
                <th class="px-6 py-2 text-left">Họ và Tên</th>
                <th class="px-6 py-2">Định mức</th>
                <th class="px-6 py-2 text-left">Phân công hiện tại</th>
                <th class="px-6 py-2 text-right">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @foreach($teachers as $teacher)
            <tr class="bg-slate-50 hover:bg-white hover:shadow-xl hover:shadow-blue-100/50 transition-all group">
                <td class="px-6 py-5 rounded-l-3xl font-bold text-blue-600">{{ $teacher->code }}</td>
                <td class="px-6 py-5">
                    <p class="font-bold text-slate-700">{{ $teacher->name }}</p>
                </td>
                <td class="px-6 py-5 text-center font-semibold text-slate-500">
                    {{ $teacher->max_slots_week }} tiết/tuần
                </td>
                <td class="px-6 py-5">
                    <div class="flex flex-wrap gap-2">
                        @foreach($teacher->assignments as $assign)
                            <span class="px-3 py-1 bg-white border border-blue-100 text-blue-600 rounded-lg text-[10px] font-bold">
                                {{ $assign->subject->name }} - {{ $assign->classroom->name }}
                            </span>
                        @endforeach
                    </div>
                </td>
                <td class="px-6 py-5 rounded-r-3xl text-right">
                    <button class="p-2 text-slate-400 hover:text-blue-600 transition">Sửa</button>
                    <button class="p-2 text-slate-400 hover:text-red-500 transition">Xóa</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection