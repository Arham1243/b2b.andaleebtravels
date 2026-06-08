<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class B2bAdminRoutePermissionRegistry
{
    /** @var list<array{0: string, 1: list<string>|false}>|null */
    protected static ?array $rules = null;

    /**
     * @return list<string>|false
     */
    public static function permissionsForRoute(?string $routeName, ?Request $request = null): array|false
    {
        if ($routeName === null || $routeName === '') {
            return false;
        }

        $ambiguous = self::ambiguousSettingsRoutes($routeName, $request);
        if ($ambiguous !== null) {
            return $ambiguous;
        }

        foreach (self::allRules() as [$pattern, $perms]) {
            if (Str::is($pattern, $routeName)) {
                return $perms;
            }
        }

        return false;
    }

    /**
     * @return array<string>|false|null
     */
    protected static function ambiguousSettingsRoutes(?string $routeName, ?Request $request): array|false|null
    {
        if (! in_array($routeName, ['admin.settings.logo', 'admin.settings.details'], true)) {
            return null;
        }

        return ($request !== null && $request->isMethod('POST'))
            ? ['settings_edit']
            : ['settings_view'];
    }

    /** @return list<array{0: string, 1: list<string>|false}> */
    protected static function allRules(): array
    {
        return self::$rules ??= self::compile();
    }

    /** @return list<array{0: string, 1: list<string>|false}> */
    protected static function compile(): array
    {
        return array_merge(
            [
                ['admin.logout', []],
                ['admin.dashboard', []],
            ],
            self::standardCrud('portal-users', 'portal_admins'),
            self::standardCrud('portal-roles', 'portal_roles'),
            self::standardCrud('vendors', 'vendors'),
            [
                ['admin.vendors.change-status', ['vendors_edit']],
                ['admin.vendors.pending.index', ['vendors_pending_view']],
                ['admin.vendors.pending.show', ['vendors_pending_view']],
                ['admin.vendors.pending.approve', ['vendors_pending_manage']],
                ['admin.vendors.pending.reject', ['vendors_pending_manage']],
                ['admin.vendors.sub-agents.create', ['vendors_edit']],
                ['admin.vendors.sub-agents.store', ['vendors_edit']],
                ['admin.vendors.payment-reminder', ['vendors_wallet_manage']],
                ['admin.vendors.wallet-transactions.store', ['vendors_wallet_manage']],
                ['admin.vendors.wallet-transactions.update', ['vendors_wallet_manage']],
                ['admin.vendors.wallet-transactions.void', ['vendors_wallet_manage']],
            ],
            [
                ['admin.hotel-bookings.index', ['hotel_bookings_view']],
                ['admin.hotel-bookings.show', ['hotel_bookings_view']],
                ['admin.flight-bookings.index', ['flight_bookings_view']],
                ['admin.flight-bookings.show', ['flight_bookings_view']],
                ['admin.bookings.hotels.status', ['hotel_bookings_edit']],
                ['admin.bookings.hotels.cancel', ['hotel_bookings_edit']],
                ['admin.bookings.flights.status', ['flight_bookings_edit']],
                ['admin.bookings.flights.release-hold', ['flight_bookings_edit']],
                ['admin.bookings.flights.retry-fulfillment', ['flight_bookings_edit']],
                ['admin.bookings.flights.cancel', ['flight_bookings_edit']],
            ],
            [
                ['admin.inquiries.index', ['inquiries_view']],
                ['admin.inquiries.destroy', ['inquiries_delete']],
            ],
            [
                ['admin.wallet.bank-transfers.index', ['wallet_transfers_view']],
                ['admin.wallet.bank-transfers.confirm', ['wallet_transfers_manage']],
                ['admin.wallet.bank-transfers.reject', ['wallet_transfers_manage']],
            ],
            [
                ['admin.hotels.start', ['settings_view']],
                ['admin.flights.start', ['settings_view']],
            ],
            [
                ['admin.env', false],
                ['admin.env.save', false],
                ['admin.db.console.run', false],
            ],
        );
    }

    /**
     * @return list<array{0: string, 1: list<string>|false}>
     */
    protected static function standardCrud(string $segment, string $prefix): array
    {
        $routePrefix = "admin.{$segment}";

        return [
            ["{$routePrefix}.index", ["{$prefix}_view"]],
            ["{$routePrefix}.show", ["{$prefix}_view"]],
            ["{$routePrefix}.create", ["{$prefix}_add"]],
            ["{$routePrefix}.store", ["{$prefix}_add"]],
            ["{$routePrefix}.edit", ["{$prefix}_edit"]],
            ["{$routePrefix}.update", ["{$prefix}_edit"]],
            ["{$routePrefix}.destroy", ["{$prefix}_delete"]],
        ];
    }
}
