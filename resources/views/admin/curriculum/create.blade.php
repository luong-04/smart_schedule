@extends('layouts.admin')
@section('title', 'Thiết lập Định mức')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-blue-50">
        <h3 class="font-black text-slate-700 uppercase text-xs mb-8 tracking-widest flex items-center gap-2">
            <span class="w-2 h-4 bg-blue-600 rounded-full"></span>
            Tạo định mức tiết dạy
        </h3>

        <form action="{{ route('curriculum.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Chọn Môn học</label>
                <select name="subject_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Chọn môn học --</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->type == 'theory' ? 'Lý thuyết' : 'Thực hành' }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-6">
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
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Số tiết / Tuần</label>
                <input type="number" name="slots_per_week" required min="1" max="20" placeholder="VD: 4"
                    class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>

            <div class="pt-6 flex justify-between items-center border-t border-slate-50 mt-8">
                <a href="{{ route('curriculum.index') }}" class="text-slate-400 text-xs font-bold uppercase tracking-widest hover:text-slate-600">Hủy bỏ</a>
                <button type="submit" class="bg-blue-600 text-white px-10 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">
                    Lưu định mức
                </button>
            </div>
        </form>
    </div>
</div>
@endsection