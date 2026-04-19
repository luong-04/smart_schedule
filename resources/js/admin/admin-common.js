import * as XLSX from 'xlsx';

window.XLSX = XLSX; // Đưa vào global để sử dụng trong các handler inline hoặc trình lắng nghe sự kiện

/**
 * Xử lý Import danh sách Giáo viên từ file Excel.
 * Sử dụng thư viện SheetJS (XLSX) để đọc dữ liệu từ máy khách trước khi gửi lên server.
 * 
 * @param {Event} event Sự kiện thay đổi file input.
 */
window.handleImportTeachers = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        let parsedData = jsonData.map(row => {
            let cleanRow = {};
            // Chuẩn hóa tên cột (trim và viết thường) để dễ dàng đối chiếu
            for (let key in row) cleanRow[key.trim().toLowerCase()] = row[key];
            return {
                code: cleanRow['mã gv'] || cleanRow['mã'] || '',
                name: cleanRow['họ và tên'] || cleanRow['tên'] || '',
                department: cleanRow['tổ chuyên môn'] || cleanRow['tổ'] || 'Chưa phân tổ',
                max_slots_week: parseInt(cleanRow['định mức'] || cleanRow['số tiết'] || 18)
            };
        });

        if(parsedData.length > 0) {
            // Chèn dữ liệu đã xử lý vào hidden input và submit form
            document.getElementById('importDataTeachers').value = JSON.stringify(parsedData);
            document.getElementById('importFormTeachers').submit();
        }
    };
    reader.readAsArrayBuffer(file);
};

/**
 * Xử lý Import danh sách Phân công giảng dạy từ file Excel.
 * 
 * @param {Event} event Sự kiện thay đổi file input.
 */
window.handleImportAssignments = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        let parsedData = jsonData.map(row => {
            let cleanRow = {};
            for (let key in row) cleanRow[key.trim().toLowerCase()] = row[key];
            
            return {
                teacher_code: cleanRow['mã gv'] || cleanRow['mã'] || '',
                class_name: cleanRow['lớp'] || cleanRow['tên lớp'] || '',
                subject_name: cleanRow['môn'] || cleanRow['tên môn'] || ''
            };
        });

        // Lọc bỏ các dòng trống hoặc không hợp lệ
        parsedData = parsedData.filter(item => item.teacher_code !== '' && item.class_name !== '' && item.subject_name !== '');

        if(parsedData.length > 0) {
            document.getElementById('importDataAssignments').value = JSON.stringify(parsedData);
            document.getElementById('importFormAssignments').submit();
        } else {
            alert("File Excel trống hoặc không đúng định dạng cột (Cần có 3 cột: Mã GV, Lớp, Môn)!");
        }
    };
    reader.readAsArrayBuffer(file);
};

/**
 * Xử lý Import danh sách Lớp học từ file Excel.
 * 
 * @param {Event} event Sự kiện thay đổi file input.
 */
window.handleImportClassrooms = function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = (e) => { 
        const data = new Uint8Array(e.target.result);
        const workbook = XLSX.read(data, {type: 'array'});
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const jsonData = XLSX.utils.sheet_to_json(firstSheet);
        
        let parsedData = jsonData.map(row => ({
            name: row['Tên lớp'] || row['Lớp'] || row['name'] || '',
            grade: row['Khối'] || row['Khối lớp'] || row['grade'] || '',
            shift: row['Ca học'] || row['Ca'] || row['shift'] || 'morning',
            homeroom_teacher: row['GVCN'] || row['Giáo viên chủ nhiệm'] || row['homeroom_teacher'] || null,
            block: row['Tổ hợp'] || row['Ban'] || row['block'] || 'Cơ bản'
        }));

        parsedData = parsedData.filter(item => item.name !== '' && item.grade !== '');

        if(parsedData.length > 0) {
            document.getElementById('importDataClassrooms').value = JSON.stringify(parsedData);
            document.getElementById('importFormClassrooms').submit();
        } else {
            alert("File Excel trống hoặc không tìm thấy cột 'Tên lớp' và 'Khối'!");
        }
    };
    reader.readAsArrayBuffer(file);
};
