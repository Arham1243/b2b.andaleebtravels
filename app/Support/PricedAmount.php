<?php

namespace App\Support;

readonly class PricedAmount
{
    public function __construct(
        public float $originalAmount,
        public float $sellAmount,
        public float $discountAmount,
        public ?string $discountType = null,
        public float $discountValue = 0.0,
        public string $product = '',
    ) {}

    public static function unchanged(float $amount, string $product = ''): self
    {
        $rounded = round(max(0, $amount), 2);

        return new self($rounded, $rounded, 0.0, null, 0.0, $product);
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount > 0.001;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'product' => $this->product,
            'original_amount' => $this->originalAmount,
            'sell_amount' => $this->sellAmount,
            'discount_amount' => $this->discountAmount,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toItineraryMeta(): array
    {
        return [
            'supplierPrice' => $this->originalAmount,
            'originalPrice' => $this->originalAmount,
            'totalPrice' => $this->sellAmount,
            'vendorDiscount' => $this->discountAmount,
            'vendorPricing' => $this->toSnapshot(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBookingFields(): array
    {
        return [
            'original_amount' => $this->originalAmount,
            'vendor_discount_amount' => $this->discountAmount,
            'vendor_discount_snapshot' => $this->hasDiscount() ? $this->toSnapshot() : null,
            'total_amount' => $this->sellAmount,
        ];
    }
}
