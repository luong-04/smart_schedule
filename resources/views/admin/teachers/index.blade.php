@extends('layouts.admin')
@section('title', 'Danh mục Giáo viên')

@section('content')
<div x-data="{ searchQuery: '', selectedTeachers: [] }" class="space-y-6">
    
    <form action="{{ route('teachers.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden" hx-boost="false">
        @csrf @method('DELETE')
        <template x-for="id in selectedTeachers" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    {{-- ===== BẢNG LỖI IMPORT CHI TIẾT (thay thế flash message che màn hình) ===== --}}
    <x-admin.import-alert />
    {{-- ===== END BẢNG LỖI ===== --}}

    <x-admin.card class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        <div>
            <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách cán bộ giảng dạy</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Quản lý theo Tổ chuyên môn và Định mức tiết</p>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
            <div class="relative w-full sm:w-72 lg:w-80">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input x-model="searchQuery" type="text" placeholder="Tìm tên hoặc mã GV..." 
                       class="w-full bg-slate-50 border-none rounded-2xl pl-11 pr-5 py-3.5 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner outline-none transition-all placeholder:font-medium">
                <button x-show="searchQuery !== ''" @click="searchQuery = ''" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors" style="display: none;">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
            
            <button x-show="selectedTeachers.length > 0" 
                    @click="if(confirm('CẢNH BÁO: Bạn sắp xóa ' + selectedTeachers.length + ' giáo viên. Toàn bộ lịch dạy của họ cũng sẽ bị xóa. Bạn có chắc chắn không?')) document.getElementById('bulkDeleteForm').submit()"
                    x-transition
                    class="w-full sm:w-auto flex items-center justify-center gap-2 bg-red-500 text-white px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-red-200 hover:bg-red-600 transition-all shrink-0">
                <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Xóa (<span x-text="selectedTeachers.length"></span>)
            </button>

            <form action="{{ route('teachers.import') }}" method="POST" id="importFormTeachers" class="hidden">
                @csrf <input type="hidden" name="import_data" id="importDataTeachers">
            </form>
            <input type="file" id="excelFileTeachers" class="hidden" accept=".xlsx, .xls" onchange="handleImportTeachers(event)">
            <button onclick="document.getElementById('excelFileTeachers').click()" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-emerald-500 text-white px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-emerald-200 hover:bg-emerald-600 transition-all shrink-0">
                <span class="material-symbols-outlined text-[16px]">upload_file</span> Import GV
            </button>

            <form action="{{ route('assignments.import') }}" method="POST" id="importFormAssignments" class="hidden">
                @csrf <input type="hidden" name="import_data" id="importDataAssignments">
            </form>
            <input type="file" id="excelFileAssignments" class="hidden" accept=".xlsx, .xls" onchange="handleImportAssignments(event)">
            <button onclick="document.getElementById('excelFileAssignments').click()" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-indigo-500 text-white px-6 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-200 hover:bg-indigo-600 transition-all shrink-0">
                <span class="material-symbols-outlined text-[16px]">assignment_turned_in</span> Import Phân Công
            </button>


            <a href="{{ route('teachers.create') }}" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-blue-600 text-white px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all shrink-0">
                <span class="material-symbols-outlined text-[16px]">person_add</span> Thêm giáo viên
            </a>
        </div>
    </x-admin.card>

    @foreach($groupedTeachers as $department => $teachers)
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 bg-slate-50/50 border-b border-slate-100 flex items-center gap-3">
            <span class="w-1.5 h-6 bg-blue-600 rounded-full"></span>
            <h3 class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ $department }} <span class="text-slate-400 font-bold">({{ $teachers->count() }} GV)</span></h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-50">
                    <tr>
                        @php 
                            $allIdsJson = $teachers->pluck('id')->toJson();
                        @endphp
                        <th class="px-6 py-5 w-12 text-center border-r border-slate-50">
                            <input type="checkbox" 
                                @change="
                                    let allIds = {{ $allIdsJson }}.map(id => String(id));
                                    if($event.target.checked) {
                                        selectedTeachers = [...new Set([...selectedTeachers, ...allIds])];
                                    } else {
                                        selectedTeachers = selectedTeachers.filter(id => !allIds.includes(String(id)));
                                    }
                                "
                                :checked="{{ $teachers->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedTeachers.includes(String(id)))"
                                class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-5 whitespace-nowrap">Giáo viên</th>
                        <th class="px-6 py-5 text-center whitespace-nowrap">Thống kê phụ trách</th>
                        <th class="px-6 py-5 text-center whitespace-nowrap">Trạng thái nghỉ</th>
                        <th class="px-6 py-5 text-right whitespace-nowrap sticky right-0 bg-white z-10 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.02)]">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($teachers as $t)
                    <tr id="teacher-{{ $t->id }}" x-show="searchQuery === '' || `{{ $t->name }}`.toLowerCase().includes(searchQuery.toLowerCase()) || `{{ $t->code }}`.toLowerCase().includes(searchQuery.toLowerCase())" 
                        class="hover:bg-slate-50 transition-all group"
                        :class="selectedTeachers.includes('{{ $t->id }}') ? 'bg-blue-50/40' : ''">
                        
                        <td class="px-6 py-3 text-center border-r border-slate-50 whitespace-nowrap bg-inherit">
                            <input type="checkbox" value="{{ $t->id }}" x-model="selectedTeachers" 
                                   class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                        </td>

                        <td class="px-6 py-3 bg-inherit">
                            <p class="font-bold text-slate-700 whitespace-nowrap">{{ $t->name }}</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase mt-0.5 tracking-widest">ID: {{ $t->code }}</p>
                        </td>
                        
                        <td class="px-6 py-3 text-center bg-inherit">
                            @php
                                $percent = $t->max_slots_week > 0 ? ($t->total_assigned_slots / $t->max_slots_week) * 100 : 0;
                                $color = $percent > 100 ? 'text-red-600 border-red-200 bg-red-50' : 'text-emerald-600 border-emerald-200 bg-emerald-50';
                            @endphp
                            <div class="flex items-center justify-center gap-2">
                                <span title="Số lớp đang dạy" class="bg-blue-50 text-blue-600 px-2 py-1 rounded-lg font-black text-[10px] uppercase border border-blue-100 flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[12px]">school</span> {{ $t->assignments->count() ?? 0 }} Lớp</span>
                                <span title="Số tiết đã xếp trên tổng định mức" class="{{ $color }} px-2 py-1 rounded-lg font-black text-[10px] uppercase border flex items-center justify-center gap-1"><span class="material-symbols-outlined text-[12px]">history_edu</span> {{ $t->total_assigned_slots }}/{{ $t->max_slots_week }} Tiết</span>
                            </div>
                        </td>
                        
                        <td class="px-6 py-3 text-center bg-inherit">
                            <div class="flex justify-center gap-1">
                                @if($t->off_days)
                                    @foreach($t->off_days as $day)
                                        <span class="size-5 rounded-full bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-[8px] font-black shadow-sm">T{{ $day }}</span>
                                    @endforeach
                                @else
                                    <span class="text-[9px] font-bold text-slate-300 uppercase italic">Chưa đăng ký</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-3 text-right whitespace-nowrap sticky right-0 bg-white group-hover:bg-slate-50 transition-colors shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.02)] border-l border-slate-50">
                            <div class="flex justify-end gap-2 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('teachers.edit', $t->id) }}" class="flex items-center justify-center w-8 h-8 bg-slate-50 border border-slate-200 rounded-lg text-blue-500 hover:bg-blue-600 hover:text-white border-transparent transition-all shadow-sm" title="Sửa hồ sơ">
                                    <span class="material-symbols-outlined text-[16px]">edit_note</span>
                                </a>
                                <form action="{{ route('teachers.destroy', $t->id) }}" method="POST"
                                    class="inline" hx-boost="false" onsubmit="return confirm('Xác nhận xóa hồ sơ giáo viên này?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" 
                                        class="flex items-center justify-center w-8 h-8 bg-slate-50 border border-slate-200 rounded-lg text-red-400 hover:bg-red-500 hover:text-white border-transparent transition-all shadow-sm" title="Xóa giáo viên">
                                        <span class="material-symbols-outlined text-[16px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-20 text-center">
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">Không tìm thấy dữ liệu</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>

@endsection