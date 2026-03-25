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
    }

    /* ===== GIAO DIỆN IN CHUYÊN NGHIỆP (CHUẨN VĂN BẢN HÀNH CHÍNH) ===== */
    @media print {
        @page { 
            size: A4 landscape; 
            margin: 15mm 20mm !important; /* Lề chuẩn A4 */
        }

        body { 
            background: white !important; 
            margin: 0 !important;
            padding: 0 !important;
            font-family: "Times New Roman", Times, serif !important; /* Bắt buộc Times New Roman */
            color: black !important;
        }

        /* Ẩn các nút bấm, menu, thanh cuộn */
        header, nav, aside, footer, .sidebar, .navbar, .print-hidden { 
            display: none !important; 
        }

        /* Xóa toàn bộ box-shadow, viền bo góc từ Tailwind */
        * {
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        
        .bg-white, .rounded-2xl, .border-gray-100, .bg-gray-50 {
            border: none !important;
            background: transparent !important;
        }
        
        .p-6, .md\:p-8 { padding: 0 !important; }
        body, main, #app, .container, .min-h-screen, .w-full, .mx-auto, .px-4 {
            width: 100% !important; max-width: 100% !important; margin: 0 !important;
        }

        #historyResult {
            display: block !important;
            width: 100% !important;
        }

        /* Bố cục văn bản in */
        #printHeader { margin-bottom: 25px !important; }
        .formal-text { font-size: 13pt !important; color: black !important; }
        .formal-title { font-size: 16pt !important; font-weight: bold !important; color: black !important; }
        
        /* Bảng chuẩn in ấn */
        #mainTable { 
            table-layout: fixed !important; 
            page-break-inside: auto !important; 
            width: 100% !important;
            border: 1px solid black !important; /* Viền bảng màu đen chuẩn */
        }
        
        tr { 
            page-break-inside: avoid !important; 
            page-break-after: auto !important; 
        }
        
        thead { display: table-header-group !important; }

        #mainTable th, #mainTable td { 
            color: black !important;
            font-size: 12pt !important; 
            vertical-align: middle !important;
            word-wrap: break-word !important; 
            padding: 8px 5px !important;
            border: 1px solid black !important; /* Ép viền đen toàn bộ ô */
        }

        #mainTable th { 
            background-color: transparent !important; /* Xóa nền xám để trông giống văn bản in */
            font-weight: bold !important;
            text-align: center !important;
        }

        /* Khối chữ ký */
        .print-signature {
            page-break-inside: avoid !important; 
            margin-top: 30px !important;
        }
    }
</style>

