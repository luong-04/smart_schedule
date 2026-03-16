@extends('layouts.admin')
@section('title', 'Thêm Môn học mới')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('subjects.store') }}" method="POST" class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        @csrf
        
        <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
            <span class="material-symbols-outlined text-blue-600 text-2xl">library_add</span>
            <div>
                <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Thêm môn học mới</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Nhập thông tin cơ bản</p>
            </div>
        </div>

        <div class="p-6 md:p-8 space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Tên môn học</label>
                <input type="text" name="name" required class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner" placeholder="VD: Tin học, Thể dục, Toán học...">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Phân loại</label>
                    <select name="type" id="subjectType" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner">
                        <option value="theory">Lý thuyết</option>
                        <option value="practice">Thực hành</option>
                    </select>
                </div>

                <div class="space-y-2" id="roomTypeContainer" style="opacity: 0.5; pointer-events: none;">
                    <label class="text-[10px] font-black text-orange-500 uppercase ml-2">Loại phòng yêu cầu (Nếu có)</label>
                    <select name="room_type_id" id="roomTypeSelect" class="w-full bg-orange-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-orange-700 focus:ring-2 focus:ring-orange-500 shadow-inner">
                        <option value="">-- Không yêu cầu phòng --</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8 bg-slate-50/50 border-t border-slate-50 flex justify-end gap-3">
            <a href="{{ route('subjects.index') }}" class="px-6 py-3 rounded-xl font-bold text-xs text-slate-500 uppercase tracking-widest hover:bg-slate-100 transition-colors">Hủy</a>
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                Lưu môn học
            </button>
        </div>
    </form>
</div>

<script>
    // Logic: Chỉ cho phép chọn Phòng khi loại môn là "Thực hành"
    const typeSelect = document.getElementById('subjectType');
    const roomContainer = document.getElementById('roomTypeContainer');
    const roomSelect = document.getElementById('roomTypeSelect');

    typeSelect.addEventListener('change', function() {
        if (this.value === 'practice') {
            roomContainer.style.opacity = '1';
            roomContainer.style.pointerEvents = 'auto';
        } else {
            roomContainer.style.opacity = '0.5';
            roomContainer.style.pointerEvents = 'none';
            roomSelect.value = ''; // Reset về không yêu cầu
        }
    });
</script>
@endsection