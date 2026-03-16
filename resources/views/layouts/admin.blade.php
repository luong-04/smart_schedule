<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hệ thống Quản lý TKB')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <style>
        body { font-family: 'Lexend', sans-serif; }
        .sidebar-item-active { 
            background-color: rgba(19, 91, 236, 0.1); 
            color: #135bec; 
            border-right: 4px solid #135bec; 
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #94a3b8; }
    </style>
</head>
<body class="bg-[#f6f6f8] text-slate-900 antialiased overflow-x-hidden">
    <div class="flex min-h-screen">
        
        <aside class="w-[20%] bg-[#F0F7FF] border-r border-slate-200 flex flex-col p-6 gap-6 sticky top-0 h-screen shrink-0">
            
            <div class="flex items-center gap-3 px-2">
                <div class="bg-blue-600 rounded-2xl p-2.5 text-white shadow-lg shadow-blue-200 shrink-0">
                    <span class="material-symbols-outlined text-3xl">school</span>
                </div>
                <div class="overflow-hidden">
                    <h1 class="text-[13px] font-black text-blue-800 uppercase leading-tight truncate" title="{{ \App\Models\Setting::getVal('school_name', 'TRƯỜNG CHƯA CÀI ĐẶT') }}">
                        {{ \App\Models\Setting::getVal('school_name', 'TRƯỜNG CHƯA CÀI ĐẶT') }}
                    </h1>
                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-1">
                        Năm học: {{ \App\Models\Setting::getVal('school_year', '2024-2025') }}
                    </p>
                </div>
            </div>

            <div class="px-2">
                <a href="{{ url('/') }}" target="_blank" class="flex items-center justify-center gap-2 w-full bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl text-[11px] font-black uppercase tracking-widest hover:bg-emerald-100 transition-colors border border-emerald-200 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">public</span>
                    Xem Trang Chủ
                </a>
            </div>

            <nav class="flex-1 flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 mt-2">
                @php
                    $groups = [
                        'Thống kê & Tổng quan' => [
                            ['name' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
                            ['name' => 'TKB đã sắp', 'icon' => 'calendar_today', 'route' => 'schedules.list'],
                        ],
                        'Quản lý Danh mục' => [
                            ['name' => 'Giáo viên', 'icon' => 'group', 'route' => 'teachers.index'],
                            ['name' => 'Môn học', 'icon' => 'book', 'route' => 'subjects.index'],
                            ['name' => 'Lớp học', 'icon' => 'meeting_room', 'route' => 'classrooms.index'],
                            ['name' => 'Cơ sở vật chất', 'icon' => 'domain', 'route' => 'rooms.index'],
                        ],
                        'Nghiệp vụ & Cấu hình' => [
                            ['name' => 'Chương trình học', 'icon' => 'history_edu', 'route' => 'curriculum.index'],
                            ['name' => 'Ma trận TKB', 'icon' => 'grid_on', 'route' => 'matrix.index'],
                            ['name' => 'Cài đặt hệ thống', 'icon' => 'settings', 'route' => 'admin.settings.index'],
                        ]
                    ];
                @endphp

                @foreach($groups as $groupName => $items)
                    <div>
                        <p class="px-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">{{ $groupName }}</p>
                        <div class="flex flex-col gap-1">
                            @foreach($items as $item)
                                @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                                <a href="{{ route($item['route']) }}" 
                                   class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ $isActive ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                                    <span class="material-symbols-outlined {{ $isActive ? 'text-blue-600' : 'text-slate-400' }}">{{ $item['icon'] }}</span>
                                    <span class="text-sm">{{ $item['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="mt-auto pt-6 border-t border-blue-100 shrink-0">
                <div class="flex items-center gap-3 p-3 bg-white rounded-2xl shadow-sm border border-blue-50">
                    <div class="size-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-xs shadow-inner shrink-0">AD</div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black truncate text-slate-700 uppercase tracking-widest">Quản trị viên</p>
                        <p class="text-[9px] text-emerald-500 font-black uppercase tracking-widest mt-0.5 flex items-center gap-1">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span> Đang hoạt động
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="w-[80%] bg-[#f8f9fa] min-h-screen flex flex-col relative">
            
            <header class="flex items-center justify-between px-10 py-6 border-b border-slate-200/60 sticky top-0 bg-white/80 backdrop-blur-xl z-40 shadow-sm">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">@yield('title')</h2>
                
                <div class="flex items-center gap-5">
                    <div class="hidden md:flex items-center gap-2.5 px-5 py-2.5 bg-emerald-50 border border-emerald-100 rounded-full shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Hệ thống ổn định</span>
                    </div>
                    <div class="w-px h-8 bg-slate-200 hidden md:block"></div>
                    <div class="flex items-center gap-3 px-5 py-2.5 bg-white border border-slate-200 rounded-full shadow-sm">
                        <span class="material-symbols-outlined text-blue-500 text-lg">schedule</span>
                        <span class="text-[11px] font-black text-slate-600 tracking-widest uppercase" id="realtime-clock">Đang tải...</span>
                    </div>
                </div>
            </header>

            <div class="p-10 flex-1">
                @yield('content')
            </div>

            <footer class="p-6 text-center border-t border-slate-200 bg-white mt-auto">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    &copy; 2024 {{ \App\Models\Setting::getVal('school_name', 'Hệ thống Xếp lịch') }}.
                </p>
            </footer>
        </main>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const days = ['Chủ Nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
            const dayOfWeek = days[now.getDay()];
            const date = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const timeString = `${dayOfWeek}, ${date}/${month}/${year} • ${hours}:${minutes}:${seconds}`;
            const clockElement = document.getElementById('realtime-clock');
            if (clockElement) clockElement.textContent = timeString;
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>