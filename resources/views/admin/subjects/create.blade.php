@extends('layouts.admin')
@section('content')
<div class="max-w-2xl mx-auto"> <div class="bg-white p-8 rounded-3xl shadow-sm border border-blue-50">
        <h3 class="font-black text-slate-700 uppercase text-sm mb-8">Thêm môn học mới</h3>
        <form action="{{ route('subjects.store') }}" method="POST" class="space-y-6">
        @csrf
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tên môn học</label>
                <input type="text" name="name" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-600 shadow-inner">
            </div>
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Loại hình</label>
                <select name="type" class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-600 shadow-inner">
                    <option value="theory">Lý thuyết</option>
                    <option value="practice">Thực hành</option>
                </select>
            </div>
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-blue-600 text-white font-bold px-12 py-4 rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">
                    Lưu môn học
                </button>
            </div>
        </form>
    </div>
</div>
@endsection