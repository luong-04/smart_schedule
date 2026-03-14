@extends('layouts.admin')

@section('content')
<div x-data="{ selectedClass: {{ $selectedClassId }} }" class="space-y-6">
    <div class="flex justify-between items-center bg-blue-50 p-4 rounded-2xl border border-blue-100">
        <div class="flex items-center gap-4">
            <span class="font-bold text-slate-700 uppercase text-xs">Đang xem lớp:</span>
            <select @change="window.location.href = '?class_id=' + $el.value" class="bg-white border-none rounded-xl shadow-sm px-4 py-2 font-bold text-blue-600 focus:ring-2 focus:ring-blue-500">
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                        Lớp {{ $class->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="bg-white text-slate-600 px-4 py-2 rounded-xl text-sm font-bold shadow-sm border border-gray-100">Tự động sắp</button>
            <button class="bg-blue-600 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-blue-200">Lưu thay đổi</button>
        </div>
    </div>

    <div class="flex gap-6">
        <div class="w-1/4 space-y-3">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest ml-2">Môn cần gán</h4>
            @foreach($assignments as $assign)
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-blue-50 cursor-move hover:border-blue-300 transition-all group">
                <div class="flex justify-between items-start">
                    <span class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-md font-bold">{{ $assign->subject->name }}</span>
                    <span class="text-[10px] font-black text-slate-300 italic">x{{ $assign->slots_per_week }} tiết</span>
                </div>
                <p class="text-sm font-bold text-slate-700 mt-2">{{ $assign->teacher->name }}</p>
            </div>
            @endforeach
        </div>

        <div class="flex-1 overflow-x-auto">
            <table class="w-full border-separate border-spacing-1.5">
                <thead>
                    <tr>
                        <th class="w-16"></th>
                        @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'] as $thu)
                        <th class="p-3 bg-slate-800 text-white rounded-xl text-xs font-bold uppercase tracking-widest">{{ $thu }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                <div id="source-subjects" class="w-1/4 space-y-3">
                    @foreach($assignments as $assign)
                    <div data-id="{{ $assign->id }}" class="bg-white p-4 rounded-2xl shadow-sm border border-blue-50 cursor-grab active:cursor-grabbing">
                        <span class="text-[10px] font-bold text-blue-600">{{ $assign->subject->name }}</span>
                        <p class="text-xs font-bold">{{ $assign->teacher->name }}</p>
                    </div>
                    @endforeach
                </div>

                @foreach(range(1, 10) as $tiet)
                <tr>
                    <td class="p-3 bg-white text-slate-400 font-black text-center rounded-xl text-xs shadow-sm">Tiết {{ $tiet }}</td>
                    @foreach(range(2, 7) as $thu)
                    <td data-day="{{ $thu }}" data-period="{{ $tiet }}" 
                        class="slot-box h-24 min-w-[120px] bg-white/50 border-2 border-dashed border-slate-200 rounded-2xl transition-all">
                        </td>
                    @endforeach
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Kích hoạt kéo từ danh sách "Môn cần gán"
        const sourceList = document.querySelector('.w-1\/4.space-y-3'); // Selector cho cột trái
        new Sortable(sourceList, {
            group: { name: 'shared', pull: 'clone', put: false },
            sort: false,
            animation: 150
        });

        // 2. Kích hoạt thả vào từng ô Tiết học
        document.querySelectorAll('.slot-box').forEach(el => {
            new Sortable(el, {
                group: 'shared',
                animation: 150,
                onAdd: function (evt) {
                    // Khi thả vào, có thể gọi API kiểm tra trùng lịch ngay lập tức ở đây
                    const assignmentId = evt.item.getAttribute('data-id');
                    const day = el.getAttribute('data-day');
                    const period = el.getAttribute('data-period');
                    console.log(`Gán ID ${assignmentId} vào Thứ ${day} Tiết ${period}`);
                }
            });
        });
    });

    function saveSchedule() {
        let data = [];
        document.querySelectorAll('.slot-box').forEach(box => {
            const item = box.querySelector('[data-id]');
            if (item) {
                data.push({
                    assignment_id: item.getAttribute('data-id'),
                    day_of_week: box.getAttribute('data-day'),
                    period: box.getAttribute('data-period')
                });
            }
        });

        fetch('{{ route("matrix.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ schedules: data, class_id: {{ $selectedClassId }} })
        }).then(res => alert('Đã lưu thời khóa biểu!'));
    }
</script>