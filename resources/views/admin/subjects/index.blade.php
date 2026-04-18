@extends('layouts.admin')
@section('title', 'Danh mục Môn học')

@section('content')
<div x-data="{ selectedSubjects: [] }" class="bg-white rounded-[2rem] shadow-sm border border-blue-50 overflow-hidden">
    
    <form action="{{ route('subjects.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden" hx-boost="false">
        @csrf @method('DELETE')
        <template x-for="id in selectedSubjects" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div class="p-6 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách môn học</h3>
        
        <div class="flex items-center gap-3">
            <button x-show="selectedSubjects.length > 0" 
                    @click="if(confirm('CẢNH BÁO: Bạn sẽ xóa ' + selectedSubjects.length + ' môn học. Thao tác này có thể ảnh hưởng đến TKB đang xếp. Có chắc chắn xóa?')) document.getElementById('bulkDeleteForm').submit()"
                    x-transition
                    class="bg-red-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">delete_sweep</span> Xóa (<span x-text="selectedSubjects.length"></span>)
            </button>

            <form action="{{ route('subjects.import') }}" method="POST" id="importFormSubjects" class="hidden">
                @csrf <input type="hidden" name="import_data" id="importDataSubjects">
            </form>
            <input type="file" id="excelFileSubjects" class="hidden" accept=".xlsx, .xls" onchange="handleImport(event, 'subjects')">
            <button onclick="document.getElementById('excelFileSubjects').click()" class="bg-emerald-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">upload_file</span> Import
            </button>

            <a href="{{ route('subjects.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">add</span> Thêm môn học
            </a>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    @php 
                        $allIdsJson = $subjects->pluck('id')->toJson();
                    @endphp
                    <th class="px-6 py-5 w-12 text-center">
                        <input type="checkbox" 
                            @change="
                                let allIds = {{ $allIdsJson }}.map(id => String(id));
                                if($event.target.checked) {
                                    selectedSubjects = [...new Set([...selectedSubjects, ...allIds])];
                                } else {
                                    selectedSubjects = selectedSubjects.filter(id => !allIds.includes(String(id)));
                                }
                            "
                            :checked="{{ $subjects->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedSubjects.includes(String(id)))"
                            class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                    </th>
                    <th class="px-4 py-5">Tên môn học</th>
                    <th class="px-6 py-5 text-center">Phân loại</th>
                    <th class="px-6 py-5 text-center">Yêu cầu phòng</th>
                    <th class="px-8 py-5 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-sm">
                @forelse($subjects as $s)
                <tr id="subject-{{ $s->id }}" class="hover:bg-blue-50/30 transition-all group"
                    :class="selectedSubjects.includes('{{ $s->id }}') ? 'bg-blue-50/50' : ''">
                    
                    <td class="px-6 py-5 text-center">
                        <input type="checkbox" value="{{ $s->id }}" x-model="selectedSubjects" 
                               class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                    </td>

                    <td class="px-4 py-5 font-bold text-slate-700">{{ $s->name }}</td>
                    
                    <td class="px-6 py-5 text-center">
                        <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase {{ $s->type == 'theory' ? 'bg-green-100 text-green-600' : 'bg-purple-100 text-purple-600' }}">
                            {{ $s->type == 'theory' ? 'Lý thuyết' : 'Thực hành' }}
                        </span>
                    </td>
                    
                    <td class="px-6 py-5 text-center">
                        @if($s->room_type_id)
                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase bg-orange-100 text-orange-600 flex items-center justify-center gap-1 w-max mx-auto">
                                <span class="material-symbols-outlined text-[14px]">meeting_room</span> {{ $s->roomType->name }}
                            </span>
                        @else
                            <span class="text-[10px] font-black uppercase text-slate-400 italic">Phòng thường</span>
                        @endif
                    </td>
                    
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('subjects.edit', $s->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-[11px] uppercase tracking-widest transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">edit</span> Sửa
                            </a>
                            <form action="{{ route('subjects.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa môn học này?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 font-bold text-[11px] uppercase tracking-widest transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">delete</span> Xóa
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-8 py-16 text-center">
                        <span class="material-symbols-outlined text-5xl text-slate-200 mb-3">menu_book</span>
                        <p class="text-xs font-black uppercase tracking-widest text-slate-400">Chưa có môn học nào</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection