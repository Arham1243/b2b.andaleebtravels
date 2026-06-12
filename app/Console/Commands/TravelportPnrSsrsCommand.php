<?php

namespace App\Console\Commands;

use App\Models\B2bFlightBooking;
use App\Services\Travelport\TravelportApiClient;
use Illuminate\Console\Command;

class TravelportPnrSsrsCommand extends Command
{
    protected $signature = 'travelport:pnr-ssrs
        {locator? : Universal Record locator (364XDI) or provider locator (GZWKT5)}
        {--booking= : Booking ID from the database (use this instead of a locator)}';

    protected $description = 'Retrieve a live Universal Record from Travelport and list every SSR the GDS has on file (proves CTCM/CTCE/DOCS were received).';

    public function handle(?string $locator = null): int
    {
        $locator = $this->resolveLocator($locator);
        if ($locator === '') {
            $this->error('Provide a locator argument or use --booking=87');

            return self::FAILURE;
        }

        $this->info("Retrieving Universal Record {$locator} live from Travelport...");

        $response = (new TravelportApiClient)->universalRecordRetrieve($locator);
        if (! ($response['success'] ?? false)) {
            $this->error('Retrieve failed: ' . ($response['error'] ?? 'unknown error'));

            return self::FAILURE;
        }

        $raw = (string) ($response['raw'] ?? '');
        preg_match_all('/<(?:[\w-]+:)?SSR\b([^>]*?)\/?>/i', $raw, $matches);

        if ($matches[1] === []) {
            $this->warn('No SSR elements found on this PNR.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($matches[1] as $attrString) {
            preg_match_all('/([\w:-]+)\s*=\s*"([^"]*)"/', $attrString, $attrMatches, PREG_SET_ORDER);
            $attrs = [];
            foreach ($attrMatches as $attrMatch) {
                $attrs[$attrMatch[1]] = html_entity_decode($attrMatch[2], ENT_QUOTES | ENT_XML1);
            }

            $rows[] = [
                $attrs['Type'] ?? '',
                $attrs['Status'] ?? '',
                $attrs['Carrier'] ?? '',
                $attrs['FreeText'] ?? '',
            ];
        }

        $this->table(['Type', 'Status', 'Carrier', 'FreeText'], $rows);
        $this->info('These SSRs are stored on Travelport\'s side (host-assigned keys), confirming the GDS received them.');

        return self::SUCCESS;
    }

    private function resolveLocator(?string $locator): string
    {
        $bookingId = (int) $this->option('booking');
        if ($bookingId > 0) {
            $booking = B2bFlightBooking::find($bookingId);
            if (! $booking) {
                $this->error("Booking {$bookingId} not found.");

                return '';
            }

            $universalLocator = strtoupper(trim($booking->travelportUniversalLocator()));
            if ($universalLocator === '') {
                $this->error("Booking {$bookingId} has no Travelport universal record locator.");

                return '';
            }

            $this->line("Using universal locator {$universalLocator} from booking {$bookingId}.");

            return $universalLocator;
        }

        $locator = strtoupper(trim((string) ($locator ?? $this->argument('locator') ?? '')));

        return $locator;
    }
}
