<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;
use App\Models\SubjectConfiguration;
use App\Models\Assignment;
use App\Models\Classroom;

echo "--- DATA AUDIT ---" . PHP_EOL;

// 1. Check for orphaned configurations
$configs = SubjectConfiguration::all();
$orphanedConfigs = 0;
foreach ($configs as $c) {
    if (!Subject::find($c->subject_id)) {
        $orphanedConfigs++;
    }
}
echo "Orphaned SubjectConfigurations: $orphanedConfigs" . PHP_EOL;

// 2. Check for orphaned assignments
$assignments = Assignment::all();
$orphanedAssignments = 0;
foreach ($assignments as $as) {
    if (!Subject::find($as->subject_id)) {
        $orphanedAssignments++;
    }
}
echo "Orphaned Assignments (Missing Subject): $orphanedAssignments" . PHP_EOL;

// 3. Check for missing configurations for assignments
$missingConfigCount = 0;
foreach ($assignments as $as) {
    $class = Classroom::find($as->class_id);
    if (!$class) continue;
    
    $config = SubjectConfiguration::where('grade', $class->grade)
        ->where('block', $class->block)
        ->where('subject_id', $as->subject_id)
        ->first();
        
    if (!$config) {
        $sName = Subject::find($as->subject_id)?->name ?? 'Unknown';
        echo "Missing Config for Class {$class->name} ({$class->grade} - {$class->block}) | Subject: {$sName}" . PHP_EOL;
        $missingConfigCount++;
    }
}
echo "Total Assignments missing Config: $missingConfigCount" . PHP_EOL;
echo "--- END AUDIT ---" . PHP_EOL;
