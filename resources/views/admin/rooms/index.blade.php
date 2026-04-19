@extends('layouts.admin')

@section('title', 'Cơ sở vật chất')

@section('content')
    <div x-data="{ selectedRoomTypes: [], selectedRooms: [] }" class="grid grid-cols-12 gap-6">

        <form action="{{ route('room-types.bulkDelete') }}" method="POST" id="bulkDeleteRoomTypesForm" class="hidden"
            hx-boost="false">
            @csrf @method('DELETE')
            <template x-for="id in selectedRoomTypes" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>

        <form action="{{ route('rooms.bulkDelete') }}" method="POST" id="bulkDeleteRoomsForm" class="hidden"
            hx-boost="false">
            @csrf @method('DELETE')
            <template x-for="id in selectedRooms" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>

        <div class="col-span-12 lg:col-span-4 space-y-6">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden flex flex-col h-full">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-white">
                    <div>
                        <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">Loại hình phòng</h3>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Danh mục chức năng</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button x-show="selectedRoomTypes.length > 0"
                            @click="if(confirm('Xóa ' + selectedRoomTypes.length + ' loại phòng? Lưu ý: Chỉ xóa được loại phòng ĐANG TRỐNG.')) document.getElementById('bulkDeleteRoomTypesForm').requestSubmit()"
                            x-transition
                            class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white p-2.5 rounded-xl transition-all border border-red-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-[16px]">delete_sweep</span>
                        </button>

                        <a href="{{ route('room-types.create') }}"
                            class="bg-blue-600 text-white p-2.5 rounded-xl shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all flex items-center justify-center">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                        </a>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                @php $allTypeIds = $roomTypes->pluck('id')->toJson(); @endphp
                                <th class="p-4 w-12 text-center">
                                    <input type="checkbox" @change="
                                                let ids = {{ $allTypeIds }}.map(id => String(id));
                                                if($event.target.checked) selectedRoomTypes = [...new Set([...selectedRoomTypes, ...ids])];
                                                else selectedRoomTypes = selectedRoomTypes.filter(id => !ids.includes(String(id)));
                                            "
                                        :checked="{{ $roomTypes->count() > 0 ? 'true' : 'false' }} && {{ $allTypeIds }}.every(id => selectedRoomTypes.includes(String(id)))"
                                        class="w-3.5 h-3.5 text-blue-600 rounded border-slate-300">
                                </th>
                                <th class="py-4">Tên loại phòng</th>
                                <th class="p-4 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-xs">
                            @forelse($roomTypes as $type)
                                <tr class="hover:bg-blue-50/30 transition-colors group"
                                    :class="selectedRoomTypes.includes('{{ $type->id }}') ? 'bg-blue-50/50' : ''">
                                    <td class="p-4 text-center">
                                        <input type="checkbox" value="{{ $type->id }}" x-model="selectedRoomTypes"
                                            class="w-3.5 h-3.5 text-blue-600 rounded border-slate-300">
                                    </td>
                                    <td class="py-4">
                                        <span
                                            class="font-black text-blue-700 uppercase tracking-widest">{{ $type->name }}</span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div
                                            class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('room-types.edit', $type->id) }}"
                                                class="text-blue-500 hover:text-blue-700">
                                                <span class="material-symbols-outlined text-[16px]">edit</span>
                                            </a>
                                            <form action="{{ route('room-types.destroy', $type->id) }}" method="POST"
                                                class="inline" hx-boost="false" onsubmit="return confirm('Xác nhận xóa loại phòng này? Lưu ý: Chỉ xóa được nếu loại phòng không chứa phòng nào.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-400 hover:text-red-600 transition-colors">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-10 text-center text-slate-400">
                                        <p class="text-xs font-black uppercase tracking-widest italic">Chưa có loại phòng</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">
                        {{ $roomTypes->count() }} LOẠI PHÒNG
                    </p>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden flex flex-col h-full">
                <div class="p-6 md:p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-2xl shadow-inner">
                            <span class="material-symbols-outlined">meeting_room</span>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Phòng học thực tế</h3>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Danh sách phòng dùng để xếp TKB
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button x-show="selectedRooms.length > 0"
                            @click="if(confirm('Xóa ' + selectedRooms.length + ' phòng học? Nếu các phòng này đã được xếp trong TKB, lịch học của môn đó sẽ bị mất phòng.')) document.getElementById('bulkDeleteRoomsForm').requestSubmit()"
                            x-transition
                            class="bg-red-500 text-white px-5 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Xóa (<span
                                x-text="selectedRooms.length"></span>)
                        </button>

                        <a href="{{ route('rooms.create') }}"
                            class="bg-emerald-500 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">add</span> Thêm Phòng
                        </a>
                    </div>
                </div>

                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead
                            class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                @php $allRoomIds = $rooms->pluck('id')->toJson(); @endphp
                                <th class="px-8 py-5 w-12 text-center">
                                    <input type="checkbox" @change="
                                                let ids = {{ $allRoomIds }}.map(id => String(id));
                                                if($event.target.checked) selectedRooms = [...new Set([...selectedRooms, ...ids])];
                                                else selectedRooms = selectedRooms.filter(id => !ids.includes(String(id)));
                                            "
                                        :checked="{{ $rooms->count() > 0 ? 'true' : 'false' }} && {{ $allRoomIds }}.every(id => selectedRooms.includes(String(id)))"
                                        class="w-4 h-4 text-blue-600 rounded border-slate-300">
                                </th>
                                <th class="py-5 px-4">Tên phòng học</th>
                                <th class="px-8 py-5 text-center">Thuộc loại phòng</th>
                                <th class="px-8 py-5 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            @forelse($rooms as $room)
                                <tr id="room-{{ $room->id }}" class="hover:bg-blue-50/20 transition-colors group"
                                    :class="selectedRooms.includes('{{ $room->id }}') ? 'bg-blue-50/40' : ''">
                                    <td class="px-8 py-5 text-center">
                                        <input type="checkbox" value="{{ $room->id }}" x-model="selectedRooms"
                                            class="w-4 h-4 text-blue-600 rounded border-slate-300">
                                    </td>
                                    <td class="py-5 px-4 font-black text-slate-700 tracking-wider">
                                        PHÒNG {{ $room->name }}
                                    </td>
                                    <td class="px-8 py-5 text-center">
                                        <span
                                            class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest">
                                            {{ $room->roomType->name }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div
                                            class="flex justify-end gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('rooms.edit', $room->id) }}"
                                                class="text-slate-400 hover:text-blue-600 font-black text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px]">edit</span> Sửa
                                            </a>
                                            <form action="{{ route('rooms.destroy', $room->id) }}" method="POST"
                                                class="inline" hx-boost="false" onsubmit="return confirm('Xác nhận xóa phòng này?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-slate-400 hover:text-red-500 font-black text-[10px] uppercase tracking-widest transition-colors flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[16px]">delete</span> Xóa
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-8 py-20 text-center">
                                        <span
                                            class="material-symbols-outlined text-6xl text-slate-200 mb-3 block">sensor_door</span>
                                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Chưa có dữ liệu
                                            phòng học</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-8 py-4 bg-slate-50/50 border-t border-slate-100">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest text-right">Dữ liệu thời gian
                        thực: {{ $rooms->count() }} phòng đang quản lý</p>
                </div>
            </div>
        </div>
    </div>
@endsection