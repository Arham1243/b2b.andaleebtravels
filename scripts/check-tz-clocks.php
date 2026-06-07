<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'App timezone: ' . config('app.timezone') . PHP_EOL;

$depRaw = '2026-06-18T12:15:00.000+04:00';
$arrRaw = '2026-06-18T12:40:00.000+03:00';
$dep = \Carbon\Carbon::parse($depRaw);
$arr = \Carbon\Carbon::parse($arrRaw);
echo 'arr tz: ' . $arr->timezone->getName() . ' ' . $arr->format('H:i P') . PHP_EOL;

// Simulate wrong approach: clock-only diff
[$dh, $dm] = [12, 15];
[$ah, $am] = [12, 40];
$clockDiff = ($ah * 60 + $am) - ($dh * 60 + $dm);
echo 'clock-only diff (12:15->12:40): ' . $clockDiff . ' min (WRONG for cross-tz)' . PHP_EOL;
echo 'tz-aware diff: ' . $dep->diffInMinutes($arr, false) . ' min (CORRECT)' . PHP_EOL;
