<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2bSavedPassenger extends Model
{
    public const TYPE_ADULT = 'ADT';

    public const TYPE_CHILD = 'CHD';

    public const TYPE_INFANT = 'INF';

    protected $fillable = [
        'b2b_vendor_id',
        'title',
        'first_name',
        'last_name',
        'passenger_type',
        'dob',
        'nationality',
        'issuing_country',
        'passport_no',
        'passport_exp',
    ];

    protected $casts = [
        'dob'          => 'date',
        'passport_exp' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(B2bVendor::class, 'b2b_vendor_id');
    }

    public static function normalizeType(?string $type): string
    {
        $type = strtoupper(trim((string) $type));

        if (in_array($type, ['C06', 'CHD', 'CNN', 'CH', 'CHL'], true)) {
            return self::TYPE_CHILD;
        }

        if ($type === self::TYPE_INFANT) {
            return self::TYPE_INFANT;
        }

        return self::TYPE_ADULT;
    }

    /**
     * @param  array<string, mixed>|self  $passenger
     */
    public static function matchesBookingType(array|self $passenger, string $bookingType): bool
    {
        $stored = is_array($passenger)
            ? ($passenger['passenger_type'] ?? null)
            : $passenger->passenger_type;

        return self::normalizeType($stored) === self::normalizeType($bookingType);
    }

    /**
     * @param  list<array<string, mixed>|self>  $passengers
     * @return list<array<string, mixed>>
     */
    public static function filterForBookingType(array $passengers, string $bookingType): array
    {
        return array_values(array_filter(
            $passengers,
            static fn (array|self $passenger): bool => self::matchesBookingType($passenger, $bookingType),
        ));
    }

    public function typeLabel(): string
    {
        return match (self::normalizeType($this->passenger_type)) {
            self::TYPE_CHILD => 'Child',
            self::TYPE_INFANT => 'Infant',
            default => 'Adult',
        };
    }

    public function ageLabel(?DateTimeInterface $asOf = null): ?string
    {
        if ($this->dob === null) {
            return null;
        }

        return self::ageLabelFromDob($this->dob->format('Y-m-d'), $asOf);
    }

    public static function ageLabelFromDob(?string $dob, ?DateTimeInterface $asOf = null): ?string
    {
        $dob = trim((string) $dob);
        if ($dob === '') {
            return null;
        }

        try {
            $dobDate = Carbon::parse($dob)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        $asOf = Carbon::parse($asOf ?? now())->startOfDay();

        if ($dobDate->gt($asOf)) {
            return null;
        }

        $years = (int) $dobDate->diffInYears($asOf);
        if ($years >= 1) {
            return $years . ' yr' . ($years === 1 ? '' : 's');
        }

        $months = (int) $dobDate->diffInMonths($asOf);
        if ($months >= 1) {
            return $months . ' mo' . ($months === 1 ? '' : 's');
        }

        $days = (int) $dobDate->diffInDays($asOf);

        return $days . ' day' . ($days === 1 ? '' : 's');
    }

    /**
     * @return array<string, string>
     */
    public static function titleOptionsForType(string $type): array
    {
        return match (self::normalizeType($type)) {
            self::TYPE_CHILD, self::TYPE_INFANT => [
                'Mstr' => 'Mstr.',
                'Miss' => 'Miss',
            ],
            default => [
                'Mr' => 'Mr.',
                'Mrs' => 'Mrs.',
                'Ms' => 'Ms.',
                'Dr' => 'Dr.',
            ],
        };
    }
}
