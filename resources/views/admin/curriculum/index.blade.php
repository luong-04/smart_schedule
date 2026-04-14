@extends('layouts.admin')
@section('title', 'Chương trình học')
@section('content')

<div x-data="{ activeGrade: 10, activeBlock: 'KHTN', selectedConfigs: [] }" class="space-y-6">
    
    <form action="{{ route('curriculum.bulkDelete') }}" method="POST" id="bulkDeleteForm" class="hidden">
        @csrf @method('DELETE')
        <template x-for="id in selectedConfigs" :key="id">
            <input type="hidden" name="ids[]" :value="id">
        </template>
    </form>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 p-2">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4">
            
            <div class="flex bg-slate-100 p-1.5 rounded-[2rem] gap-1">
                @foreach([10, 11, 12] as $grade)
                <button @click="activeGrade = {{ $grade }}" 
                    :class="activeGrade === {{ $grade }} ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="px-8 py-3 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
                    Khối {{ $grade }}
                </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button x-show="selectedConfigs.length > 0" 
                        @click="if(confirm('CẢNH BÁO: Bạn sắp xóa ' + selectedConfigs.length + ' định mức chương trình học. Điều này có thể ảnh hưởng đến số tiết Ma trận. Bạn có chắc chắn không?')) document.getElementById('bulkDeleteForm').submit()"
                        x-transition
                        class="bg-red-500 text-white px-6 py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-200 hover:bg-red-600 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">delete_sweep</span> Xóa (<span x-text="selectedConfigs.length"></span>)
                </button>

                <form action="{{ route('curriculum.import') }}" method="POST" id="importFormCurriculum" class="hidden">
                    @csrf <input type="hidden" name="import_data" id="importDataCurriculum">
                </form>
                <input type="file" id="excelFileCurriculum" class="hidden" accept=".xlsx, .xls" onchange="handleImportCurriculum(event)">
                <button onclick="document.getElementById('excelFileCurriculum').click()" class="bg-emerald-500 text-white px-8 py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-emerald-100 hover:bg-emerald-600 transition-all text-center flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">upload_file</span> Import
                </button>

                <a href="{{ route('curriculum.create') }}" class="bg-blue-600 text-white px-8 py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all text-center flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span> Thêm định mức
                </a>
            </div>
        </div>

        <div class="px-5 pb-4 border-t border-slate-50 pt-4 mt-2">
            <div class="flex flex-wrap gap-2">
                @foreach(['KHTN', 'KHXH', 'Cơ bản'] as $b)
                <button @click="activeBlock = '{{ $b }}'" 
                    :class="activeBlock === '{{ $b }}' ? 'bg-emerald-500 text-white shadow-md' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'"
                    class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all border border-emerald-100">
                    Tổ hợp {{ $b }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    @foreach([10, 11, 12] as $grade)
        @foreach(['KHTN', 'KHXH', 'Cơ bản'] as $blockName)
        
        <div x-show="activeGrade === {{ $grade }} && activeBlock === '{{ $blockName }}'" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform translate-y-4" 
             x-transition:enter-end="opacity-100 transform translate-y-0" 
             style="display: none;">
             
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden">
                <div class="p-8 border-b border-slate-50 bg-slate-50/30">
                    <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                        Chương trình Khối {{ $grade }} - Tổ hợp {{ $blockName }}
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                            <tr>
                                @php 
                                    $allConfigsForGrade = $groupedConfigs->get($grade) ?? collect(); 
                                    $configs = $allConfigsForGrade->where('block', $blockName);
                                    $allIdsJson = $configs->pluck('id')->toJson();
                                @endphp
                                <th class="px-6 py-6 w-12 text-center border-r border-slate-50">
                                    <input type="checkbox" 
                                        @change="
                                            let allIds = {{ $allIdsJson }};
                                            if($event.target.checked) {
                                                selectedConfigs = [...new Set([...selectedConfigs, ...allIds])];
                                            } else {
                                                selectedConfigs = selectedConfigs.filter(id => !allIds.includes(id));
                                            }
                                        "
                                        :checked="{{ $configs->count() > 0 ? 'true' : 'false' }} && {{ $allIdsJson }}.every(id => selectedConfigs.includes(id))"
                                        class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer">
                                </th>
                                <th class="px-8 py-6">Môn học</th>
                                <th class="px-8 py-6">Loại hình</th>
                                <th class="px-8 py-6 text-center">Số tiết/Tuần</th>
                                <th class="px-8 py-6 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($configs as $c)
                            <tr class="hover:bg-blue-50/20 transition-all group" :class="selectedConfigs.includes({{ $c->id }}) ? 'bg-blue-50/40' : ''">
                                
                                <td class="px-6 py-5 text-center border-r border-slate-50">
                                    <input type="checkbox" value="{{ $c->id }}" x-model="selectedConfigs" 
                                           class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 cursor-pointer transition-all">
                                </td>

                                <td class="px-8 py-5">
                                    <span class="font-black text-slate-700 uppercase tracking-tight">{{ $c->subject->name }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase {{ $c->subject->type == 'theory' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                                        {{ $c->subject->type == 'theory' ? 'Lý thuyết' : 'Thực hành' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-50 font-black text-blue-600 text-lg border border-slate-100 shadow-inner">
                                        {{ $c->slots_per_week }}
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('curriculum.edit', $c->id) }}" class="p-2 bg-slate-50 rounded-xl text-blue-500 hover:bg-blue-500 hover:text-white transition-all shadow-sm border border-slate-100">
                                            <span class="material-symbols-outlined text-[16px]">edit</span>
                                        </a>
                                        <form action="{{ route('curriculum.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Xóa định mức môn này?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 bg-slate-50 rounded-xl text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-sm border border-slate-100">
                                                <span class="material-symbols-outlined text-[16px]">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-30">
                                        <span class="material-symbols-outlined text-5xl mb-3">auto_stories</span>
                                        <p class="text-xs font-black uppercase tracking-widest">Chưa thiết lập định mức cho Tổ hợp này</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    @endforeach
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    function handleImportCurriculum(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = (e) => { 
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            
            let jsonData = [];
            workbook.SheetNames.forEach(sheetName => {
                jsonData = jsonData.concat(XLSX.utils.sheet_to_json(workbook.Sheets[sheetName]));
            });
            
            let parsedData = jsonData.map(row => {
                let subject_name = '', grade = '', block = 'Cơ bản', slots = 2;
                
                // Thuật toán quét từ khóa thông minh (Bất chấp tên cột trong Excel)
                for (let key in row) {
                    let k = key.toString().trim().toLowerCase();
                    let val = row[key];
                    
                    if (k.includes('môn')) subject_name = val;
                    if (k.includes('khối')) grade = String(val).replace(/[^0-9]/g, '');
                    if (k.includes('tổ hợp') || k.includes('ban') || k.includes('block')) {
                        let checkStr = String(val).toUpperCase().replace(/\s/g, '');
                        if (checkStr.includes('KHTN') || checkStr.includes('TỰNHIÊN')) block = 'KHTN';
                        else if (checkStr.includes('KHXH') || checkStr.includes('XÃHỘI')) block = 'KHXH';
                    }
                    if (k.includes('tiết') || k.includes('định mức')) slots = parseInt(val);
                }

                return { subject_name, grade, block, slots };
            });

            // Lọc bỏ các dòng trống
            parsedData = parsedData.filter(item => item.subject_name !== '' && item.grade !== '');

            if(parsedData.length > 0) {
                document.getElementById('importDataCurriculum').value = JSON.stringify(parsedData);
                document.getElementById('importFormCurriculum').submit();
            } else {
                alert("Không đọc được dữ liệu! Vui lòng kiểm tra lại file Excel.");
            }
        };
        reader.readAsArrayBuffer(file);
    }
</script>

@endsection