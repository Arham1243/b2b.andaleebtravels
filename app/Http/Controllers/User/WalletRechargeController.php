<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\B2bWalletLedger;
use App\Models\B2bWalletRecharge;
use App\Models\Config;
use App\Traits\UploadImageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletRechargeController extends Controller
{
    use UploadImageTrait;

    protected $tabbyApiKey = 'pk_03168c56-d196-4e58-a72a-48dbebb88b87';
    protected $tabbyMerchantCode = 'ATA';
    protected $tabbyApiUrl = 'https://api.tabby.ai/api/v2';

    protected $paybyPartnerId = '200009116289';
    protected $paybyApiUrl = 'https://api.payby.com/sgs/api/acquire2';
    protected $paybyPrivateKey = 'user/assets/files/payby-private-key.pem';

    /**
     * Store proof under public/uploads/wallet-bank-proofs (not storage disk symlink).
     *
     * @return non-falsy-string|null Relative path from public/, e.g. uploads/wallet-bank-proofs/uuid.png
     */
    protected function storeWalletBankProofPublic(\Illuminate\Http\UploadedFile $file): ?string
    {
        $relativeDir = 'uploads/wallet-bank-proofs';
        $dir = public_path($relativeDir);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            Log::error('Wallet bank proof: could not create directory', ['dir' => $dir]);

            return null;
        }

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        if (! $file->move($dir, $filename)) {
            return null;
        }

        return $relativeDir.'/'.$filename;
    }

    public function rechargeCard()
    {
        return view('user.wallet.recharge-card', $this->rechargePageData())
            ->with('title', 'Recharge - Credit - Debit Card');
    }

    public function rechargeTabby()
    {
        return view('user.wallet.recharge-tabby', $this->rechargePageData())
            ->with('title', 'Recharge - Tabby');
    }

    public function rechargeBankTransfer()
    {
        $bankInstructions = trim((string) (Config::where('config_key', 'WALLET_BANK_TRANSFER_DETAILS')->value('config_value') ?? ''));

        return view('user.wallet.recharge-bank-transfer', array_merge($this->rechargePageData(), [
            'bankInstructions' => $bankInstructions,
        ]))->with('title', 'Recharge - Bank Transfer');
    }

    public function submitBankTransfer(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $proofPath = $this->storeWalletBankProofPublic($request->file('proof'));
        if (!$proofPath) {
            return redirect()->back()->withInput()->with('notify_error', 'Could not upload proof image. Please try again.');
        }

        B2bWalletRecharge::create([
            'b2b_vendor_id' => Auth::id(),
            'transaction_number' => B2bWalletRecharge::generateTransactionNumber(),
            'amount' => (float) $validated['amount'],
            'currency' => 'AED',
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'proof_image_path' => $proofPath,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('user.wallet.recharge.bank-transfer')
            ->with('notify_success', 'Your transfer details were submitted. We will credit your wallet after verifying payment.');
    }

    /**
     * @return array{recharges: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    protected function rechargePageData(): array
    {
        $recharges = B2bWalletRecharge::where('b2b_vendor_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return compact('recharges');
    }

    protected function rechargeListRoute(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'tabby' => 'user.wallet.recharge.tabby',
            'bank_transfer' => 'user.wallet.recharge.bank-transfer',
            default => 'user.wallet.recharge.card',
        };
    }

    public function processCard(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
            'proof' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $proofPath = $this->storeWalletBankProofPublic($request->file('proof'));
        if (! $proofPath) {
            return redirect()->back()->withInput()->with('notify_error', 'Could not upload payment proof. Please try again.');
        }

        return $this->beginRecharge($request, 'payby', ['proof_image_path' => $proofPath]);
    }

    public function processTabby(Request $request)
    {
        return $this->beginRecharge($request, 'tabby');
    }

    /**
     * @param  array<string, mixed>  $extraSession
     */
    protected function beginRecharge(Request $request, string $paymentMethod, array $extraSession = []): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
        ]);

        $listRoute = $this->rechargeListRoute($paymentMethod);

        try {
            $transactionNumber = B2bWalletRecharge::generateTransactionNumber();

            $rechargeData = array_merge([
                'transaction_number' => $transactionNumber,
                'amount' => (float) $request->input('amount'),
                'payment_method' => $paymentMethod,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ], $extraSession);

            session()->put("wallet_recharge.{$transactionNumber}", $rechargeData);

            $redirectUrl = $paymentMethod === 'payby'
                ? $this->paybyRedirect($rechargeData)
                : $this->tabbyRedirect($rechargeData);

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Wallet recharge process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route($listRoute)
                ->with('notify_error', 'Payment initiation failed. Please try again.');
        }
    }

    public function paymentSuccess(Request $request, $transactionNumber)
    {
        try {
            // Check if already processed
            $existing = B2bWalletRecharge::where('transaction_number', $transactionNumber)->first();
            if ($existing && $existing->isPaid()) {
                return redirect()->route($this->rechargeListRoute($existing->payment_method))
                    ->with('notify_success', 'This recharge has already been processed.');
            }

            // Get data from session
            $rechargeData = session()->get("wallet_recharge.{$transactionNumber}");

            if (!$rechargeData) {
                return redirect()->route('user.wallet.recharge.card')
                    ->with('notify_error', 'Recharge session expired. Please try again.');
            }

            $listRoute = $this->rechargeListRoute($rechargeData['payment_method']);

            // Verify payment with gateway
            if ($rechargeData['payment_method'] === 'payby') {
                $verification = $this->verifyPayByPayment($transactionNumber);
            } else {
                $verification = $this->verifyTabbyPayment($transactionNumber);
            }

            if ($verification['success']) {
                // Create DB record as paid
                $recharge = B2bWalletRecharge::create([
                    'b2b_vendor_id' => Auth::id(),
                    'transaction_number' => $transactionNumber,
                    'amount' => $rechargeData['amount'],
                    'currency' => 'AED',
                    'payment_method' => $rechargeData['payment_method'],
                    'status' => 'paid',
                    'proof_image_path' => $rechargeData['proof_image_path'] ?? null,
                    'payment_response' => $verification['data'] ?? null,
                    'paid_at' => now(),
                    'ip_address' => $rechargeData['ip_address'],
                    'user_agent' => $rechargeData['user_agent'],
                ]);

                // Add amount to vendor wallet via ledger
                B2bWalletLedger::recordCredit(
                    Auth::id(),
                    (float) $recharge->amount,
                    'Wallet Recharge #' . $recharge->transaction_number,
                    B2bWalletRecharge::class,
                    $recharge->id
                );

                Log::info('Wallet recharged successfully', [
                    'vendor_id' => Auth::id(),
                    'recharge_id' => $recharge->id,
                    'amount' => $recharge->amount,
                ]);

                session()->forget("wallet_recharge.{$transactionNumber}");

                return redirect()->route($listRoute)
                    ->with('notify_success', 'Wallet recharged successfully with ' . number_format($recharge->amount, 2) . ' AED');
            }

            // Verification failed  -  create DB record as failed
            B2bWalletRecharge::create([
                'b2b_vendor_id' => Auth::id(),
                'transaction_number' => $transactionNumber,
                'amount' => $rechargeData['amount'],
                'currency' => 'AED',
                'payment_method' => $rechargeData['payment_method'],
                'status' => 'failed',
                'failure_reason' => $verification['error'] ?? 'Payment verification failed',
                'proof_image_path' => $rechargeData['proof_image_path'] ?? null,
                'payment_response' => $verification['data'] ?? null,
                'ip_address' => $rechargeData['ip_address'],
                'user_agent' => $rechargeData['user_agent'],
            ]);

            session()->forget("wallet_recharge.{$transactionNumber}");

            return redirect()->route($listRoute)
                ->with('notify_error', 'Payment verification failed. Please contact support.');
        } catch (\Exception $e) {
            Log::error('Wallet recharge payment success handler failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('user.wallet.recharge.card')
                ->with('notify_error', 'Something went wrong. Please contact support.');
        }
    }

    public function paymentFailed(Request $request, $transactionNumber = null)
    {
        try {
            if ($transactionNumber) {
                $rechargeData = session()->get("wallet_recharge.{$transactionNumber}");

                if ($rechargeData) {
                    $listRoute = $this->rechargeListRoute($rechargeData['payment_method']);
                    // Only create a failed record if not already in DB
                    $existing = B2bWalletRecharge::where('transaction_number', $transactionNumber)->first();
                    if (!$existing) {
                        B2bWalletRecharge::create([
                            'b2b_vendor_id' => Auth::id(),
                            'transaction_number' => $transactionNumber,
                            'amount' => $rechargeData['amount'],
                            'currency' => 'AED',
                            'payment_method' => $rechargeData['payment_method'],
                            'status' => 'failed',
                            'failure_reason' => 'Payment was cancelled or failed by the user.',
                            'proof_image_path' => $rechargeData['proof_image_path'] ?? null,
                            'ip_address' => $rechargeData['ip_address'],
                            'user_agent' => $rechargeData['user_agent'],
                        ]);
                    }

                    session()->forget("wallet_recharge.{$transactionNumber}");

                    return redirect()->route($listRoute)
                        ->with('notify_error', 'Payment was cancelled or failed.');
                }
            }

            return redirect()->route('user.wallet.recharge.card')
                ->with('notify_error', 'Payment was cancelled or failed.');
        } catch (\Exception $e) {
            Log::error('Wallet recharge payment failed handler error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('user.wallet.recharge.card')
                ->with('notify_error', 'Payment failed.');
        }
    }

    public function retryPayment($recharge)
    {
        try {
            $recharge = B2bWalletRecharge::where('b2b_vendor_id', Auth::id())
                ->where('id', $recharge)
                ->where('status', 'failed')
                ->whereIn('payment_method', ['payby', 'tabby'])
                ->firstOrFail();

            $transactionNumber = B2bWalletRecharge::generateTransactionNumber();

            $rechargeData = [
                'transaction_number' => $transactionNumber,
                'amount' => $recharge->amount,
                'payment_method' => $recharge->payment_method,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            session()->put("wallet_recharge.{$transactionNumber}", $rechargeData);

            if ($rechargeData['payment_method'] === 'payby') {
                $redirectUrl = $this->paybyRedirect($rechargeData);
            } else {
                $redirectUrl = $this->tabbyRedirect($rechargeData);
            }

            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Wallet recharge retry failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route($this->rechargeListRoute($recharge->payment_method ?? 'payby'))
                ->with('notify_error', 'Payment retry failed. Please try again.');
        }
    }

    protected function paybyRedirect(array $data): string
    {
        $requestTime = now()->timestamp * 1000;
        $amount = number_format((float)$data['amount'], 2, '.', '');

        $requestData = [
            'requestTime' => $requestTime,
            'bizContent' => [
                'merchantOrderNo' => $data['transaction_number'],
                'subject' => 'WALLET RECHARGE',
                'totalAmount' => [
                    'currency' => 'AED',
                    'amount' => $amount
                ],
                'paySceneCode' => 'PAYPAGE',
                'paySceneParams' => [
                    'redirectUrl' => route('user.wallet.payment.success', ['transactionNumber' => $data['transaction_number']]),
                    'backUrl' => route('user.wallet.payment.failed', ['transactionNumber' => $data['transaction_number']]),
                ],
                'reserved' => 'Andaleeb Wallet Recharge',
                'accessoryContent' => [
                    'goodsDetail' => [
                        'body' => 'Wallet Recharge',
                        'categoriesTree' => 'CT12',
                        'goodsCategory' => 'GC10',
                        'goodsId' => 'GI1006',
                        'goodsName' => 'Wallet Recharge - ' . $amount . ' AED',
                        'price' => [
                            'currency' => 'AED',
                            'amount' => $amount
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

    protected function tabbyRedirect(array $data): string
    {
        $user = Auth::user();
        $amount = number_format((float)$data['amount'], 2, '.', '');

        $requestData = [
            'payment' => [
                'amount' => $amount,
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
                    'reference_id' => $data['transaction_number'],
                    'items' => [[
                        'title' => 'Wallet Recharge',
                        'description' => 'Wallet Recharge - ' . $amount . ' AED',
                        'quantity' => 1,
                        'unit_price' => $amount,
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
                    'order_id' => $data['transaction_number'],
                    'customer' => (string)$user->id
                ],
                'attachment' => [
                    'body' => json_encode([
                        'recharge_details' => [
                            'transaction_number' => $data['transaction_number'],
                            'amount' => $data['amount']
                        ]
                    ]),
                    'content_type' => 'application/vnd.tabby.v1+json'
                ]
            ],
            'lang' => 'en',
            'merchant_code' => $this->tabbyMerchantCode,
            'merchant_urls' => [
                'success' => route('user.wallet.payment.success', ['transactionNumber' => $data['transaction_number']]),
                'cancel' => route('user.wallet.payment.failed', ['transactionNumber' => $data['transaction_number']]),
                'failure' => route('user.wallet.payment.failed', ['transactionNumber' => $data['transaction_number']]),
            ],
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
                // Store tabby payment ID in session
                $sessionData = session()->get("wallet_recharge.{$data['transaction_number']}");
                if ($sessionData) {
                    $sessionData['tabby_payment_id'] = $responseData['payment']['id'];
                    session()->put("wallet_recharge.{$data['transaction_number']}", $sessionData);
                }
            }

            return $responseData['configuration']['available_products']['installments'][0]['web_url'];
        }

        throw new \Exception('Tabby checkout creation failed: No redirect URL found in response');
    }

    protected function verifyPayByPayment(string $transactionNumber): array
    {
        try {
            $requestTime = now()->timestamp * 1000;

            $requestData = [
                'requestTime' => $requestTime,
                'bizContent' => [
                    'merchantOrderNo' => $transactionNumber
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
                'transaction_number' => $transactionNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function verifyTabbyPayment(string $transactionNumber): array
    {
        return [
            'success' => true,
            'data' => []
        ];
    }
}
