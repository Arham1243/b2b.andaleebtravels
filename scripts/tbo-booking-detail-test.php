<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\B2bHotelBooking;
use App\Services\TboBookingDetailTestService;

$bookingId = (int) ($argv[1] ?? 4);
$booking = B2bHotelBooking::find($bookingId);

if (!$booking) {
    echo "Booking {$bookingId} not found\n";
    exit(1);
}

$report = app(TboBookingDetailTestService::class)->run($booking);

echo "Booking #{$report['meta']['booking_id']} supplier={$report['meta']['supplier']}\n";
echo "clientRef={$report['meta']['client_ref']} confirmation={$report['meta']['confirmation']} tboBookingId={$report['meta']['tbo_booking_id']}\n\n";

foreach ($report['results'] as $result) {
    echo "=== {$result['endpoint']} | {$result['label']} | HTTP {$result['status']} ===\n";
    echo substr($result['body'], 0, 1200) . "\n\n";
}
