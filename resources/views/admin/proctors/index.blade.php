@extends('layouts.admin')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; } 
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    /* ===== HIỂN THỊ BẢNG TRÊN WEB ===== */
    #mainTable {
        border-collapse: collapse !important;
        width: 100% !important;
        border: 1px solid #d1d5db !important;
    }
    #mainTable th, #mainTable td {
        border: 1px solid #d1d5db !important;
        padding: 10px 8px;
    }
    #mainTable th {
        background-color: #f9fafb;
        white-space: nowrap !important;
    }

    /* ===== BỘ LỌC TỐI THƯỢNG CHO IN ẤN & PDF TRÌNH DUYỆT (A4 ĐỨNG) ===== */
    @media print {
        @page { 
            size: A4 portrait; 
            margin: 15mm; 
        }

        html, body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            font-family: "Times New Roman", Times, serif !important;
            color: black !important;
            line-height: 1.15 !important;
        }

        header, nav, aside, footer, .sidebar, .navbar, .print-hidden { 
            display: none !important; 
        }

        #app, main, .container {
            width: 100% !important;
            max-width: none !important;
            min-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
            border: none !important;
            background: transparent !important;
        }

        body.print-single-mode #historyResult { display: block !important; width: 100% !important; max-width: 100% !important; }
        body.print-single-mode #printAllContainer { display: none !important; }

        body.print-all-mode #historyResult { display: none !important; }
        body.print-all-mode #printAllContainer { display: block !important; width: 100% !important; max-width: 100% !important; }

        #printHeader { margin-bottom: 15px !important; width: 100% !important; }
        .formal-text { font-size: 13pt !important; margin: 0 !important; color: black !important;}
        .formal-title { font-size: 16pt !important; font-weight: bold !important; color: black !important;}
        
        table.data-table { 
            table-layout: fixed !important; 
            width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid black !important; 
        }

        table.data-table td, table.data-table th { 
            font-size: 12pt !important; 
            vertical-align: middle !important;
            padding: 4px 4px !important; 
            border: 1px solid black !important; 
            color: black !important;
        }

        table.data-table td {
            white-space: normal !important; 
            word-wrap: break-word !important; 
        }

        table.data-table th { 
            background-color: transparent !important;
            font-weight: bold !important;
            text-align: center !important;
            white-space: nowrap !important;
        }

        table.data-table tr.title-row td {
            border: none !important;
            border-bottom: 1px solid transparent !important;
        }

        table.signature-table td {
            border: none !important;
        }

        .page-break { page-break-after: always !important; }
    }
</style>

