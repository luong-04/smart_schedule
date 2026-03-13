<body class="flex h-screen overflow-hidden">
    <aside class="w-[30%] bg-white rounded-3xl shadow-xl flex flex-col p-6 m-4 overflow-hidden border border-blue-50">
        <div class="grid grid-cols-2 gap-4 mb-8">
            <div class="bg-blue-600 p-4 rounded-2xl text-white shadow-lg shadow-blue-100">
                <p class="text-[10px] uppercase font-bold opacity-80">Giáo viên</p>
                <p class="text-2xl font-black">45</p>
            </div>
            <div class="bg-slate-800 p-4 rounded-2xl text-white shadow-lg shadow-slate-200">
                <p class="text-[10px] uppercase font-bold opacity-80">Lớp học</p>
                <p class="text-2xl font-black">24</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-2">TKB Đã lưu</p>
            <div class="space-y-2">
                @php
                    $savedSchedules = ['Học kỳ 1 - Khối 10', 'Học kỳ 1 - Khối 11', 'TKB Tạm thời - Tuần 20'];
                @endphp
                @foreach($savedSchedules as $item)
                <a href="#" class="flex items-center gap-3 p-4 bg-slate-50 hover:bg-blue-50 rounded-2xl transition group">
                    <span class="text-xl group-hover:scale-110 transition">📄</span>
                    <div>
                        <p class="text-sm font-bold text-slate-700">{{ $item }}</p>
                        <p class="text-[9px] text-slate-400 italic font-medium">Cập nhật: 2 giờ trước</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </aside>

    <main class="w-[70%] flex flex-col">
        <header class="h-20 bg-white border-b px-10 flex justify-between items-center">
            <span class="font-bold text-gray-600">GIAO DIỆN QUẢN LÝ</span>
            <button class="bg-brand-primary text-white px-6 py-2 rounded-full shadow-lg">Sắp lịch mới</button>
        </header>
        <section class="flex-1 p-10 overflow-y-auto">
            @yield('content')
        </section>
    </main>
</body>