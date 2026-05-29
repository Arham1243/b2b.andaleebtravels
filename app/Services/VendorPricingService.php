<?php

namespace App\Services;

use App\Models\B2bVendor;
use App\Support\PricedAmount;

class VendorPricingService
{
    public const PRODUCT_FLIGHT = 'flight';
    public const PRODUCT_HOTEL = 'hotel';

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    public function applyFlight(?B2bVendor $vendor, float $originalAmount): PricedAmount
    {
        return $this->applyProduct($vendor, self::PRODUCT_FLIGHT, $originalAmount);
    }

    public function applyHotel(?B2bVendor $vendor, float $originalAmount): PricedAmount
    {
        return $this->applyProduct($vendor, self::PRODUCT_HOTEL, $originalAmount);
    }

    public function applyProduct(?B2bVendor $vendor, string $product, float $originalAmount): PricedAmount
    {
        $originalAmount = round(max(0, $originalAmount), 2);

        if ($originalAmount <= 0) {
            return PricedAmount::unchanged(0, $product);
        }

        $rule = $this->resolveRule($this->resolveAgency($vendor), $product);

        if ($rule === null) {
            return PricedAmount::unchanged($originalAmount, $product);
        }

        $sellAmount = $this->calculateSellAmount($originalAmount, $rule['type'], $rule['value']);
        $discountAmount = round(max(0, $originalAmount - $sellAmount), 2);

        return new PricedAmount(
            originalAmount: $originalAmount,
            sellAmount: $sellAmount,
            discountAmount: $discountAmount,
            discountType: $rule['type'],
            discountValue: $rule['value'],
            product: $product,
        );
    }

    /**
     * @param  array<string, mixed>  $itinerary
     * @return array<string, mixed>
     */
    public function applyFlightItinerary(?B2bVendor $vendor, array $itinerary): array
    {
        $supplierPrice = (float) ($itinerary['supplierPrice'] ?? $itinerary['originalPrice'] ?? $itinerary['totalPrice'] ?? 0);

        if ($supplierPrice <= 0) {
            return $itinerary;
        }

        $priced = $this->applyFlight($vendor, $supplierPrice);

        $itinerary = array_merge($itinerary, $priced->toItineraryMeta());

        if (isset($itinerary['listing_meta']) && is_array($itinerary['listing_meta'])) {
            $itinerary['listing_meta']['price'] = $priced->sellAmount;
        }

        return $itinerary;
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return list<array<string, mixed>>
     */
    public function applyFlightResults(?B2bVendor $vendor, array $results): array
    {
        return array_map(fn (array $result) => $this->applyFlightItinerary($vendor, $result), $results);
    }

    public function buildBookingPricingFromSell(?B2bVendor $vendor, string $product, float $sellAmount): array
    {
        $sellAmount = round(max(0, $sellAmount), 2);
        $rule = $this->resolveRule($this->resolveAgency($vendor), $product);

        if ($rule === null) {
            return [
                'original_amount' => $sellAmount,
                'vendor_discount_amount' => 0,
                'vendor_discount_snapshot' => null,
                'total_amount' => $sellAmount,
            ];
        }

        $originalAmount = $this->estimateOriginalFromSell($sellAmount, $rule['type'], $rule['value']);
        $discountAmount = round(max(0, $originalAmount - $sellAmount), 2);

        $priced = new PricedAmount(
            originalAmount: $originalAmount,
            sellAmount: $sellAmount,
            discountAmount: $discountAmount,
            discountType: $rule['type'],
            discountValue: $rule['value'],
            product: $product,
        );

        return $priced->toBookingFields();
    }

    /**
     * @param  array<string, mixed>  $itineraryData
     * @return array<string, mixed>
     */
    public function bookingFieldsFromFlightItinerary(array $itineraryData, float $fallbackSellAmount): array
    {
        $sellAmount = round((float) ($itineraryData['totalPrice'] ?? $fallbackSellAmount), 2);
        $originalAmount = round((float) ($itineraryData['originalPrice'] ?? $itineraryData['supplierPrice'] ?? $sellAmount), 2);
        $discountAmount = round((float) ($itineraryData['vendorDiscount'] ?? max(0, $originalAmount - $sellAmount)), 2);
        $snapshot = $itineraryData['vendorPricing'] ?? null;

        if ($discountAmount <= 0.001) {
            return [
                'original_amount' => $sellAmount,
                'vendor_discount_amount' => 0,
                'vendor_discount_snapshot' => null,
                'total_amount' => $sellAmount,
            ];
        }

        return [
            'original_amount' => $originalAmount,
            'vendor_discount_amount' => $discountAmount,
            'vendor_discount_snapshot' => is_array($snapshot) ? $snapshot : null,
            'total_amount' => $sellAmount,
        ];
    }

    public function resolveAgency(?B2bVendor $vendor): ?B2bVendor
    {
        if ($vendor === null) {
            return null;
        }

        return $vendor->walletAgency();
    }

    /**
     * @return array{type: string, value: float}|null
     */
    public function resolveRule(?B2bVendor $agency, string $product): ?array
    {
        if ($agency === null) {
            return null;
        }

        $typeField = $product === self::PRODUCT_HOTEL ? 'hotel_discount_type' : 'flight_discount_type';
        $valueField = $product === self::PRODUCT_HOTEL ? 'hotel_discount_value' : 'flight_discount_value';

        $type = strtolower(trim((string) ($agency->{$typeField} ?? '')));
        $value = round((float) ($agency->{$valueField} ?? 0), 2);

        if (! in_array($type, [self::TYPE_PERCENT, self::TYPE_FIXED], true) || $value <= 0) {
            return null;
        }

        if ($type === self::TYPE_PERCENT && $value >= 100) {
            return null;
        }

        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    private function calculateSellAmount(float $originalAmount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT) {
            return round(max(0, $originalAmount * (1 - ($value / 100))), 2);
        }

        return round(max(0, $originalAmount - $value), 2);
    }

    private function estimateOriginalFromSell(float $sellAmount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT && $value < 100) {
            return round($sellAmount / (1 - ($value / 100)), 2);
        }

        return round($sellAmount + $value, 2);
    }
}
