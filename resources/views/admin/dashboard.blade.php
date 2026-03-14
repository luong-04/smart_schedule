@extends('layouts.admin')
@section('title', 'Bảng điều khiển')
@section('content')

<div class="space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="p-6 bg-blue-50/50 rounded-[2rem] border border-blue-100 hover:shadow-xl hover:shadow-blue-200/20 transition-all group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-200">
                    <span class="material-symbols-outlined">person_pin</span>
                </div>
                <span class="text-[10px] font-black text-blue-600 bg-blue-100 px-2 py-1 rounded-full">NHÂN SỰ</span>
            </div>
            <p class="text-slate-500 text-xs font-black uppercase tracking-widest">Tổng Giáo viên</p>
            <h3 class="text-4xl font-black mt-2 text-slate-800">{{ $stats['teachers'] }}</h3>
        </div>

        <div class="p-6 bg-purple-50/50 rounded-[2rem] border border-purple-100 hover:shadow-xl hover:shadow-purple-200/20 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-purple-600 text-white rounded-2xl shadow-lg shadow-purple-200">
                    <span class="material-symbols-outlined">groups</span>
                </div>
                <span class="text-[10px] font-black text-purple-600 bg-purple-100 px-2 py-1 rounded-full">HỌC TẬP</span>
            </div>
            <p class="text-slate-500 text-xs font-black uppercase tracking-widest">Tổng Lớp học</p>
            <h3 class="text-4xl font-black mt-2 text-slate-800">{{ $stats['classrooms'] }}</h3>
        </div>

        <div class="p-6 bg-amber-50/50 rounded-[2rem] border border-amber-100 hover:shadow-xl hover:shadow-amber-200/20 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-amber-600 text-white rounded-2xl shadow-lg shadow-amber-200">
                    <span class="material-symbols-outlined">meeting_room</span>
                </div>
                <span class="text-[10px] font-black text-amber-600 bg-amber-100 px-2 py-1 rounded-full">CƠ SỞ</span>
            </div>
            <p class="text-slate-500 text-xs font-black uppercase tracking-widest">Tổng Phòng học</p>
            <h3 class="text-4xl font-black mt-2 text-slate-800">{{ $stats['rooms'] }}</h3>
        </div>

        <div class="p-6 bg-emerald-50/50 rounded-[2rem] border border-emerald-100 hover:shadow-xl hover:shadow-emerald-200/20 transition-all">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-emerald-600 text-white rounded-2xl shadow-lg shadow-emerald-200">
                    <span class="material-symbols-outlined">assignment_turned_in</span>
                </div>
                <span class="text-[10px] font-black text-emerald-600 bg-emerald-100 px-2 py-1 rounded-full">NHIỆM VỤ</span>
            </div>
            <p class="text-slate-500 text-xs font-black uppercase tracking-widest">Đã Phân công</p>
            <h3 class="text-4xl font-black mt-2 text-slate-800">{{ $stats['assignments'] }}</h3>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center">
            <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">history</span>
                Hoạt động phân công gần đây
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-8 py-5">Giáo viên</th>
                        <th class="px-8 py-5">Môn học</th>
                        <th class="px-8 py-5 text-center">Lớp</th>
                        <th class="px-8 py-5 text-right">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recentAssignments as $assign)
                    <tr class="hover:bg-blue-50/10 transition-colors group">
                        <td class="px-8 py-5">
                            <p class="font-bold text-slate-700 uppercase">{{ $assign->teacher->name }}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">{{ $assign->teacher->code }}</p>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-xs font-bold text-slate-600">{{ $assign->subject->name }}</span>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg text-[10px] font-black">LỚP {{ $assign->classroom->name }}</span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-600">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Đã gán
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('matrix.index') }}" class="flex-1 flex items-center justify-center gap-3 p-6 bg-blue-600 text-white rounded-3xl shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all group">
            <span class="material-symbols-outlined group-hover:scale-110 transition-transform">grid_on</span>
            <span class="font-black uppercase text-xs tracking-widest">Bắt đầu sắp lịch TKB mới</span>
        </a>
        <button class="flex-1 flex items-center justify-center gap-3 p-6 bg-slate-100 text-slate-600 rounded-3xl hover:bg-slate-200 transition-all group border border-slate-200">
            <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">cloud_upload</span>
            <span class="font-black uppercase text-xs tracking-widest">Xuất báo cáo tổng hợp</span>
        </button>
    </div>
</div>

@endsection