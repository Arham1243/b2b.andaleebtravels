<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$b = App\Models\B2bFlightBooking::find(41);
if (! $b) {
    echo "not found\n";
    exit(1);
}

echo 'provider=' . $b->provider . PHP_EOL;
echo 'ticket_status=' . $b->ticket_status . PHP_EOL;
echo 'pnr=' . $b->sabre_record_locator . PHP_EOL;
echo 'tickets=' . json_encode($b->ticket_numbers) . PHP_EOL;
echo 'resolved=' . json_encode($b->resolvedTicketNumbers()) . PHP_EOL;
echo 'universal=' . $b->travelportUniversalLocator() . PHP_EOL;

$tr = $b->ticket_response;
if (is_array($tr)) {
    echo 'ticket_response keys: ' . implode(', ', array_keys($tr)) . PHP_EOL;
    echo 'ETR present: ' . (isset($tr['ETR']) || isset($tr['Body']['AirTicketingRsp']['ETR']) ? 'yes' : 'no') . PHP_EOL;
}
