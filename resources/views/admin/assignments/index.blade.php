@extends('layouts.admin')

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <div class="w-full lg:w-1/3 bg-slate-50 p-6 rounded-3xl border border-blue-100">
        <h4 class="text-lg font-bold text-slate-800 mb-4 px-2">Phân công mới</h4>
        <form action="{{ route('assignments.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase ml-2 mb-1">Giáo viên</label>
                <select name="teacher_id" class="w-full p-3 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($teachers as $t) <option value="{{ $t->id }}">{{ $t->name }}</option> @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase ml-2 mb-1">Lớp học</label>
                <select name="class_id" class="w-full p-3 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($classes as $c) <option value="{{ $c->id }}">{{ $c->name }} (Khối {{ $c->grade }})</option> @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase ml-2 mb-1">Môn học</label>
                    <select name="subject_id" class="w-full p-3 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500">
                        @foreach($subjects as $s) <option value="{{ $s->id }}">{{ $s->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase ml-2 mb-1">Số tiết/Tuần</label>
                    <input type="number" name="slots_per_week" value="1" class="w-full p-3 bg-white border-none rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all mt-4">
                XÁC NHẬN PHÂN CÔNG
            </button>
        </form>
    </div>

    <div class="flex-1 bg-white rounded-3xl shadow-sm p-6 border border-blue-50">
        <h4 class="text-lg font-bold text-slate-800 mb-6 px-2">Dữ liệu giảng dạy thực tế</h4>
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-y-2">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase tracking-widest">
                        <th class="px-4 py-2 text-left">Giáo viên</th>
                        <th class="px-4 py-2 text-left">Môn học</th>
                        <th class="px-4 py-2 text-left">Lớp</th>
                        <th class="px-4 py-2 text-center">Tiết/T</th>
                        <th class="px-4 py-2 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $a)
                    <tr class="bg-slate-50 hover:bg-blue-50 transition-all rounded-2xl group">
                        <td class="px-4 py-4 rounded-l-2xl font-bold text-slate-700">{{ $a->teacher->name }}</td>
                        <td class="px-4 py-4"><span class="px-3 py-1 bg-white text-blue-600 rounded-lg text-xs font-bold shadow-sm">{{ $a->subject->name }}</span></td>
                        <td class="px-4 py-4 text-slate-600 font-medium">{{ $a->classroom->name }}</td>
                        <td class="px-4 py-4 text-center font-black text-blue-500">{{ $a->slots_per_week }}</td>
                        <td class="px-4 py-4 rounded-r-2xl text-right uppercase text-[10px] font-bold text-red-400 hover:text-red-600 cursor-pointer">Gỡ bỏ</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection