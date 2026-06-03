<?php

namespace App\Support;

use App\Models\B2bVendor;
use App\Models\B2bWalletLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class WalletLedgerResolver
{
    /**
     * @return array{
     *     walletLedger: Collection<int, B2bWalletLedger>,
     *     ledgerFilters: array{category: string|null, from: string|null, till: string|null, has_filters: bool},
     *     ledgerTotalCount: int
     * }
     */
    public static function resolve(B2bVendor $vendor, Request $request): array
    {
        $ledgerFilters = self::resolveFilters($request);

        $ledgerQuery = $vendor->walletLedger()->with(['reference', 'settlementEntries'])->latest();

        if ($ledgerFilters['from'] !== null) {
            $ledgerQuery->whereDate('created_at', '>=', $ledgerFilters['from']);
        }

        if ($ledgerFilters['till'] !== null) {
            $ledgerQuery->whereDate('created_at', '<=', $ledgerFilters['till']);
        }

        $walletLedger = $ledgerQuery->get();

        if ($ledgerFilters['category'] !== null) {
            $walletLedger = $walletLedger
                ->filter(fn (B2bWalletLedger $entry) => WalletLedgerDescription::adminFilterCategory($entry) === $ledgerFilters['category'])
                ->values();
        }

        return [
            'walletLedger' => $walletLedger,
            'ledgerFilters' => $ledgerFilters,
            'ledgerTotalCount' => $vendor->walletLedger()->count(),
        ];
    }

    /**
     * @return array{category: string|null, from: string|null, till: string|null, has_filters: bool}
     */
    public static function resolveFilters(Request $request): array
    {
        $category = $request->input('ledger_category');
        if ($category === 'recharge') {
            $category = 'other';
        }
        $category = in_array($category, WalletLedgerDescription::ledgerFilterSlugs(), true) ? $category : null;

        $from = self::parseFilterDate($request->input('ledger_from'));
        $till = self::parseFilterDate($request->input('ledger_till'));

        if ($from !== null && $till !== null && Carbon::parse($from)->gt(Carbon::parse($till))) {
            [$from, $till] = [$till, $from];
        }

        return [
            'category' => $category,
            'from' => $from,
            'till' => $till,
            'has_filters' => $category !== null || $from !== null || $till !== null,
        ];
    }

    private static function parseFilterDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
