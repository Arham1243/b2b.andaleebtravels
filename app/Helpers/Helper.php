<?php

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

if (! function_exists('sanitizedLink')) {
    function sanitizedLink($url)
    {
        return '//' . preg_replace('/^(https?:\/\/)?(www\.)?/', '', $url);
    }
}

if (! function_exists('walletBankProofUrl')) {
    /**
     * Proof files are stored under public/; URL is always via asset() (no /storage).
     */
    function walletBankProofUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        return asset($path);
    }
}

if (! function_exists('companyCurrency')) {
    /** ISO code for amounts in emails and plain-text contexts (no HTML symbol). */
    function companyCurrency(): string
    {
        return (string) config('app.company_currency', 'AED');
    }
}

if (! function_exists('currencySymbol')) {
    function currencySymbol(): HtmlString
    {
        return new HtmlString('<span class="dirham">D</span>');
    }
}

if (! function_exists('formatPrice')) {
    function formatPrice($price, bool $float = true): HtmlString
    {
        $val = $float
            ? number_format($price, 2, '.', ',')
            : number_format($price, 0, '.', ',');

        return new HtmlString(currencySymbol()->toHtml() . $val);
    }
}

if (! function_exists('formatCreditLimitDisplay')) {
    function formatCreditLimitDisplay(?\App\Models\B2bVendor $vendor, string $emptyLabel = 'Not set'): HtmlString
    {
        if ($vendor === null || ! $vendor->hasCreditLimit()) {
            return new HtmlString('<span class="text-muted">' . e($emptyLabel) . '</span>');
        }

        return formatPrice($vendor->creditLimitAmount());
    }
}

if (! function_exists('formatCreditMetricDisplay')) {
    /** Credit used/available — show em dash when no credit limit is configured. */
    function formatCreditMetricDisplay(?\App\Models\B2bVendor $vendor, float $amount, string $emptyLabel = '—'): HtmlString
    {
        if ($vendor === null || ! $vendor->hasCreditLimit()) {
            return new HtmlString('<span class="text-muted">' . e($emptyLabel) . '</span>');
        }

        return formatPrice($amount);
    }
}

if (! function_exists('formatBookingSupplierLabel')) {
    /** Supplier label for booking UIs (e.g. TBO must stay uppercase). */
    function formatBookingSupplierLabel(?string $supplier, string $emptyLabel = 'N/A'): string
    {
        $s = strtolower(trim((string) $supplier));

        if ($s === '') {
            return $emptyLabel;
        }

        return match ($s) {
            'tbo' => 'TBO',
            default => ucfirst($s),
        };
    }
}

if (! function_exists('formatBookingCancelledAt')) {
    /**
     * @param  \Carbon\Carbon|\DateTimeInterface|string|null  $value
     */
    function formatBookingCancelledAt($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable) {
            return null;
        }
    }
}

if (! function_exists('formatBookingCancelledByLabel')) {
    function formatBookingCancelledByLabel(?string $by): string
    {
        return match ($by) {
            'vendor' => 'Agent',
            'vendor_release' => 'Hold released (agent)',
            'admin_release' => 'Hold released (admin)',
            'admin' => 'Administrator',
            default => $by ? ucfirst(str_replace('_', ' ', $by)) : '—',
        };
    }
}

