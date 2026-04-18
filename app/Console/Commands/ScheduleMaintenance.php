<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ScheduleMaintenance extends Command
{
    /**
     * Tên lệnh và tham số.
     */
    protected $signature = 'schedule:maintenance';

    /**
     * Mô tả lệnh.
     */
    protected $description = 'Thực hiện bảo trì TKB: Gia hạn TKB cho tuần tiếp theo và dọn dẹp dữ liệu cũ (sau 1 năm).';

    /**
     * Thực thi logic bảo trì.
     */
    public function handle()
    {
        $this->info('--- BẮT ĐẦU BẢO TRÌ THỜI KHÓA BIỂU ---');

        $this->rolloverSchedules();
        $this->cleanupOldSchedules();

        $this->info('--- HOÀN TẤT BẢO TRÌ ---');
    }

    /**
     * Tự động gia hạn TKB cho tuần tiếp theo nếu không có thay đổi.
     */
    private function rolloverSchedules()
    {
        $this->comment('Đang kiểm tra gia hạn TKB cho tuần tới...');

        // Xác định tuần này (Monday to Sunday)
        $thisMonday = Carbon::now()->startOfWeek();
        $thisSunday = Carbon::now()->endOfWeek();

        // Xác định tuần tới
        $nextMonday = $thisMonday->copy()->addWeek();
        $nextSunday = $thisSunday->copy()->addWeek();

        // Lấy tất cả các class_id có TKB trong tuần này
        $currentSchedules = Schedule::where('applies_from', $thisMonday->toDateString())
            ->get()
            ->groupBy('class_id');

        $count = 0;
        foreach ($currentSchedules as $classId => $schedules) {
            // Kiểm tra xem tuần tới đã có TKB chưa
            $exists = Schedule::where('class_id', $classId)
                ->where('applies_from', $nextMonday->toDateString())
                ->exists();

            if (!$exists) {
                // Clone TKB của tuần này sang tuần tới
                foreach ($schedules as $sch) {
                    $newSch = $sch->replicate();
                    $newSch->applies_from = $nextMonday;
                    $newSch->applies_to   = $nextSunday;
                    $newSch->save();
                }
                $count++;
            }
        }

        $this->info("Đã gia hạn TKB cho {$count} lớp sang tuần mới ({$nextMonday->format('d/m')} - {$nextSunday->format('d/m')}).");
    }

    /**
     * Dọn dẹp TKB đã hết hạn hơn 1 năm.
     */
    private function cleanupOldSchedules()
    {
        $this->comment('Đang dọn dẹp dữ liệu TKB cũ (hơn 1 năm)...');

        $oneYearAgo = Carbon::now()->subYear();
        
        $deleted = Schedule::where('applies_to', '<', $oneYearAgo->toDateString())
            ->delete();

        if ($deleted > 0) {
            $this->info("Đã xóa {$deleted} bản ghi TKB cũ từ trước ngày {$oneYearAgo->format('d/m/Y')}.");
        } else {
            $this->info('Không có dữ liệu cũ cần xóa.');
        }
    }
}
