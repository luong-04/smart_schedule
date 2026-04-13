@extends('layouts.admin')
@section('title', 'Cập nhật Định mức')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-blue-50">
        <h3 class="font-black text-slate-700 uppercase text-xs mb-8 tracking-widest">Chỉnh sửa định mức</h3>

        <form action="{{ route('curriculum.update', $curriculum->id) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Môn học (Không thể đổi)</label>
                <input type="text" disabled value="{{ $curriculum->subject->name }}" class="w-full bg-slate-100 border-none rounded-2xl px-5 py-4 text-sm text-slate-500 font-bold">
                <input type="hidden" name="subject_id" value="{{ $curriculum->subject_id }}">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Khối lớp</label>
                    <select name="grade" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="10" {{ $curriculum->grade == 10 ? 'selected' : '' }}>Khối 10</option>
                        <option value="11" {{ $curriculum->grade == 11 ? 'selected' : '' }}>Khối 11</option>
                        <option value="12" {{ $curriculum->grade == 12 ? 'selected' : '' }}>Khối 12</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tổ hợp (Ban)</label>
                    <select name="block" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="KHTN" {{ $curriculum->block == 'KHTN' ? 'selected' : '' }}>Khoa học Tự nhiên</option>
                        <option value="KHXH" {{ $curriculum->block == 'KHXH' ? 'selected' : '' }}>Khoa học Xã hội</option>
                        <option value="Cơ bản" {{ $curriculum->block == 'Cơ bản' ? 'selected' : '' }}>Cơ bản / Khác</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Số tiết / Tuần</label>
                <input type="number" name="slots_per_week" value="{{ $curriculum->slots_per_week }}" required min="1" max="20"
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>

            <div class="pt-6 flex justify-between items-center border-t border-slate-50 mt-8">
                <a href="{{ route('curriculum.index') }}" class="text-slate-400 text-xs font-bold uppercase tracking-widest hover:text-slate-600">Quay lại</a>
                <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">
                    Cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection