<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hệ thống Quản lý TKB')</title>
    <link rel="icon" type="image/png" href="https://ui-avatars.com/api/?name=S&background=886cc0&color=fff&rounded=true">
    
    
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin/admin-common.js'])
    
    <script src="https://unpkg.com/htmx.org@1.9.12"></script>
    <script src="https://unpkg.com/idiomorph/dist/idiomorph-ext.min.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>

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

        /* Đổi màu thanh loading NProgress thành màu xanh chủ đạo của bạn */
        #nprogress .bar { background: #135bec !important; height: 3px !important; }
        #nprogress .peg { box-shadow: 0 0 10px #135bec, 0 0 5px #135bec !important; }
        #nprogress .spinner-icon { border-top-color: #135bec !important; border-left-color: #135bec !important; }
    </style>
</head>

<body 
    @hasSection('body_attrs') 
        @yield('body_attrs') 
    @else 
        hx-boost="true" hx-ext="morph" hx-swap="morph:outerHTML" 
    @endif 
    class="bg-[#f6f6f8] text-slate-900 antialiased overflow-x-hidden">
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
                        {{ \App\Models\Setting::getVal('semester', 'Học kỳ 1') }} • {{ \App\Models\Setting::getVal('school_year', '2024-2025') }}
                    </p>
                </div>
            </div>

            <div class="px-2">
                <a href="{{ url('/') }}" target="_blank" class="flex items-center justify-center gap-2 w-full bg-emerald-50 text-emerald-600 px-4 py-3 rounded-xl text-[11px] font-black uppercase tracking-widest hover:bg-emerald-100 transition-colors border border-emerald-200 shadow-sm">
                    <span class="material-symbols-outlined text-[16px]">public</span>
                    Xem Trang Chủ
                </a>
            </div>

            <nav id="sidebar-nav" class="flex-1 flex flex-col gap-6 overflow-y-auto custom-scrollbar pr-2 mt-2">
                
                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Thống kê & Tổng quan</p>
                    <div class="flex flex-col gap-1">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('admin.dashboard') ? 'text-blue-600' : 'text-slate-400' }}">dashboard</span>
                            <span class="text-sm">Dashboard</span>
                        </a>
                        <a href="{{ route('schedules.list') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('schedules.list') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('schedules.list') ? 'text-blue-600' : 'text-slate-400' }}">calendar_today</span>
                            <span class="text-sm">TKB đã sắp</span>
                        </a>
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Quản lý Danh mục</p>
                    <div class="flex flex-col gap-1">
                        @can('quan_ly_giao_vien')
                        <a href="{{ route('teachers.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('teachers.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('teachers.*') ? 'text-blue-600' : 'text-slate-400' }}">group</span>
                            <span class="text-sm">Giáo viên</span>
                        </a>
                        @endcan

                        @can('quan_ly_mon_hoc')
                        <a href="{{ route('subjects.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('subjects.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('subjects.*') ? 'text-blue-600' : 'text-slate-400' }}">book</span>
                            <span class="text-sm">Môn học</span>
                        </a>
                        @endcan

                        @can('quan_ly_lop_hoc')
                        <a href="{{ route('classrooms.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('classrooms.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('classrooms.*') ? 'text-blue-600' : 'text-slate-400' }}">meeting_room</span>
                            <span class="text-sm">Lớp học</span>
                        </a>
                        @endcan

                        @can('quan_ly_co_so_vat_chat')
                        <a href="{{ route('rooms.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('rooms.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('rooms.*') ? 'text-blue-600' : 'text-slate-400' }}">domain</span>
                            <span class="text-sm">Cơ sở vật chất</span>
                        </a>
                        @endcan
                    </div>
                </div>

                <div>
                    <p class="px-3 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Nghiệp vụ & Cấu hình</p>
                    <div class="flex flex-col gap-1">
                        @can('quan_ly_xep_lich')
                        <a href="{{ route('curriculum.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('curriculum.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('curriculum.*') ? 'text-blue-600' : 'text-slate-400' }}">history_edu</span>
                            <span class="text-sm">Chương trình học</span>
                        </a>
                        <a href="{{ route('matrix.index') }}" hx-boost="false" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('matrix.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('matrix.*') ? 'text-blue-600' : 'text-slate-400' }}">grid_on</span>
                            <span class="text-sm">Ma trận TKB</span>
                        </a>
                        @endcan

                        @can('quan_ly_giam_thi')
                        <a href="{{ route('admin.proctors.index') }}" hx-boost="false" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('admin.proctors.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('admin.proctors.*') ? 'text-blue-600' : 'text-slate-400' }}">badge</span>
                            <span class="text-sm">Phân công Giám thị</span>
                        </a>
                        @endcan
                        
                        @role('Super Admin')
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('users.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('users.*') ? 'text-blue-600' : 'text-slate-400' }}">manage_accounts</span>
                            <span class="text-sm">Tài khoản & Phân quyền</span>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('admin.settings.*') ? 'sidebar-item-active font-bold shadow-sm' : 'text-slate-600 hover:bg-white hover:text-blue-600 hover:shadow-sm' }}">
                            <span class="material-symbols-outlined {{ request()->routeIs('admin.settings.*') ? 'text-blue-600' : 'text-slate-400' }}">settings</span>
                            <span class="text-sm">Cài đặt hệ thống</span>
                        </a>
                        @endrole
                    </div>
                </div>
            </nav>

            <div class="mt-auto pt-6 border-t border-blue-100 shrink-0">
                <div class="flex items-center justify-between p-3 bg-white rounded-2xl shadow-sm border border-blue-50">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="size-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-xs shadow-inner shrink-0 uppercase">
                            {{ mb_substr(Auth::user()->name ?? 'AD', 0, 2) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-black truncate text-slate-700 uppercase tracking-widest">{{ Auth::user()->name }}</p>
                            <p class="text-[9px] text-emerald-500 font-black uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span> Đang hoạt động
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" title="Đăng xuất khỏi hệ thống">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700 p-2 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center justify-center">
                            <span class="material-symbols-outlined text-lg">logout</span>
                        </button>
                    </form>
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
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::getVal('school_name', 'Hệ thống Xếp lịch') }}.
                </p>
            </footer>
        </main>
    </div>

    <script>
        // Cấu hình NProgress chạy khi click đổi trang (Native fallback khi không dùng hx-boost)
        // Delay tiến trình để không hiển thị thanh load nếu trả về quá nhanh
        let nprogressTimer;
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    const target = link.getAttribute('target');
                    if (href && href !== '#' && !href.startsWith('javascript:') && target !== '_blank' && !e.ctrlKey && !e.metaKey) {
                        clearTimeout(nprogressTimer);
                        nprogressTimer = setTimeout(() => NProgress.start(), 150);
                    }
                });
            });
        });
        
        // HTMX config (hiển thị loading khi HTMX gọi AJAX)
        document.addEventListener('htmx:configRequest', function(event) { 
            clearTimeout(nprogressTimer);
            nprogressTimer = setTimeout(() => NProgress.start(), 150); 
            
            // Tự động thêm CSRF Token vào Header cho HTMX (Dùng cho hx-delete, hx-post...)
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                event.detail.headers['X-CSRF-TOKEN'] = csrfToken;
            }
        });
        
        // ===========================================================================
        // BÍ QUYẾT XỬ LÝ THANH CUỘN DÀNH RIÊNG CHO HTMX & RELOAD
        // Lưu vị trí khi chuẩn bị rời trang (full reload hoặc qua HTMX)
        // ===========================================================================
        function saveScrollPositions() {
            const sb = document.getElementById('sidebar-nav');
            if (sb) {
                sessionStorage.setItem('sidebar_scroll_position', sb.scrollTop);
            }
            sessionStorage.setItem('main_scroll_' + window.location.pathname, window.scrollY);
        }

        window.addEventListener('beforeunload', saveScrollPositions);
        document.addEventListener('htmx:beforeRequest', saveScrollPositions);

        // Phục hồi khi load trang hoặc morph
        function restoreScrollPositions() {
            const sidebar = document.getElementById('sidebar-nav');
            const targetSBPos = sessionStorage.getItem('sidebar_scroll_position');
            if (sidebar && targetSBPos !== null) {
                sidebar.scrollTop = parseInt(targetSBPos, 10);
            }
            
            const targetMainPos = sessionStorage.getItem('main_scroll_' + window.location.pathname);
            setTimeout(() => {
                if (targetMainPos !== null) {
                    window.scrollTo({ top: parseInt(targetMainPos, 10), behavior: 'instant' });
                } else {
                    // Nếu không có lịch sử cuộn cho trang này, và đang không có thẻ hash, thì cuộn lên top
                    if (!window.location.hash) {
                        window.scrollTo({ top: 0, behavior: 'instant' });
                    }
                }
            }, 10);
        }

        // 1. Phục hồi sau khi morph
        document.addEventListener('htmx:afterSettle', function(evt) {
            clearTimeout(nprogressTimer);
            NProgress.done();
            restoreScrollPositions();
            
            // Xử lý Alpine sau khi morph vì body thay đổi cấu trúc
            if (window.Alpine) {
                // Alpine sẽ tự nhận diện DOM thông qua MutationObserver
            }
        });

        // 2. Phục hồi cho lần tải đầu tiên (F5)
        window.addEventListener('DOMContentLoaded', restoreScrollPositions);
        // ===========================================================================

        // Logic Đồng hồ (Được tối ưu để không bị lỗi khi chuyển trang bằng AJAX)
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

        // Xóa interval cũ nếu trang bị đổi qua lại (Tránh lỗi nhảy giờ nhanh)
        if (window.clockInterval) clearInterval(window.clockInterval);
        window.clockInterval = setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>