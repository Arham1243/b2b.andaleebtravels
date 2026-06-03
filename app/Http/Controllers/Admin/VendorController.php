<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorApprovedMail;
use App\Mail\VendorInviteMail;
use App\Mail\VendorPaymentReminderMail;
use App\Models\B2bVendor;
use App\Models\Config;
use App\Models\B2bWalletLedger;
use App\Services\AdminManualWalletTransactionService;
use App\Services\AdminWalletLedgerAdjustmentService;
use App\Services\VendorPricingService;
use App\Support\B2bVendorValidation;
use App\Support\VendorWalletCredit;
use App\Support\WalletLedgerResolver;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    use UploadImageTrait;

    public function __construct(
        private readonly AdminManualWalletTransactionService $manualWalletTransactionService,
        private readonly AdminWalletLedgerAdjustmentService $walletLedgerAdjustmentService,
    ) {}

    public function index()
    {
        $vendors = B2bVendor::approvedAgencies()->latest()->get();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function pendingIndex()
    {
        $vendors = B2bVendor::pendingSignups()->latest()->get();

        return view('admin.vendors.pending.index', compact('vendors'));
    }

    public function pendingShow(B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        return view('admin.vendors.pending.show', compact('vendor'));
    }

    public function approve(Request $request, B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        $vendor->update(['status' => 'active']);

        try {
            Mail::to($vendor->email)->send(new VendorApprovedMail($vendor));
        } catch (\Exception $e) {
            Log::error('Failed to send vendor approval email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.pending.index')
            ->with('notify_success', 'Agency approved successfully. Login notification email sent.');
    }

    public function reject(Request $request, B2bVendor $vendor)
    {
        if (!$vendor->isPendingApproval() || !$vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.index')
                ->with('notify_error', 'This signup request is no longer pending.');
        }

        if ($vendor->agency_logo) {
            Storage::disk('public')->delete($vendor->agency_logo);
        }

        $vendor->delete();

        return redirect()->route('admin.vendors.pending.index')
            ->with('notify_success', 'Signup request rejected and removed.');
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateVendor($request);

        $plainPassword = $validated['password'] ?? '12345678';

        $agencyLogo = $request->hasFile('agency_logo')
            ? $this->uploadImage($request->file('agency_logo'), 'Vendors/AgencyLogo')
            : null;

        $vendor = B2bVendor::create([
            'name' => $validated['travel_agency'],
            'travel_agency' => $validated['travel_agency'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'designation' => $validated['designation'],
            'username' => $validated['username'],
            'trade_license_number' => $validated['trade_license_number'],
            'trade_license_expiry' => $validated['trade_license_expiry'],
            'agency_logo' => $agencyLogo,
            'agent_code' => $this->generateUniqueAgentCode(),
            'password' => Hash::make($plainPassword),
            'status' => $validated['status'],
        ]);

        try {
            Mail::to($vendor->email)->send(new VendorInviteMail($vendor, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send vendor invite email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.index')
            ->with('notify_success', 'Vendor created successfully! Invite email sent.');
    }

    public function show(Request $request, B2bVendor $vendor)
    {
        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor);
        }

        $vendor->load('parentVendor');

        if ($vendor->isAgencyAccount()) {
            VendorWalletCredit::syncVendorPools($vendor);
            $vendor->refresh();
        }

        $ledgerData = WalletLedgerResolver::resolve($vendor, $request);
        $walletLedger = $ledgerData['walletLedger'];
        $ledgerFilters = $ledgerData['ledgerFilters'];
        $ledgerTotalCount = $ledgerData['ledgerTotalCount'];

        $hotelBookings = $vendor->hotelBookings()->latest()->get();
        $flightBookings = $vendor->flightBookings()->latest()->get();
        $subAgents = $vendor->subAgents()->latest()->get();

        $stats = [
            'hotel_bookings'  => $hotelBookings->count(),
            'flight_bookings' => $flightBookings->count(),
            'ledger_entries'  => $ledgerTotalCount,
            'sub_agents'      => $subAgents->count(),
        ];

        $openUnpaidCredits = $vendor->isAgencyAccount()
            ? $vendor->openUnpaidCredits()
            : collect();

        return view('admin.vendors.show', compact(
            'vendor',
            'walletLedger',
            'hotelBookings',
            'flightBookings',
            'subAgents',
            'stats',
            'ledgerFilters',
            'ledgerTotalCount',
            'openUnpaidCredits'
        ));
    }

    public function sendPaymentReminder(B2bVendor $vendor)
    {
        if ($vendor->isPendingApproval()) {
            return redirect()->back()->with('notify_error', 'Approve the vendor before sending a payment reminder.');
        }

        if (! $vendor->isAgencyAccount()) {
            return redirect()->back()->with('notify_error', 'Payment reminders can only be sent to agency accounts.');
        }

        $email = filter_var($vendor->email, FILTER_VALIDATE_EMAIL);

        if (! $email) {
            return redirect()->back()->with('notify_error', 'This vendor does not have a valid email address.');
        }

        VendorWalletCredit::syncVendorPools($vendor);
        $vendor->refresh();

        try {
            Mail::to($email)->send(new VendorPaymentReminderMail($vendor));
        } catch (\Exception $e) {
            Log::error('Vendor payment reminder email failed', [
                'vendor_id' => $vendor->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to send payment reminder. Please try again.');
        }

        return redirect()->back()->with('notify_success', 'Payment reminder sent to ' . $email . '.');
    }

    public function storeWalletTransaction(Request $request, B2bVendor $vendor)
    {
        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor)
                ->with('notify_error', 'Approve the vendor before adjusting wallet.');
        }

        $validated = $request->validate([
            'type' => 'required|in:credit,debit,unpaid_credit,unpaid_credit_settlement',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'transaction_date' => 'required|date|before_or_equal:today',
            'transaction_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|min:3|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
            'settles_ledger_id' => 'nullable|integer|exists:b2b_wallet_ledger,id',
        ]);

        $adminId = (int) auth('admin')->id();

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $this->uploadWalletLedgerAttachment($request->file('attachment'));
        }

        try {
            $entry = $this->manualWalletTransactionService->store($vendor, $validated, $adminId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('notify_error', collect($e->errors())->flatten()->first());
        }

        if ($entry->isUnpaidCreditSettlement()) {
            $message = 'Payment received recorded for ' . number_format((float) $entry->amount, 2) . ' AED (wallet balance unchanged: ' . number_format((float) $vendor->fresh()->main_balance, 2) . ' AED).';
        } elseif ($entry->isUnpaidCredit()) {
            $message = 'Unpaid credit of ' . number_format((float) $entry->amount, 2) . ' AED added. New balance: ' . number_format((float) $vendor->fresh()->main_balance, 2) . ' AED.';
        } else {
            $verb = $entry->isCredit() ? 'credited' : 'debited';
            $message = 'Wallet ' . $verb . ' ' . number_format((float) $entry->amount, 2) . ' AED. New balance: ' . number_format((float) $vendor->fresh()->main_balance, 2) . ' AED.';
        }

        return redirect()->back()->with('notify_success', $message);
    }

    public function updateWalletTransaction(Request $request, B2bVendor $vendor, B2bWalletLedger $ledger)
    {
        $this->ensureLedgerBelongsToVendor($vendor, $ledger);

        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor)
                ->with('notify_error', 'Approve the vendor before adjusting wallet.');
        }

        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01|max:99999999.99',
            'transaction_date' => 'required|date|before_or_equal:today',
            'transaction_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|min:3|max:500',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:5120',
            'remove_attachment' => 'nullable|boolean',
        ]);

        $adminId = (int) auth('admin')->id();

        if ($request->boolean('remove_attachment')) {
            $this->deleteWalletLedgerAttachment($ledger->attachment_path);
            $validated['attachment_path'] = null;
        } elseif ($request->hasFile('attachment')) {
            $this->deleteWalletLedgerAttachment($ledger->attachment_path);
            $validated['attachment_path'] = $this->uploadWalletLedgerAttachment($request->file('attachment'));
        }

        try {
            $entry = $this->walletLedgerAdjustmentService->update($ledger, $validated, $adminId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('notify_error', collect($e->errors())->flatten()->first());
        }

        return redirect()->back()
            ->with('notify_success', 'Transaction updated. Wallet balance is now ' . number_format((float) $vendor->fresh()->main_balance, 2) . ' AED.');
    }

    public function voidWalletTransaction(Request $request, B2bVendor $vendor, B2bWalletLedger $ledger)
    {
        $this->ensureLedgerBelongsToVendor($vendor, $ledger);

        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.pending.show', $vendor)
                ->with('notify_error', 'Approve the vendor before adjusting wallet.');
        }

        $adminId = (int) auth('admin')->id();

        try {
            $this->walletLedgerAdjustmentService->void($ledger, $adminId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->with('notify_error', collect($e->errors())->flatten()->first());
        }

        return redirect()->back()
            ->with('notify_success', 'Transaction voided. Wallet balance is now ' . number_format((float) $vendor->fresh()->main_balance, 2) . ' AED.');
    }

    private function ensureLedgerBelongsToVendor(B2bVendor $vendor, B2bWalletLedger $ledger): void
    {
        if ((int) $ledger->b2b_vendor_id !== (int) $vendor->id) {
            abort(404);
        }
    }

    private function uploadWalletLedgerAttachment(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs('uploads/wallet-ledger', $filename, 'public');
    }

    private function deleteWalletLedgerAttachment(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function edit(B2bVendor $vendor)
    {
        if ($vendor->isAgencyAccount()) {
            VendorWalletCredit::syncVendorPools($vendor);
            $vendor->refresh();
        }

        $config = Config::pluck('config_value', 'config_key')->toArray();
        $adminProviders = $this->parseProviderConfig(
            $config['HOTEL_SEARCH_PROVIDERS'] ?? null,
            ['yalago', 'tbo', 'tripindeal']
        ) ?? ['yalago', 'tbo', 'tripindeal'];
        $adminFlightProviders = $this->parseProviderConfig(
            $config['FLIGHT_SEARCH_PROVIDERS'] ?? null,
            ['sabre']
        ) ?? ['sabre'];

        return view('admin.vendors.edit', compact('vendor', 'adminProviders', 'adminFlightProviders'));
    }

    public function update(Request $request, B2bVendor $vendor)
    {
        $validated = $this->validateVendor($request, $vendor->id, $vendor);

        if (!$vendor->isPendingApproval() && ($validated['status'] ?? '') === 'pending') {
            return redirect()->back()
                ->withInput()
                ->with('notify_error', 'Approved vendors cannot be set back to pending approval.');
        }

        if ($vendor->parent_vendor_id) {
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'status' => $validated['status'],
            ];
        } else {
            $data = [
                'name' => $validated['travel_agency'],
                'travel_agency' => $validated['travel_agency'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'designation' => $validated['designation'],
                'username' => $validated['username'],
                'trade_license_number' => $validated['trade_license_number'],
                'trade_license_expiry' => $validated['trade_license_expiry'],
                'status' => $validated['status'],
                'hotel_search_providers' => $this->parseProviderConfig(
                    $request->input('hotel_search_providers'),
                    ['yalago', 'tbo', 'tripindeal']
                ),
                'flight_search_providers' => $this->parseProviderConfig(
                    $request->input('flight_search_providers'),
                    ['sabre']
                ),
                'vendor_discounts_enabled' => $request->boolean('vendor_discounts_enabled'),
                'flight_discount_type' => $request->boolean('vendor_discounts_enabled')
                    ? $this->normalizeDiscountType($validated['flight_discount_type'] ?? null, (float) ($validated['flight_discount_value'] ?? 0))
                    : null,
                'flight_discount_value' => $request->boolean('vendor_discounts_enabled')
                    ? $this->normalizeDiscountValue($validated['flight_discount_type'] ?? null, $validated['flight_discount_value'] ?? 0)
                    : 0,
                'hotel_discount_type' => $request->boolean('vendor_discounts_enabled')
                    ? $this->normalizeDiscountType($validated['hotel_discount_type'] ?? null, (float) ($validated['hotel_discount_value'] ?? 0))
                    : null,
                'hotel_discount_value' => $request->boolean('vendor_discounts_enabled')
                    ? $this->normalizeDiscountValue($validated['hotel_discount_type'] ?? null, $validated['hotel_discount_value'] ?? 0)
                    : 0,
                'vendor_markups_enabled' => $request->boolean('vendor_markups_enabled'),
                'flight_markup_type' => $request->boolean('vendor_markups_enabled')
                    ? $this->normalizeMarkupType($validated['flight_markup_type'] ?? null, (float) ($validated['flight_markup_value'] ?? 0))
                    : null,
                'flight_markup_value' => $request->boolean('vendor_markups_enabled')
                    ? $this->normalizeMarkupValue($validated['flight_markup_type'] ?? null, $validated['flight_markup_value'] ?? 0)
                    : 0,
                'hotel_markup_type' => $request->boolean('vendor_markups_enabled')
                    ? $this->normalizeMarkupType($validated['hotel_markup_type'] ?? null, (float) ($validated['hotel_markup_value'] ?? 0))
                    : null,
                'hotel_markup_value' => $request->boolean('vendor_markups_enabled')
                    ? $this->normalizeMarkupValue($validated['hotel_markup_type'] ?? null, $validated['hotel_markup_value'] ?? 0)
                    : 0,
            ];

            if ($request->hasFile('agency_logo')) {
                $data['agency_logo'] = $this->uploadImage(
                    $request->file('agency_logo'),
                    'Vendors/AgencyLogo',
                    $vendor->agency_logo
                );
            }
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $vendor->update($data);

        if ($vendor->isAgencyAccount()) {
            VendorWalletCredit::syncVendorPools($vendor);
        }

        $vendor->refresh();

        if ($vendor->isPendingApproval() && $vendor->isAgencyAccount()) {
            return redirect()->route('admin.vendors.edit', $vendor)
                ->with('notify_success', 'Vendor updated successfully.');
        }

        if ($vendor->parent_vendor_id) {
            return redirect()->route('admin.vendors.edit', $vendor)
                ->with('notify_success', 'Sub agent updated successfully.');
        }

        return redirect()->route('admin.vendors.edit', $vendor)
            ->with('notify_success', 'Vendor updated successfully.');
    }

    public function createSubAgent(B2bVendor $vendor)
    {
        if ($vendor->parent_vendor_id) {
            return redirect()->route('admin.vendors.show', $vendor->parent_vendor_id)
                ->with('notify_error', 'Sub agents cannot be added under another sub agent.');
        }

        return view('admin.vendors.sub-agents.create', compact('vendor'));
    }

    public function storeSubAgent(Request $request, B2bVendor $vendor)
    {
        if ($vendor->parent_vendor_id) {
            return redirect()->route('admin.vendors.show', $vendor->parent_vendor_id)
                ->with('notify_error', 'Sub agents cannot be added under another sub agent.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:b2b_vendors,email|max:255',
            'username' => B2bVendorValidation::usernameRule(),
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ], B2bVendorValidation::messages());

        $agencyAgentCode = trim((string) $vendor->agent_code);
        if ($agencyAgentCode === '') {
            return redirect()->back()
                ->withInput()
                ->with('notify_error', 'Parent agency must have an agent code before adding sub agents.');
        }

        $plainPassword = $validated['password'] ?? '12345678';

        $markupSnapshot = app(VendorPricingService::class)->markupSnapshotFromAgency($vendor);

        $subAgent = B2bVendor::create(array_merge([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'agent_code' => $agencyAgentCode,
            'password' => Hash::make($plainPassword),
            'status' => $validated['status'],
            'parent_vendor_id' => $vendor->id,
        ], $markupSnapshot));

        try {
            Mail::to($subAgent->email)->send(new VendorInviteMail($subAgent, $plainPassword));
        } catch (\Exception $e) {
            Log::error('Failed to send sub-agent invite email: ' . $e->getMessage());
        }

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('notify_success', 'Sub agent created successfully! Invite email sent.');
    }

    public function changeStatus(B2bVendor $vendor)
    {
        if ($vendor->status === 'pending') {
            return redirect()->back()->with('notify_error', 'Pending signup requests must be approved or rejected from Signup Requests.');
        }

        $vendor->update([
            'status' => $vendor->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->back()->with('notify_success', 'Vendor status changed successfully!');
    }

    public function destroy(B2bVendor $vendor)
    {
        if ($vendor->hasAssociatedData()) {
            return redirect()->back()->with(
                'notify_error',
                'Cannot delete this vendor. This account has existing bookings or related data.'
            );
        }

        if ($vendor->agency_logo) {
            Storage::disk('public')->delete($vendor->agency_logo);
        }

        $parentId = $vendor->parent_vendor_id;
        $vendor->delete();

        if ($parentId) {
            return redirect()->route('admin.vendors.show', $parentId)
                ->with('notify_success', 'Sub agent deleted successfully.');
        }

        return redirect()->route('admin.vendors.index')->with('notify_success', 'Vendor deleted successfully!');
    }

    private function validateVendor(Request $request, ?int $vendorId = null, ?B2bVendor $vendor = null): array
    {
        $common = [
            'email' => B2bVendorValidation::emailRule($vendorId),
            'username' => B2bVendorValidation::usernameRule($vendorId),
            'password' => 'nullable|string|min:8',
        ];

        if ($vendor && $vendor->parent_vendor_id) {
            return $request->validate(array_merge($common, [
                'name' => 'required|string|max:255',
                'status' => 'required|in:active,inactive',
            ]), B2bVendorValidation::messages());
        }

        $canSetPending = $vendor && $vendor->isPendingApproval() && $vendor->isAgencyAccount();
        $statusRule = $canSetPending
            ? 'required|in:active,inactive,pending'
            : 'required|in:active,inactive';

        $rules = array_merge($common, [
            'travel_agency' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'trade_license_number' => 'required|string|max:255',
            'trade_license_expiry' => $vendorId ? 'required|date' : 'required|date|after_or_equal:today',
            'agency_logo' => 'nullable|image|max:2048',
            'status' => $statusRule,
            'hotel_search_providers' => 'nullable|array',
            'hotel_search_providers.*' => 'in:yalago,tbo,tripindeal',
            'flight_search_providers' => 'nullable|array',
            'flight_search_providers.*' => 'in:sabre',
            'vendor_discounts_enabled' => 'nullable|boolean',
            'flight_discount_type' => 'nullable|in:percent,fixed',
            'flight_discount_value' => 'nullable|numeric|min:0|max:99999999.99',
            'hotel_discount_type' => 'nullable|in:percent,fixed',
            'hotel_discount_value' => 'nullable|numeric|min:0|max:99999999.99',
            'vendor_markups_enabled' => 'nullable|boolean',
            'flight_markup_type' => 'nullable|in:percent,fixed',
            'flight_markup_value' => 'nullable|numeric|min:0|max:99999999.99',
            'hotel_markup_type' => 'nullable|in:percent,fixed',
            'hotel_markup_value' => 'nullable|numeric|min:0|max:99999999.99',
        ]);

        return $request->validate($rules, B2bVendorValidation::messages());
    }

    private function normalizeDiscountType(?string $type, float $value): ?string
    {
        $type = strtolower(trim((string) $type));

        if (! in_array($type, ['percent', 'fixed'], true) || $value <= 0) {
            return null;
        }

        return $type;
    }

    private function normalizeDiscountValue(?string $type, mixed $value): float
    {
        $type = strtolower(trim((string) $type));
        $numeric = round((float) $value, 2);

        if (! in_array($type, ['percent', 'fixed'], true) || $numeric <= 0) {
            return 0;
        }

        if ($type === 'percent' && $numeric >= 100) {
            return 0;
        }

        return $numeric;
    }

    private function normalizeMarkupType(?string $type, float $value): ?string
    {
        $type = strtolower(trim((string) $type));

        if (! in_array($type, ['percent', 'fixed'], true) || $value <= 0) {
            return null;
        }

        return $type;
    }

    private function normalizeMarkupValue(?string $type, mixed $value): float
    {
        $type = strtolower(trim((string) $type));
        $numeric = round((float) $value, 2);

        if (! in_array($type, ['percent', 'fixed'], true) || $numeric <= 0) {
            return 0;
        }

        return $numeric;
    }

    private function parseProviderConfig($raw, array $allowed): ?array
    {
        if (empty($raw)) {
            return null;
        }

        $providers = [];

        if (is_array($raw)) {
            $providers = $raw;
        } elseif (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $providers = $decoded;
            } else {
                $providers = array_map('trim', explode(',', $raw));
            }
        }

        $providers = array_values(array_unique(array_filter(array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $providers))));

        $providers = array_values(array_intersect($providers, $allowed));

        return empty($providers) ? null : $providers;
    }

    private function generateUniqueAgentCode(): string
    {
        do {
            $code = 'AT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (B2bVendor::where('agent_code', $code)->exists());

        return $code;
    }
}