<div class="container mx-auto px-4 py-8" x-data="proctorManager()">
    
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print-hidden">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Phân công Giám thị</h2>
            <p class="text-gray-500 mt-1 text-sm">Quản lý và sắp xếp lịch gác thi tự động</p>
        </div>
        
        <div class="flex flex-col items-end gap-2">
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-3 rounded-xl shadow-sm flex items-center print-hidden">
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-3 rounded-xl shadow-sm flex flex-col print-hidden">
                    @foreach ($errors->all() as $error)
                        <span class="font-medium">⚠️ {{ $error }}</span>
                    @endforeach
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-3 rounded-xl shadow-sm flex items-center print-hidden">
                    <span class="font-medium">⚠️ {{ session('error') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="flex space-x-2 border-b border-gray-200 mb-8 print-hidden">
        <button @click="activeTab = 'setup'" 
                :class="activeTab === 'setup' ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="py-3 px-6 text-sm font-semibold rounded-t-xl transition-all duration-200 border-b-2">
            ⚙️ Sắp Lịch Mới
        </button>
        <button @click="activeTab = 'history'" 
                :class="activeTab === 'history' ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                class="py-3 px-6 text-sm font-semibold rounded-t-xl transition-all duration-200 border-b-2">
            🕒 Lịch sử & Xuất File
        </button>
    </div>

    <div x-show="activeTab === 'setup'" style="display: none;" class="print-hidden">
        <form action="{{ route('admin.proctors.assign') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            @csrf
            <input type="hidden" name="import_data" :value="JSON.stringify(proctors)">

            <div class="col-span-1 bg-white p-6 md:p-8 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-100 flex flex-col h-full">
                <h3 class="text-xl font-bold mb-6 text-gray-800 flex items-center">
                    <span class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-3">1</span> Cấu Hình
                </h3>
                
                <div class="space-y-5 flex-grow">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tên Kỳ Thi</label>
                        <input type="text" name="exam_name" placeholder="VD: Thi cuối kỳ 2" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ngày Bắt Đầu</label>
                        <input type="date" name="start_date" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Số Ngày Thi</label>
                            <input type="number" name="total_days" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Phòng/Ngày</label>
                            <input type="number" name="rooms_per_day" min="1" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-100 outline-none" required>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <label class="block text-sm font-semibold text-gray-700 mb-4">Ràng Buộc Thuật Toán</label>
                        <div class="space-y-3">
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" name="constraint_dept" value="1" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900">Tránh cùng Đơn vị</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" name="constraint_room" value="1" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900">Tránh lặp Phòng cũ</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" name="constraint_pair" value="1" checked class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer">
                                <span class="text-sm text-gray-600 group-hover:text-gray-900">Tránh trùng cặp Giám thị</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-md transition-all">
                        Xác nhận & Phân công
                    </button>
                </div>
            </div>

            <div class="col-span-1 lg:col-span-2 bg-white p-6 md:p-8 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-100 flex flex-col h-full">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center">
                        <span class="bg-emerald-100 text-emerald-600 p-2 rounded-lg mr-3">2</span> Danh sách Giám thị
                    </h3>
                    <div>
                        <input type="file" id="excelFile" class="hidden" accept=".xlsx, .xls" @change="handleFileUpload($event)">
                        <label for="excelFile" class="cursor-pointer bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-semibold shadow-sm">
                            📥 Import Excel
                        </label>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <input type="text" x-model="newProctorName" placeholder="Nhập tên giám thị..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-100">
                    <input type="text" x-model="newProctorDept" @keydown.enter="addProctor()" placeholder="Đơn vị / Khoa..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-indigo-100">
                    <button type="button" @click="addProctor()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                        Thêm
                    </button>
                </div>
                
                <div class="flex-grow overflow-hidden rounded-xl border border-gray-200 bg-gray-50 relative h-[400px]">
                    <div class="absolute inset-0 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">STT</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Họ và Tên</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Đơn vị</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase">Xóa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template x-for="(proc, index) in proctors" :key="index">
                                    <tr class="hover:bg-indigo-50/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="index + 1"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800" x-text="proc.name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="proc.department || 'Khác'"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <button type="button" @click="proctors.splice(index, 1)" class="text-red-500 hover:text-red-700 font-bold p-1">✕</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'history'" style="display: none;">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-100 print-hidden">
            <h3 class="text-lg font-bold text-gray-800 mb-4">1. Chọn Kỳ thi để tra cứu</h3>
            <div class="flex flex-col md:flex-row gap-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                
                <input type="text" id="searchExamInput" oninput="filterExams()" placeholder="🔍 Gõ tên kỳ thi để tìm nhanh..." class="w-full md:w-72 py-3 px-4 bg-white border border-gray-300 rounded-xl outline-none focus:ring-2 focus:ring-indigo-200 text-gray-700 font-medium">
                
                <div class="flex-grow flex items-center bg-white rounded-xl border border-gray-300 px-3">
                    <select id="examSelector" onchange="fetchExamDates()" class="w-full py-3 bg-transparent border-none outline-none text-gray-700 font-medium cursor-pointer">
                        <option value="">-- Hoặc chọn từ danh sách --</option>
                        @foreach($exams as $ex)
                            <option value="{{ $ex->id }}" class="exam-option" {{ request('auto_load_exam') == $ex->id ? 'selected' : '' }}>
                                {{ $ex->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="examControls" class="hidden mt-6 space-y-4">
                <div class="flex flex-col md:flex-row gap-4 items-center bg-white border border-indigo-100 p-4 rounded-xl shadow-sm">
                    <div class="w-full md:w-auto font-bold text-indigo-800 uppercase tracking-wide text-sm whitespace-nowrap">
                        <span class="bg-indigo-100 px-3 py-1 rounded-md">Ngày đang xem</span>
                    </div>
                    <div class="flex flex-wrap gap-2 w-full justify-start">
                        <button onclick="exportWord()" class="bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white border border-blue-200 font-semibold py-2 px-5 rounded-lg transition-all">
                            📝 Tải Word
                        </button>
                        <button onclick="exportExcel()" class="bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 font-semibold py-2 px-5 rounded-lg transition-all">
                            📊 Tải Excel
                        </button>
                        <button onclick="printSingle()" class="bg-gray-100 text-gray-700 hover:bg-gray-700 hover:text-white border border-gray-300 font-semibold py-2 px-5 rounded-lg transition-all">
                            🖨️ In / PDF
                        </button>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4 items-center bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm">
                    <div class="w-full md:w-auto font-bold text-amber-800 uppercase tracking-wide text-sm whitespace-nowrap">
                        <span class="bg-amber-200 px-3 py-1 rounded-md">Toàn bộ Lịch thi</span>
                    </div>
                    <div class="flex flex-wrap gap-2 w-full justify-start">
                        <button onclick="exportWordAll()" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition-all">
                            📝 Tải Word (Tất cả)
                        </button>
                        <button onclick="exportExcelAll()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition-all">
                            📊 Tải Excel (Tất cả)
                        </button>
                        <button onclick="printAll()" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition-all">
                            🖨️ In Tất Cả
                        </button>
                        <div class="flex-grow"></div> 
                        <button onclick="deleteExam()" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-5 rounded-lg shadow-md transition-all mt-2 md:mt-0">
                            🗑️ Xóa Lịch Này
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="historyResult" class="hidden mt-8">
            <div id="printHeader" class="w-full">
                <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                    <div style="width: 40%; text-align: center;">
                        <p class="formal-text" style="font-weight: normal; margin-bottom: 8px; white-space: nowrap;">BỘ GIÁO DỤC VÀ ĐÀO TẠO</p>
                        <p class="formal-text" style="line-height: 1; margin-bottom: 8px;">........................................</p>
                        <p class="formal-text" style="line-height: 1;">........................................</p>
                    </div>
                    <div style="width: 5%;"></div>
                    <div style="width: 55%; text-align: center;">
                        <p class="formal-text" style="font-weight: bold; margin-bottom: 2px;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                        <p class="formal-text" style="font-weight: bold;">Độc lập - Tự do - Hạnh phúc</p>
                        <div style="margin: 2px auto 0; border-bottom: 1px solid black; width: 60%;"></div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 35px; margin-bottom: 20px;">
                    <h2 class="formal-title uppercase" id="printExamName" style="margin: 0;">BẢNG PHÂN CÔNG GIÁM THỊ</h2>
                </div>
            </div>
            
            <table id="mainTable" class="data-table">
                <colgroup>
                    <col style="width: 7%;">  
                    <col style="width: 24%;"> 
                    <col style="width: 19%;"> 
                    <col style="width: 12%;"> 
                    <col style="width: 12%;">  
                    <col style="width: 10%;">  
                    <col style="width: 16%;"> 
                </colgroup>
                <thead style="display: table-header-group;">
                    <tr class="title-row" style="border: none !important; background: transparent !important;">
                        <td colspan="7" style="border: none !important; text-align: right; font-style: italic; font-size: 11pt; padding: 4px 8px;">
                            <span style="color: #4b5563;">Kỳ thi:</span> <strong style="color: black;" id="repeatExamName"></strong> &nbsp;|&nbsp; 
                            <span style="color: #4b5563;">Ngày:</span> <strong style="color: black;" id="repeatDate"></strong>
                        </td>
                    </tr>
                    <tr>
                        <th style="font-weight: bold; white-space: nowrap;">STT</th>
                        <th style="font-weight: bold; white-space: nowrap;">Họ và tên</th>
                        <th style="font-weight: bold; white-space: nowrap;">Đơn vị</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 1</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 2</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 3</th>
                        <th style="font-weight: bold; white-space: nowrap;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody"></tbody>
            </table>

            <table class="signature-table" style="width: 100%; border: none !important; margin-top: 30px; page-break-inside: avoid;">
                <tr style="page-break-inside: avoid;">
                    <td style="width: 60%; border: none !important;"></td>
                    <td style="width: 40%; text-align: center; border: none !important; vertical-align: top;">
                        <p class="formal-text" style="margin: 0; page-break-inside: avoid; text-align: center; color: black;">
                            <span style="font-weight: bold; font-size: 13pt;">Người phân công</span><br>
                            <span style="font-size: 12pt; font-style: italic; font-weight: normal;">(Ký, ghi rõ họ tên)</span>
                        </p>
                        <div style="height: 80px;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <div id="printAllContainer" class="hidden"></div>

        <div class="print-hidden">
            <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 flex items-center justify-between mt-6">
                <span class="text-sm font-semibold text-gray-600">Lịch phân công từng ngày:</span>
                <div id="dayPagination" class="flex space-x-2 overflow-x-auto"></div>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('proctorManager', () => ({
            activeTab: '{{ request()->has("auto_load_exam") ? "history" : "setup" }}',
            proctors: [],
            newProctorName: '',
            newProctorDept: '',
            
            addProctor() {
                if (this.newProctorName.trim() !== '') {
                    this.proctors.push({
                        name: this.newProctorName.trim(),
                        department: this.newProctorDept.trim() || 'Khác',
                        code: ''
                    });
                    this.newProctorName = '';
                    this.newProctorDept = '';
                }
            },
            handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => { 
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const imported = XLSX.utils.sheet_to_json(firstSheet).map(row => ({
                        name: row['Họ và Tên'] || row['Name'] || row['Tên'] || 'Chưa rõ',
                        code: row['Mã'] || row['Code'] || '',
                        department: row['Đơn vị'] || row['Khoa'] || 'Khác'
                    }));
                    this.proctors = [...this.proctors, ...imported];
                    event.target.value = '';
                };
                reader.readAsArrayBuffer(file);
            }
        }));
    });

    let fullHistoryData = {}; 
    let currentDatesList = [];
    let currentSelectedDate = "";
    let currentExamName = "";
    let searchTimeout = null;

    function removeVietnameseTones(str) {
        if (!str) return "";
        str = str.toLowerCase();
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
        str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
        str = str.replace(/đ/g, "d");
        str = str.replace(/\u0300|\u0301|\u0303|\u0309|\u0323/g, ""); 
        str = str.replace(/\u02C6|\u0306|\u031B/g, ""); 
        return str.trim();
    }

    function filterExams() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const rawInput = document.getElementById('searchExamInput').value;
            const input = removeVietnameseTones(rawInput);
            const select = document.getElementById('examSelector');
            const options = select.options;
            
            let foundMatch = false;
            let matchIndex = 0;
            
            for (let i = 1; i < options.length; i++) { 
                const text = removeVietnameseTones(options[i].text);
                if (text.includes(input)) {
                    options[i].style.display = '';
                    if (!foundMatch && input !== '') {
                        matchIndex = i;
                        foundMatch = true;
                    }
                } else {
                    options[i].style.display = 'none';
                }
            }
            
            if (foundMatch && select.selectedIndex !== matchIndex) {
                select.selectedIndex = matchIndex;
                fetchExamDates();
            } else if (input === '') {
                select.selectedIndex = 0;
                fetchExamDates();
            }
        }, 300); 
    }

    document.addEventListener('DOMContentLoaded', function() {
        const autoLoadId = "{{ request('auto_load_exam') }}";
        if(autoLoadId) {
            setTimeout(() => {
                const selector = document.getElementById('examSelector');
                if(selector) {
                    selector.value = autoLoadId;
                    fetchExamDates();
                }
            }, 150);
        }
    });

    function fetchExamDates() {
        const examId = document.getElementById('examSelector').value;
        const resultDiv = document.getElementById('historyResult');
        const examControls = document.getElementById('examControls');

        if(!examId) {
            resultDiv.classList.add('hidden');
            examControls.classList.add('hidden');
            return;
        }

        const timestamp = new Date().getTime();
        axios.get(`{{ route('admin.proctors.history') }}?exam_id=${examId}&_t=${timestamp}`).then(response => {
            const res = response.data;
            fullHistoryData = res.data_by_date;
            currentExamName = res.exam_name;
            currentDatesList = res.dates;
            
            document.getElementById('printExamName').innerText = `BẢNG PHÂN CÔNG GIÁM THỊ`;

            if(currentDatesList.length > 0) {
                currentSelectedDate = currentDatesList[0];
                renderPaginationButtons();
                renderTableForSelectedDate();

                examControls.classList.remove('hidden');
                resultDiv.classList.remove('hidden');
            } else {
                resultDiv.classList.add('hidden');
                examControls.classList.add('hidden');
                alert("Kỳ thi này chưa có dữ liệu phân công.");
            }
        }).catch(err => {
            alert("Có lỗi xảy ra khi tải dữ liệu.");
        });
    }

    function renderPaginationButtons() {
        const container = document.getElementById('dayPagination');
        container.innerHTML = '';

        currentDatesList.forEach((date, index) => {
            const isSelected = (date === currentSelectedDate);
            const btn = document.createElement('button');
            btn.innerText = `Ngày ${index + 1}`;
            
            btn.className = isSelected 
                ? "bg-indigo-600 text-white font-bold py-2 px-5 rounded-md shadow-md transform scale-105 transition-all"
                : "bg-white text-gray-700 border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600 font-semibold py-2 px-5 rounded-md transition-all";
            
            btn.onclick = () => {
                currentSelectedDate = date;
                renderTableForSelectedDate();
                renderPaginationButtons(); 
            };
            container.appendChild(btn);
        });
    }

    function renderTableForSelectedDate() {
        const tbody = document.getElementById('historyTableBody');
        if(!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;

        tbody.classList.remove('animate-fade-in');
        void tbody.offsetWidth; 
        tbody.classList.add('animate-fade-in');

        document.getElementById('repeatExamName').innerText = currentExamName.toUpperCase();
        document.getElementById('repeatDate').innerText = currentSelectedDate;

        tbody.innerHTML = '';
        
        let dailyData = fullHistoryData[currentSelectedDate];

        dailyData.forEach((item, index) => {
            const roomName = item.room !== "(Trống)" ? item.room : "";
            const markGt1 = item.gt1 ? `<span style="font-weight: bold;">${roomName}</span>` : "";
            const markGt2 = item.gt2 ? `<span style="font-weight: bold;">${roomName}</span>` : "";

            const row = `<tr>
                    <td style="text-align: center;">${index + 1}</td>
                    <td style="text-align: left; font-weight: bold;">${item.name}</td>
                    <td style="text-align: left;">${item.department}</td>
                    <td style="text-align: center;">${markGt1}</td>
                    <td style="text-align: center;">${markGt2}</td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: left;"></td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    function generatePrintHTML(date, dailyData, addPageBreak) {
        let html = `
        <div class="${addPageBreak ? 'page-break' : ''}">
            <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                <div style="width: 40%; text-align: center;">
                    <p class="formal-text" style="font-weight: normal; margin-bottom: 8px; white-space: nowrap;">BỘ GIÁO DỤC VÀ ĐÀO TẠO</p>
                    <p class="formal-text" style="line-height: 1; margin-bottom: 8px;">........................................</p>
                    <p class="formal-text" style="line-height: 1;">........................................</p>
                </div>
                <div style="width: 5%;"></div>
                <div style="width: 55%; text-align: center;">
                    <p class="formal-text" style="font-weight: bold; margin-bottom: 2px;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                    <p class="formal-text" style="font-weight: bold;">Độc lập - Tự do - Hạnh phúc</p>
                    <div style="margin: 2px auto 0; border-bottom: 1px solid black; width: 60%;"></div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 35px; margin-bottom: 20px;">
                <h2 class="formal-title uppercase" style="margin: 0; font-size: 16pt; font-weight: bold; color: black;">BẢNG PHÂN CÔNG GIÁM THỊ</h2>
            </div>

            <table class="data-table">
                <colgroup>
                    <col style="width: 7%;">  
                    <col style="width: 24%;"> 
                    <col style="width: 19%;"> 
                    <col style="width: 12%;"> 
                    <col style="width: 12%;">  
                    <col style="width: 10%;">  
                    <col style="width: 16%;"> 
                </colgroup>
                <thead style="display: table-header-group;">
                    <tr class="title-row" style="border: none !important; background: transparent !important;">
                        <td colspan="7" style="border: none !important; text-align: right; font-style: italic; font-size: 11pt; padding: 4px 8px;">
                            <span style="color: #4b5563;">Kỳ thi:</span> <strong style="color: black;">${currentExamName.toUpperCase()}</strong> &nbsp;|&nbsp; 
                            <span style="color: #4b5563;">Ngày:</span> <strong style="color: black;">${date}</strong>
                        </td>
                    </tr>
                    <tr>
                        <th style="font-weight: bold; white-space: nowrap;">STT</th>
                        <th style="font-weight: bold; white-space: nowrap;">Họ và tên</th>
                        <th style="font-weight: bold; white-space: nowrap;">Đơn vị</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 1</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 2</th>
                        <th style="font-weight: bold; white-space: nowrap;">GT 3</th>
                        <th style="font-weight: bold; white-space: nowrap;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>`;
        
        dailyData.forEach((item, idx) => {
            const roomName = item.room !== "(Trống)" ? item.room : "";
            const markGt1 = item.gt1 ? `<span style="font-weight: bold;">${roomName}</span>` : "";
            const markGt2 = item.gt2 ? `<span style="font-weight: bold;">${roomName}</span>` : "";

            html += `<tr>
                    <td style="text-align: center;">${idx + 1}</td>
                    <td style="text-align: left; font-weight: bold;">${item.name}</td>
                    <td style="text-align: left;">${item.department}</td>
                    <td style="text-align: center;">${markGt1}</td>
                    <td style="text-align: center;">${markGt2}</td>
                    <td style="text-align: center;"></td>
                    <td style="text-align: left;"></td>
                </tr>`;
        });
        
        html += `</tbody>
            </table>
            
            <table class="signature-table" style="width: 100%; border: none !important; margin-top: 15px; page-break-inside: avoid;">
                <tr style="page-break-inside: avoid;">
                    <td style="width: 60%; border: none !important;"></td>
                    <td style="width: 40%; text-align: center; border: none !important; vertical-align: top;">
                        <p class="formal-text" style="margin: 0; page-break-inside: avoid; text-align: center; color: black;">
                            <span style="font-weight: bold; font-size: 13pt;">Người phân công</span><br>
                            <span style="font-size: 12pt; font-style: italic; font-weight: normal;">(Ký, ghi rõ họ tên)</span>
                        </p>
                        <div style="height: 80px;"></div>
                    </td>
                </tr>
            </table>
        </div>`;
        return html;
    }

    function printSingle() {
        if (!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;
        const container = document.getElementById('printAllContainer');
        container.innerHTML = generatePrintHTML(currentSelectedDate, fullHistoryData[currentSelectedDate], false);
        document.body.classList.add('print-all-mode');
        document.body.classList.remove('print-single-mode');
        window.print();
    }

    function exportWord() {
        if (!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;
        const dailyData = fullHistoryData[currentSelectedDate];
        generateWordFile([currentSelectedDate], [dailyData], `PhanCong_Ngay_${currentSelectedDate.replace(/[^0-9]/gi, '_')}`);
    }

    function exportExcel() {
        if (!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;
        const dailyData = fullHistoryData[currentSelectedDate];
        generateExcelFile([currentSelectedDate], [dailyData], `DS_GiamThi_Ngay_${currentSelectedDate.replace(/[^0-9]/gi, '_')}`);
    }

    function printAll() {
        if (currentDatesList.length === 0) return;
        const container = document.getElementById('printAllContainer');
        container.innerHTML = '';
        currentDatesList.forEach((date, index) => {
            const isLast = index === currentDatesList.length - 1;
            container.innerHTML += generatePrintHTML(date, fullHistoryData[date], !isLast);
        });
        document.body.classList.add('print-all-mode');
        document.body.classList.remove('print-single-mode');
        window.print();
    }

    function exportWordAll() {
        if (currentDatesList.length === 0) return;
        const dataArrays = currentDatesList.map(date => fullHistoryData[date]);
        generateWordFile(currentDatesList, dataArrays, `DS_GiamThi_ToanBo_${currentExamName.replace(/[^a-zA-Z0-9]/gi, '_')}`);
    }

    function exportExcelAll() {
        if (currentDatesList.length === 0) return;
        const dataArrays = currentDatesList.map(date => fullHistoryData[date]);
        generateExcelFile(currentDatesList, dataArrays, `DS_GiamThi_ToanBo_${currentExamName.replace(/[^a-zA-Z0-9]/gi, '_')}`);
    }

    function generateWordFile(datesArray, dataArrays, fileName) {
        let htmlContent = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:w="urn:schemas-microsoft-com:office:word"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <title>Bảng Phân Công Giám Thị</title>
            <style>
                @page WordSection1 { 
                    size: 595.3pt 841.9pt; 
                    margin: 42.5pt; 
                }
                div.WordSection1 { page: WordSection1; }
                body { font-family: "Times New Roman", serif; font-size: 13pt; }
                table { border-collapse: collapse; }
                .data-table { width: 100%; margin-top: 15px; table-layout: fixed; }
                .data-table td { border: 1px solid black; padding: 6px 4px; text-align: center; vertical-align: middle; font-size: 12pt; word-wrap: break-word; }
                .data-table th { border: 1px solid black; padding: 6px 4px; text-align: center; vertical-align: middle; font-size: 12pt; font-weight: bold; white-space: nowrap; }
                .align-left { text-align: left !important; padding-left: 8px !important; }
            </style>
        </head>
        <body>
        <div class="WordSection1">`;

        for(let i = 0; i < datesArray.length; i++) {
            const date = datesArray[i];
            const dailyData = dataArrays[i];

            if (i > 0) {
                htmlContent += `<br clear="all" style="page-break-before:always; mso-special-character:line-break;" />`;
            }

            htmlContent += `
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
                <tr>
                    <td width="40%" align="center" valign="top">
                        <p style="margin: 0 0 8px 0; font-size: 13pt; font-weight: normal; white-space: nowrap;">BỘ GIÁO DỤC VÀ ĐÀO TẠO</p>
                        <p style="margin: 0 0 8px 0; font-size: 13pt; line-height: 1.2;">........................................</p>
                        <p style="margin: 0; font-size: 13pt; line-height: 1.2;">........................................</p>
                    </td>
                    <td width="5%"></td>
                    <td width="55%" align="center" valign="top">
                        <p style="margin: 0; font-size: 13pt; font-weight: bold;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                        <p style="margin: 0; font-size: 13pt; font-weight: bold;">Độc lập - Tự do - Hạnh phúc</p>
                        <p style="margin: 0; font-size: 13pt;"><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></p>
                    </td>
                </tr>
            </table>

            <p style="text-align: center; font-size: 16pt; font-weight: bold; margin-top: 20px; margin-bottom: 5px; text-transform: uppercase;">
                BẢNG PHÂN CÔNG GIÁM THỊ
            </p>

            <table class="data-table" width="100%" border="1" cellpadding="5" cellspacing="0">
                <thead style="display: table-header-group;">
                    <tr style="border: none;">
                        <td colspan="7" style="border-top: none; border-left: none; border-right: none; border-bottom: none; text-align: right; padding-bottom: 5px;">
                            <span style="font-size: 11pt; font-style: italic;">
                                Kỳ thi: <b>${currentExamName.toUpperCase()}</b> &nbsp;|&nbsp; Ngày: <b>${date}</b>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th width="7%">STT</th>
                        <th class="align-left" width="24%">Họ và tên</th>
                        <th class="align-left" width="19%">Đơn vị</th>
                        <th width="12%">GT 1</th>
                        <th width="12%">GT 2</th>
                        <th width="10%">GT 3</th>
                        <th class="align-left" width="16%">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>`;

            dailyData.forEach((item, index) => {
                const roomName = item.room !== "(Trống)" ? item.room : "";
                const markGt1 = item.gt1 ? roomName : "";
                const markGt2 = item.gt2 ? roomName : "";

                htmlContent += `
                    <tr>
                        <td>${index + 1}</td>
                        <td class="align-left"><b>${item.name}</b></td>
                        <td class="align-left">${item.department}</td>
                        <td><b>${markGt1}</b></td>
                        <td><b>${markGt2}</b></td>
                        <td></td>
                        <td></td>
                    </tr>`;
            });

            htmlContent += `
                </tbody>
            </table>
            
            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-top: 30px; page-break-inside: avoid;">
                <tr style="page-break-inside: avoid;">
                    <td width="60%"></td>
                    <td width="40%" align="center" valign="top">
                        <p style="margin: 0; text-align: center; page-break-inside: avoid;">
                            <span style="font-weight: bold; font-size: 13pt;">Người phân công</span><br>
                            <span style="font-style: italic; font-size: 12pt;">(Ký, ghi rõ họ tên)</span>
                        </p>
                        <div style="height: 80px;"></div>
                    </td>
                </tr>
            </table>`;
        }

        htmlContent += `
        </div>
        </body></html>`;

        const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = fileName + `.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function generateExcelFile(datesArray, dataArrays, fileName) {
        const wb = XLSX.utils.book_new();

        for(let i = 0; i < datesArray.length; i++) {
            const date = datesArray[i];
            const dailyData = dataArrays[i];

            const excelData = [
                ["BỘ GIÁO DỤC VÀ ĐÀO TẠO", "", "", "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM", "", "", ""],
                ["----------------------------", "", "", "Độc lập - Tự do - Hạnh phúc", "", "", ""],
                ["", "", "", "", "", "", ""],
                ["BẢNG PHÂN CÔNG GIÁM THỊ", "", "", "", "", "", ""],
                ["", "", "", `Kỳ thi: ${currentExamName.toUpperCase()}  |  Ngày: ${date}`, "", "", ""],
                ["STT", "Họ và tên", "Đơn vị", "GT 1", "GT 2", "GT 3", "Ghi chú"]
            ];

            dailyData.forEach((item, index) => {
                const roomName = item.room !== "(Trống)" ? item.room : "";
                const markGt1 = item.gt1 ? roomName : "";
                const markGt2 = item.gt2 ? roomName : "";

                excelData.push([
                    index + 1, item.name, item.department,
                    markGt1, markGt2, "", ""  
                ]);
            });
            
            const startSigRow = excelData.length;
            excelData.push(["", "", "", "", "", "", ""]); 
            excelData.push(["", "", "", "Người phân công", "", "", ""]);
            excelData.push(["", "", "", "(Ký, ghi rõ họ tên)", "", "", ""]);
            excelData.push(["", "", "", "", "", "", ""]);
            excelData.push(["", "", "", "", "", "", ""]);

            const ws = XLSX.utils.aoa_to_sheet(excelData);
            
            // Bề rộng được nới ra tổng đúng 78
            ws['!cols'] = [ 
                {wch: 5}, {wch: 24}, {wch: 19}, 
                {wch: 10}, {wch: 10}, {wch: 5}, {wch: 9}  
            ];
            
            ws['!merges'] = [
                { s: { r: 0, c: 0 }, e: { r: 0, c: 2 } }, 
                { s: { r: 1, c: 0 }, e: { r: 1, c: 2 } }, 
                { s: { r: 0, c: 3 }, e: { r: 0, c: 6 } }, 
                { s: { r: 1, c: 3 }, e: { r: 1, c: 6 } }, 
                { s: { r: 3, c: 0 }, e: { r: 3, c: 6 } }, 
                { s: { r: 4, c: 0 }, e: { r: 4, c: 6 } }, // Đã ép dòng tiêu đề nằm nguyên khối A-G
                { s: { r: startSigRow + 1, c: 3 }, e: { r: startSigRow + 1, c: 6 } }, 
                { s: { r: startSigRow + 2, c: 3 }, e: { r: startSigRow + 2, c: 6 } }
            ];
            
            for(let j=0; j<7; j++) {
                const cellRef = XLSX.utils.encode_cell({r: 5, c: j});
                if(!ws[cellRef]) continue;
                ws[cellRef].s = { font: { bold: true }, alignment: { horizontal: "center", vertical: "center" } };
            }

            ws['A5'].s = { font: { italic: true }, alignment: { horizontal: "right" } };

            if(!ws['D'+(startSigRow+2)]) ws['D'+(startSigRow+2)] = { v: "Người phân công" };
            if(!ws['D'+(startSigRow+3)]) ws['D'+(startSigRow+3)] = { v: "(Ký, ghi rõ họ tên)" };
            ws['D'+(startSigRow+2)].s = { font: { bold: true }, alignment: { horizontal: "center" } };
            ws['D'+(startSigRow+3)].s = { font: { italic: true }, alignment: { horizontal: "center" } };

            ws['!pageSetup'] = { 
                paperSize: 9, 
                orientation: 'portrait', 
                fitToWidth: 1, 
                fitToHeight: 999,
                scale: 85
            };
            
            ws['!margins'] = { left: 0.3, right: 0.3, top: 0.5, bottom: 0.5, header: 0.3, footer: 0.3 };

            let safeSheetName = "Ngay_" + (i + 1);
            XLSX.utils.book_append_sheet(wb, ws, safeSheetName);
        }
        
        XLSX.writeFile(wb, fileName + `.xlsx`);
    }

    function deleteExam() {
        const examId = document.getElementById('examSelector').value;
        if (!examId) return;

        if(confirm("⚠️ CẢNH BÁO: Bạn có chắc chắn muốn xóa toàn bộ lịch thi này?\nHành động này không thể hoàn tác!")) {
            axios.delete(`/admin/proctors/${examId}`, {
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(res => {
                alert("Đã xóa lịch thi thành công!");
                window.location.href = "{{ route('admin.proctors.index') }}"; 
            }).catch(err => {
                let errorMsg = "Có lỗi xảy ra khi xóa!";
                if (err.response) {
                    errorMsg += `\nMã lỗi: ${err.response.status}\nChi tiết: ${err.response.data.message || err.response.statusText}`;
                } else {
                    errorMsg += `\nChi tiết: ${err.message}`;
                }
                alert(errorMsg);
                console.error(err);
            });
        }
    }
</script>
@endsection