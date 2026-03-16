@extends('layouts.admin')
@section('title', 'Quản lý Lớp học')

@section('content')

@php
    // Tự động gom nhóm danh sách lớp học theo Khối
    $groupedClassrooms = $classrooms->groupBy('grade');
@endphp

<div x-data="{ activeGrade: 10 }" class="space-y-6 max-w-6xl mx-auto">
    
    <div class="bg-white p-2 rounded-[2rem] border border-slate-100 flex gap-2 shadow-sm">
        @foreach([10, 11, 12] as $grade)
        <button @click="activeGrade = {{ $grade }}" 
                :class="activeGrade === {{ $grade }} ? 'bg-blue-600 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50'"
                class="flex-1 py-3.5 rounded-[1.5rem] text-xs font-black uppercase tracking-widest transition-all">
            Khối lớp {{ $grade }}
        </button>
        @endforeach
    </div>

    @foreach([10, 11, 12] as $grade)
    <div x-show="activeGrade === {{ $grade }}" x-transition style="display: none;">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-blue-50 overflow-hidden">
            
            <div class="p-6 md:p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-2xl shadow-inner">
                        <span class="material-symbols-outlined">meeting_room</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-700 uppercase tracking-widest">Danh sách lớp - Khối {{ $grade }}</h3>
                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wider">Quản lý phân ca và Giáo viên chủ nhiệm</p>
                    </div>
                </div>
                <a href="{{ route('classrooms.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">add</span> Thêm lớp
                </a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-white text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5">Tên lớp</th>
                            <th class="px-6 py-5 text-center">Ca học</th>
                            <th class="px-6 py-5">Giáo viên Chủ nhiệm</th>
                            <th class="px-8 py-5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        @php $classesInGrade = $groupedClassrooms->get($grade) ?? collect(); @endphp
                        
                        @forelse($classesInGrade as $c)
                        <tr class="hover:bg-blue-50/30 transition-all group">
                            
                            <td class="px-8 py-5 font-black text-blue-700 uppercase text-sm tracking-wider">
                                Lớp {{ $c->name }}
                            </td>
                            
                            <td class="px-6 py-5 text-center">
                                <span class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase {{ $c->shift == 'morning' ? 'bg-orange-100 text-orange-600 border border-orange-200' : 'bg-indigo-100 text-indigo-600 border border-indigo-200' }}">
                                    {{ $c->shift == 'morning' ? 'Ca Sáng' : 'Ca Chiều' }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-5 font-bold text-slate-600">
                                @if($c->homeroom_teacher)
                                    <span class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-emerald-500 text-lg">person_check</span>
                                        {{ $c->homeroom_teacher }}
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
                            <td colspan="4" class="px-8 py-20 text-center">
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
@endsection