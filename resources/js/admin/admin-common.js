import * as XLSX from 'xlsx';

window.XLSX = XLSX; // Make it global for simple inline onchange handlers or attach event listeners here

document.addEventListener('DOMContentLoaded', () => {
    // 1. Script Import Giáo Viên
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
                for (let key in row) cleanRow[key.trim().toLowerCase()] = row[key];
                return {
                    code: cleanRow['mã gv'] || cleanRow['mã'] || '',
                    name: cleanRow['họ và tên'] || cleanRow['tên'] || '',
                    department: cleanRow['tổ chuyên môn'] || cleanRow['tổ'] || 'Chưa phân tổ',
                    max_slots_week: parseInt(cleanRow['định mức'] || cleanRow['số tiết'] || 18)
                };
            });

            if(parsedData.length > 0) {
                document.getElementById('importDataTeachers').value = JSON.stringify(parsedData);
                document.getElementById('importFormTeachers').submit();
            }
        };
        reader.readAsArrayBuffer(file);
    };

    // 2. Script Import Phân Công
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

    // 3. Script Import Lớp Học
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
});
