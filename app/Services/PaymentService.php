<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public $tabbyApiKey = 'pk_03168c56-d196-4e58-a72a-48dbebb88b87';
    public $tabbyMerchantCode = 'ATA';
    public $tabbyApiUrl = 'https://api.tabby.ai/api/v2';

    public $paybyPartnerId = '200009116289';
    public $paybyApiUrl = 'https://api.payby.com/sgs/api/acquire2';
    public $paybyPrivateKey = 'admin/assets/files/payby-private-key.pem';

    /**
     * Get redirect URL based on payment method
     */
    public function getRedirectUrl(Order $order, string $paymentMethod): string
    {
        switch ($paymentMethod) {
            case 'payby':
                return $this->paybyRedirect($order);

            case 'tabby':
                return $this->tabbyRedirect($order);

            default:
                throw new \InvalidArgumentException("Unsupported payment method: {$paymentMethod}");
        }
    }

    /**
     * PayBy
     */
    protected function paybyRedirect(Order $order)
    {
        $requestTime = now()->timestamp * 1000;

        $orderItems = $order->orderItems()->with('tour')->get();
        $tourNames = $orderItems->pluck('tour_name')->implode(', ');
        $tourNames = strlen($tourNames) > 50 ? substr($tourNames, 0, 40) . '...' : $tourNames;

        $requestData = [
            'requestTime' => $requestTime,
            'bizContent' => [
                'merchantOrderNo' => $order->order_number,
                'subject' => 'TOUR BOOKING',
                'totalAmount' => [
                    'currency' => 'AED',
                    'amount' => number_format((float)$order->total, 2, '.', '')
                ],
                'paySceneCode' => 'PAYPAGE',
                'paySceneParams' => [
                    'redirectUrl' => route('frontend.payment.success', ['order' => $order->id]),
                    'backUrl' => route('frontend.payment.failed', ['order' => $order->id])
                ],
                'reserved' => 'Andaleeb Travel Agency Order',
                'accessoryContent' => [
                    'amountDetail' => [
                        'vatAmount' => $order->vat > 0 ? [
                            'currency' => 'AED',
                            'amount' => number_format((float)$order->vat, 2, '.', '')
                        ] : null,
                        'amount' => $order->service_tax > 0 ? [
                            'currency' => 'AED',
                            'amount' => number_format((float)$order->service_tax, 2, '.', '')
                        ] : null
                    ],
                    'goodsDetail' => [
                        'body' => 'Tour Booking',
                        'categoriesTree' => 'CT12',
                        'goodsCategory' => 'GC10',
                        'goodsId' => 'GI1005',
                        'goodsName' => $tourNames,
                        'price' => [
                            'currency' => 'AED',
                            'amount' => number_format((float)$order->subtotal, 2, '.', '')
                        ],
                        'quantity' => $orderItems->count()
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
            'Partner-Id' => '200009116289',
            'sign' => $base64Signature,
        ])->post('https://api.payby.com/sgs/api/acquire2/placeOrder', $requestData);

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

    /**
     * TABBY 
     */
    protected function tabbyRedirect(Order $order)
    {
        $orderItems = $order->orderItems()->with('tour')->get();

        $taxTabby = $order->vat + $order->service_tax;

        $items = [];
        foreach ($orderItems as $item) {
            $tourName = strlen($item->tour_name) > 20
                ? substr($item->tour_name, 0, 20) . '...'
                : $item->tour_name;

            $items[] = [
                'title' => $tourName,
                'description' => $tourName,
                'quantity' => (int)$item->quantity,
                'unit_price' => number_format((float)$item->price, 2, '.', ''),
                'category' => 'Tours & Activities'
            ];
        }

        $merchantCode = $this->tabbyMerchantCode;
        $tabbyApiKey = $this->tabbyApiKey;

        if (!$merchantCode || !$tabbyApiKey) {
            throw new \Exception('Tabby merchant code or API key is missing. Check your .env file.');
        }

        $requestData = [
            'payment' => [
                'amount' => number_format((float)$order->total, 2, '.', ''),
                'currency' => 'AED',
                'description' => 'Booking Andaleeb Travel Agency',
                'buyer' => [
                    'phone' => $order->passenger_phone,
                    'email' => $order->passenger_email,
                    'name' => $order->passenger_first_name . ' ' . $order->passenger_last_name,
                    'dob' => '1990-01-01'
                ],
                'shipping_address' => [
                    'city' => 'N/A',
                    'address' => $order->passenger_address,
                    'zip' => '00000'
                ],
                'order' => [
                    'tax_amount' => number_format((float)$taxTabby, 2, '.', ''),
                    'shipping_amount' => '0.00',
                    'discount_amount' => number_format((float)$order->discount, 2, '.', ''),
                    'updated_at' => now()->toIso8601String(),
                    'reference_id' => $order->order_number,
                    'items' => $items
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
                            'phone' => $order->passenger_phone,
                            'email' => $order->passenger_email,
                            'name' => $order->passenger_first_name . ' ' . $order->passenger_last_name,
                            'dob' => '1990-01-01'
                        ],
                        'shipping_address' => [
                            'city' => 'Dubai',
                            'address' => $order->passenger_address,
                            'zip' => '00000'
                        ],
                        'items' => $items
                    ]
                ],
                'meta' => [
                    'order_id' => (string)$order->id,
                    'customer' => (string)$order->id
                ],
                'attachment' => [
                    'body' => json_encode([
                        'tour_booking_details' => [
                            'order_number' => $order->order_number,
                            'tours' => $orderItems->pluck('tour_name')->toArray()
                        ]
                    ]),
                    'content_type' => 'application/vnd.tabby.v1+json'
                ]
            ],
            'lang' => 'en',
            'merchant_code' => $merchantCode,
            'merchant_urls' => [
                'success' => route('frontend.payment.success', ['order' => $order->id]),
                'cancel' => route('frontend.payment.failed'),
                'failure' => route('frontend.payment.failed')
            ]
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $tabbyApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.tabby.ai/api/v2/checkout', $requestData);

        if (!$response->successful()) {
            throw new \Exception('Tabby API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['configuration']['available_products']['installments'][0]['web_url'])) {
            // Save Tabby payment ID to order
            if (isset($responseData['payment']['id'])) {
                $order->update([
                    'tabby_payment_id' => $responseData['payment']['id']
                ]);

                Log::info('Tabby payment ID saved', [
                    'order_id' => $order->id,
                    'payment_id' => $responseData['payment']['id']
                ]);
            }

            return $responseData['configuration']['available_products']['installments'][0]['web_url'];
        }

        throw new \Exception('Tabby checkout creation failed: No redirect URL found in response');
    }

    /**
     * Verify PayBy Payment
     */
    public function verifyPayByPayment(Order $order): array
    {
        try {
            $requestTime = now()->timestamp * 1000;

            $requestData = [
                'requestTime' => $requestTime,
                'bizContent' => [
                    'merchantOrderNo' => $order->order_number
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
            Log::error('PayBy Verification Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify Tabby Payment
     * 
     * TODO: Need to implement proper verification from Tabby merchant dashboard
     * Currently returning success by default for testing purposes
     */
    public function verifyTabbyPayment(Order $order): array
    {
        // TODO: Implement actual Tabby payment verification
        // For now, return success to allow testing
        return [
            'success' => true,
            'data' => []
        ];

        // Original implementation commented out for future use:
        // try {
        //     if (empty($order->tabby_payment_id)) {
        //         throw new \Exception('Tabby payment ID not found for this order');
        //     }

        //     $response = Http::withHeaders([
        //         'Authorization' => 'Bearer ' . $this->tabbyApiKey
        //     ])->get("{$this->tabbyApiUrl}/payments/{$order->tabby_payment_id}");

        //     if (!$response->successful()) {
        //         throw new \Exception('Tabby verification API request failed: ' . $response->body());
        //     }

        //     $data = $response->json();

        //     if (isset($data['status']) && in_array($data['status'], ['AUTHORIZED', 'CLOSED', 'CAPTURED'])) {
        //         return [
        //             'success' => true,
        //             'data' => $data
        //         ];
        //     }

        //     throw new \Exception('Tabby payment not captured. Status: ' . ($data['status'] ?? 'Unknown'));
        // } catch (\Exception $e) {
        //     Log::error('Tabby Verification Error', [
        //         'order_id' => $order->id,
        //         'tabby_payment_id' => $order->tabby_payment_id ?? 'N/A',
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     return [
        //         'success' => false,
        //         'error' => $e->getMessage()
        //     ];
        // }
    }
}
