@extends('layouts.admin')
@section('title', 'Tạo lớp học mới')
@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white p-8 rounded-3xl shadow-sm border border-blue-50">
        <h3 class="font-black text-slate-700 uppercase text-xs mb-8 tracking-widest">Thiết lập lớp học</h3>
        <form action="{{ route('classrooms.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tên lớp</label>
                <input type="text" name="name" required placeholder="VD: 10A1" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Khối lớp</label>
                    <select name="grade" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="10">Khối 10</option>
                        <option value="11">Khối 11</option>
                        <option value="12">Khối 12</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tổ hợp (Ban)</label>
                    <select name="block" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="KHTN">Khoa học Tự nhiên</option>
                        <option value="KHXH">Khoa học Xã hội</option>
                        <option value="Cơ bản">Cơ bản / Khác</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Ca học</label>
                    <select name="shift" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="morning">Ca Sáng</option>
                        <option value="afternoon">Ca Chiều</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Giáo viên chủ nhiệm (Có thể bỏ trống)</label>
                <select name="homeroom_teacher" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Không phân công --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->name }}">{{ $teacher->name }} ({{ $teacher->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white font-bold px-12 py-4 rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all uppercase text-[10px] tracking-widest">
                    Thêm lớp học
                </button>
            </div>
        </form>
    </div>
</div>
@endsection