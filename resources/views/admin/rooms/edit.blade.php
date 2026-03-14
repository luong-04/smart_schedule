@extends('layouts.admin')
@section('title', 'Cập nhật Phòng học')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-blue-50">
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('rooms.index') }}" class="p-2 bg-slate-100 rounded-full hover:bg-slate-200 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h3 class="font-black text-slate-700 uppercase text-xs tracking-widest">Sửa phòng: {{ $room->name }}</h3>
        </div>

        <form action="{{ route('rooms.update', $room->id) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Tên phòng</label>
                <input type="text" name="name" value="{{ $room->name }}" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2 mb-2 block">Cập nhật loại phòng</label>
                <select name="room_type_id" required class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-blue-500 appearance-none">
                    @foreach($roomTypes as $type)
                        <option value="{{ $type->id }}" {{ $room->room_type_id == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pt-6 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white font-bold px-12 py-4 rounded-2xl shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all">
                    Lưu cập nhật
                </button>
            </div>
        </form>
    </div>
</div>
@endsection