if (! function_exists('bookingCancellationApiPayload')) {
    /**
     * Cancel_response envelope stores payload under api_response; older rows may be raw JSON only.
     *
     * @return array<string, mixed>|null
     */
    function bookingCancellationApiPayload(?array $cancelResponse): ?array
    {
        if ($cancelResponse === null || $cancelResponse === []) {
            return null;
        }

        return isset($cancelResponse['api_response']) && is_array($cancelResponse['api_response'])
            ? $cancelResponse['api_response']
            : $cancelResponse;
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($date)
    {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format('M j, Y - g:i A');
    }
}
if (! function_exists('formatDate')) {
    function formatDate($date)
    {
        return Carbon::parse($date)->format('M j, Y');
    }
}
if (! function_exists('formatKey')) {
    function formatKey($value)
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
if (! function_exists('sanitizeBulletText')) {
    function sanitizeBulletText(string $text)
    {
        // Decode any HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Normalize bullet symbols to a single delimiter
        $text = preg_replace('/[•●▪◦]/u', '||', $text);

        // Normalize line breaks
        $text = preg_replace("/\r\n|\r|\n/", '||', $text);

        // Split into items
        $items = array_filter(array_map('trim', explode('||', $text)));

        // Build HTML list
        $html = '<ul class="tour-terms">';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

if (! function_exists('vendorPricing')) {
    function vendorPricing(): \App\Services\VendorPricingService
    {
        return app(\App\Services\VendorPricingService::class);
    }
}

/**
 * Unified flight sell price — agency discount then agent/agency markup.
 * Pass supplier/list fare; returns the amount shown across search, checkout, and bookings.
 */
if (! function_exists('flightSellPrice')) {
    function flightSellPrice(float $amount): float
    {
        return vendorPricing()->applyFlight(auth()->user(), $amount)->sellAmount;
    }
}

/**
 * @param  list<array<string, mixed>>  $results
 * @return list<array<string, mixed>>
 */
if (! function_exists('applyFlightSearchPricing')) {
    function applyFlightSearchPricing(array $results): array
    {
        return vendorPricing()->applyFlightResults(auth()->user(), $results);
    }
}

/**
 * @param  array<string, mixed>  $itineraryData
 * @return array<string, mixed>
 */
if (! function_exists('flightBookingPricingFields')) {
    function flightBookingPricingFields(array $itineraryData, float $fallbackSellAmount): array
    {
        return vendorPricing()->bookingFieldsFromFlightItinerary($itineraryData, $fallbackSellAmount);
    }
}

/**
 * Unified hotel sell price — agency discount then agent/agency markup.
 * Pass list price (after any legacy commission step).
 */
if (! function_exists('hotelSellPrice')) {
    function hotelSellPrice(float $listAmount): float
    {
        return vendorPricing()->applyHotel(auth()->user(), $listAmount)->sellAmount;
    }
}

/**
 * Supplier net → legacy commission → vendor discount.
 * Commission/markup will be consolidated into hotelSellPrice() later.
 */
if (! function_exists('hotelSellPriceFromNet')) {
    function hotelSellPriceFromNet(float $netAmount, float $commissionPercentage = 10): float
    {
        $listAmount = round($netAmount + ($commissionPercentage / 100) * $netAmount, 2);

        return hotelSellPrice($listAmount);
    }
}

/**
 * Yalago board payload → legacy commission → vendor discount.
 */
if (! function_exists('hotelSellPriceFromBoard')) {
    function hotelSellPriceFromBoard(array $board, float $commissionPercent): float
    {
        if (! empty($board['IsBindingPrice'])) {
            $listAmount = round((float) $board['GrossCost']['Amount'], 2);
        } else {
            $net = (float) $board['NetCost']['Amount'];
            $listAmount = round($net + ($net * $commissionPercent) / 100, 2);
        }

        return hotelSellPrice($listAmount);
    }
}

if (! function_exists('hotelBookingPricingFields')) {
    function hotelBookingPricingFields(float $sellAmount): array
    {
        return vendorPricing()->buildBookingPricingFromSell(
            auth()->user(),
            \App\Services\VendorPricingService::PRODUCT_HOTEL,
            $sellAmount
        );
    }
}

if (! function_exists('bookingVendorAgency')) {
    /** Agency account for a booking (parent agency when booked by a sub-agent). */
    function bookingVendorAgency(?\App\Models\B2bVendor $booker): ?\App\Models\B2bVendor
    {
        if ($booker === null) {
            return null;
        }

        if ($booker->parent_vendor_id) {
            $booker->loadMissing('parentVendor');

            return $booker->parentVendor;
        }

        return $booker;
    }
}

if (! function_exists('bookingVendorIsSubAgent')) {
    function bookingVendorIsSubAgent(?\App\Models\B2bVendor $booker): bool
    {
        return $booker !== null && $booker->parent_vendor_id !== null;
    }
}