<div class="container mx-auto px-4 py-8" x-data="proctorManager()">
    
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print-hidden">
        <div>
            <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">Phân công Giám thị</h2>
            <p class="text-gray-500 mt-1 text-sm">Quản lý và sắp xếp lịch gác thi tự động</p>
        </div>
        
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-3 rounded-r-xl shadow-sm flex items-center">
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
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
                
                <div class="flex-grow overflow-hidden rounded-xl border border-gray-200 bg-gray-50 relative h-[500px]">
                    <div class="absolute inset-0 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">STT</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Họ và Tên</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase">Đơn vị</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template x-for="(proc, index) in proctors" :key="index">
                                    <tr class="hover:bg-indigo-50/50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="index + 1"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800" x-text="proc.name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="proc.department || 'Khác'"></td>
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
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-gray-100 print:p-0 print:border-none print:shadow-none">
            
            <div class="flex flex-col md:flex-row gap-4 mb-8 bg-gray-50 p-4 rounded-xl border border-gray-100 print-hidden">
                <div class="flex-grow flex items-center bg-white rounded-xl border border-gray-200 px-3">
                    <select id="examSelector" onchange="fetchExamDates()" class="w-full py-3 bg-transparent border-none outline-none text-gray-700 font-medium">
                        <option value="">-- Chọn Kỳ thi để tra cứu --</option>
                        @foreach($exams as $ex)
                            <option value="{{ $ex->id }}" {{ request('auto_load_exam') == $ex->id ? 'selected' : '' }}>
                                {{ $ex->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <button id="btnExportWord" onclick="exportWord()" style="display: none;" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all">
                    📝 Tải Word
                </button>
                <button id="btnExportExcel" onclick="exportExcel()" style="display: none;" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all">
                    📊 Tải Excel
                </button>
                <button id="btnExportPDF" onclick="window.print()" style="display: none;" class="bg-gray-800 hover:bg-black text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all">
                    🖨️ In / PDF
                </button>
            </div>

            <div id="historyResult" class="hidden">
                
                <div id="printHeader" class="w-full text-black">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-5/12 text-center">
                            <p class="formal-text font-semibold m-0">BỘ GIÁO DỤC VÀ ĐÀO TẠO</p>
                            <div class="mx-auto border-b border-black w-1/2 mt-1"></div>
                        </div>
                        <div class="w-7/12 text-center">
                            <p class="formal-text font-bold m-0">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                            <p class="formal-text font-bold m-0">Độc lập - Tự do - Hạnh phúc</p>
                            <div class="mx-auto border-b border-black w-3/5 mt-1"></div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-8 mb-4">
                        <h2 class="formal-title uppercase m-0" id="printExamName">BẢNG PHÂN CÔNG GIÁM THỊ</h2>
                        <p class="formal-text italic mt-2">Lịch thi ngày: <span id="printDate" class="font-bold">.....</span></p>
                    </div>
                </div>
                
                <div class="overflow-x-auto mb-4">
                    <table id="mainTable">
                        <colgroup>
                            <col style="width: 5%;">  
                            <col style="width: 20%;"> 
                            <col style="width: 17%;"> 
                            <col style="width: 12%;"> 
                            <col style="width: 8%;">  
                            <col style="width: 8%;">  
                            <col style="width: 8%;">  
                            <col style="width: 22%;"> 
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="whitespace-nowrap font-bold text-sm">STT</th>
                                <th class="font-bold text-sm">Họ và tên</th>
                                <th class="font-bold text-sm">Đơn vị</th>
                                <th class="whitespace-nowrap font-bold text-sm">Phòng thi</th>
                                <th class="whitespace-nowrap font-bold text-sm">GT 1</th>
                                <th class="whitespace-nowrap font-bold text-sm">GT 2</th>
                                <th class="whitespace-nowrap font-bold text-sm">GT 3</th>
                                <th class="font-bold text-sm">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="text-gray-900"></tbody>
                    </table>
                </div>

                <div class="flex justify-end print-signature">
                    <div class="text-center w-64 text-black mt-4">
                        <p class="formal-text font-bold mb-1">Người phân công</p>
                        <p class="text-[12pt] italic mb-20">(Ký, ghi rõ họ tên)</p>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 print-hidden flex items-center justify-between mt-6">
                    <span class="text-sm font-semibold text-gray-600">Lịch phân công từng ngày:</span>
                    <div id="dayPagination" class="flex space-x-2 overflow-x-auto">
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('proctorManager', () => ({
            activeTab: '{{ request()->has("auto_load_exam") ? "history" : "setup" }}',
            proctors: [],
            handleFileUpload(event) {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => { 
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, {type: 'array'});
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    this.proctors = XLSX.utils.sheet_to_json(firstSheet).map(row => ({
                        name: row['Họ và Tên'] || row['Name'] || row['Tên'] || 'Chưa rõ',
                        code: row['Mã'] || row['Code'] || '',
                        department: row['Đơn vị'] || row['Khoa'] || 'Khác'
                    }));
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

    function fetchExamDates() {
        const examId = document.getElementById('examSelector').value;
        const resultDiv = document.getElementById('historyResult');
        const btnExcel = document.getElementById('btnExportExcel');
        const btnPDF = document.getElementById('btnExportPDF');
        const btnWord = document.getElementById('btnExportWord');

        if(!examId) {
            resultDiv.classList.add('hidden');
            btnExcel.style.display = 'none';
            btnPDF.style.display = 'none';
            btnWord.style.display = 'none';
            return;
        }

        axios.get(`{{ route('admin.proctors.history') }}?exam_id=${examId}`).then(response => {
            const res = response.data;
            fullHistoryData = res.data_by_date;
            currentExamName = res.exam_name;
            currentDatesList = res.dates;
            
            document.getElementById('printExamName').innerText = `BẢNG PHÂN CÔNG GIÁM THỊ - ${res.exam_name.toUpperCase()}`;

            if(currentDatesList.length > 0) {
                currentSelectedDate = currentDatesList[0];
                renderPaginationButtons();
                renderTableForSelectedDate();

                btnExcel.style.display = 'block';
                btnPDF.style.display = 'block';
                btnWord.style.display = 'block';
                resultDiv.classList.remove('hidden');
            } else {
                resultDiv.classList.add('hidden');
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

        document.getElementById('printDate').innerText = currentSelectedDate;
        
        tbody.innerHTML = '';
        const dailyData = fullHistoryData[currentSelectedDate];

        dailyData.forEach((item, index) => {
            const checkIcon = `<span class="font-bold text-lg">X</span>`;
            const roomName = item.room !== "(Trống)" ? item.room : "";
            const markGt1 = item.gt1 ? checkIcon : "";
            const markGt2 = item.gt2 ? checkIcon : "";

            const row = `<tr class="hover:bg-indigo-50 transition-colors">
                    <td class="text-center">${index + 1}</td>
                    <td class="text-left font-bold" style="text-align: left;">${item.name}</td>
                    <td class="text-left" style="text-align: left;">${item.department}</td>
                    <td class="text-center font-bold whitespace-nowrap">${roomName}</td>
                    <td class="text-center">${markGt1}</td>
                    <td class="text-center">${markGt2}</td>
                    <td class="text-center"></td>
                    <td class="text-left"></td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    }

    function exportWord() {
        if (!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;
        const dailyData = fullHistoryData[currentSelectedDate];

        let htmlContent = `
        <html xmlns:v="urn:schemas-microsoft-com:vml"
              xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:w="urn:schemas-microsoft-com:office:word"
              xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
              xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <title>Bảng Phân Công Giám Thị</title>
            <xml>
                <w:WordDocument>
                    <w:View>Print</w:View>
                    <w:Zoom>100</w:Zoom>
                    <w:DoNotOptimizeForBrowser/>
                </w:WordDocument>
            </xml>
            <style>
                @page Section1 { 
                    size: 841.9pt 595.3pt; /* A4 Landscape */
                    mso-page-orientation: landscape; 
                    margin: 42.5pt 56.7pt 42.5pt 56.7pt; 
                    mso-footer: f1;
                }
                div.Section1 { page: Section1; }
                body { font-family: "Times New Roman", serif; font-size: 13pt; }
                
                table.data-table { border-collapse: collapse; width: 100%; margin-top: 20px;}
                table.data-table th, table.data-table td { border: 1px solid windowtext; padding: 6px 4px; text-align: center; vertical-align: middle; font-size: 12pt;}
                table.data-table th { font-weight: bold; }
                .align-left { text-align: left !important; padding-left: 8px !important; }
                p.MsoFooter { margin: 0; font-family: "Times New Roman", serif; font-size: 12pt; }
            </style>
        </head>
        <body>
        <div class="Section1">
            
            <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 30px;">
                <tr style="border: none;">
                    <td style="width: 40%; text-align: center; border: none; vertical-align: top; padding: 0;">
                        <p style="margin: 0; font-size: 13pt; font-weight: normal;">BỘ GIÁO DỤC VÀ ĐÀO TẠO</p>
                        <hr size="1" color="black" style="width: 45%; margin-top: 2px; margin-bottom: 0; padding: 0;">
                    </td>
                    <td style="width: 60%; text-align: center; border: none; vertical-align: top; padding: 0;">
                        <p style="margin: 0; font-size: 13pt; font-weight: bold;">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                        <p style="margin: 0; font-size: 13pt; font-weight: bold;">Độc lập - Tự do - Hạnh phúc</p>
                        <hr size="1" color="black" style="width: 55%; margin-top: 2px; margin-bottom: 0; padding: 0;">
                    </td>
                </tr>
            </table>

            <p style="text-align: center; font-size: 16pt; font-weight: bold; margin-top: 20px; margin-bottom: 5px; text-transform: uppercase;">
                BẢNG PHÂN CÔNG GIÁM THỊ - ${currentExamName.toUpperCase()}
            </p>
            <p style="text-align: center; font-size: 13pt; font-style: italic; margin-bottom: 20px;">
                Lịch thi ngày: ${currentSelectedDate}
            </p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%; white-space: nowrap;">STT</th>
                        <th class="align-left" style="width: 20%;">Họ và tên</th>
                        <th class="align-left" style="width: 17%;">Đơn vị</th>
                        <th style="width: 12%; white-space: nowrap;">Phòng thi</th>
                        <th style="width: 8%; white-space: nowrap;">GT 1</th>
                        <th style="width: 8%; white-space: nowrap;">GT 2</th>
                        <th style="width: 8%; white-space: nowrap;">GT 3</th>
                        <th class="align-left" style="width: 22%;">Ghi chú</th>
                    </tr>
                </thead>
                <tbody>`;

        dailyData.forEach((item, index) => {
            htmlContent += `
                <tr>
                    <td style="text-align: center;">${index + 1}</td>
                    <td class="align-left"><b>${item.name}</b></td>
                    <td class="align-left">${item.department}</td>
                    <td style="text-align: center; white-space: nowrap;"><b>${item.room !== "(Trống)" ? item.room : ""}</b></td>
                    <td style="text-align: center;">${item.gt1 ? "X" : ""}</td>
                    <td style="text-align: center;">${item.gt2 ? "X" : ""}</td>
                    <td style="text-align: center;"></td>
                    <td></td>
                </tr>`;
        });

        htmlContent += `
                </tbody>
            </table>
            
            <table style="width: 100%; border: none; margin-top: 30px; border-collapse: collapse; page-break-inside: avoid;">
                <tr style="border: none;">
                    <td style="width: 60%; border: none;"></td>
                    <td style="width: 40%; border: none; text-align: center; vertical-align: top;">
                        <p style="font-weight: bold; font-size: 13pt; margin: 0;">Người phân công</p>
                        <p style="font-style: italic; font-size: 12pt; margin: 0 0 80px 0;">(Ký, ghi rõ họ tên)</p>
                    </td>
                </tr>
            </table>

        </div>
        
        <div style="mso-element:footer" id="f1">
            <p class="MsoFooter" style="text-align: right;">Trang <span style="mso-field-code:' PAGE '"></span> / <span style="mso-field-code:' NUMPAGES '"></span></p>
        </div>

        </body></html>`;

        const blob = new Blob(['\ufeff', htmlContent], { type: 'application/msword' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        const safeFileName = currentSelectedDate.replace(/[^0-9]/gi, '_');
        link.download = `PhanCong_Ngay_${safeFileName}.doc`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function exportExcel() {
        if (!currentSelectedDate || !fullHistoryData[currentSelectedDate]) return;

        const dailyData = fullHistoryData[currentSelectedDate];
        const excelData = [
            ["BỘ GIÁO DỤC VÀ ĐÀO TẠO", "", "", "", "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM"],
            ["----------------------------", "", "", "", "Độc lập - Tự do - Hạnh phúc"],
            [""],
            ["", "", `BẢNG PHÂN CÔNG GIÁM THỊ - ${currentExamName.toUpperCase()}`],
            ["", "", `Lịch thi ngày: ${currentSelectedDate}`],
            [""],
            ["STT", "Họ và tên", "Đơn vị", "Phòng thi", "GT 1", "GT 2", "GT 3", "Ghi chú"]
        ];

        dailyData.forEach((item, index) => {
            excelData.push([
                index + 1, item.name, item.department,
                item.room !== "(Trống)" ? item.room : "",
                item.gt1 ? "X" : "", item.gt2 ? "X" : "",
                "", ""  
            ]);
        });
        
        const startSigRow = excelData.length;
        excelData.push(["", "", "", "", "", "", "", ""]); 
        excelData.push(["", "", "", "", "", "Người phân công", "", ""]);
        excelData.push(["", "", "", "", "", "(Ký, ghi rõ họ tên)", "", ""]);
        excelData.push(["", "", "", "", "", "", "", ""]);
        excelData.push(["", "", "", "", "", "", "", ""]);

        const ws = XLSX.utils.aoa_to_sheet(excelData);
        ws['!cols'] = [ {wch:5}, {wch:22}, {wch:20}, {wch:12}, {wch:10}, {wch:10}, {wch:10}, {wch:22} ];
        
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: 2 } }, 
            { s: { r: 1, c: 0 }, e: { r: 1, c: 2 } }, 
            { s: { r: 0, c: 4 }, e: { r: 0, c: 7 } }, 
            { s: { r: 1, c: 4 }, e: { r: 1, c: 7 } }, 
            { s: { r: 3, c: 0 }, e: { r: 3, c: 7 } }, 
            { s: { r: 4, c: 0 }, e: { r: 4, c: 7 } },
            { s: { r: startSigRow + 1, c: 5 }, e: { r: startSigRow + 1, c: 7 } }, 
            { s: { r: startSigRow + 2, c: 5 }, e: { r: startSigRow + 2, c: 7 } }
        ];
        
        if(!ws['F'+(startSigRow+2)]) ws['F'+(startSigRow+2)] = { v: "Người phân công" };
        if(!ws['F'+(startSigRow+3)]) ws['F'+(startSigRow+3)] = { v: "(Ký, ghi rõ họ tên)" };
        
        ws['F'+(startSigRow+2)].s = { font: { bold: true }, alignment: { horizontal: "center" } };
        ws['F'+(startSigRow+3)].s = { font: { italic: true }, alignment: { horizontal: "center" } };

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Phan_Cong");
        
        const safeFileName = currentSelectedDate.replace(/[^0-9]/gi, '_');
        XLSX.writeFile(wb, `DS_GiamThi_Ngay_${safeFileName}.xlsx`);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const autoLoadId = "{{ request('auto_load_exam') }}";
        if(autoLoadId) {
            fetchExamDates(); 
        }
    });
</script>
@endsection