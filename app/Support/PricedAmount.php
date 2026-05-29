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
        public float $amountAfterDiscount = 0.0,
        public float $markupAmount = 0.0,
        public ?string $markupType = null,
        public float $markupValue = 0.0,
    ) {}

    public static function unchanged(float $amount, string $product = ''): self
    {
        $rounded = round(max(0, $amount), 2);

        return new self(
            originalAmount: $rounded,
            sellAmount: $rounded,
            discountAmount: 0.0,
            product: $product,
            amountAfterDiscount: $rounded,
        );
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount > 0.001;
    }

    public function hasMarkup(): bool
    {
        return $this->markupAmount > 0.001;
    }

    public function hasAdjustment(): bool
    {
        return $this->hasDiscount() || $this->hasMarkup();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return [
            'product' => $this->product,
            'original_amount' => $this->originalAmount,
            'amount_after_discount' => $this->amountAfterDiscount,
            'sell_amount' => $this->sellAmount,
            'discount_amount' => $this->discountAmount,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'markup_amount' => $this->markupAmount,
            'markup_type' => $this->markupType,
            'markup_value' => $this->markupValue,
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
            'vendorMarkup' => $this->markupAmount,
            'amountAfterDiscount' => $this->amountAfterDiscount,
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
            'vendor_markup_amount' => $this->markupAmount,
            'vendor_markup_snapshot' => $this->hasMarkup() ? $this->toSnapshot() : null,
            'total_amount' => $this->sellAmount,
        ];
    }
}
