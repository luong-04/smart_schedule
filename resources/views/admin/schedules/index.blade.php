@extends('layouts.admin')
@section('title', 'Ma trận Xếp lịch')

@section('content')
<div class="flex flex-col gap-6">
    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 text-blue-600 rounded-2xl">
                <span class="material-symbols-outlined">filter_list</span>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Đang hiển thị</p>
                <form action="{{ route('matrix.index') }}" method="GET" id="classFilter">
                    <select name="class_id" onchange="this.form.submit()" class="bg-transparent border-none p-0 font-black text-slate-800 focus:ring-0 text-lg uppercase">
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                                Lớp {{ $class->name }} - Khối {{ $class->grade }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
        <button onclick="saveSchedule()" class="bg-blue-600 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">
            Lưu bản TKB hiện tại
        </button>
    </div>

    <div class="grid grid-cols-12 gap-6 items-start">
        <div class="col-span-12 lg:col-span-3 space-y-4">
            <div class="bg-[#F0F7FF] p-6 rounded-[2rem] border border-blue-100">
                <h3 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">inventory_2</span>
                    Môn đã phân công
                </h3>
                <div class="space-y-3" id="subject-pool">
                    @foreach($assignments as $assign)
                    <div class="p-4 bg-white rounded-2xl border border-blue-50 shadow-sm cursor-move hover:shadow-md transition-all group" 
                         draggable="true" 
                         data-assignment-id="{{ $assign->id }}">
                        <p class="font-black text-slate-700 uppercase text-xs tracking-tight">{{ $assign->subject->name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">{{ $assign->teacher->name }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-9 bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-4 py-5 border-r border-slate-100 text-[10px] font-black text-slate-400 uppercase">Tiết</th>
                        @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'] as $day)
                        <th class="px-4 py-5 text-[10px] font-black text-slate-700 uppercase tracking-widest">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @for($p = 1; $p <= 5; $p++)
                    <tr>
                        <td class="px-4 py-8 border-r border-slate-100 text-center font-black text-slate-300">{{ $p }}</td>
                        @for($d = 2; $d <= 7; $d++)
                        <td class="p-2 border-r border-slate-50">
                            <div class="schedule-slot min-h-[80px] rounded-2xl border-2 border-dashed border-slate-100 flex items-center justify-center p-2 text-center transition-all hover:bg-blue-50/30"
                                 data-day="{{ $d }}" 
                                 data-period="{{ $p }}">
                                </div>
                        </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Logic kéo thả cơ bản (Dùng chung cho v2.0)
    document.addEventListener('DOMContentLoaded', () => {
        const draggables = document.querySelectorAll('[draggable="true"]');
        const slots = document.querySelectorAll('.schedule-slot');

        draggables.forEach(dr => {
            dr.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('assignment_id', dr.dataset.assignmentId);
                e.dataTransfer.setData('html', dr.innerHTML);
            });
        });

        slots.forEach(slot => {
            slot.addEventListener('dragover', (e) => e.preventDefault());
            slot.addEventListener('drop', (e) => {
                const id = e.dataTransfer.getData('assignment_id');
                const html = e.dataTransfer.getData('html');
                slot.innerHTML = `<div class="bg-blue-600 text-white p-2 rounded-xl text-[10px] font-bold uppercase shadow-lg shadow-blue-100 w-full" data-id="${id}">${html}</div>`;
                slot.classList.remove('border-dashed');
                slot.classList.add('border-solid', 'border-blue-100');
            });
        });
    });

    function saveSchedule() {
        const data = [];
        document.querySelectorAll('.schedule-slot [data-id]').forEach(item => {
            const parent = item.parentElement;
            data.push({
                assignment_id: item.dataset.id,
                day_of_week: parent.dataset.day,
                period: parent.dataset.period
            });
        });

        fetch('{{ route("matrix.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ schedules: data })
        }).then(res => res.json()).then(data => alert('Đã lưu TKB thành công!'));
    }
</script>
@endsection