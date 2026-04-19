@extends('layouts.admin')
@section('title', 'Bảng điều khiển')
@section('content')

    <div class="space-y-10 animate-fade-in flex flex-col">
        {{-- Welcome Announcement --}}
        <div
            class="relative overflow-hidden bg-white/40 backdrop-blur-md border border-blue-100/50 rounded-3xl p-4 shadow-sm flex items-center gap-4 group shrink-0">
            <div class="p-2.5 bg-blue-600 text-white rounded-2xl shadow-lg shrink-0 z-10">
                <span class="material-symbols-outlined text-xl">campaign</span>
            </div>
            <div class="flex-1 overflow-hidden relative h-10">
                <div class="absolute inset-0 animate-marquee">
                    <div class="absolute inset-y-0 left-0 flex items-center whitespace-nowrap">
                        <span class="font-black text-blue-800 text-sm tracking-wide uppercase">
                            {{ \App\Models\Setting::getVal('dashboard_welcome_message', 'Chào mừng bạn đến với hệ thống Sắp xếp Lịch giảng dạy thông minh!') }}
                        </span>
                    </div>
                    <div class="absolute inset-y-0 left-full flex items-center whitespace-nowrap">
                        <span class="font-black text-blue-800 text-sm tracking-wide uppercase">
                            {{ \App\Models\Setting::getVal('dashboard_welcome_message', 'Chào mừng bạn đến với hệ thống Sắp xếp Lịch giảng dạy thông minh!') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stat Cards Row --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 shrink-0">
            {{-- Card 1 --}}
            <div
                class="p-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-blue-100 hover:shadow-2xl hover:shadow-blue-200/40 transition-all group relative overflow-hidden">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/10 transition-colors">
                </div>
                <div class="flex justify-between items-start mb-5 relative z-10">
                    <div
                        class="p-3 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-2xl shadow-xl shadow-blue-200 group-hover:rotate-6 transition-transform">
                        <span class="material-symbols-outlined text-xl">person_pin</span>
                    </div>
                    <span
                        class="text-[8px] font-black text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-blue-100/50">GV</span>
                </div>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest relative z-10">Giáo viên</p>
                <h3 class="text-3xl font-black mt-1 text-slate-800 tracking-tight relative z-10 tabular-nums">
                    {{ $stats['teachers'] }}</h3>
            </div>

            {{-- Card 2 --}}
            <div
                class="p-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-purple-100 hover:shadow-2xl hover:shadow-purple-200/40 transition-all group relative overflow-hidden">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/5 rounded-full blur-2xl group-hover:bg-purple-500/10 transition-colors">
                </div>
                <div class="flex justify-between items-start mb-5 relative z-10">
                    <div
                        class="p-3 bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-2xl shadow-xl shadow-purple-200 group-hover:rotate-6 transition-transform">
                        <span class="material-symbols-outlined text-xl">groups</span>
                    </div>
                    <span
                        class="text-[8px] font-black text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-purple-100/50">LỚP</span>
                </div>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest relative z-10">Lớp học</p>
                <h3 class="text-3xl font-black mt-1 text-slate-800 tracking-tight relative z-10 tabular-nums">
                    {{ $stats['classrooms'] }}</h3>
            </div>

            {{-- Card 3 --}}
            <div
                class="p-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-amber-100 hover:shadow-2xl hover:shadow-amber-200/40 transition-all group relative overflow-hidden">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/10 transition-colors">
                </div>
                <div class="flex justify-between items-start mb-5 relative z-10">
                    <div
                        class="p-3 bg-gradient-to-br from-amber-500 to-amber-700 text-white rounded-2xl shadow-xl shadow-amber-200 group-hover:rotate-6 transition-transform">
                        <span class="material-symbols-outlined text-xl">meeting_room</span>
                    </div>
                    <span
                        class="text-[8px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-amber-100/50">PHÒNG</span>
                </div>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest relative z-10">Phòng học</p>
                <h3 class="text-3xl font-black mt-1 text-slate-800 tracking-tight relative z-10 tabular-nums">
                    {{ $stats['rooms'] }}</h3>
            </div>

            {{-- Card 4 --}}
            <div
                class="p-6 bg-white/60 backdrop-blur-xl rounded-[2.5rem] border border-emerald-100 hover:shadow-2xl hover:shadow-emerald-200/40 transition-all group relative overflow-hidden">
                <div
                    class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/10 transition-colors">
                </div>
                <div class="flex justify-between items-start mb-5 relative z-10">
                    <div
                        class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-700 text-white rounded-2xl shadow-xl shadow-emerald-200 group-hover:rotate-6 transition-transform">
                        <span class="material-symbols-outlined text-xl">assignment_turned_in</span>
                    </div>
                    <span
                        class="text-[8px] font-black text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full uppercase tracking-tighter border border-emerald-100/50">PC</span>
                </div>
                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest relative z-10">Phân công</p>
                <h3 class="text-3xl font-black mt-1 text-slate-800 tracking-tight relative z-10 tabular-nums">
                    {{ $stats['assignments'] }}</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch flex-1">
            {{-- Left column: Recent Table --}}
            <div class="lg:col-span-2 space-y-8 flex flex-col">
                <div
                    class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-xl hover:shadow-slate-200/40 transition-all flex-1">
                    <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                            <h3 class="font-black text-slate-700 uppercase tracking-widest text-xs flex items-center gap-2">
                                TKB Đã xếp gần đây
                            </h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead
                                class="bg-slate-100/50 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-50">
                                <tr>
                                    <th class="px-8 py-5">Lớp / GVCN</th>
                                    <th class="px-8 py-5 text-center">Khối</th>
                                    <th class="px-8 py-5">Cập nhật</th>
                                    <th class="px-8 py-5 text-right">Lệnh</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($recentSchedules as $sch)
                                    @php $class = $sch->assignment->classroom; @endphp
                                    <tr class="hover:bg-blue-50/20 transition-all group/row">
                                        <td class="px-8 py-4">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="size-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-xs border border-blue-100">
                                                    {{ $class->name }}
                                                </div>
                                                <div>
                                                    <p class="font-black text-slate-700 uppercase tracking-widest text-[10px]">
                                                        LỚP {{ $class->name }}</p>
                                                    <p class="text-[9px] text-slate-400 font-bold uppercase">GVCN:
                                                        {{ $class->homeroomTeacher->name ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-4 text-center">
                                            <span
                                                class="bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg text-[9px] font-black uppercase border border-indigo-100 whitespace-nowrap">KHỐI
                                                {{ $class->grade }}</span>
                                        </td>
                                        <td class="px-8 py-4">
                                            <span
                                                class="text-[10px] font-bold text-slate-500 whitespace-nowrap">{{ $sch->updated_at->diffForHumans() }}</span>
                                        </td>
                                        <td class="px-8 py-4 text-right whitespace-nowrap">
                                            <div
                                                class="flex justify-end gap-2 opacity-0 group-hover/row:opacity-100 transition-all">
                                                <a href="{{ route('schedules.list', ['class_id' => $class->id]) }}"
                                                    class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all border border-emerald-100"
                                                    title="Xem">
                                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                                </a>
                                                <a href="{{ route('matrix.index', ['class_id' => $class->id]) }}"
                                                    class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all border border-blue-100"
                                                    title="Sửa">
                                                    <span class="material-symbols-outlined text-lg">edit_calendar</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-8 py-20 text-center">
                                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-[0.2em]">Chưa có
                                                dữ liệu</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right column: Progress & Tips --}}
            <div class="space-y-8 flex flex-col h-full">
                {{-- Progress Card --}}
                <div
                    class="bg-gradient-to-br from-indigo-600 via-blue-700 to-blue-800 rounded-[2.5rem] p-8 text-white shadow-2xl shadow-blue-300/40 relative overflow-hidden group hover:-translate-y-1 transition-all flex-1 flex flex-col">
                    <div
                        class="absolute -right-10 -top-10 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-700">
                    </div>

                    <div class="relative z-10 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-8">
                            <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-md border border-white/20">
                                <span class="material-symbols-outlined text-2xl text-blue-100">donut_large</span>
                            </div>
                            <span
                                class="text-[8px] font-black tracking-widest uppercase bg-white/10 px-2.5 py-1 rounded-full border border-white/20">TIẾN
                                ĐỘ</span>
                        </div>

                        @php
                            $totalClasses = $stats['classrooms'];
                            $percent = $totalClasses > 0 ? round(($scheduledCount / $totalClasses) * 100) : 0;
                        @endphp

                        <div class="space-y-6 mt-auto">
                            <div>
                                <h4 class="text-4xl font-black tracking-tighter tabular-nums mb-1">
                                    {{ $scheduledCount }}<span
                                        class="text-xl text-blue-200/50 ml-1">/{{ $totalClasses }}</span></h4>
                                <p class="text-[9px] font-black uppercase tracking-widest text-blue-200/70">Lớp hoàn tất xếp
                                    lịch</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-[10px] font-black uppercase text-emerald-300">
                                    <span>{{ $percent }}%</span>
                                </div>
                                <div class="h-2 w-full bg-black/20 rounded-full overflow-hidden p-0.5">
                                    <div class="h-full bg-gradient-to-r from-emerald-400 to-emerald-300 rounded-full relative transition-all duration-1000"
                                        style="width: {{ $percent }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tips Card (Compact) --}}
                <div
                    class="bg-white rounded-[2.5rem] p-7 border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-xl transition-all">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl border border-amber-100 shadow-sm">
                            <span class="material-symbols-outlined text-lg">lightbulb</span>
                        </div>
                        <h3 class="font-black text-slate-700 uppercase tracking-widest text-[10px]">Gợi ý</h3>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3 p-3 bg-slate-50/50 rounded-2xl border border-slate-50">
                            <span class="material-symbols-outlined text-emerald-500 text-sm mt-0.5">verified</span>
                            <p class="text-[10px] font-bold text-slate-500 leading-tight uppercase tracking-tight">Kiểm tra
                                <span class="text-blue-600">Định mức</span> thường xuyên.</p>
                        </div>
                        <div class="flex items-start gap-3 p-3 bg-slate-50/50 rounded-2xl border border-slate-50">
                            <span class="material-symbols-outlined text-indigo-500 text-sm mt-0.5">bolt</span>
                            <p class="text-[10px] font-bold text-slate-500 leading-tight uppercase tracking-tight">Dùng
                                <span class="text-indigo-600">Ma trận TKB</span> để kéo thả.</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Máy chủ ổn
                                định</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .animate-marquee {
            animation: marquee 15s linear infinite;
        }

        .animate-marquee:hover {
            animation-play-state: paused;
        }

        .animate-fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endsection