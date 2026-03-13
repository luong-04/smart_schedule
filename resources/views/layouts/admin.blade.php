<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Schedule Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F0F7FF] flex h-screen overflow-hidden font-sans">

    <aside class="w-[30%] bg-white m-4 rounded-[2rem] shadow-xl border border-blue-50 flex flex-col p-8">
        <div class="mb-10">
            <h1 class="text-2xl font-black text-blue-600 tracking-tighter">SMART-SCHEDULE</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Premium Edition</p>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-10">
            <div class="bg-blue-600 p-4 rounded-2xl text-white shadow-lg shadow-blue-100">
                <p class="text-[9px] uppercase font-bold opacity-80">Giáo viên</p>
                <p class="text-xl font-black">45</p>
            </div>
            <div class="bg-slate-800 p-4 rounded-2xl text-white">
                <p class="text-[9px] uppercase font-bold opacity-80">Lớp học</p>
                <p class="text-xl font-black">24</p>
            </div>
        </div>

        <nav class="flex-1 space-y-2 overflow-y-auto">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-4">Danh mục quản lý</p>
            
            <a href="{{ route('teachers.index') }}" class="flex items-center gap-4 p-4 {{ request()->routeIs('teachers.*') ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-slate-500 hover:bg-slate-50' }} rounded-2xl transition-all font-bold">
                <span class="text-lg">👨‍🏫</span> Giáo viên
            </a>

            <a href="{{ route('assignments.index') }}" class="flex items-center gap-4 p-4 {{ request()->routeIs('assignments.*') ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-slate-500 hover:bg-slate-50' }} rounded-2xl transition-all font-bold">
                <span class="text-lg">📚</span> Phân công
            </a>

            <a href="{{ route('matrix.index') }}" class="flex items-center gap-4 p-4 {{ request()->routeIs('matrix.*') ? 'bg-blue-50 text-blue-600 shadow-sm' : 'text-slate-500 hover:bg-slate-50' }} rounded-2xl transition-all font-bold">
                <span class="text-lg">🗓️</span> Ma trận sắp lịch
            </a>

            <a href="#" class="flex items-center gap-4 p-4 text-slate-500 hover:bg-slate-50 rounded-2xl transition-all font-bold opacity-50">
                <span class="text-lg">🏫</span> Lớp học & Phòng
            </a>
        </nav>

        <div class="pt-6 border-t border-slate-100">
            <a href="/" class="text-xs font-bold text-slate-400 hover:text-blue-600 transition">← Quay lại Trang chủ</a>
        </div>
    </aside>

    <main class="w-[70%] flex flex-col my-4 mr-4 bg-white rounded-[2rem] shadow-xl border border-blue-50 overflow-hidden">
        <header class="h-20 border-b border-slate-50 px-10 flex justify-between items-center">
            <h2 class="font-black text-slate-800 uppercase tracking-tight">Giao diện quản lý</h2>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs font-black text-slate-800">Admin</p>
                    <p class="text-[10px] text-green-500 font-bold">Đang trực tuyến</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full border-2 border-white shadow-sm"></div>
            </div>
        </header>

        <section class="flex-1 p-10 overflow-y-auto bg-[#FCFDFF]">
            @yield('content')
        </section>
    </main>

</body>
</html>