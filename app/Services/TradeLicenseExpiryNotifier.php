<?php

namespace App\Services;

use App\Models\B2bVendor;
use App\Models\Config;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TradeLicenseExpiryNotifier
{
    protected string $adminEmail;

    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->adminEmail = (string) (
            $config['ADMIN_NOTIFICATION_EMAIL']
            ?? $config['ADMINEMAIL']
            ?? config('mail.from.address', 'info@andaleebtours.com')
        );
    }

    public function notifyExpiredLoginAttempt(B2bVendor $vendor): void
    {
        $agencyName = $vendor->display_agency_name ?: $vendor->name;
        $expiryDate = $vendor->effective_trade_license_expiry?->format('d M Y') ?? '—';

        try {
            Mail::send('user.emails.trade-license-expired-user', [
                'vendor' => $vendor,
                'agencyName' => $agencyName,
                'expiryDate' => $expiryDate,
            ], function ($message) use ($vendor) {
                $message->to($vendor->email)
                    ->subject('Trade license expired - ' . config('app.name'));
            });
        } catch (Exception $e) {
            Log::error('Trade license expired email (user) failed', [
                'vendor_id' => $vendor->id,
                'message' => $e->getMessage(),
            ]);
        }

        if (!$this->adminEmail) {
            return;
        }

        try {
            Mail::send('user.emails.trade-license-expired-admin', [
                'vendor' => $vendor,
                'agencyName' => $agencyName,
                'expiryDate' => $expiryDate,
            ], function ($message) use ($agencyName) {
                $message->to($this->adminEmail)
                    ->subject('Agency trade license expired - ' . $agencyName);
            });
        } catch (Exception $e) {
            Log::error('Trade license expired email (admin) failed', [
                'vendor_id' => $vendor->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
