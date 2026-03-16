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
                Thời khóa biểu đã xếp gần đây
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-8 py-5">Lớp học</th>
                        <th class="px-8 py-5 text-center">Khối</th>
                        <th class="px-8 py-5">Thời gian cập nhật</th>
                        <th class="px-8 py-5 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentSchedules as $sch)
                    @php $class = $sch->assignment->classroom; @endphp
                    <tr class="hover:bg-blue-50/10 transition-colors group">
                        <td class="px-8 py-5">
                            <p class="font-black text-blue-700 uppercase tracking-widest">LỚP {{ $class->name }}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">GVCN: {{ $class->homeroom_teacher ?? 'Đang cập nhật' }}</p>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="bg-slate-100 text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase">KHỐI {{ $class->grade }}</span>
                        </td>
                        <td class="px-8 py-5">
                            <span class="text-xs font-bold text-slate-500">{{ $sch->updated_at->diffForHumans() }}</span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('schedules.list') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[10px] font-black uppercase bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">visibility</span>
                                    Xem TKB
                                </a>
                                <a href="{{ route('matrix.index', ['class_id' => $class->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-[10px] font-black uppercase bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">edit_calendar</span>
                                    Xếp lịch
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-12 text-center">
                            <span class="material-symbols-outlined text-4xl text-slate-200 mb-2">event_busy</span>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">
                                Chưa có thời khóa biểu nào được xếp
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[2.5rem] p-8 text-white shadow-xl shadow-blue-200/50 relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute right-20 -bottom-10 w-40 h-40 bg-blue-400/20 rounded-full blur-2xl"></div>

            <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="material-symbols-outlined text-3xl text-blue-200">donut_large</span>
                        <h3 class="text-sm font-black tracking-widest uppercase text-blue-50">Tiến độ xếp lịch toàn trường</h3>
                    </div>
                    <p class="text-blue-100/80 text-xs font-medium max-w-md leading-relaxed">Hãy đảm bảo tất cả các lớp đều được phân công thời khóa biểu hợp lệ trước khi bắt đầu tuần học mới.</p>
                </div>
                
                <div class="bg-white/10 rounded-2xl p-6 mt-8 backdrop-blur-md border border-white/20 shadow-inner">
                    @php
                        $scheduledCount = $recentSchedules->count();
                        $totalClasses = $stats['classrooms'];
                        $percent = $totalClasses > 0 ? round(($scheduledCount / $totalClasses) * 100) : 0;
                    @endphp
                    <div class="flex justify-between items-end mb-4">
                        <div>
                            <p class="text-5xl font-black">{{ $scheduledCount }}<span class="text-xl text-blue-200/50 font-bold"> / {{ $totalClasses }}</span></p>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-200 mt-2">Lớp đã xếp TKB</p>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-emerald-300 drop-shadow-md">{{ $percent }}%</span>
                        </div>
                    </div>
                    <div class="h-3 w-full bg-black/20 rounded-full overflow-hidden shadow-inner border border-white/10">
                        <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-300 rounded-full relative transition-all duration-1000" style="width: {{ $percent }}%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 border border-slate-100 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-amber-100 text-amber-600 rounded-xl shadow-inner">
                        <span class="material-symbols-outlined text-xl">lightbulb</span>
                    </div>
                    <h3 class="font-black text-slate-700 uppercase tracking-widest text-xs">Gợi ý thông minh</h3>
                </div>
                <div class="space-y-5">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">Luôn thiết lập <span class="text-blue-600">Định mức tiết</span> chuẩn xác để hệ thống tự động cảnh báo khi phân công lố.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-emerald-500 text-lg">check_circle</span>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed">Tính năng <span class="text-emerald-600">Xuất Excel</span> ở danh sách TKB giúp dễ dàng in ấn và chia sẻ cho giáo viên.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 pt-5 border-t border-slate-50 flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trạng thái máy chủ</span>
                <span class="flex items-center gap-1.5 text-[10px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping absolute"></span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 relative"></span>
                    Đang ổn định
                </span>
            </div>
        </div>

    </div>
</div>

@endsection