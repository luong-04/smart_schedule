@props(['errors' => session('import_errors'), 'successCount' => session('import_success_count')])

@if($errors && count($errors) > 0)
<div x-data="{ open: true }" class="bg-white rounded-[2rem] shadow-sm border border-amber-200 overflow-hidden mb-6">
    <div class="flex items-center justify-between p-5 bg-amber-50 border-b border-amber-100 cursor-pointer" @click="open = !open">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl">warning</span>
            </div>
            <div>
                <p class="text-sm font-black text-amber-800">
                    Import hoàn tất — {{ $successCount ?? 0 }} dòng thành công,
                    <span class="text-red-600">{{ count($errors) }} dòng lỗi</span>
                </p>
                <p class="text-[10px] text-amber-600 font-bold uppercase tracking-widest mt-0.5">Nhấn để xem / ẩn danh sách lỗi chi tiết</p>
            </div>
        </div>
        <span class="material-symbols-outlined text-amber-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
    </div>

    <div x-show="open" x-transition class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                <tr>
                    <th class="px-5 py-3 whitespace-nowrap">Dòng</th>
                    <th class="px-5 py-3 whitespace-nowrap">Mã</th>
                    <th class="px-5 py-3 whitespace-nowrap">Tên</th>
                    <th class="px-5 py-3 whitespace-nowrap text-red-500">Nguyên nhân lỗi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($errors as $err)
                <tr class="hover:bg-red-50/30 transition-colors">
                    <td class="px-5 py-3 font-black text-slate-500">#{{ $err['row'] ?? '' }}</td>
                    <td class="px-5 py-3 font-bold text-slate-700 uppercase">{{ $err['code'] ?? $err['teacher'] ?? '' }}</td>
                    <td class="px-5 py-3 font-bold text-slate-700">{{ $err['name'] ?? $err['class'] ?? $err['subject'] ?? '' }}</td>
                    <td class="px-5 py-3 font-medium text-red-500">{{ $err['reason'] ?? '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($successCount && !$errors)
<div class="bg-emerald-50 border border-emerald-200 rounded-[2rem] p-5 flex items-center gap-3 mb-6">
    <span class="material-symbols-outlined text-emerald-600 text-2xl">check_circle</span>
    <p class="text-sm font-bold text-emerald-700">🎉 Đã import thành công {{ $successCount }} dữ liệu!</p>
</div>
@endif
