@extends('layouts.admin')
@section('title', 'Quản lý Lớp học')

@section('content')

@php
    $groupedClassrooms = $classrooms->groupBy('grade');
@endphp

<div x-data="{ activeGrade: 10, activeBlock: 'all', selectedClasses: [] }" class="space-y-4 max-w-6xl mx-auto">
    
    <form action="{{ route('classrooms.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden">
        @csrf @method('DELETE')
        <template x-for="id in selectedClasses" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2 shadow-sm w-full md:w-auto">
            @foreach([10, 11, 12] as $grade)
            <button @click="activeGrade = {{ $grade }}" 
                    :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                    class="flex-1 px-8 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
                Khối lớp {{ $grade }}
            </button>
            @endforeach
        </div>

        <div>
            <form action="{{ route('classrooms.import') }}" method="POST" id="importFormClassrooms" class="hidden">
                @csrf <input type="hidden" name="import_data" id="importDataClassrooms">
            </form>
            <input type="file" id="excelFileClassrooms" class="hidden" accept=".xlsx, .xls" onchange="handleImportClassrooms(event)">
            <button onclick="document.getElementById('excelFileClassrooms').click()" class="bg-emerald-500 text-white px-6 py-3.5 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-200 hover:bg-emerald-600 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-[16px]">upload_file</span> Import Lớp học
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <button @click="activeBlock = 'all'" 
                :class="activeBlock === 'all' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
            Tất cả các lớp
        </button>
        <button @click="activeBlock = 'KHTN'" 
                :class="activeBlock === 'KHTN' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
            Khoa học Tự nhiên
        </button>
        <button @click="activeBlock = 'KHXH'" 
                :class="activeBlock === 'KHXH' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
            Khoa học Xã hội
        </button>
        <button @click="activeBlock = 'Cơ bản'" 
                :class="activeBlock === 'Cơ bản' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
            Cơ bản / Khác
        </button>
    </div>

    @foreach([10, 11, 12] as $grade)
    <div x-show="activeGrade === {{ $grade }}" x-transition style="display: none;">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden">
            
            <div class="p-6 md:p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-2xl shadow-inner">
                        <span class="material-symbols-outlined">meeting_room</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách lớp - Khối {{ $grade }}</h3>
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wider">Quản lý Tổ hợp, Phân ca và GVCN</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3">
                    <button x-show="selectedClasses.length > 0" 
                            @click="if(confirm('CẢNH BÁO: Bạn sắp xóa ' + selectedClasses.length + ' lớp học. Thao tác này sẽ xóa toàn bộ thời khóa biểu của các lớp đó. Bạn có chắc chắn không?')) document.getElementById('bulkDeleteForm').submit()"
                            x-transition
                            class="bg-red-500 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">delete_sweep</span> Xóa (<span x-text="selectedClasses.length"></span>) lớp
                    </button>

                    <a href="{{ route('classrooms.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">add</span> Thêm lớp
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            @php 
                                $classesInGrade = $groupedClassrooms->get($grade) ?? collect(); 
                                $allIdsJson = $classesInGrade->pluck('id')->toJson();
                            @endphp
                            <th class="px-6 py-5 w-12 text-center">
                                <input type="checkbox" 
                                    @change="
                                        let allIds = {{ $allIdsJson }};
                                        if($event.target.checked) {
                                            selectedClasses = [...new Set([...selectedClasses, ...allIds])];
                                        } else {
                                            selectedClasses = selectedClasses.filter(id => !allIds.includes(id));
                                        }
                                    "
                                    :checked="{{ $classesInGrade->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedClasses.includes(id))"
                                    class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                            </th>
                            <th class="px-4 py-5">Tên lớp / Tổ hợp</th>
                            <th class="px-6 py-5 text-center">Ca học</th>
                            <th class="px-6 py-5">Giáo viên Chủ nhiệm</th>
                            <th class="px-8 py-5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        @forelse($classesInGrade as $c)
                        <tr x-show="activeBlock === 'all' || activeBlock === '{{ $c->block ?? 'Cơ bản' }}'" 
                            class="hover:bg-blue-50/30 transition-all group"
                            :class="selectedClasses.includes({{ $c->id }}) ? 'bg-blue-50/50' : ''">
                            
                            <td class="px-6 py-5 text-center">
                                <input type="checkbox" value="{{ $c->id }}" x-model="selectedClasses" 
                                       class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                            </td>

                            <td class="px-4 py-5 font-black text-blue-700 uppercase text-sm tracking-wider flex items-center gap-3">
                                Lớp {{ $c->name }}
                                <span class="text-[9px] bg-blue-50 text-blue-600 px-2.5 py-1 rounded-lg border border-blue-100 tracking-widest">
                                    {{ $c->block ?? 'Cơ bản' }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-5 text-center">
                                <span class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase {{ $c->shift == 'morning' ? 'bg-orange-100 text-orange-600 border border-orange-200' : 'bg-indigo-100 text-indigo-600 border border-indigo-200' }}">
                                    {{ $c->shift == 'morning' ? 'Ca Sáng' : 'Ca Chiều' }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-5 font-bold text-slate-600">
                                @if($c->homeroom_teacher_id)
                                    <span class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-500 text-lg">person_check</span>
                                        {{ $c->homeroomTeacher->name ?? '' }}
                                    </span>
                                @else
                                    <span class="flex items-center gap-2 text-slate-400 italic text-xs">
                                        <span class="material-symbols-outlined text-slate-300 text-lg">person_off</span>
                                        Chưa phân công
                                    </span>
                                @endif
                            </td>
                            
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-4 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('classrooms.edit', $c->id) }}" class="text-blue-500 hover:text-blue-700 font-bold text-[11px] uppercase tracking-widest transition-colors flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">edit</span> Sửa
                                    </a>
                                    <form action="{{ route('classrooms.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa Lớp {{ $c->name }} không?')">
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
                            <td colspan="5" class="px-8 py-20 text-center">
                                <span class="material-symbols-outlined text-6xl text-slate-200 mb-3 block">meeting_room</span>
                                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Chưa có lớp học nào thuộc khối {{ $grade }}</p>
                                <a href="{{ route('classrooms.create') }}" class="text-[10px] font-bold text-blue-500 hover:text-blue-600 underline mt-2 block">Nhấn vào đây để thêm mới</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    function handleImportClassrooms(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => { 
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet);
            
            // "Dịch" các cột tiếng Việt sang đúng tên biến mà PHP cần
            let parsedData = jsonData.map(row => ({
                name: row['Tên lớp'] || row['Lớp'] || row['name'] || '',
                grade: row['Khối'] || row['Khối lớp'] || row['grade'] || '',
                shift: row['Ca học'] || row['Ca'] || row['shift'] || 'morning',
                homeroom_teacher: row['GVCN'] || row['Giáo viên chủ nhiệm'] || row['homeroom_teacher'] || null,
                block: row['Tổ hợp'] || row['Ban'] || row['block'] || 'Cơ bản'
            }));

            // Lọc bỏ các dòng trống
            parsedData = parsedData.filter(item => item.name !== '' && item.grade !== '');

            if(parsedData.length > 0) {
                document.getElementById('importDataClassrooms').value = JSON.stringify(parsedData);
                document.getElementById('importFormClassrooms').submit();
            } else {
                alert("File Excel trống hoặc không tìm thấy cột 'Tên lớp' và 'Khối'!");
            }
        };
        reader.readAsArrayBuffer(file);
    }
</script>

@endsection