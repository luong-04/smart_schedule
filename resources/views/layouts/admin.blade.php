<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Schedule Admin')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Lexend', sans-serif; }
        .sidebar-item-active { background-color: rgba(19, 91, 236, 0.1); color: #135bec; border-right: 4px solid #135bec; }
    </style>
</head>
<body class="bg-[#f6f6f8] text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="w-[20%] bg-[#F0F7FF] border-r border-slate-200 flex flex-col p-6 gap-8 sticky top-0 h-screen">
            <div class="flex items-center gap-3 px-2">
                <div class="bg-blue-600 rounded-xl p-2 text-white">
                    <span class="material-symbols-outlined text-3xl">calendar_month</span>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight italic">Smart<span class="text-blue-600">Schedule</span></h1>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Premium v2.0</p>
                </div>
            </div>

            <nav class="flex-1 flex flex-col gap-6 overflow-y-auto custom-scrollbar">
                @php
                    $groups = [
                        'Thống kê & Tổng quan' => [
                            ['name' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
                            ['name' => 'TKB đã sắp', 'icon' => 'calendar_today', 'route' => 'schedules.list'],
                        ],
                        'Quản lý Danh mục' => [
                            ['name' => 'Giáo viên', 'icon' => 'group', 'route' => 'teachers.index'],
                            ['name' => 'Môn học', 'icon' => 'book', 'route' => 'subjects.index'],
                            ['name' => 'Lớp học', 'icon' => 'school', 'route' => 'classrooms.index'],
                            ['name' => 'Phòng học', 'icon' => 'meeting_room', 'route' => 'rooms.index'],
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
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $isActive ? 'sidebar-item-active font-bold' : 'text-slate-600 hover:bg-white hover:text-blue-600' }}">
                                    <span class="material-symbols-outlined">{{ $item['icon'] }}</span>
                                    <span class="text-sm">{{ $item['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="mt-auto pt-6 border-t border-slate-200">
                <div class="flex items-center gap-3 p-2 bg-white/40 rounded-xl">
                    <div class="size-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-xs">AD</div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-bold truncate text-slate-700">Quản trị viên</p>
                        <p class="text-[10px] text-green-500 font-black uppercase">Online</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="w-[80%] bg-white min-h-screen flex flex-col">
            <header class="flex items-center justify-between px-8 py-6 border-b border-slate-100 sticky top-0 bg-white/80 backdrop-blur-md z-10">
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">@yield('title')</h2>
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                        <input class="pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-sm w-64 focus:ring-2 focus:ring-blue-500/20" placeholder="Tìm kiếm dữ liệu..." type="text"/>
                    </div>
                    <button class="p-2 text-slate-400 hover:bg-slate-50 rounded-xl relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute top-2 right-2 size-2 bg-red-500 rounded-full border-2 border-white"></span>
                    </button>
                </div>
            </header>

            <div class="p-8 flex-1">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>