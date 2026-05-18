<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\B2bWalletLedger;
use App\Models\B2bWalletRecharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletBankTransferController extends Controller
{
    public function index()
    {
        $title = 'Wallet Bank Transfers';

        $recharges = B2bWalletRecharge::query()
            ->with(['vendor', 'confirmedByAdmin'])
            ->where('payment_method', 'bank_transfer')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.wallet-bank-transfers.index', compact('title', 'recharges'));
    }

    public function confirm(Request $request, B2bWalletRecharge $recharge)
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        if ($recharge->payment_method !== 'bank_transfer') {
            abort(404);
        }

        if ($recharge->status !== 'pending') {
            return redirect()->back()->with('notify_error', 'This request is not pending.');
        }

        try {
            DB::transaction(function () use ($recharge, $request) {
                $locked = B2bWalletRecharge::whereKey($recharge->id)
                    ->where('payment_method', 'bank_transfer')
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->firstOrFail();

                $note = $request->input('note');
                $paymentResponse = array_merge($locked->payment_response ?? [], [
                    'admin_confirmation_note' => $note,
                    'confirmed_at' => now()->toIso8601String(),
                ]);

                B2bWalletLedger::recordCredit(
                    $locked->b2b_vendor_id,
                    (float) $locked->amount,
                    'Wallet Recharge #' . $locked->transaction_number,
                    B2bWalletRecharge::class,
                    $locked->id
                );

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'admin_confirmed_at' => now(),
                    'confirmed_by_b2b_admin_id' => Auth::guard('admin')->id(),
                    'payment_response' => $paymentResponse,
                ]);
            });

            Log::info('Wallet bank transfer confirmed', [
                'recharge_id' => $recharge->id,
                'admin_id' => Auth::guard('admin')->id(),
            ]);

            return redirect()->back()->with('notify_success', 'Payment confirmed and wallet credited.');
        } catch (\Throwable $e) {
            Log::error('Wallet bank transfer confirm failed', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('notify_error', 'Unable to confirm: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, B2bWalletRecharge $recharge)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($recharge->payment_method !== 'bank_transfer') {
            abort(404);
        }

        if ($recharge->status !== 'pending') {
            return redirect()->back()->with('notify_error', 'This request is not pending.');
        }

        $recharge->update([
            'status' => 'failed',
            'failure_reason' => $request->input('reason'),
            'payment_response' => array_merge($recharge->payment_response ?? [], [
                'rejected_at' => now()->toIso8601String(),
                'rejected_by_b2b_admin_id' => Auth::guard('admin')->id(),
            ]),
        ]);

        return redirect()->back()->with('notify_success', 'Submission rejected.');
    }
}
