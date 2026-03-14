@extends('layouts.admin')

@section('title', 'Cơ sở vật chất')

@section('content')
<div class="grid grid-cols-12 gap-6">
    <div class="col-span-12 lg:col-span-4 space-y-6">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden flex flex-col h-full">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-white">
                <div>
                    <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Loại hình phòng</h3>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Danh mục chức năng</p>
                </div>
                <a href="{{ route('room-types.create') }}" class="bg-blue-600 text-white p-2.5 rounded-xl shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
            </div>

            <div class="p-6 space-y-3 flex-1 overflow-y-auto">
                @forelse($roomTypes as $type)
                <div class="flex justify-between items-center p-4 bg-slate-50/50 border border-transparent hover:border-blue-100 hover:bg-white rounded-2xl group transition-all">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full shadow-sm shadow-blue-200"></div>
                        <span class="text-xs font-black text-slate-600 uppercase tracking-tight">{{ $type->name }}</span>
                    </div>
                    <div class="flex gap-3 opacity-0 group-hover:opacity-100 transition-all">
                        <a href="{{ route('room-types.edit', $type->id) }}" class="text-blue-500 hover:text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </a>
                        <form action="{{ route('room-types.destroy', $type->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa loại phòng này?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-300 hover:text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-10 opacity-20">
                    <p class="text-[10px] font-black uppercase tracking-widest">Trống</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-span-12 lg:col-span-8">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách phòng chi tiết</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Vị trí & Cơ sở vật chất</p>
                </div>
                <a href="{{ route('rooms.create') }}" class="bg-slate-900 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-slate-100">
                    + Tạo phòng mới
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        <tr>
                            <th class="px-8 py-5">Phòng</th>
                            <th class="px-8 py-5">Công năng</th>
                            <th class="px-8 py-5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        @forelse($rooms as $room)
                        <tr class="hover:bg-blue-50/20 transition-all group">
                            <td class="px-8 py-5 font-black text-slate-700 uppercase tracking-tight">{{ $room->name }}</td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase bg-blue-50 text-blue-600 border border-blue-100 shadow-sm">
                                    {{ $room->roomType->name }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-4">
                                    <a href="{{ route('rooms.edit', $room->id) }}" class="text-slate-400 hover:text-blue-600 font-black text-[10px] uppercase tracking-widest transition-colors">Sửa</a>
                                    <form action="{{ route('rooms.destroy', $room->id) }}" method="POST" onsubmit="return confirm('Xóa phòng này?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-500 font-black text-[10px] uppercase tracking-widest transition-colors">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-8 py-16 text-center">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-300 italic">Chưa có dữ liệu phòng</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-8 py-4 bg-slate-50/30 border-t border-slate-50">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Dữ liệu thời thực: {{ $rooms->count() }} phòng đang quản lý</p>
            </div>
        </div>
    </div>
</div>
@endsection