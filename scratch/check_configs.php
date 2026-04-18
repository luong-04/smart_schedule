<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubjectConfiguration;
use App\Models\Subject;

$configs = SubjectConfiguration::all();
echo "Total Configs: " . $configs->count() . PHP_EOL;

foreach ($configs->take(50) as $c) {
    $sName = Subject::find($c->subject_id)?->name ?? 'Unknown';
    echo "ID: {$c->id} | Grade: {$c->grade} | Block: {$c->block} | Sub: {$sName} | Slots: {$c->slots_per_week}" . PHP_EOL;
}
