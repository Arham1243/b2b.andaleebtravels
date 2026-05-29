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

        $amountAfterDiscount = $originalAmount;
        $discountAmount = 0.0;
        $discountType = null;
        $discountValue = 0.0;

        if ($this->discountsEnabled($vendor)) {
            $discountRule = $this->resolveDiscountRule($this->resolveAgency($vendor), $product);

            if ($discountRule !== null) {
                $amountAfterDiscount = $this->calculateDiscountedAmount(
                    $originalAmount,
                    $discountRule['type'],
                    $discountRule['value'],
                );
                $discountAmount = round(max(0, $originalAmount - $amountAfterDiscount), 2);
                $discountType = $discountRule['type'];
                $discountValue = $discountRule['value'];
            }
        }

        $sellAmount = $amountAfterDiscount;
        $markupAmount = 0.0;
        $markupType = null;
        $markupValue = 0.0;

        $markupRule = $this->resolveMarkupRule($vendor, $product);

        if ($markupRule !== null) {
            $sellAmount = $this->calculateMarkedUpAmount(
                $amountAfterDiscount,
                $markupRule['type'],
                $markupRule['value'],
            );
            $markupAmount = round(max(0, $sellAmount - $amountAfterDiscount), 2);
            $markupType = $markupRule['type'];
            $markupValue = $markupRule['value'];
        }

        return new PricedAmount(
            originalAmount: $originalAmount,
            sellAmount: $sellAmount,
            discountAmount: $discountAmount,
            discountType: $discountType,
            discountValue: $discountValue,
            product: $product,
            amountAfterDiscount: $amountAfterDiscount,
            markupAmount: $markupAmount,
            markupType: $markupType,
            markupValue: $markupValue,
        );
    }

    /**
     * @param  array<string, mixed>  $itinerary
     * @return array<string, mixed>
     */
    public function applyFlightItinerary(?B2bVendor $vendor, array $itinerary): array
    {
        if (! $this->pricingAdjustmentsEnabled($vendor)) {
            return $itinerary;
        }

        $supplierPrice = $this->resolveItineraryAmount($itinerary);

        if ($supplierPrice <= 0) {
            return $itinerary;
        }

        $priced = $this->applyFlight($vendor, $supplierPrice);

        if ($priced->sellAmount <= 0 || ! $priced->hasAdjustment()) {
            return $itinerary;
        }

        $itinerary = array_merge($itinerary, $priced->toItineraryMeta());

        if (isset($itinerary['listing_meta']) && is_array($itinerary['listing_meta'])) {
            $itinerary['listing_meta']['price'] = $priced->sellAmount;
        }

        return $itinerary;
    }

    /**
     * @param  array<string, mixed>  $itinerary
     */
    private function resolveItineraryAmount(array $itinerary): float
    {
        foreach (['supplierPrice', 'originalPrice', 'totalPrice'] as $key) {
            if (! array_key_exists($key, $itinerary)) {
                continue;
            }

            $value = $itinerary[$key];

            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                $amount = round((float) $value, 2);
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        return 0.0;
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

        $markupRule = $this->resolveMarkupRule($vendor, $product);
        $amountAfterDiscount = $sellAmount;

        if ($markupRule !== null) {
            $amountAfterDiscount = $this->estimateAmountBeforeMarkup($sellAmount, $markupRule['type'], $markupRule['value']);
        }

        $discountRule = $this->resolveDiscountRule($this->resolveAgency($vendor), $product);
        $originalAmount = $amountAfterDiscount;

        if ($discountRule !== null) {
            $originalAmount = $this->estimateOriginalFromDiscounted($amountAfterDiscount, $discountRule['type'], $discountRule['value']);
        }

        $priced = $this->applyProduct($vendor, $product, $originalAmount);

        if (abs($priced->sellAmount - $sellAmount) > 0.02) {
            return [
                'original_amount' => $originalAmount,
                'vendor_discount_amount' => round(max(0, $originalAmount - $amountAfterDiscount), 2),
                'vendor_discount_snapshot' => $discountRule !== null ? [
                    'product' => $product,
                    'original_amount' => $originalAmount,
                    'amount_after_discount' => $amountAfterDiscount,
                    'sell_amount' => $sellAmount,
                    'discount_amount' => round(max(0, $originalAmount - $amountAfterDiscount), 2),
                    'discount_type' => $discountRule['type'],
                    'discount_value' => $discountRule['value'],
                ] : null,
                'vendor_markup_amount' => round(max(0, $sellAmount - $amountAfterDiscount), 2),
                'vendor_markup_snapshot' => $markupRule !== null ? [
                    'product' => $product,
                    'amount_after_discount' => $amountAfterDiscount,
                    'sell_amount' => $sellAmount,
                    'markup_amount' => round(max(0, $sellAmount - $amountAfterDiscount), 2),
                    'markup_type' => $markupRule['type'],
                    'markup_value' => $markupRule['value'],
                ] : null,
                'total_amount' => $sellAmount,
            ];
        }

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
        $amountAfterDiscount = round((float) ($itineraryData['amountAfterDiscount'] ?? $sellAmount), 2);
        $discountAmount = round((float) ($itineraryData['vendorDiscount'] ?? max(0, $originalAmount - $amountAfterDiscount)), 2);
        $markupAmount = round((float) ($itineraryData['vendorMarkup'] ?? max(0, $sellAmount - $amountAfterDiscount)), 2);
        $snapshot = $itineraryData['vendorPricing'] ?? null;

        if ($discountAmount <= 0.001 && $markupAmount <= 0.001) {
            return [
                'original_amount' => $sellAmount,
                'vendor_discount_amount' => 0,
                'vendor_discount_snapshot' => null,
                'vendor_markup_amount' => 0,
                'vendor_markup_snapshot' => null,
                'total_amount' => $sellAmount,
            ];
        }

        return [
            'original_amount' => $originalAmount,
            'vendor_discount_amount' => $discountAmount,
            'vendor_discount_snapshot' => is_array($snapshot) && $discountAmount > 0.001 ? $snapshot : null,
            'vendor_markup_amount' => $markupAmount,
            'vendor_markup_snapshot' => is_array($snapshot) && $markupAmount > 0.001 ? $snapshot : null,
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

    public function discountsEnabled(?B2bVendor $vendor): bool
    {
        $agency = $this->resolveAgency($vendor);

        return $agency !== null && (bool) ($agency->vendor_discounts_enabled ?? false);
    }

    public function agencyMarkupsEnabled(?B2bVendor $vendor): bool
    {
        $agency = $this->resolveAgency($vendor);

        return $agency !== null && (bool) ($agency->vendor_markups_enabled ?? false);
    }

    public function agentMarkupOverrideEnabled(?B2bVendor $vendor): bool
    {
        return $vendor !== null && (bool) ($vendor->agent_markup_override_enabled ?? false);
    }

    public function markupsEffective(?B2bVendor $vendor): bool
    {
        if ($vendor === null) {
            return false;
        }

        return $this->resolveMarkupRule($vendor, self::PRODUCT_FLIGHT) !== null
            || $this->resolveMarkupRule($vendor, self::PRODUCT_HOTEL) !== null;
    }

    public function pricingAdjustmentsEnabled(?B2bVendor $vendor): bool
    {
        return $this->discountsEnabled($vendor) || $this->markupsEffective($vendor);
    }

    /**
     * @return array<string, mixed>
     */
    public function markupSnapshotFromAgency(B2bVendor $agency): array
    {
        return [
            'agent_markup_override_enabled' => false,
            'agent_flight_markup_type' => null,
            'agent_flight_markup_value' => 0,
            'agent_hotel_markup_type' => null,
            'agent_hotel_markup_value' => 0,
        ];
    }

    /**
     * @return array{type: string, value: float}|null
     */
    public function resolveDiscountRule(?B2bVendor $agency, string $product): ?array
    {
        if ($agency === null || ! (bool) ($agency->vendor_discounts_enabled ?? false)) {
            return null;
        }

        $typeField = $product === self::PRODUCT_HOTEL ? 'hotel_discount_type' : 'flight_discount_type';
        $valueField = $product === self::PRODUCT_HOTEL ? 'hotel_discount_value' : 'flight_discount_value';

        return $this->normalizePricingRule(
            (string) ($agency->{$typeField} ?? ''),
            (float) ($agency->{$valueField} ?? 0),
            true,
        );
    }

    /**
     * @return array{type: string, value: float}|null
     */
    public function resolveMarkupRule(?B2bVendor $vendor, string $product): ?array
    {
        if ($vendor === null) {
            return null;
        }

        if ($this->agentMarkupOverrideEnabled($vendor)) {
            $agentRule = $this->resolveAgentMarkupRule($vendor, $product);
            if ($agentRule !== null) {
                return $agentRule;
            }
        }

        $agency = $this->resolveAgency($vendor);

        if ($agency === null || ! (bool) ($agency->vendor_markups_enabled ?? false)) {
            return null;
        }

        $typeField = $product === self::PRODUCT_HOTEL ? 'hotel_markup_type' : 'flight_markup_type';
        $valueField = $product === self::PRODUCT_HOTEL ? 'hotel_markup_value' : 'flight_markup_value';

        return $this->normalizePricingRule(
            (string) ($agency->{$typeField} ?? ''),
            (float) ($agency->{$valueField} ?? 0),
            false,
        );
    }

    /**
     * @return array{type: string, value: float}|null
     */
    private function resolveAgentMarkupRule(B2bVendor $vendor, string $product): ?array
    {
        $typeField = $product === self::PRODUCT_HOTEL ? 'agent_hotel_markup_type' : 'agent_flight_markup_type';
        $valueField = $product === self::PRODUCT_HOTEL ? 'agent_hotel_markup_value' : 'agent_flight_markup_value';

        return $this->normalizePricingRule(
            (string) ($vendor->{$typeField} ?? ''),
            (float) ($vendor->{$valueField} ?? 0),
            false,
        );
    }

    /**
     * @return array{type: string, value: float}|null
     */
    private function normalizePricingRule(string $type, float $value, bool $isDiscount): ?array
    {
        $type = strtolower(trim($type));
        $value = round(max(0, $value), 2);

        if (! in_array($type, [self::TYPE_PERCENT, self::TYPE_FIXED], true) || $value <= 0) {
            return null;
        }

        if ($isDiscount && $type === self::TYPE_PERCENT && $value >= 100) {
            return null;
        }

        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    private function calculateDiscountedAmount(float $originalAmount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT) {
            return round(max(0, $originalAmount * (1 - ($value / 100))), 2);
        }

        return round(max(0, $originalAmount - $value), 2);
    }

    private function calculateMarkedUpAmount(float $amountAfterDiscount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT) {
            return round(max(0, $amountAfterDiscount * (1 + ($value / 100))), 2);
        }

        return round(max(0, $amountAfterDiscount + $value), 2);
    }

    private function estimateOriginalFromDiscounted(float $discountedAmount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT && $value < 100) {
            return round($discountedAmount / (1 - ($value / 100)), 2);
        }

        return round($discountedAmount + $value, 2);
    }

    private function estimateAmountBeforeMarkup(float $sellAmount, string $type, float $value): float
    {
        if ($type === self::TYPE_PERCENT && $value > 0) {
            return round($sellAmount / (1 + ($value / 100)), 2);
        }

        return round(max(0, $sellAmount - $value), 2);
    }
}
