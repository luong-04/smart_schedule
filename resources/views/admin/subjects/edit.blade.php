@extends('layouts.admin')
@section('title', 'Sửa Môn học')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('subjects.update', $subject->id) }}" method="POST" class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
        @csrf @method('PUT')
        
        <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3 bg-slate-50/50">
            <span class="material-symbols-outlined text-blue-600 text-2xl">edit_document</span>
            <div>
                <h3 class="font-black text-slate-700 uppercase tracking-widest text-sm">Chỉnh sửa môn học</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Cập nhật thông tin</p>
            </div>
        </div>

        <div class="p-6 md:p-8 space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Tên môn học</label>
                <input type="text" name="name" value="{{ $subject->name }}" required class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2">Phân loại</label>
                    <select name="type" id="subjectType" class="w-full bg-slate-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-inner">
                        <option value="theory" {{ $subject->type == 'theory' ? 'selected' : '' }}>Lý thuyết</option>
                        <option value="practice" {{ $subject->type == 'practice' ? 'selected' : '' }}>Thực hành</option>
                    </select>
                </div>

                <div class="space-y-2" id="roomTypeContainer" style="{{ $subject->type == 'theory' ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                    <label class="text-[10px] font-black text-orange-500 uppercase ml-2">Loại phòng yêu cầu</label>
                    <select name="room_type_id" id="roomTypeSelect" class="w-full bg-orange-50 border-none rounded-xl px-5 py-3.5 text-sm font-bold text-orange-700 focus:ring-2 focus:ring-orange-500 shadow-inner">
                        <option value="">-- Không yêu cầu phòng --</option>
                        @foreach($roomTypes as $rt)
                            <option value="{{ $rt->id }}" {{ $subject->room_type_id == $rt->id ? 'selected' : '' }}>{{ $rt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8 bg-slate-50/50 border-t border-slate-50 flex justify-end gap-3">
            <a href="{{ route('subjects.index') }}" class="px-6 py-3 rounded-xl font-bold text-xs text-slate-500 uppercase tracking-widest hover:bg-slate-100 transition-colors">Hủy</a>
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<script>
    // Xử lý ẩn/hiện mục chọn phòng
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
            roomSelect.value = '';
        }
    });
</script>
@endsection