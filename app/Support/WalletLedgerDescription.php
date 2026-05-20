<?php

namespace App\Support;

use App\Models\B2bFlightBooking;
use App\Models\B2bHotelBooking;
use App\Models\B2bWalletLedger;
use App\Models\B2bWalletRecharge;

final class WalletLedgerDescription
{
    public static function debitHotelPayment(B2bHotelBooking $booking): string
    {
        $name = $booking->hotel_name ?: 'Hotel';

        return "Wallet payment for hotel booking - {$name} (#{$booking->booking_number})";
    }

    public static function debitFlightPayment(B2bFlightBooking $booking): string
    {
        return 'Wallet payment for flight booking - ' . self::flightRouteLabel($booking) . " (#{$booking->booking_number})";
    }

    public static function creditHotelRefund(B2bHotelBooking $booking): string
    {
        $name = $booking->hotel_name ?: 'Hotel';

        return "Refund - Hotel booking {$name} (#{$booking->booking_number})";
    }

    public static function creditFlightRefund(B2bFlightBooking $booking): string
    {
        return 'Refund - Flight booking ' . self::flightRouteLabel($booking) . " (#{$booking->booking_number})";
    }

    public static function creditRecharge(B2bWalletRecharge $recharge): string
    {
        $method = self::formatPaymentMethod($recharge->payment_method);

        return "Wallet recharge via {$method} (#{$recharge->transaction_number})";
    }

    public static function manualAdjustment(string $description): string
    {
        return trim($description);
    }

    public static function isRefundDescription(?string $description): bool
    {
        $description = (string) $description;

        return str_starts_with($description, 'Refund -')
            || str_starts_with($description, 'Refund -');
    }

    public static function formatPaymentMethod(?string $method): string
    {
        return match (strtolower((string) $method)) {
            'bank_transfer' => 'Bank transfer',
            'card', 'payby' => 'Card',
            'tabby' => 'Tabby',
            'tamara' => 'Tamara',
            'wallet' => 'Wallet',
            default => $method ? ucfirst(str_replace('_', ' ', $method)) : 'Unknown',
        };
    }

    /** @return 'hotel'|'flight'|'recharge'|'other' */
    public static function adminFilterCategory(B2bWalletLedger $entry): string
    {
        $reference = $entry->reference;

        if ($reference instanceof B2bHotelBooking) {
            return 'hotel';
        }

        if ($reference instanceof B2bFlightBooking) {
            return 'flight';
        }

        if ($reference instanceof B2bWalletRecharge) {
            return 'recharge';
        }

        $desc = strtolower((string) $entry->description);

        if (str_contains($desc, 'hotel')) {
            return 'hotel';
        }

        if (str_contains($desc, 'flight')) {
            return 'flight';
        }

        if (str_contains($desc, 'recharge')) {
            return 'recharge';
        }

        return 'other';
    }

    public static function adminReasonLabel(B2bWalletLedger $entry): string
    {
        if ($entry->isVoided()) {
            return 'Voided';
        }

        if ($entry->is_manual || str_contains(strtolower((string) $entry->description), 'manual adjustment')) {
            return $entry->isCredit() ? 'Manual credit' : 'Manual debit';
        }

        $reference = $entry->reference;

        if ($reference instanceof B2bHotelBooking) {
            if ($entry->isDebit()) {
                return 'Hotel booking payment';
            }

            if (self::isRefundDescription($entry->description)) {
                return 'Hotel booking refund';
            }

            return 'Hotel booking';
        }

        if ($reference instanceof B2bFlightBooking) {
            if ($entry->isDebit()) {
                return 'Flight booking payment';
            }

            if (self::isRefundDescription($entry->description)) {
                return 'Flight booking refund';
            }

            return 'Flight booking';
        }

        if ($reference instanceof B2bWalletRecharge) {
            return 'Wallet recharge';
        }

        return self::reasonLabelFromDescription($entry);
    }

    private static function reasonLabelFromDescription(B2bWalletLedger $entry): string
    {
        $desc = strtolower((string) $entry->description);

        if (str_contains($desc, 'manual adjustment')) {
            return $entry->isCredit() ? 'Manual credit' : 'Manual debit';
        }

        if (str_contains($desc, 'recharge')) {
            return 'Wallet recharge';
        }

        if (self::isRefundDescription($entry->description)) {
            if (str_contains($desc, 'flight')) {
                return 'Flight booking refund';
            }
            if (str_contains($desc, 'hotel')) {
                return 'Hotel booking refund';
            }

            return 'Booking refund';
        }

        if ($entry->isDebit()) {
            if (str_contains($desc, 'flight')) {
                return 'Flight booking payment';
            }
            if (str_contains($desc, 'hotel')) {
                return 'Hotel booking payment';
            }

            return 'Wallet debit';
        }

        if ($entry->isCredit()) {
            return 'Wallet credit';
        }

        return 'Wallet entry';
    }

    public static function adminReasonClass(B2bWalletLedger $entry): string
    {
        $label = self::adminReasonLabel($entry);

        return match ($label) {
            'Voided' => 'pm-void',
            'Hotel booking payment', 'Flight booking payment', 'Wallet debit' => 'pm-debit-booking',
            'Hotel booking refund', 'Flight booking refund', 'Booking refund' => 'pm-refund',
            'Wallet recharge', 'Wallet credit' => 'pm-recharge',
            'Manual credit', 'Manual debit' => 'pm-manual',
            default => 'pm-system',
        };
    }

    /** @return array{label: string, url: string|null} */
    public static function adminReferenceLink(B2bWalletLedger $entry): array
    {
        $reference = $entry->reference;

        if ($reference instanceof B2bHotelBooking) {
            return [
                'label' => $reference->booking_number,
                'url' => route('admin.hotel-bookings.show', $reference->id),
            ];
        }

        if ($reference instanceof B2bFlightBooking) {
            return [
                'label' => $reference->booking_number,
                'url' => route('admin.flight-bookings.show', $reference->id),
            ];
        }

        if ($reference instanceof B2bWalletRecharge) {
            return [
                'label' => $reference->transaction_number,
                'url' => null,
            ];
        }

        return ['label' => '', 'url' => null];
    }

    private static function flightRouteLabel(B2bFlightBooking $booking): string
    {
        $from = strtoupper((string) ($booking->from_airport ?? ''));
        $to = strtoupper((string) ($booking->to_airport ?? ''));

        if ($from !== '' && $to !== '') {
            return "{$from} → {$to}";
        }

        return $from !== '' ? $from : ($to !== '' ? $to : 'Flight');
    }
}
