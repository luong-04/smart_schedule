<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập hệ thống</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased text-gray-900">

    @php
        // Lấy tên trường từ Database. 
        // LƯU Ý: Nếu trong bảng 'settings' của bạn dùng chữ khác (ví dụ 'ten_truong'), hãy sửa chữ 'school_name' thành chữ đó nhé.
        $schoolName = \App\Models\Setting::where('key', 'school_name')->value('value') ?? 'Hệ Thống Smart Schedule';
    @endphp

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-800 px-4">
        <div
            class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden p-8 space-y-8 transform transition-all hover:scale-[1.01]">

            <div class="text-center">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4 shadow-inner">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422M12 14v6m-3-3l3 3m0 0l3-3m-3 3V14"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">{{ $schoolName }}</h2>
                <p class="text-sm text-gray-500 mt-2">Vui lòng đăng nhập để tiếp tục</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                @if ($errors->any())
                    <div
                        class="bg-red-50 text-red-500 px-4 py-3 rounded-lg text-sm border border-red-100 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Thông tin đăng nhập không chính xác.
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email đăng nhập</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="admin@domain.com"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all shadow-sm">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                        <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl flex justify-center items-center">
                    ĐĂNG NHẬP
                </button>
            </form>

            <div class="text-center text-xs text-gray-400 mt-6 pt-6 border-t border-gray-100">
                &copy; {{ date('Y') }} Hệ thống sắp xếp lịch.<br>Developed with by TTL
            </div>
        </div>
    </div>
</body>

</html>