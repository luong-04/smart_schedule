@extends('layouts.admin')
@section('title', 'Danh mục Giáo viên')

@section('content')
<div x-data="{ searchQuery: '', selectedTeachers: [] }" class="space-y-6">
    
    <form action="{{ route('teachers.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden">
        @csrf @method('DELETE')
        <template x-for="id in selectedTeachers" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div class="bg-white p-6 md:p-8 rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
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
                    x-transition style="display: none;"
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
    </div>

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
                        <th class="px-6 py-6 w-12 text-center border-r border-slate-50 whitespace-nowrap">
                            <input type="checkbox" 
                                @change="
                                    let allIds = {{ $allIdsJson }};
                                    if($event.target.checked) {
                                        selectedTeachers = [...new Set([...selectedTeachers, ...allIds])];
                                    } else {
                                        selectedTeachers = selectedTeachers.filter(id => !allIds.includes(id));
                                    }
                                "
                                :checked="{{ $teachers->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedTeachers.includes(id))"
                                class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                        </th>
                        <th class="px-6 py-6 whitespace-nowrap">Mã định danh</th>
                        <th class="px-8 py-6 whitespace-nowrap">Họ và tên</th>
                        <th class="px-8 py-6 text-center whitespace-nowrap">Định mức/Tuần</th>
                        <th class="px-8 py-6 text-center whitespace-nowrap text-blue-500">Số lớp dạy</th>
                        <th class="px-8 py-6 text-center whitespace-nowrap">Tải trọng phân công</th>
                        <th class="px-8 py-6 text-center whitespace-nowrap">Trạng thái nghỉ</th>
                        <th class="px-8 py-6 text-right whitespace-nowrap">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    @forelse($teachers as $t)
                    <tr x-show="searchQuery === '' || `{{ $t->name }}`.toLowerCase().includes(searchQuery.toLowerCase()) || `{{ $t->code }}`.toLowerCase().includes(searchQuery.toLowerCase())" 
                        class="hover:bg-blue-50/20 transition-all group"
                        :class="selectedTeachers.includes({{ $t->id }}) ? 'bg-blue-50/40' : ''">
                        
                        <td class="px-6 py-5 text-center border-r border-slate-50 whitespace-nowrap">
                            <input type="checkbox" value="{{ $t->id }}" x-model="selectedTeachers" 
                                   class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                        </td>

                        <td class="px-6 py-5 font-black text-slate-600 uppercase whitespace-nowrap">{{ $t->code }}</td>
                        <td class="px-8 py-5 font-bold text-slate-700 whitespace-nowrap">{{ $t->name }}</td>
                        
                        <td class="px-8 py-5 text-center whitespace-nowrap">
                            <span class="font-black text-slate-600">{{ $t->max_slots_week }}</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">Tiết</span>
                        </td>

                        <td class="px-8 py-5 text-center whitespace-nowrap">
                            <span class="bg-blue-50 text-blue-600 px-4 py-1.5 rounded-xl font-black text-[10px] uppercase border border-blue-100">
                                {{ $t->assignments->count() ?? 0 }} Lớp
                            </span>
                        </td>
                        
                        <td class="px-8 py-5 text-center whitespace-nowrap">
                            @php
                                $percent = $t->max_slots_week > 0 ? ($t->total_assigned_slots / $t->max_slots_week) * 100 : 0;
                                $color = $percent > 100 ? 'text-red-600 border-red-200 bg-red-50' : 'text-emerald-600 border-emerald-200 bg-emerald-50';
                            @endphp
                            <span class="{{ $color }} px-4 py-1.5 rounded-xl font-black text-[10px] uppercase border">
                                Đã xếp {{ $t->total_assigned_slots }} Tiết
                            </span>
                        </td>
                        
                        <td class="px-8 py-5 text-center whitespace-nowrap">
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
                        
                        <td class="px-8 py-5 text-right whitespace-nowrap">
                            <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('teachers.edit', $t->id) }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-blue-500 font-bold text-[10px] uppercase tracking-widest hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]">edit_note</span> Sửa
                                </a>
                                <form action="{{ route('teachers.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Xác nhận xóa hồ sơ giáo viên này?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-red-400 font-bold text-[10px] uppercase tracking-widest hover:bg-red-500 hover:text-white hover:border-red-500 transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-[14px]">delete</span> Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-8 py-20 text-center">
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    // 1. Script Import Giáo Viên
    function handleImportTeachers(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet);
            
            let parsedData = jsonData.map(row => {
                let cleanRow = {};
                for (let key in row) cleanRow[key.trim().toLowerCase()] = row[key];
                return {
                    code: cleanRow['mã gv'] || cleanRow['mã'] || '',
                    name: cleanRow['họ và tên'] || cleanRow['tên'] || '',
                    department: cleanRow['tổ chuyên môn'] || cleanRow['tổ'] || 'Chưa phân tổ',
                    max_slots_week: parseInt(cleanRow['định mức'] || cleanRow['số tiết'] || 18)
                };
            });

            if(parsedData.length > 0) {
                document.getElementById('importDataTeachers').value = JSON.stringify(parsedData);
                document.getElementById('importFormTeachers').submit();
            }
        };
        reader.readAsArrayBuffer(file);
    }

    // 2. Script Import Phân Công
    function handleImportAssignments(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet);
            
            let parsedData = jsonData.map(row => {
                let cleanRow = {};
                for (let key in row) cleanRow[key.trim().toLowerCase()] = row[key];
                
                return {
                    teacher_code: cleanRow['mã gv'] || cleanRow['mã'] || '',
                    class_name: cleanRow['lớp'] || cleanRow['tên lớp'] || '',
                    subject_name: cleanRow['môn'] || cleanRow['tên môn'] || ''
                };
            });

            // Lọc bỏ dòng trống
            parsedData = parsedData.filter(item => item.teacher_code !== '' && item.class_name !== '' && item.subject_name !== '');

            if(parsedData.length > 0) {
                document.getElementById('importDataAssignments').value = JSON.stringify(parsedData);
                document.getElementById('importFormAssignments').submit();
            } else {
                alert("File Excel trống hoặc không đúng định dạng cột (Cần có 3 cột: Mã GV, Lớp, Môn)!");
            }
        };
        reader.readAsArrayBuffer(file);
    }
</script>
@endsection