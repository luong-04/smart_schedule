<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Classroom;
use App\Models\SubjectConfiguration;
use App\Services\ScheduleDataService;

$classId = 1;
$appliesFrom = '2026-04-13';
$scheduleName = 'Học kỳ 1 - 2024-2025'; // Fallback name

$classroom = Classroom::find($classId);
$assignments = Assignment::where('class_id', $classId)->with(['subject', 'teacher'])->get();
$curriculums = SubjectConfiguration::where('grade', $classroom->grade)->where('block', $classroom->block)->pluck('slots_per_week', 'subject_id')->all();

$service = new ScheduleDataService();
$counts = $service->getUsedCounts($scheduleName, $assignments, $appliesFrom);

echo "--- Sidebar Debug for Class 1 ---" . PHP_EOL;
foreach ($assignments as $as) {
    $max = $curriculums[$as->subject_id] ?? 0;
    $used = $counts[1][$as->id] ?? 0;
    echo "Sub: {$as->subject->name} | Max: $max | Used: $used | Remaining: " . ($max - $used) . PHP_EOL;
}
