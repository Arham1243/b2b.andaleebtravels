<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bWalletRecharge;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WalletRechargeController extends Controller
{
    protected $tabbyApiKey = 'pk_03168c56-d196-4e58-a72a-48dbebb88b87';
    protected $tabbyMerchantCode = 'ATA';
    protected $tabbyApiUrl = 'https://api.tabby.ai/api/v2';

    protected $paybyPartnerId = '200009116289';
    protected $paybyApiUrl = 'https://api.payby.com/sgs/api/acquire2';
    protected $paybyPrivateKey = 'user/assets/files/payby-private-key.pem';

    public function index()
    {
        $recharges = B2bWalletRecharge::where('b2b_vendor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('user.wallet.recharge', compact('recharges'))
            ->with('title', 'Wallet Recharge');
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'payment_method' => 'required|in:payby,tabby',
        ]);

        try {
            $recharge = B2bWalletRecharge::create([
                'b2b_vendor_id' => Auth::id(),
                'transaction_number' => B2bWalletRecharge::generateTransactionNumber(),
                'amount' => $validated['amount'],
                'currency' => 'AED',
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($validated['payment_method'] === 'payby') {
                $redirectUrl = $this->paybyRedirect($recharge);
            } else {
                $redirectUrl = $this->tabbyRedirect($recharge);
            }

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Wallet recharge process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($recharge)) {
                $recharge->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }

            return redirect()->route('user.wallet.recharge')
                ->with('notify_error', 'Payment initiation failed. Please try again.');
        }
    }

    public function paymentSuccess(Request $request, $recharge)
    {
        try {
            $recharge = B2bWalletRecharge::findOrFail($recharge);

            if ($recharge->isPaid()) {
                return redirect()->route('user.wallet.recharge')
                    ->with('notify_success', 'This recharge has already been processed.');
            }

            // Verify payment
            if ($recharge->payment_method === 'payby') {
                $verification = $this->verifyPayByPayment($recharge);
            } else {
                $verification = $this->verifyTabbyPayment($recharge);
            }

            if ($verification['success']) {
                $recharge->update([
                    'status' => 'paid',
                    'payment_response' => $verification['data'] ?? null,
                    'paid_at' => now(),
                ]);

                // Add amount to vendor wallet
                $vendor = Auth::user();
                $vendor->update([
                    'main_balance' => $vendor->main_balance + $recharge->amount,
                ]);

                Log::info('Wallet recharged successfully', [
                    'vendor_id' => $vendor->id,
                    'recharge_id' => $recharge->id,
                    'amount' => $recharge->amount,
                ]);

                return redirect()->route('user.wallet.recharge')
                    ->with('notify_success', 'Wallet recharged successfully with ' . number_format($recharge->amount, 2) . ' AED');
            }

            $recharge->update([
                'status' => 'failed',
                'failure_reason' => $verification['error'] ?? 'Payment verification failed',
                'payment_response' => $verification['data'] ?? null,
            ]);

            return redirect()->route('user.wallet.recharge')
                ->with('notify_error', 'Payment verification failed. Please contact support.');
        } catch (\Exception $e) {
            Log::error('Wallet recharge payment success handler failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('user.wallet.recharge')
                ->with('notify_error', 'Something went wrong. Please contact support.');
        }
    }

    public function paymentFailed(Request $request, $recharge = null)
    {
        try {
            if ($recharge) {
                $recharge = B2bWalletRecharge::findOrFail($recharge);

                if ($recharge->status === 'pending') {
                    $recharge->update([
                        'status' => 'failed',
                        'failure_reason' => 'Payment was cancelled or failed by the user.',
                    ]);
                }
            }

            return redirect()->route('user.wallet.recharge')
                ->with('notify_error', 'Payment was cancelled or failed.');
        } catch (\Exception $e) {
            Log::error('Wallet recharge payment failed handler error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('user.wallet.recharge')
                ->with('notify_error', 'Payment failed.');
        }
    }

    protected function paybyRedirect(B2bWalletRecharge $recharge): string
    {
        $requestTime = now()->timestamp * 1000;

        $requestData = [
            'requestTime' => $requestTime,
            'bizContent' => [
                'merchantOrderNo' => $recharge->transaction_number,
                'subject' => 'WALLET RECHARGE',
                'totalAmount' => [
                    'currency' => 'AED',
                    'amount' => number_format((float)$recharge->amount, 2, '.', '')
                ],
                'paySceneCode' => 'PAYPAGE',
                'paySceneParams' => [
                    'redirectUrl' => route('user.wallet.payment.success', ['recharge' => $recharge->id]),
                    'backUrl' => route('user.wallet.payment.failed', ['recharge' => $recharge->id])
                ],
                'reserved' => 'Andaleeb Wallet Recharge',
                'accessoryContent' => [
                    'goodsDetail' => [
                        'body' => 'Wallet Recharge',
                        'categoriesTree' => 'CT12',
                        'goodsCategory' => 'GC10',
                        'goodsId' => 'GI1006',
                        'goodsName' => 'Wallet Recharge - ' . number_format($recharge->amount, 2) . ' AED',
                        'price' => [
                            'currency' => 'AED',
                            'amount' => number_format((float)$recharge->amount, 2, '.', '')
                        ],
                        'quantity' => 1
                    ],
                    'terminalDetail' => [
                        'operatorId' => 'OP1000000000000001',
                        'storeId' => 'SI100000000000002',
                        'terminalId' => 'TI100999999999900',
                        'merchantName' => 'ANDALEEB TRAVEL AGENCY',
                        'storeName' => 'ANDALEEB TRAVEL AGENCY'
                    ]
                ]
            ]
        ];

        $jsonPayload = json_encode($requestData);

        $privateKeyPath = public_path($this->paybyPrivateKey);
        if (!file_exists($privateKeyPath)) {
            throw new \Exception('Private key file not found at: ' . $privateKeyPath);
        }

        $privateKey = file_get_contents($privateKeyPath);
        $signature = '';
        openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64Signature = base64_encode($signature);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Content-Language' => 'en',
            'Partner-Id' => $this->paybyPartnerId,
            'sign' => $base64Signature,
        ])->post($this->paybyApiUrl . '/placeOrder', $requestData);

        if (!$response->successful()) {
            throw new \Exception('PayBy API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (
            isset($responseData['head']['applyStatus']) &&
            $responseData['head']['applyStatus'] === 'SUCCESS' &&
            isset($responseData['body']['acquireOrder']['status']) &&
            $responseData['body']['acquireOrder']['status'] === 'CREATED'
        ) {
            return $responseData['body']['interActionParams']['tokenUrl'] ?? throw new \Exception('Payment URL not found in response');
        }

        throw new \Exception('PayBy order creation failed: ' . ($responseData['head']['msg'] ?? 'Unknown error'));
    }

    protected function tabbyRedirect(B2bWalletRecharge $recharge): string
    {
        $user = Auth::user();

        $requestData = [
            'payment' => [
                'amount' => number_format((float)$recharge->amount, 2, '.', ''),
                'currency' => 'AED',
                'description' => 'Wallet Recharge - Andaleeb Travel Agency',
                'buyer' => [
                    'phone' => $user->phone ?? '0000000000',
                    'email' => $user->email ?? 'customer@example.com',
                    'name' => $user->name ?? 'Customer',
                    'dob' => '1990-01-01'
                ],
                'shipping_address' => [
                    'city' => 'N/A',
                    'address' => 'N/A',
                    'zip' => '00000'
                ],
                'order' => [
                    'tax_amount' => '0.00',
                    'shipping_amount' => '0.00',
                    'discount_amount' => '0.00',
                    'updated_at' => now()->toIso8601String(),
                    'reference_id' => $recharge->transaction_number,
                    'items' => [[
                        'title' => 'Wallet Recharge',
                        'description' => 'Wallet Recharge - ' . number_format($recharge->amount, 2) . ' AED',
                        'quantity' => 1,
                        'unit_price' => number_format((float)$recharge->amount, 2, '.', ''),
                        'category' => 'Wallet Recharge'
                    ]]
                ],
                'buyer_history' => [
                    'registered_since' => now()->subYears(2)->toIso8601String(),
                    'loyalty_level' => 0,
                    'wishlist_count' => 0,
                    'is_social_networks_connected' => true,
                    'is_phone_number_verified' => true,
                    'is_email_verified' => true
                ],
                'order_history' => [
                    [
                        'purchased_at' => now()->subMonths(3)->toIso8601String(),
                        'amount' => '100.00',
                        'payment_method' => 'card',
                        'status' => 'new',
                        'buyer' => [
                            'phone' => $user->phone ?? '0000000000',
                            'email' => $user->email ?? 'customer@example.com',
                            'name' => $user->name ?? 'Customer',
                            'dob' => '1990-01-01'
                        ],
                        'shipping_address' => [
                            'city' => 'Dubai',
                            'address' => 'N/A',
                            'zip' => '00000'
                        ],
                        'items' => [[
                            'title' => 'Wallet Recharge',
                            'description' => 'Wallet Recharge',
                            'quantity' => 1,
                            'unit_price' => '100.00',
                            'category' => 'Wallet Recharge'
                        ]]
                    ]
                ],
                'meta' => [
                    'order_id' => (string)$recharge->id,
                    'customer' => (string)$user->id
                ],
                'attachment' => [
                    'body' => json_encode([
                        'recharge_details' => [
                            'transaction_number' => $recharge->transaction_number,
                            'amount' => $recharge->amount
                        ]
                    ]),
                    'content_type' => 'application/vnd.tabby.v1+json'
                ]
            ],
            'lang' => 'en',
            'merchant_code' => $this->tabbyMerchantCode,
            'merchant_urls' => [
                'success' => route('user.wallet.payment.success', ['recharge' => $recharge->id]),
                'cancel' => route('user.wallet.payment.failed'),
                'failure' => route('user.wallet.payment.failed')
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->tabbyApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->tabbyApiUrl . '/checkout', $requestData);

        if (!$response->successful()) {
            throw new \Exception('Tabby API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['configuration']['available_products']['installments'][0]['web_url'])) {
            if (isset($responseData['payment']['id'])) {
                $recharge->update([
                    'tabby_payment_id' => $responseData['payment']['id']
                ]);
            }

            return $responseData['configuration']['available_products']['installments'][0]['web_url'];
        }

        throw new \Exception('Tabby checkout creation failed: No redirect URL found in response');
    }

    protected function verifyPayByPayment(B2bWalletRecharge $recharge): array
    {
        try {
            $requestTime = now()->timestamp * 1000;

            $requestData = [
                'requestTime' => $requestTime,
                'bizContent' => [
                    'merchantOrderNo' => $recharge->transaction_number
                ]
            ];

            $jsonPayload = json_encode($requestData);
            $privateKeyPath = public_path($this->paybyPrivateKey);

            if (!file_exists($privateKeyPath)) {
                throw new \Exception('PayBy private key file not found');
            }

            $privateKey = file_get_contents($privateKeyPath);
            $signature = '';
            openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $base64Signature = base64_encode($signature);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Content-Language' => 'en',
                'Partner-Id' => $this->paybyPartnerId,
                'sign' => $base64Signature,
            ])->post($this->paybyApiUrl . '/getOrder', $requestData);

            if (!$response->successful()) {
                throw new \Exception('PayBy verification API request failed: ' . $response->body());
            }

            $responseData = $response->json();

            if (
                isset($responseData['body']['acquireOrder']['status']) &&
                $responseData['body']['acquireOrder']['status'] === 'SETTLED' &&
                isset($responseData['head']['applyStatus']) &&
                $responseData['head']['applyStatus'] === 'SUCCESS'
            ) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }

            throw new \Exception('PayBy payment not settled. Status: ' . ($responseData['body']['acquireOrder']['status'] ?? 'Unknown'));
        } catch (\Exception $e) {
            Log::error('PayBy Wallet Recharge Verification Error', [
                'recharge_id' => $recharge->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function verifyTabbyPayment(B2bWalletRecharge $recharge): array
    {
        return [
            'success' => true,
            'data' => []
        ];
    }
}
