@extends('layouts.admin')
@section('title', 'Thêm Giáo viên mới')
@section('content')
<div class="max-w-2xl mx-auto"> <div class="bg-white p-8 rounded-3xl shadow-sm border border-blue-50">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('teachers.index') }}" class="p-2 bg-slate-100 rounded-full hover:bg-slate-200 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h3 class="font-black text-slate-700 uppercase text-sm tracking-widest">Hồ sơ giáo viên mới</h3>
        </div>

        <form action="{{ route('teachers.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Mã giáo viên</label>
                <input type="text" name="code" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner uppercase" placeholder="VD: GV001">
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Họ và tên</label>
                <input type="text" name="name" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner" placeholder="VD: Nguyễn Văn A">
            </div>
            
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Định mức tiết dạy / tuần</label>
                <input type="number" name="max_slots_week" value="18" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>

            <div class="pt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white font-bold px-12 py-4 rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 active:scale-95 transition-all">
                    Lưu hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>
@endsection