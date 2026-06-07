<?php

$path = __DIR__ . '/../app/Http/Controllers/User/FlightController.php';
$lines = file($path);
// Remove getSabreToken (375) through listingTimestamp closing brace (1725) — 0-indexed 374..1724
$before = array_slice($lines, 0, 374);
$after = array_slice($lines, 1725);

$insert = <<<'PHP'

    private function buildFlightProviderManager(): FlightProviderManager
    {
        $providers = [];

        if ($this->isProviderEnabled('sabre')) {
            $providers[] = new SabreFlightProvider();
        }

        if ($this->isProviderEnabled('travelport')) {
            $providers[] = new TravelportFlightProvider();
        }

        return new FlightProviderManager($providers);
    }

    private function hasAnyFlightProviderEnabled(): bool
    {
        return $this->isProviderEnabled('sabre') || $this->isProviderEnabled('travelport');
    }

PHP;

file_put_contents($path, implode('', $before) . $insert . implode('', $after));
echo "Trimmed controller to " . count($before) + substr_count($insert, "\n") + count($after) . " lines\n";
