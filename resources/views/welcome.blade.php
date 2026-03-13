<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tra cứu Thời khóa biểu - Smart Schedule</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F0F7FF] min-h-screen">
    <header class="p-6 flex justify-between items-center bg-white shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-blue-600">THPT SMART SCHOOL</h1>
            <p class="text-xs font-bold text-gray-400 uppercase">Niên khóa: 2024 - 2025</p>
        </div>
        <a href="{{ route('teachers.index') }}" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold shadow-lg shadow-blue-100 transition hover:scale-105">
            QUẢN LÝ
        </a>
    </header>

    <main class="max-w-6xl mx-auto pt-20 px-4">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-black text-slate-800 mb-6">Tra cứu Thời khóa biểu</h2>
            
            <div class="relative max-w-2xl mx-auto group">
                <input type="text" placeholder="Nhập tên lớp (10A1...) hoặc tên Giáo viên..." 
                    class="w-full p-6 pl-10 pr-40 bg-white rounded-full shadow-2xl border-none focus:ring-4 focus:ring-blue-200 text-lg transition-all">
                <button class="absolute right-3 top-3 bottom-3 bg-blue-600 text-white px-8 rounded-full font-black hover:bg-blue-700 transition">
                    TÌM KIẾM
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-xl p-8 border border-blue-50">
             <div class="flex justify-between items-end mb-6 border-b pb-4">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800">Lớp: 10A1</h3>
                    <p class="text-blue-600 font-medium">GVCN: Nguyễn Văn A</p>
                </div>
                <button class="text-sm font-bold text-slate-400 hover:text-blue-600">📥 Tải PDF</button>
             </div>
             
             <table class="w-full border-separate border-spacing-1">
                <thead>
                    <tr>
                        <th class="w-20"></th>
                        @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'] as $thu)
                            <th class="p-3 bg-blue-50 text-blue-600 rounded-xl text-xs font-black uppercase">{{ $thu }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(range(1, 10) as $tiet)
                    <tr>
                        <td class="p-4 text-center text-[10px] font-bold text-slate-400 bg-gray-50 rounded-xl">Tiết {{ $tiet }}</td>
                        @foreach(range(2, 7) as $thu)
                            <td class="p-2 border border-blue-50 rounded-xl text-center">
                                <div class="text-xs font-bold text-slate-700">Toán học</div>
                                <div class="text-[9px] text-gray-400">GV: Trần Thị B</div>
                                <div class="text-[9px] text-blue-500 font-bold uppercase">Phòng: Lớp</div>
                            </td>
                        @endforeach
                    </tr>
                    @if($tiet == 5) <tr><td colspan="7" class="h-4"></td></tr> @endif
                    @endforeach
                </tbody>
             </table>
        </div>
    </main>

    <footer class="mt-20 p-10 bg-white border-t text-center">
        <div class="grid grid-cols-3 max-w-4xl mx-auto mb-8 text-sm text-slate-500">
            <p>📍 Địa chỉ: 123 Đường ABC, TP. HCM</p>
            <p>📞 SĐT: 0123.456.789</p>
            <p>✉️ Email: thpt@edu.vn</p>
        </div>
        <p class="font-black text-slate-800">Hiệu trưởng: Nguyễn Văn A - Niên khóa 2024-2025</p>
    </footer>
</body>
</html>