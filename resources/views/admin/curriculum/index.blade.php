@extends('layouts.admin')
@section('title', 'Chương trình học')
@section('content')

<div x-data="{ activeGrade: 10, activeBlock: 'KHTN' }" class="space-y-6">
    
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

            <a href="{{ route('curriculum.create') }}" class="bg-blue-600 text-white px-8 py-4 rounded-3xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all text-center">
                + Thêm môn học vào khối
            </a>
        </div>

        <div class="px-5 pb-4">
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
                        <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-6">Môn học</th>
                                <th class="px-8 py-6">Loại hình</th>
                                <th class="px-8 py-6 text-center">Số tiết/Tuần</th>
                                <th class="px-8 py-6 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @php 
                                // Lọc dữ liệu: Lấy đúng môn của Khối này VÀ Tổ hợp này
                                $allConfigsForGrade = $groupedConfigs->get($grade) ?? collect(); 
                                $configs = $allConfigsForGrade->where('block', $blockName);
                            @endphp
                            
                            @forelse($configs as $c)
                            <tr class="hover:bg-blue-50/20 transition-all group">
                                <td class="px-8 py-5">
                                    <span class="font-black text-slate-700 uppercase tracking-tight">{{ $c->subject->name }}</span>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase {{ $c->subject->type == 'theory' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                                        {{ $c->subject->type == 'theory' ? 'Lý thuyết' : 'Thực hành' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-50 font-black text-blue-600 text-lg border border-slate-100">
                                        {{ $c->slots_per_week }}
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('curriculum.edit', $c->id) }}" class="p-2 bg-slate-50 rounded-xl text-blue-500 hover:bg-blue-500 hover:text-white transition-all shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </a>
                                        <form action="{{ route('curriculum.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Xóa định mức môn này?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 bg-slate-50 rounded-xl text-red-400 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-30">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
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

@endsection