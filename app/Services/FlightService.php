<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Models\Config;
use App\Services\Travelport\TravelportBookingService;
use App\Support\SabreAirRulesResponseParser;
use App\Support\SabreApplicationResultsParser;
use App\Support\SabreStructuredFareRulesFallback;
use App\Support\SabrePriceQuoteResolver;
use App\Support\FlightBookingTicketResolver;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlightService
{
    public float $commissionPercentage;

    public string $tabbyApiKey = 'pk_03168c56-d196-4e58-a72a-48dbebb88b87';
    public string $tabbyMerchantCode = 'ATA';
    public string $tabbyApiUrl = 'https://api.tabby.ai/api/v2';

    public string $paybyPartnerId = '200009116289';
    public string $paybyApiUrl = 'https://api.payby.com/sgs/api/acquire2';
    public string $paybyPrivateKey = 'user/assets/files/payby-private-key.pem';

    public string $tamaraApiUrl = 'https://api-sandbox.tamara.co';
    public ?string $tamaraApiToken = null;

    private string $sabreBasicAuth = 'VmpFNk1qVTROak13T2poT1NrdzZRVUU9OlJtRnBjMkZzTVRBPQ==';

    private string $sabrePcc = '8NJL';
    private string $sabreCompanyCode = 'TN';

    private string $sabreSoapUsername = '258630';
    private string $sabreSoapPassword = 'Faisal10';
    private string $sabreSoapOrganization = '8NJL';
    private string $sabreSoapDomain = 'AA';

    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->commissionPercentage = ((float) ($config['FLIGHT_COMMISSION_PERCENTAGE'] ?? 0)) / 100;
        $this->tamaraApiUrl = rtrim(env('TAMARA_API_URL', $this->tamaraApiUrl), '/');
        $this->tamaraApiToken = env('TAMARA_API_TOKEN');
    }

    /**
     * Sabre CERT/PROD calls (shop, revalidate, PNR, ticket, SOAP) often exceed default Guzzle timeouts (~10s).
     */
    private function sabreHttp(): PendingRequest
    {
        return Http::timeout((int) config('services.sabre.http_timeout', 90))
            ->connectTimeout((int) config('services.sabre.http_connect_timeout', 30));
    }

    public function createBookingRecord(array $data): B2bFlightBooking
    {
        $provider = normalizeFlightBookingProvider(
            $data['provider'] ?? data_get($data, 'itinerary_data.supplier')
        );

        return B2bFlightBooking::create([
            'b2b_vendor_id' => auth()->id(),
            'booking_number' => B2bFlightBooking::generateBookingNumber(),
            'provider' => $provider,
            'itinerary_id' => $data['itinerary_id'] ?? null,
            'from_airport' => $data['from_airport'] ?? null,
            'to_airport' => $data['to_airport'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
            'return_date' => $data['return_date'] ?? null,
            'adults' => $data['adults'] ?? 1,
            'children' => $data['children'] ?? 0,
            'infants' => $data['infants'] ?? 0,
            'passengers_data' => $data['passengers_data'] ?? null,
            'itinerary_data' => $data['itinerary_data'] ?? null,
            'search_request' => $data['search_request'] ?? null,
            'search_response' => $data['search_response'] ?? null,
            'total_amount' => $data['total_amount'] ?? 0,
            'original_amount' => $data['original_amount'] ?? null,
            'vendor_discount_amount' => $data['vendor_discount_amount'] ?? 0,
            'vendor_discount_snapshot' => $data['vendor_discount_snapshot'] ?? null,
            'vendor_markup_amount' => $data['vendor_markup_amount'] ?? 0,
            'vendor_markup_snapshot' => $data['vendor_markup_snapshot'] ?? null,
            'currency' => $data['currency'] ?? 'AED',
            'payment_method' => $data['payment_method'] ?? null,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'ticket_status' => 'pending',
            'source_market' => $data['source_market'] ?? 'AE',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getRedirectUrl(B2bFlightBooking $booking, string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'payby' => $this->paybyRedirect($booking),
            'tabby' => $this->tabbyRedirect($booking),
            'tamara' => $this->tamaraRedirect($booking),
            default => throw new \InvalidArgumentException("Unsupported payment method: {$paymentMethod}"),
        };
    }

    protected function paybyRedirect(B2bFlightBooking $booking): string
    {
        $requestTime = now()->timestamp * 1000;
        $finalAmount = $booking->total_amount - ($booking->wallet_amount ?? 0);

        $requestData = [
            'requestTime' => $requestTime,
            'bizContent' => [
                'merchantOrderNo' => $booking->booking_number,
                'subject' => 'FLIGHT BOOKING',
                'totalAmount' => [
                    'currency' => 'AED',
                    'amount' => number_format((float) $finalAmount, 2, '.', ''),
                ],
                'paySceneCode' => 'PAYPAGE',
                'paySceneParams' => [
                    'redirectUrl' => route('user.flights.payment.success', ['booking' => $booking->id]),
                    'backUrl' => route('user.flights.payment.failed', ['booking' => $booking->id]),
                ],
                'reserved' => 'Andaleeb Flight Booking',
                'accessoryContent' => [
                    'goodsDetail' => [
                        'body' => 'Flight Booking',
                        'categoriesTree' => 'CT12',
                        'goodsCategory' => 'GC10',
                        'goodsId' => 'GI2001',
                        'goodsName' => 'Flight Booking',
                        'price' => [
                            'currency' => 'AED',
                            'amount' => number_format((float) $finalAmount, 2, '.', ''),
                        ],
                        'quantity' => 1,
                    ],
                    'terminalDetail' => [
                        'operatorId' => 'OP1000000000000001',
                        'storeId' => 'SI100000000000002',
                        'terminalId' => 'TI100999999999900',
                        'merchantName' => 'ANDALEEB TRAVEL AGENCY',
                        'storeName' => 'ANDALEEB TRAVEL AGENCY',
                    ],
                ],
            ],
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

    protected function tabbyRedirect(B2bFlightBooking $booking): string
    {
        $remainingAmount = $booking->total_amount - ($booking->wallet_amount ?? 0);
        $finalAmount = $remainingAmount + ($remainingAmount * $this->commissionPercentage);

        $items = [[
            'title' => 'Flight Booking',
            'description' => 'Flight Booking - Andaleeb Travel Agency',
            'quantity' => 1,
            'unit_price' => number_format((float) $finalAmount, 2, '.', ''),
            'category' => 'Air Ticket',
        ]];

        if (!$this->tabbyMerchantCode || !$this->tabbyApiKey) {
            throw new \Exception('Tabby merchant code or API key is missing. Check your .env file.');
        }

        $lead = $booking->passengers_data['lead'] ?? [];

        $requestData = [
            'payment' => [
                'amount' => number_format((float) $finalAmount, 2, '.', ''),
                'currency' => 'AED',
                'description' => 'Flight Booking - Andaleeb Travel Agency',
                'buyer' => [
                    'phone' => $lead['phone'] ?? 'N/A',
                    'email' => $lead['email'] ?? 'N/A',
                    'name' => trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')),
                    'dob' => '1990-01-01',
                ],
                'shipping_address' => [
                    'city' => 'N/A',
                    'address' => $lead['address'] ?? 'N/A',
                    'zip' => '00000',
                ],
                'order' => [
                    'tax_amount' => '0.00',
                    'shipping_amount' => '0.00',
                    'discount_amount' => '0.00',
                    'updated_at' => now()->toIso8601String(),
                    'reference_id' => $booking->booking_number,
                    'items' => $items,
                ],
                'buyer_history' => [
                    'registered_since' => now()->subYears(2)->toIso8601String(),
                    'loyalty_level' => 0,
                    'wishlist_count' => 0,
                    'is_social_networks_connected' => true,
                    'is_phone_number_verified' => true,
                    'is_email_verified' => true,
                ],
                'order_history' => [
                    [
                        'purchased_at' => now()->subMonths(3)->toIso8601String(),
                        'amount' => '100.00',
                        'payment_method' => 'card',
                        'status' => 'new',
                        'buyer' => [
                            'phone' => $lead['phone'] ?? 'N/A',
                            'email' => $lead['email'] ?? 'N/A',
                            'name' => trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')),
                            'dob' => '1990-01-01',
                        ],
                        'shipping_address' => [
                            'city' => 'Dubai',
                            'address' => $lead['address'] ?? 'N/A',
                            'zip' => '00000',
                        ],
                        'items' => $items,
                    ],
                ],
                'meta' => [
                    'order_id' => (string) $booking->id,
                    'customer' => (string) $booking->id,
                ],
                'attachment' => [
                    'body' => json_encode([
                        'booking_details' => [
                            'booking_number' => $booking->booking_number,
                            'route' => ($booking->from_airport ?? '') . '-' . ($booking->to_airport ?? ''),
                        ],
                    ]),
                    'content_type' => 'application/vnd.tabby.v1+json',
                ],
            ],
            'lang' => 'en',
            'merchant_code' => $this->tabbyMerchantCode,
            'merchant_urls' => [
                'success' => route('user.flights.payment.success', ['booking' => $booking->id]),
                'cancel' => route('user.flights.payment.failed'),
                'failure' => route('user.flights.payment.failed'),
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
                $booking->update([
                    'tabby_payment_id' => $responseData['payment']['id'],
                ]);
            }

            return $responseData['configuration']['available_products']['installments'][0]['web_url'];
        }

        throw new \Exception('Tabby checkout creation failed: No redirect URL found in response');
    }

    protected function tamaraRedirect(B2bFlightBooking $booking): string
    {
        if (empty($this->tamaraApiToken)) {
            throw new \Exception('Tamara API token is missing. Check your .env file.');
        }

        $remainingAmount = $booking->total_amount - ($booking->wallet_amount ?? 0);
        $finalAmount = $remainingAmount + ($remainingAmount * $this->commissionPercentage);
        $amount = number_format((float) $finalAmount, 2, '.', '');

        $lead = $booking->passengers_data['lead'] ?? [];

        $address = [
            'first_name' => $lead['first_name'] ?? 'N/A',
            'last_name' => $lead['last_name'] ?? 'N/A',
            'line1' => $lead['address'] ?? 'N/A',
            'city' => 'Dubai',
            'country_code' => 'AE',
            'phone_number' => $lead['phone'] ?? 'N/A',
        ];

        $requestData = [
            'order_reference_id' => $booking->booking_number,
            'order_number' => $booking->booking_number,
            'description' => "Flight booking {$booking->booking_number}",
            'country_code' => 'AE',
            'locale' => 'en_US',
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'total_amount' => [
                'amount' => $amount,
                'currency' => 'AED',
            ],
            'shipping_amount' => [
                'amount' => '0.00',
                'currency' => 'AED',
            ],
            'tax_amount' => [
                'amount' => '0.00',
                'currency' => 'AED',
            ],
            'items' => [
                [
                    'reference_id' => (string) $booking->id,
                    'type' => 'Flight Booking',
                    'name' => 'Flight Booking',
                    'sku' => $booking->booking_number,
                    'quantity' => 1,
                    'unit_price' => [
                        'amount' => $amount,
                        'currency' => 'AED',
                    ],
                    'tax_amount' => [
                        'amount' => '0.00',
                        'currency' => 'AED',
                    ],
                    'total_amount' => [
                        'amount' => $amount,
                        'currency' => 'AED',
                    ],
                ],
            ],
            'consumer' => [
                'first_name' => $lead['first_name'] ?? 'N/A',
                'last_name' => $lead['last_name'] ?? 'N/A',
                'phone_number' => $lead['phone'] ?? 'N/A',
                'email' => $lead['email'] ?? 'N/A',
            ],
            'billing_address' => $address,
            'shipping_address' => $address,
            'merchant_url' => [
                'success' => route('user.flights.payment.success', ['booking' => $booking->id]),
                'failure' => route('user.flights.payment.failed', ['booking' => $booking->id]),
                'cancel' => route('user.flights.payment.failed', ['booking' => $booking->id]),
                'notification' => route('user.flights.payment.success', ['booking' => $booking->id]),
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->tamaraApiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->tamaraApiUrl . '/checkout', $requestData);

        if (!$response->successful()) {
            throw new \Exception('Tamara API request failed: ' . $response->body());
        }

        $responseData = $response->json();
        $orderId = $responseData['order_id'] ?? null;
        $checkoutUrl = $responseData['checkout_url'] ?? null;

        if (!$checkoutUrl || !$orderId) {
            throw new \Exception('Tamara checkout creation failed: checkout URL or order ID missing.');
        }

        $booking->update([
            'payment_reference' => $orderId,
            'payment_response' => $responseData,
        ]);

        return $checkoutUrl;
    }

    public function verifyPayByPayment(B2bFlightBooking $booking): array
    {
        try {
            $requestTime = now()->timestamp * 1000;

            $requestData = [
                'requestTime' => $requestTime,
                'bizContent' => [
                    'merchantOrderNo' => $booking->booking_number,
                ],
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
                    'data' => $responseData,
                ];
            }

            throw new \Exception('PayBy payment not settled. Status: ' . ($responseData['body']['acquireOrder']['status'] ?? 'Unknown'));
        } catch (\Exception $e) {
            Log::error('PayBy Verification Error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function verifyTabbyPayment(B2bFlightBooking $booking): array
    {
        return [
            'success' => true,
            'data' => [],
        ];
    }

    public function verifyTamaraPayment(B2bFlightBooking $booking): array
    {
        try {
            if (empty($this->tamaraApiToken)) {
                throw new \Exception('Tamara API token is missing. Check your .env file.');
            }

            if (empty($booking->payment_reference)) {
                throw new \Exception('Tamara order ID is missing for this booking.');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->tamaraApiToken,
                'Accept' => 'application/json',
            ])->get($this->tamaraApiUrl . '/orders/' . $booking->payment_reference);

            if (!$response->successful()) {
                throw new \Exception('Tamara verification API request failed: ' . $response->body());
            }

            $responseData = $response->json();
            $status = strtolower((string) ($responseData['status'] ?? ''));
            $paidStatuses = ['approved', 'authorised', 'authorized', 'captured', 'fully_captured', 'partially_captured'];

            if (!in_array($status, $paidStatuses, true)) {
                throw new \Exception('Tamara payment not approved. Status: ' . ($responseData['status'] ?? 'unknown'));
            }

            $tamaraAmount = (float) data_get($responseData, 'total_amount.amount', 0);
            $expectedAmount = (float) (
                ($booking->total_amount - ($booking->wallet_amount ?? 0))
                + (($booking->total_amount - ($booking->wallet_amount ?? 0)) * $this->commissionPercentage)
            );

            if ($tamaraAmount > 0 && abs($tamaraAmount - $expectedAmount) > 0.01) {
                throw new \Exception('Tamara amount mismatch detected.');
            }

            return [
                'success' => true,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('Tamara Verification Error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createSabrePnr(B2bFlightBooking $booking): array
    {
        try {
            $token = $this->getSabreToken();

            $searchResponse = $booking->search_response ?? [];
            $itineraryData = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
            $sabreItineraryId = (int) ($itineraryData['sabre_itinerary_id'] ?? $booking->itinerary_id);
            $groupIndex = array_key_exists('sabre_group_index', $itineraryData)
                ? (int) $itineraryData['sabre_group_index']
                : null;
            $itineraryRaw = $this->findRawItinerary($searchResponse, $sabreItineraryId, $groupIndex);

            if (!$itineraryRaw) {
                throw new \Exception('Unable to locate itinerary for booking.');
            }

            $payload = $this->buildPnrPayload($booking, $searchResponse, $itineraryRaw, true);
            $createResult = $this->postSabreCreatePnr($token, $payload);
            $responseData = $createResult['data'];
            $locator = $this->extractSabrePnrLocator($responseData);

            if ($locator === null && $this->shouldRetrySabrePnrWithoutAirPrice($responseData)) {
                Log::info('Retrying Sabre PNR create without embedded AirPrice', [
                    'booking_id' => $booking->id,
                ]);

                $payload = $this->buildPnrPayload($booking, $searchResponse, $itineraryRaw, false);
                $createResult = $this->postSabreCreatePnr($token, $payload);
                $responseData = $createResult['data'];
                $locator = $this->extractSabrePnrLocator($responseData);

                if ($locator !== null) {
                    $booking->update([
                        'booking_request' => $payload,
                        'booking_response' => $responseData,
                        'sabre_record_locator' => $locator,
                        'hold_expires_at' => now()->addHour(),
                    ]);

                    $reprice = $this->storeSabrePriceQuotesOnPnr($booking->fresh(), $token);
                    if (($reprice['records'] ?? []) === []) {
                        Log::warning('Sabre hold PNR created but SOAP repricing stored no price quote', [
                            'booking_id' => $booking->id,
                            'locator' => $locator,
                            'error' => $reprice['error'] ?? null,
                        ]);
                    }
                }
            }

            if (! ($createResult['successful'] ?? false) && $locator === null) {
                throw new \Exception('Sabre PNR creation failed: ' . ($createResult['body'] ?? ''));
            }

            $booking->update([
                'booking_request' => $payload,
                'booking_response' => $responseData,
            ]);

            $storedPriceQuotes = SabrePriceQuoteResolver::fromBookingResponse(
                is_array($responseData) ? $responseData : null,
            );
            if ($locator && $storedPriceQuotes === []) {
                Log::warning('Sabre PNR created without stored price quote', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                ]);
            }

            $errorMessage = null;
            if ($locator) {
                // Sabre does not return a ticketingDeadline in CreatePassengerNameRecordRS,
                // so we default hold expiry to 1 hour from now as per airline standard policy.
                $holdExpiresAt = now()->addHour();

                $pnrUpdate = [
                    'sabre_record_locator' => $locator,
                    'hold_expires_at'      => $holdExpiresAt,
                ];
                if ($booking->payment_method !== 'hold') {
                    $pnrUpdate['booking_status'] = 'confirmed';
                }

                $booking->update($pnrUpdate);
            } else {
                $messages = SabreApplicationResultsParser::messages($responseData);
                $errorMessage = $messages !== []
                    ? implode('; ', $messages)
                    : 'Sabre PNR creation completed without a record locator.';

                Log::error('Hold PNR creation failed', [
                    'booking_id' => $booking->id,
                    'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'sabre_messages' => $messages,
                    'application_status' => data_get($responseData, 'CreatePassengerNameRecordRS.ApplicationResults.status'),
                ]);
            }

            return [
                'success' => (bool) $locator,
                'data' => $responseData,
                'locator' => $locator,
                'error' => $locator ? null : ($errorMessage ?? 'Sabre PNR creation completed without a record locator.'),
            ];
        } catch (\Exception $e) {
            Log::error('Sabre PNR creation exception', [
                'booking_id' => $booking->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function issueSabreTicket(B2bFlightBooking $booking): array
    {
        try {
            $token = $this->getSabreToken();
            $locator = $booking->sabre_record_locator;

            if (empty($locator)) {
                throw new \Exception('Missing Sabre record locator for ticketing.');
            }

            $priceQuoteRecords = $this->shouldRefreshSabrePriceQuote($booking)
                ? []
                : $this->resolveSabrePriceQuoteRecords($booking, $token);
            if ($priceQuoteRecords === []) {
                Log::info('Repricing Sabre PNR before ticketing', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                ]);

                $repriceResult = $this->storeSabrePriceQuotesOnPnr($booking, $token);
                $priceQuoteRecords = $repriceResult['records'] ?? [];
            }

            if ($priceQuoteRecords === []) {
                $repriceError = $repriceResult['error'] ?? null;
                throw new \Exception($repriceError ?: 'No active Sabre price quote found on PNR. Repricing failed.');
            }

            $payload = [
                'AirTicketRQ' => [
                    'version' => '1.3.0',
                    'DesignatePrinter' => [
                        'Printers' => [
                            'Hardcopy' => ['LNIATA' => (string) config('services.sabre.ticket_printer_lniata', 'FA8CFB')],
                            'Ticket' => ['CountryCode' => (string) config('services.sabre.ticket_printer_country_code', 'TG')],
                        ],
                    ],
                    'Itinerary' => [
                        'ID' => $locator,
                    ],
                    'Ticketing' => [
                        [
                            'FOP_Qualifiers' => [
                                'BasicFOP' => ['Type' => 'CK'],
                            ],
                            'PricingQualifiers' => [
                                'PriceQuote' => SabrePriceQuoteResolver::buildAirTicketPriceQuotePayload($priceQuoteRecords),
                            ],
                        ],
                    ],
                    'PostProcessing' => [
                        'EndTransaction' => [
                            'Source' => ['ReceivedFrom' => 'API TEST'],
                        ],
                    ],
                ],
            ];

            $response = $this->sabreHttp()->withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post('https://api.cert.platform.sabre.com/v1.3.0/air/ticket', $payload);

            if (!$response->successful()) {
                throw new \Exception('Sabre ticketing failed: ' . $response->body());
            }

            $responseData = $response->json();
            $ticketNumbers = FlightBookingTicketResolver::fromResponse(is_array($responseData) ? $responseData : null);

            if ($ticketNumbers === []) {
                $ticketNumbers = $this->fetchSabreTicketNumbersFromGetBooking($booking);
            }

            if ($ticketNumbers === []) {
                $messages = SabreApplicationResultsParser::messages(is_array($responseData) ? $responseData : null, 'AirTicketRS');
                $error = $messages !== []
                    ? implode('; ', $messages)
                    : 'Sabre ticketing completed without ticket numbers.';

                throw new \Exception($error);
            }

            $ticketUpdate = [
                'ticket_request' => $payload,
                'ticket_response' => $responseData,
                'ticket_numbers' => $ticketNumbers,
                'ticket_status' => 'issued',
            ];

            if ($booking->booking_status === 'hold') {
                $ticketUpdate['booking_status'] = 'confirmed';
            }

            $booking->update($ticketUpdate);
            $booking->reconcileStatusAfterHoldPayment();

            return [
                'success' => true,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'AUTH CARRIER INVLD')) {
                Log::error('Sabre ticketing authority error', [
                    'booking_id' => $booking->id,
                    'locator' => $booking->sabre_record_locator,
                    'validating_carrier' => $this->resolveSabreValidatingCarrier($booking),
                    'pcc' => $this->sabrePcc,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::error('Sabre ticketing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            $booking->update([
                'ticket_status' => 'failed',
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createTravelportHoldPnr(B2bFlightBooking $booking): array
    {
        return app(TravelportBookingService::class)->createHold($booking);
    }

    /** Book Now and hold both use airPrice → airHold (no GDS card). */
    public function createTravelportReservation(B2bFlightBooking $booking): array
    {
        return $this->createTravelportHoldPnr($booking);
    }

    public function issueTravelportTicket(B2bFlightBooking $booking): array
    {
        return app(TravelportBookingService::class)->issueTicket($booking);
    }

    public function cancelTravelportBooking(B2bFlightBooking $booking): array
    {
        return app(TravelportBookingService::class)->cancelHold($booking);
    }

    /**
     * Post-payment PNR + ticketing for paid bookings (Sabre and Travelport).
     * Idempotent when ticket is already issued.
     *
     * @return array{success: bool, already_complete?: bool, error?: string, stage?: string, pnr?: string|null}
     */
    public function fulfillPaidBooking(B2bFlightBooking $booking): array
    {
        $booking->reconcileStatusAfterHoldPayment();

        if ($booking->payment_status !== 'paid') {
            return [
                'success' => false,
                'error' => 'Booking payment is not completed.',
            ];
        }

        if ($booking->booking_status === 'cancelled') {
            return [
                'success' => false,
                'error' => 'Booking has been cancelled.',
            ];
        }

        if ($booking->hasVerifiedTicketIssue()) {
            return [
                'success' => true,
                'already_complete' => true,
                'pnr' => $booking->sabre_record_locator,
            ];
        }

        if ($booking->ticket_status === 'issued' && ! $booking->hasVerifiedTicketIssue()) {
            $booking->update([
                'ticket_status' => 'pending',
                'ticket_numbers' => [],
            ]);
            $booking->refresh();
        }

        if (empty($booking->sabre_record_locator)) {
            $pnrResult = $booking->isTravelport()
                ? $this->createTravelportHoldPnr($booking)
                : $this->createSabrePnr($booking);

            if (! ($pnrResult['success'] ?? false)) {
                $booking->update(['booking_status' => 'failed']);

                Log::error('Flight PNR creation failed during fulfillment', [
                    'booking_id' => $booking->id,
                    'provider' => $booking->isTravelport() ? 'travelport' : 'sabre',
                    'payment_status' => $booking->payment_status,
                    'result' => $pnrResult,
                ]);

                return [
                    'success' => false,
                    'stage' => 'pnr',
                    'error' => $pnrResult['error'] ?? $pnrResult['message'] ?? 'Unable to confirm booking at the airline.',
                ];
            }

            $booking->refresh();

            if ($booking->isTravelport() && $booking->payment_method !== 'hold') {
                $booking->update(['booking_status' => 'confirmed']);
            }
        }

        if ($booking->ticket_status !== 'issued') {
            $ticketResult = $booking->isTravelport()
                ? $this->issueTravelportTicket($booking)
                : $this->issueSabreTicket($booking);

            if (! ($ticketResult['success'] ?? false)) {
                Log::error('Flight ticketing failed during fulfillment', [
                    'booking_id' => $booking->id,
                    'provider' => $booking->isTravelport() ? 'travelport' : 'sabre',
                    'pnr' => $booking->sabre_record_locator,
                    'error' => $ticketResult['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'stage' => 'ticket',
                    'error' => $ticketResult['error'] ?? 'Booking confirmed but ticketing failed.',
                    'pnr' => $booking->sabre_record_locator,
                ];
            }

            $booking->refresh();
        }

        $booking->reconcileStatusAfterHoldPayment();

        return [
            'success' => true,
            'pnr' => $booking->sabre_record_locator,
        ];
    }

    /**
     * @return list<string>
     */
    public function syncSabreTicketNumbersIfMissing(B2bFlightBooking $booking): array
    {
        if (! $booking->isSabre() || ! $booking->hasAirlinePnr()) {
            return [];
        }

        $existing = $booking->resolvedTicketNumbers();
        if ($existing !== []) {
            return $existing;
        }

        $numbers = $this->fetchSabreTicketNumbersFromGetBooking($booking);
        if ($numbers === []) {
            return [];
        }

        $booking->update(['ticket_numbers' => $numbers]);

        return $numbers;
    }

    /**
     * @return list<string>
     */
    private function fetchSabreTicketNumbersFromGetBooking(B2bFlightBooking $booking): array
    {
        $fetch = $this->fetchLiveSabreBookingDetails($booking);
        if (! ($fetch['ok'] ?? false)) {
            return [];
        }

        $response = is_array($fetch['response'] ?? null) ? $fetch['response'] : [];

        return FlightBookingTicketResolver::normalizeList($response['tickets'] ?? []);
    }

    /**
     * @return array{ok: bool, source: string|null, response: array<string, mixed>|null, error: string|null}
     */
    public function fetchLiveSabreBookingDetails(B2bFlightBooking $booking): array
    {
        $locator = trim((string) ($booking->sabre_record_locator ?? ''));
        if ($locator === '') {
            return [
                'ok' => false,
                'source' => null,
                'response' => null,
                'error' => 'Missing Sabre PNR on this booking.',
            ];
        }

        $surname = trim((string) (
            data_get($booking->passengers_data, 'lead.last_name')
            ?? data_get($booking->passengers_data, 'passengers.0.last_name')
            ?? ''
        ));

        try {
            $token = $this->getSabreToken();
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'source' => null,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }

        $rest = $this->fetchSabreGetBooking($token, $locator, $surname !== '' ? $surname : null);
        if ($rest['ok']) {
            return $rest;
        }

        $soap = $this->fetchSabreGetReservationDetails($token, $locator);
        if ($soap['ok']) {
            return $soap;
        }

        return [
            'ok' => false,
            'source' => null,
            'response' => null,
            'error' => $soap['error'] ?? $rest['error'] ?? 'Unable to fetch Sabre booking details.',
        ];
    }

    /**
     * @return array{ok: bool, source: string, response: array<string, mixed>|null, error: string|null}
     */
    private function fetchSabreGetBooking(string $token, string $locator, ?string $surname): array
    {
        $payloads = [];

        if ($surname !== null && $surname !== '') {
            $payloads[] = [
                'confirmationId' => $locator,
                'bookingSource' => 'SABRE',
                'surname' => $surname,
            ];
        }

        $payloads[] = [
            'confirmationId' => $locator,
            'bookingSource' => 'SABRE',
        ];

        $lastError = 'Sabre Get Booking request failed.';

        foreach ($payloads as $payload) {
            try {
                $response = $this->sabreHttp()->withToken($token)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post('https://api.cert.platform.sabre.com/v1/trip/orders/getBooking', $payload);

                $body = $response->json() ?? [];

                if ($response->successful() && empty(data_get($body, 'errors'))) {
                    $normalized = $this->normalizeGetBookingResponse($body);
                    if (! empty($normalized['confirmationId'])) {
                        return [
                            'ok' => true,
                            'source' => 'live',
                            'response' => $normalized,
                            'error' => null,
                        ];
                    }
                }

                $lastError = (string) (
                    data_get($body, 'errors.0.description')
                    ?? data_get($body, 'message')
                    ?? ('Get Booking failed: HTTP ' . $response->status())
                );
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }

        return [
            'ok' => false,
            'source' => 'rest',
            'response' => null,
            'error' => $lastError,
        ];
    }

    /**
     * @return array{ok: bool, source: string, response: array<string, mixed>|null, error: string|null}
     */
    private function fetchSabreGetReservationDetails(string $bearerToken, string $locator): array
    {
        try {
            $session = $this->createSoapSession($bearerToken);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'source' => 'soap',
                'response' => null,
                'error' => 'Sabre session: ' . $e->getMessage(),
            ];
        }

        $binaryToken = $session['token'] ?? null;
        if (! $binaryToken) {
            return [
                'ok' => false,
                'source' => 'soap',
                'response' => null,
                'error' => 'Sabre session returned empty token.',
            ];
        }

        try {
            $reservation = $this->getReservation($binaryToken, $locator, $bearerToken);
            if (! ($reservation['success'] ?? false)) {
                return [
                    'ok' => false,
                    'source' => 'soap',
                    'response' => null,
                    'error' => $reservation['error'] ?? 'GetReservation failed.',
                ];
            }

            $parsed = $this->parseGetReservationXml($reservation['response']);
            $parsed['confirmationId'] = $parsed['confirmationId'] ?: $locator;

            return [
                'ok' => true,
                'source' => 'live',
                'response' => $parsed,
                'error' => null,
            ];
        } finally {
            try {
                $this->closeSession($binaryToken, $bearerToken);
            } catch (\Throwable) {
                // Read-only lookup; ignore session close failures.
            }
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function normalizeGetBookingResponse(array $body): array
    {
        $booking = data_get($body, 'booking') ?? $body;

        $travelers = [];
        foreach ((array) data_get($booking, 'travelers', []) as $traveler) {
            $name = trim(implode(' ', array_filter([
                data_get($traveler, 'givenName'),
                data_get($traveler, 'surname'),
            ])));

            if ($name !== '') {
                $travelers[] = $name;
            }
        }

        $flights = [];
        foreach ((array) data_get($booking, 'flights', data_get($booking, 'flightDetails', [])) as $flight) {
            $from = data_get($flight, 'fromAirportCode') ?? data_get($flight, 'fromAirport');
            $to = data_get($flight, 'toAirportCode') ?? data_get($flight, 'toAirport');
            $departure = data_get($flight, 'departureDate') ?? data_get($flight, 'departureDateTime');
            $flightNumber = data_get($flight, 'flightNumber');

            $parts = array_filter([
                $departure,
                $from && $to ? "{$from} → {$to}" : null,
                $flightNumber ? 'Flt ' . $flightNumber : null,
            ]);

            if ($parts !== []) {
                $flights[] = implode(' · ', $parts);
            }
        }

        $tickets = [];
        foreach ((array) data_get($booking, 'tickets', data_get($booking, 'ticketDetails', [])) as $ticket) {
            $number = data_get($ticket, 'number') ?? data_get($ticket, 'ticketNumber');
            if (is_string($number) && $number !== '') {
                $tickets[] = $number;
            }
        }

        foreach ((array) data_get($booking, 'accountingItems', []) as $item) {
            $number = data_get($item, 'ticketNumber') ?? data_get($item, 'documentNumber');
            if (is_string($number) && $number !== '') {
                $tickets[] = $number;
            }
        }

        $tickets = array_values(array_unique(array_filter(array_map(
            static fn ($number) => preg_replace('/\D+/', '', (string) $number),
            $tickets,
        ))));

        return [
            'confirmationId' => data_get($booking, 'confirmationId') ?? data_get($body, 'confirmationId'),
            'bookingStatus' => data_get($booking, 'bookingStatus') ?? data_get($booking, 'status'),
            'ticketStatus' => data_get($booking, 'ticketStatus') ?? ($tickets !== [] ? 'issued' : null),
            'bookingId' => data_get($booking, 'bookingId') ?? data_get($booking, 'id'),
            'travelers' => $travelers,
            'flights' => $flights,
            'tickets' => array_values(array_unique($tickets)),
            'apiSource' => 'Get Booking (REST)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseGetReservationXml(string $xml): array
    {
        $data = [
            'confirmationId' => null,
            'bookingStatus' => null,
            'ticketStatus' => null,
            'bookingId' => null,
            'travelers' => [],
            'flights' => [],
            'tickets' => [],
            'apiSource' => 'GetReservation (SOAP)',
        ];

        if (preg_match('/<(?:[\w-]+:)?RecordLocator[^>]*>([^<]+)</i', $xml, $match)) {
            $data['confirmationId'] = trim($match[1]);
        }

        if (preg_match('/<(?:[\w-]+:)?BookingDetails[^>]*Status="([^"]+)"/i', $xml, $match)) {
            $data['bookingStatus'] = trim($match[1]);
        } elseif (preg_match('/<(?:[\w-]+:)?Reservation[^>]*Status="([^"]+)"/i', $xml, $match)) {
            $data['bookingStatus'] = trim($match[1]);
        }

        if (preg_match_all('/<(?:[\w-]+:)?GivenName[^>]*>([^<]+)</i', $xml, $givenNames)
            && preg_match_all('/<(?:[\w-]+:)?Surname[^>]*>([^<]+)</i', $xml, $surnames)) {
            $count = min(count($givenNames[1]), count($surnames[1]));
            for ($i = 0; $i < $count; $i++) {
                $data['travelers'][] = trim($givenNames[1][$i]) . ' ' . trim($surnames[1][$i]);
            }
        }

        if (preg_match_all('/<(?:[\w-]+:)?FlightSegment[^>]*FlightNumber="([^"]*)"[^>]*DepartureDateTime="([^"]*)"[^>]*>/i', $xml, $segments, PREG_SET_ORDER)) {
            foreach ($segments as $segment) {
                $data['flights'][] = trim($segment[2]) . ' · Flt ' . trim($segment[1]);
            }
        } elseif (preg_match_all('/<(?:[\w-]+:)?FlightSegment[^>]*DepartureDateTime="([^"]*)"[^>]*FlightNumber="([^"]*)"[^>]*>/i', $xml, $segments, PREG_SET_ORDER)) {
            foreach ($segments as $segment) {
                $data['flights'][] = trim($segment[1]) . ' · Flt ' . trim($segment[2]);
            }
        }

        if (preg_match_all('/<(?:[\w-]+:)?TicketNumber[^>]*>([^<]+)</i', $xml, $ticketNumbers)) {
            foreach ($ticketNumbers[1] as $ticketNumber) {
                $ticketNumber = trim($ticketNumber);
                if ($ticketNumber !== '') {
                    $data['tickets'][] = $ticketNumber;
                }
            }
        }

        if ($data['tickets'] !== []) {
            $data['ticketStatus'] = 'issued';
        }

        return $data;
    }

    private function shouldRefreshSabrePriceQuote(B2bFlightBooking $booking): bool
    {
        $hadAuthCarrierError = false;
        $hadInvalidPqError = false;

        $ticketResponse = is_array($booking->ticket_response) ? $booking->ticket_response : null;
        if ($ticketResponse !== null) {
            foreach (SabreApplicationResultsParser::messages($ticketResponse, 'AirTicketRS') as $message) {
                if (str_contains($message, 'INVALID PQ NUMBER')) {
                    $hadInvalidPqError = true;
                }
                if (str_contains($message, 'AUTH CARRIER INVLD')) {
                    $hadAuthCarrierError = true;
                }
            }
        }

        if ($hadAuthCarrierError) {
            return false;
        }

        if ($hadInvalidPqError || $booking->ticket_status === 'failed') {
            return true;
        }

        return false;
    }

    /**
     * @return list<int>
     */
    private function resolveSabrePriceQuoteRecords(B2bFlightBooking $booking, string $bearerToken): array
    {
        $fromBooking = SabrePriceQuoteResolver::fromBookingResponse(
            is_array($booking->booking_response) ? $booking->booking_response : null,
        );
        if ($fromBooking !== []) {
            return $fromBooking;
        }

        $locator = trim((string) $booking->sabre_record_locator);
        if ($locator === '') {
            return [];
        }

        return $this->fetchSabrePriceQuotesFromPnr($bearerToken, $locator);
    }

    /**
     * @return list<int>
     */
    private function fetchSabrePriceQuotesFromPnr(string $bearerToken, string $locator): array
    {
        try {
            $session = $this->createSoapSession($bearerToken);
        } catch (\Throwable $e) {
            Log::warning('Sabre PQ lookup session failed', [
                'locator' => $locator,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $binaryToken = $session['token'] ?? null;
        if (! $binaryToken) {
            return [];
        }

        try {
            $reservation = $this->getReservation($binaryToken, $locator, $bearerToken);
            if (! ($reservation['success'] ?? false)) {
                return [];
            }

            return SabrePriceQuoteResolver::fromXml((string) ($reservation['response'] ?? ''));
        } finally {
            try {
                $this->closeSession($binaryToken, $bearerToken);
            } catch (\Throwable) {
                // Read-only lookup; ignore session close failures.
            }
        }
    }

    /**
     * @return array{records: list<int>, error: string|null}
     */
    private function storeSabrePriceQuotesOnPnr(B2bFlightBooking $booking, string $bearerToken): array
    {
        $locator = trim((string) $booking->sabre_record_locator);
        if ($locator === '') {
            return ['records' => [], 'error' => 'Missing Sabre record locator.'];
        }

        $session = $this->createSoapSession($bearerToken);
        $binaryToken = $session['token'] ?? null;
        if (! $binaryToken) {
            return [
                'records' => [],
                'error' => 'Unable to establish Sabre SOAP session for repricing.',
            ];
        }

        try {
            $reservation = $this->getReservation($binaryToken, $locator, $bearerToken);
            if (! ($reservation['success'] ?? false)) {
                throw new \Exception($reservation['error'] ?? 'Unable to retrieve Sabre reservation for repricing.');
            }

            $priceResult = $this->otaAirPriceRetain(
                $binaryToken,
                $booking,
                $bearerToken,
            );
            if (! ($priceResult['success'] ?? false)) {
                throw new \Exception($priceResult['error'] ?? 'Sabre SOAP repricing failed.');
            }

            $records = SabrePriceQuoteResolver::fromXml((string) ($priceResult['response'] ?? ''));
            $end = $this->endTransaction($binaryToken, $bearerToken);
            if (! ($end['success'] ?? false)) {
                Log::warning('Sabre end transaction after repricing failed', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                    'error' => $end['error'] ?? null,
                ]);
            }

            if ($records === []) {
                $reservationAfterPrice = $this->getReservation($binaryToken, $locator, $bearerToken);
                if ($reservationAfterPrice['success'] ?? false) {
                    $records = SabrePriceQuoteResolver::fromXml((string) ($reservationAfterPrice['response'] ?? ''));
                }
            }

            if ($records === []) {
                Log::warning('Sabre repricing returned no price quote records', [
                    'booking_id' => $booking->id,
                    'locator' => $locator,
                    'response_excerpt' => mb_substr((string) ($priceResult['response'] ?? ''), 0, 2000),
                ]);
            }

            return [
                'records' => $records,
                'error' => $records === [] ? 'Sabre repricing completed without active price quote records.' : null,
            ];
        } catch (\Throwable $e) {
            Log::error('Sabre repricing before ticketing failed', [
                'booking_id' => $booking->id,
                'locator' => $locator,
                'error' => $e->getMessage(),
            ]);

            return [
                'records' => [],
                'error' => $e->getMessage(),
            ];
        } finally {
            try {
                $this->closeSession($binaryToken, $bearerToken);
            } catch (\Throwable) {
                // Ignore session close failures after repricing attempt.
            }
        }
    }

    /**
     * @return array{success: bool, response?: string, error?: string}
     */
    private function otaAirPriceRetain(string $binaryToken, B2bFlightBooking $booking, ?string $bearerToken = null): array
    {
        $passengerTypeXml = '';
        foreach ($this->buildPassengerTypes($booking) as $type) {
            $code = htmlspecialchars((string) ($type['Code'] ?? 'ADT'), ENT_XML1);
            $quantity = htmlspecialchars((string) ($type['Quantity'] ?? '1'), ENT_XML1);
            $passengerTypeXml .= "<PassengerType Code=\"{$code}\" Quantity=\"{$quantity}\"/>";
        }

        $flightQualifiersXml = '';
        $validatingCarrier = $this->resolveSabreValidatingCarrier($booking);
        if ($validatingCarrier !== null) {
            $carrier = htmlspecialchars($validatingCarrier, ENT_XML1);
            $flightQualifiersXml = <<<XML
                <FlightQualifiers>
                    <VendorPrefs>
                        <Airline Code="{$carrier}"/>
                    </VendorPrefs>
                </FlightQualifiers>
            XML;
        }

        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>OTA_AirPrice_Retain</eb:ConversationId>
            <eb:Service>OTA_AirPriceLLSRQ</eb:Service>
            <eb:Action>OTA_AirPriceLLSRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>price-{$timestamp}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <OTA_AirPriceRQ xmlns="http://webservices.sabre.com/sabreXML/2011/10" Version="2.17.0">
            <PriceRequestInformation Retain="true">
                <OptionalQualifiers>
                    {$flightQualifiersXml}
                    <PricingQualifiers>
                        {$passengerTypeXml}
                    </PricingQualifiers>
                </OptionalQualifiers>
            </PriceRequestInformation>
        </OTA_AirPriceRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'OTA_AirPriceLLSRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'OTA_AirPrice failed: ' . $response->body(),
            ];
        }

        $body = $response->body();
        if (preg_match('/<(?:[\w-]+:)?ApplicationResults[^>]*\bstatus="(?:NotProcessed|Incomplete)"/i', $body)
            || preg_match('/<(?:[\w-]+:)?Error\b/i', $body)) {
            $fault = $this->extractSoapFault($body);

            return [
                'success' => false,
                'error' => $fault ?: 'OTA_AirPrice returned an error response.',
                'response' => $body,
            ];
        }

        return [
            'success' => true,
            'response' => $body,
        ];
    }

    public function cancelSabreBooking(B2bFlightBooking $booking): array
    {
        $locator = $booking->sabre_record_locator;
        if (empty($locator)) {
            throw new \Exception('Missing Sabre record locator.');
        }

        $bearerToken = $this->getSabreToken();

        try {
            $session = $this->createSoapSession($bearerToken);
        } catch (\Throwable $e) {
            // The session creator already logs the full SOAP fault; bubble its message up.
            throw new \Exception('Unable to establish Sabre session: ' . $e->getMessage());
        }

        $binaryToken = $session['token'] ?? null;

        if (!$binaryToken) {
            throw new \Exception('Unable to establish Sabre session: empty token returned.');
        }

        $reservation = $this->getReservation($binaryToken, $locator, $bearerToken);
        if (!($reservation['success'] ?? false)) {
            throw new \Exception($reservation['error'] ?? 'Unable to retrieve reservation.');
        }

        $cancel = $this->cancelItinerary($binaryToken, $bearerToken);
        $end = $this->endTransaction($binaryToken, $bearerToken);
        $close = $this->closeSession($binaryToken, $bearerToken);

        return [
            'success' => (bool) ($cancel['success'] ?? false),
            'reservation' => $reservation,
            'cancel' => $cancel,
            'end' => $end,
            'close' => $close,
        ];
    }

    public function revalidateItinerary(
        array $searchResponse,
        int $itineraryId,
        int $adults,
        int $children,
        int $infants,
        ?int $groupIndex = null,
        int $pricingIndex = 0,
    ): array {
        try {
            $token = $this->getSabreToken();

            $itineraryRaw = $this->findRawItinerary($searchResponse, $itineraryId, $groupIndex);
            if (!$itineraryRaw) {
                throw new \Exception('Unable to locate itinerary for revalidation.');
            }

            $payload = $this->buildRevalidatePayload($searchResponse, $itineraryRaw, $adults, $children, $infants, $pricingIndex);
            if (empty($payload['OTA_AirLowFareSearchRQ']['OriginDestinationInformation'])) {
                throw new \Exception('Unable to build revalidation payload.');
            }

            $response = $this->sabreHttp()->withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post('https://api.cert.platform.sabre.com/v4/shop/flights/revalidate', $payload);

            if (!$response->successful()) {
                throw new \Exception('Sabre revalidation failed: ' . $response->body());
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Sabre revalidation failed', [
                'itinerary_id' => $itineraryId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getSabreToken(): string
    {
        if (empty($this->sabreBasicAuth)) {
            throw new \Exception('Sabre credentials are not configured.');
        }

        $response = $this->sabreHttp()->asForm()->withHeaders([
            'Authorization' => 'Basic ' . $this->sabreBasicAuth,
        ])->post('https://api.cert.platform.sabre.com/v2/auth/token', [
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Unable to fetch Sabre token.');
        }

        $token = $response->json('access_token');
        if (!$token) {
            throw new \Exception('Sabre token missing in response.');
        }

        return $token;
    }

    private function findRawItinerary(array $grouped, int $itineraryId, ?int $groupIndex = null): ?array
    {
        $groups = $grouped['itineraryGroups'] ?? [];

        if ($groupIndex !== null && isset($groups[$groupIndex])) {
            foreach ($groups[$groupIndex]['itineraries'] ?? [] as $itinerary) {
                if ((int) ($itinerary['id'] ?? 0) === $itineraryId) {
                    return $itinerary;
                }
            }
        }

        foreach ($groups as $group) {
            foreach ($group['itineraries'] ?? [] as $itinerary) {
                if ((int) ($itinerary['id'] ?? 0) === $itineraryId) {
                    return $itinerary;
                }
            }
        }

        return null;
    }

    private function buildPnrPayload(B2bFlightBooking $booking, array $grouped, array $itineraryRaw, bool $includeAirPrice = true): array
    {
        $segments = $this->buildAirBookSegments($booking, $grouped, $itineraryRaw);
        $passengers = $booking->passengers_data['passengers'] ?? [];
        $lead = $booking->passengers_data['lead'] ?? [];

        $personNames = [];
        $index = 1;
        foreach ($passengers as $passenger) {
            $personNames[] = [
                'NameNumber' => $index . '.1',
                'PassengerType' => $passenger['type'] ?? 'ADT',
                'GivenName' => strtoupper((string) ($passenger['first_name'] ?? '')),
                'Surname' => strtoupper((string) ($passenger['last_name'] ?? '')),
            ];
            $index++;
        }

        // Sabre rejects phone strings with spaces / parens / '+' – normalize to digits + dashes.
        $rawPhone = (string) ($lead['phone'] ?? '');
        $cleanPhone = preg_replace('/[^0-9\-]/', '', str_replace(['+', '(', ')', ' '], ['', '', '', '-'], $rawPhone));
        $cleanPhone = trim((string) $cleanPhone, '-') ?: '0000000000';

        $createRq = [
                'version' => '2.5.0',
                'haltOnAirPriceError' => $includeAirPrice,
                'targetCity' => $this->sabrePcc,
                'TravelItineraryAddInfo' => [
                    'AgencyInfo' => [
                        'Ticketing' => [
                            'TicketType' => '7TAW',
                        ],
                    ],
                    'CustomerInfo' => [
                        'ContactNumbers' => [
                            // Sabre schema requires ContactNumber to be an array of contacts.
                            'ContactNumber' => [
                                [
                                    'NameNumber'    => '1.1',
                                    'Phone'         => $cleanPhone,
                                    'PhoneUseType'  => 'H',
                                ],
                            ],
                        ],
                        'PersonName' => $personNames,
                    ],
                ],
                'AirBook' => [
                    'OriginDestinationInformation' => [
                        'FlightSegment' => $segments,
                    ],
                ],
                'PostProcessing' => [
                    'EndTransaction' => [
                        'Source' => [
                            'ReceivedFrom' => 'SP TEST',
                        ],
                    ],
                ],
            ];

        if ($includeAirPrice) {
            $createRq['AirPrice'] = [
                [
                    'PriceRequestInformation' => $this->buildSabreAirPriceRequestInformation(
                        $booking,
                        true,
                        true,
                        true,
                        true,
                        $itineraryRaw,
                        $grouped,
                    ),
                ],
            ];
        }

        return [
            'CreatePassengerNameRecordRQ' => $createRq,
        ];
    }

    /**
     * @return array{successful: bool, body: string, data: ?array}
     */
    private function postSabreCreatePnr(string $token, array $payload): array
    {
        $response = $this->sabreHttp()->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.cert.platform.sabre.com/v2.5.0/passenger/records?mode=create', $payload);

        return [
            'successful' => $response->successful(),
            'body' => (string) $response->body(),
            'data' => $response->json(),
        ];
    }

    private function extractSabrePnrLocator(?array $responseData): ?string
    {
        $locator = data_get($responseData, 'CreatePassengerNameRecordRS.ItineraryRef.ID')
            ?? data_get($responseData, 'CreatePassengerNameRecordRS.ItineraryRef[0].ID')
            ?? data_get($responseData, 'TravelItineraryReadRS.ItineraryRef.ID')
            ?? data_get($responseData, 'ItineraryRef.ID');

        $locator = trim((string) $locator);

        return $locator !== '' ? $locator : null;
    }

    private function shouldRetrySabrePnrWithoutAirPrice(?array $responseData): bool
    {
        if ($this->extractSabrePnrLocator($responseData) !== null) {
            return false;
        }

        $messages = implode(' ', SabreApplicationResultsParser::messages($responseData));
        $normalized = strtoupper($messages);

        return str_contains($normalized, 'NO COMBINABLE FARES')
            || str_contains($normalized, 'UNABLE TO PERFORM AIR BOOKING')
            || str_contains($normalized, 'AIR PRICE')
            || data_get($responseData, 'CreatePassengerNameRecordRS.ApplicationResults.status') === 'Incomplete';
    }

    private function buildRevalidatePayload(array $grouped, array $itineraryRaw, int $adults, int $children, int $infants, int $pricingIndex = 0): array
    {
        $originDestinations = $this->buildRevalidateOriginDestinations($grouped, $itineraryRaw, $pricingIndex);
        $passengerTypes = $this->buildRevalidatePassengerTypes($adults, $children, $infants);
        $totalPassengers = max(1, $adults + $children + $infants);

        return [
            'OTA_AirLowFareSearchRQ' => [
                'Version' => '4',
                'AvailableFlightsOnly' => true,
                'POS' => [
                    'Source' => [
                        [
                            'PseudoCityCode' => $this->sabrePcc,
                            'RequestorID' => [
                                'CompanyName' => [
                                    'Code' => $this->sabreCompanyCode,
                                ],
                                'ID' => '1',
                                'Type' => '1',
                            ],
                        ],
                    ],
                ],
                'OriginDestinationInformation' => $originDestinations,
                'TravelerInfoSummary' => [
                    'SeatsRequested' => [$totalPassengers],
                    'AirTravelerAvail' => [
                        [
                            'PassengerTypeQuantity' => $passengerTypes,
                        ],
                    ],
                    'PriceRequestInformation' => [
                        'TPA_Extensions' => new \stdClass(),
                    ],
                ],
                'TravelPreferences' => [
                    'ValidInterlineTicket' => true,
                    'TPA_Extensions' => [
                        'DataSources' => [
                            'NDC' => 'Disable',
                            'ATPCO' => 'Enable',
                            'LCC' => 'Disable',
                        ],
                        'PreferNDCSourceOnTie' => [
                            'Value' => false,
                        ],
                        'ExcludeCallDirectCarriers' => [
                            'Enabled' => true,
                        ],
                        'KeepSameCabin' => [
                            'Enabled' => true,
                        ],
                        'VerificationItinCallLogic' => [
                            'AlwaysCheckAvailability' => true,
                            'Value' => 'M',
                        ],
                    ],
                    'AncillaryFees' => [
                        'AncillaryFeeGroup' => [
                            ['Code' => 'BG'],
                            ['Code' => 'ML'],
                        ],
                        'Enable' => true,
                        'Summary' => true,
                    ],
                    'Baggage' => [
                        'Description' => true,
                        'RequestType' => 'A',
                        'CarryOnInfo' => true,
                    ],
                    'CabinPref' => [
                        [
                            'Cabin' => 'Y',
                            'PreferLevel' => 'Only',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildRevalidatePassengerTypes(int $adults, int $children, int $infants): array
    {
        $types = [
            ['Code' => 'ADT', 'Quantity' => max(1, $adults)],
        ];

        if ($children > 0) {
            $types[] = ['Code' => 'C06', 'Quantity' => $children];
        }

        if ($infants > 0) {
            $types[] = ['Code' => 'INF', 'Quantity' => $infants];
        }

        return $types;
    }

    private function buildRevalidateOriginDestinations(array $grouped, array $itineraryRaw, int $pricingIndex = 0): array
    {
        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $legById = collect($grouped['legDescs'] ?? [])->keyBy('id');

        $group = $grouped['itineraryGroups'][0] ?? [];
        $legDescriptions = $group['groupDescription']['legDescriptions'] ?? [];

        $bookingCodes = $this->extractBookingCodes($itineraryRaw, $pricingIndex);
        $bookingCodeIndex = 0;

        $originDestinations = [];

        foreach ($itineraryRaw['legs'] ?? [] as $legIndex => $legRef) {
            $leg = $legById->get($legRef['ref'] ?? null);
            if (!$leg) {
                continue;
            }

            $legDate = $legDescriptions[$legIndex]['departureDate'] ?? null;
            $flights = [];
            $origin = null;
            $destination = null;
            $departureDateTime = null;

            foreach ($leg['schedules'] ?? [] as $scheduleRef) {
                $schedule = $scheduleById->get($scheduleRef['ref'] ?? null);
                if (!$schedule) {
                    continue;
                }

                $departureTime = $schedule['departure']['time'] ?? '00:00:00';
                $arrivalTime = $schedule['arrival']['time'] ?? '00:00:00';
                $departureDate = $legDate ? $legDate . 'T' . $departureTime : $departureTime;
                $arrivalDate = $legDate ? $legDate . 'T' . $arrivalTime : $arrivalTime;

                $departureDate = $this->normalizeSabreDateTime($departureDate);
                $arrivalDate = $this->normalizeSabreDateTime($arrivalDate);

                if (!$origin) {
                    $origin = $schedule['departure']['airport'] ?? '';
                    $departureDateTime = $departureDate;
                }

                $destination = $schedule['arrival']['airport'] ?? '';

                $flights[] = [
                    'ClassOfService' => $bookingCodes[$bookingCodeIndex] ?? 'Y',
                    'Number' => (int) ($schedule['carrier']['marketingFlightNumber'] ?? 0) ?: (string) ($schedule['carrier']['marketingFlightNumber'] ?? ''),
                    'DepartureDateTime' => $departureDate,
                    'ArrivalDateTime' => $arrivalDate,
                    'Type' => 'A',
                    'OriginLocation' => [
                        'LocationCode' => $schedule['departure']['airport'] ?? '',
                    ],
                    'DestinationLocation' => [
                        'LocationCode' => $schedule['arrival']['airport'] ?? '',
                    ],
                    'Airline' => [
                        'Operating' => $schedule['carrier']['operating'] ?? ($schedule['carrier']['marketing'] ?? ''),
                        'Marketing' => $schedule['carrier']['marketing'] ?? '',
                    ],
                ];

                $bookingCodeIndex++;
            }

            if (!$origin || !$destination || empty($flights)) {
                continue;
            }

            $originDestinations[] = [
                'DepartureDateTime' => $departureDateTime,
                'DestinationLocation' => [
                    'LocationCode' => $destination,
                    'LocationType' => 'A',
                ],
                'OriginLocation' => [
                    'LocationCode' => $origin,
                    'LocationType' => 'A',
                ],
                'TPA_Extensions' => [
                    'SegmentType' => [
                        'Code' => 'O',
                    ],
                    'Flight' => $flights,
                ],
            ];
        }

        return $originDestinations;
    }

    private function normalizeSabreDateTime(string $value): string
    {
        $normalized = preg_replace('/([+-][0-9]{2}:[0-9]{2}|Z)$/', '', $value);
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/', $normalized)) {
            return $normalized . ':00';
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSabreAirPriceRequestInformation(
        B2bFlightBooking $booking,
        bool $retain,
        bool $includePassengerTypes = true,
        bool $includeFlightQualifiers = false,
        bool $includeCommandPricing = false,
        ?array $itineraryRaw = null,
        ?array $grouped = null,
    ): array {
        $request = ['Retain' => $retain];
        $optionalQualifiers = [];

        if ($includeFlightQualifiers) {
            $validatingCarrier = $this->resolveSabreValidatingCarrier($booking);
            if ($validatingCarrier !== null) {
                $optionalQualifiers['FlightQualifiers'] = [
                    'VendorPrefs' => [
                        'Airline' => ['Code' => $validatingCarrier],
                    ],
                ];
            }
        }

        $pricingQualifiers = [];

        if ($includePassengerTypes) {
            foreach ($this->buildPassengerTypes($booking) as $type) {
                $pricingQualifiers['PassengerType'][] = [
                    'Code' => $type['Code'],
                    'Quantity' => $type['Quantity'],
                ];
            }
        }

        if ($includeCommandPricing) {
            $fareBasis = $this->resolveSabreFareBasisCode($booking, $itineraryRaw, $grouped);
            if ($fareBasis !== null) {
                $pricingQualifiers['CommandPricing'] = [
                    ['FareBasis' => ['Code' => $fareBasis]],
                ];
            }
        }

        if ($pricingQualifiers !== []) {
            $optionalQualifiers['PricingQualifiers'] = $pricingQualifiers;
        }

        if ($optionalQualifiers !== []) {
            $request['OptionalQualifiers'] = $optionalQualifiers;
        }

        return $request;
    }

    private function resolveSabreFareBasisCode(B2bFlightBooking $booking, ?array $itineraryRaw = null, ?array $grouped = null): ?string
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $fareRules = is_array($itinerary['fare_rules'] ?? null) ? $itinerary['fare_rules'] : [];

        foreach ($fareRules['components'] ?? [] as $component) {
            if (! is_array($component)) {
                continue;
            }

            $code = strtoupper(trim((string) ($component['fare_basis'] ?? '')));
            if ($code !== '') {
                return $code;
            }
        }

        $summary = trim((string) ($itinerary['fare_basis'] ?? ''));
        if ($summary !== '') {
            $first = trim(explode('/', str_replace(' / ', '/', $summary))[0]);

            return $first !== '' ? strtoupper($first) : null;
        }

        if ($itineraryRaw !== null || $grouped !== null) {
            $pricingIndex = (int) data_get($itinerary, 'sabre_pricing_index', 0);
            $pricingBlock = data_get($itineraryRaw, "pricingInformation.{$pricingIndex}")
                ?? data_get($itineraryRaw, 'pricingInformation.0');
            $components = data_get($pricingBlock, 'fare.passengerInfoList.0.passengerInfo.fareComponents', []);
            $fareComponentById = collect($grouped['fareComponentDescs'] ?? [])->keyBy('id');

            foreach (is_array($components) ? $components : [] as $component) {
                if (! is_array($component)) {
                    continue;
                }

                $ref = (int) ($component['ref'] ?? 0);
                $desc = $fareComponentById->get($ref);
                $code = strtoupper(trim((string) (is_array($desc) ? ($desc['fareBasisCode'] ?? '') : '')));
                if ($code !== '') {
                    return $code;
                }
            }
        }

        return null;
    }

    private function resolveSabreValidatingCarrier(B2bFlightBooking $booking): ?string
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $carrier = strtoupper(trim((string) ($itinerary['validating_carrier'] ?? '')));
        if (strlen($carrier) === 2) {
            return $carrier;
        }

        foreach ($itinerary['legs'] ?? [] as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $marketing = strtoupper(trim((string) ($segment['carrier'] ?? '')));
                if (strlen($marketing) === 2) {
                    return $marketing;
                }
            }
        }

        return null;
    }

    private function buildPassengerTypes(B2bFlightBooking $booking): array
    {
        $types = [
            ['Code' => 'ADT', 'Quantity' => (string) max(1, (int) $booking->adults)],
        ];

        if ($booking->children > 0) {
            $types[] = ['Code' => 'C06', 'Quantity' => (string) $booking->children];
        }

        if ($booking->infants > 0) {
            $types[] = ['Code' => 'INF', 'Quantity' => (string) $booking->infants];
        }

        return $types;
    }

    private function buildAirBookSegments(B2bFlightBooking $booking, array $grouped, array $itineraryRaw): array
    {
        $storedSegments = $this->buildAirBookSegmentsFromStoredItinerary($booking);
        if ($storedSegments !== []) {
            return $storedSegments;
        }

        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $legById = collect($grouped['legDescs'] ?? [])->keyBy('id');

        $group = $grouped['itineraryGroups'][0] ?? [];
        $legDescriptions = $group['groupDescription']['legDescriptions'] ?? [];

        $bookingCodes = $this->extractBookingCodes(
            $itineraryRaw,
            (int) data_get($booking->itinerary_data, 'sabre_pricing_index', 0),
        );
        $bookingCodeIndex = 0;

        $segments = [];
        $totalPassengers = max(1, (int) ($booking->adults + $booking->children + $booking->infants));

        foreach ($itineraryRaw['legs'] ?? [] as $legIndex => $legRef) {
            $leg = $legById->get($legRef['ref'] ?? null);
            if (!$leg) {
                continue;
            }

            $legDate = $legDescriptions[$legIndex]['departureDate'] ?? null;
            $dayAccumulator = 0;

            foreach ($leg['schedules'] ?? [] as $scheduleRef) {
                $dayAccumulator += (int) ($scheduleRef['departureDateAdjustment'] ?? 0);
                $schedule = $scheduleById->get($scheduleRef['ref'] ?? null);
                if (!$schedule) {
                    continue;
                }

                $departureTime = $schedule['departure']['time'] ?? '00:00:00';
                $departureDateTime = $this->composeSabreSegmentDateTime($legDate, $departureTime, $dayAccumulator);

                $segments[] = [
                    'DepartureDateTime' => $departureDateTime,
                    'FlightNumber' => (string) ($schedule['carrier']['marketingFlightNumber'] ?? ''),
                    'NumberInParty' => (string) $totalPassengers,
                    'ResBookDesigCode' => $bookingCodes[$bookingCodeIndex] ?? 'Y',
                    'Status' => 'NN',
                    'DestinationLocation' => [
                        'LocationCode' => $schedule['arrival']['airport'] ?? '',
                    ],
                    'MarketingAirline' => [
                        'Code' => $schedule['carrier']['marketing'] ?? '',
                        'FlightNumber' => (string) ($schedule['carrier']['marketingFlightNumber'] ?? ''),
                    ],
                    'OriginLocation' => [
                        'LocationCode' => $schedule['departure']['airport'] ?? '',
                    ],
                ];

                $bookingCodeIndex++;
            }
        }

        return $segments;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAirBookSegmentsFromStoredItinerary(B2bFlightBooking $booking): array
    {
        $itinerary = is_array($booking->itinerary_data) ? $booking->itinerary_data : [];
        $totalPassengers = max(1, (int) ($booking->adults + $booking->children + $booking->infants));
        $segments = [];

        foreach ($itinerary['legs'] ?? [] as $leg) {
            if (! is_array($leg)) {
                continue;
            }

            foreach ($leg['segments'] ?? [] as $segment) {
                if (! is_array($segment)) {
                    continue;
                }

                $departureDateTime = trim((string) ($segment['departure_datetime'] ?? ''));
                if ($departureDateTime === '') {
                    return [];
                }

                try {
                    $departureDateTime = $this->normalizeSabreDateTime(
                        \Carbon\Carbon::parse($departureDateTime)->format('Y-m-d\TH:i:s'),
                    );
                } catch (\Throwable) {
                    return [];
                }

                $carrier = strtoupper(trim((string) ($segment['carrier'] ?? '')));
                $flightNumber = trim((string) ($segment['flight_number'] ?? ''));
                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                $to = strtoupper(trim((string) ($segment['to'] ?? '')));
                $bookingCode = strtoupper(trim((string) ($segment['booking_code'] ?? 'Y')));

                if ($carrier === '' || $flightNumber === '' || $from === '' || $to === '') {
                    return [];
                }

                $segments[] = [
                    'DepartureDateTime' => $departureDateTime,
                    'FlightNumber' => $flightNumber,
                    'NumberInParty' => (string) $totalPassengers,
                    'ResBookDesigCode' => $bookingCode !== '' ? $bookingCode : 'Y',
                    'Status' => 'NN',
                    'DestinationLocation' => [
                        'LocationCode' => $to,
                    ],
                    'MarketingAirline' => [
                        'Code' => $carrier,
                        'FlightNumber' => $flightNumber,
                    ],
                    'OriginLocation' => [
                        'LocationCode' => $from,
                    ],
                ];
            }
        }

        return $segments;
    }

    private function composeSabreSegmentDateTime(?string $legDate, string $departureTime, int $dayAdjustment = 0): string
    {
        if ($legDate) {
            $date = \Carbon\Carbon::parse($legDate)->addDays($dayAdjustment)->format('Y-m-d');
            $departureDateTime = $date . 'T' . $departureTime;
        } else {
            $departureDateTime = $departureTime;
        }

        return $this->normalizeSabreDateTime($departureDateTime);
    }

    private function extractBookingCodes(array $itineraryRaw, int $pricingIndex = 0): array
    {
        $codes = [];
        $pricingInformation = $itineraryRaw['pricingInformation'] ?? [];
        $pricingBlock = is_array($pricingInformation[$pricingIndex] ?? null)
            ? $pricingInformation[$pricingIndex]
            : ($pricingInformation[0] ?? []);
        $fareComponents = $pricingBlock['fare']['passengerInfoList'][0]['passengerInfo']['fareComponents'] ?? [];

        foreach ($fareComponents as $component) {
            foreach (($component['segments'] ?? []) as $segment) {
                $code = $segment['segment']['bookingCode'] ?? null;
                if ($code) {
                    $codes[] = $code;
                }
            }
        }

        return $codes;
    }

    private function createSoapSession(?string $bearerToken = null): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $messageId = 'mid:' . now()->format('Ymd-Hi') . '-' . rand(1000, 9999) . '@andaleebtravels.com';

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext" xmlns:wsu="http://schemas.xmlsoap.org/ws/2002/12/utility">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From>
                <eb:PartyId>WebServiceClient</eb:PartyId>
            </eb:From>
            <eb:To>
                <eb:PartyId>Sabre</eb:PartyId>
            </eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>SessionCreate_V1_Test</eb:ConversationId>
            <eb:Service>SessionCreateRQ</eb:Service>
            <eb:Action>SessionCreateRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>{$messageId}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:UsernameToken>
                <wsse:Username>{$this->sabreSoapUsername}</wsse:Username>
                <wsse:Password>{$this->sabreSoapPassword}</wsse:Password>
                <Organization>{$this->sabreSoapOrganization}</Organization>
                <Domain>{$this->sabreSoapDomain}</Domain>
            </wsse:UsernameToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <sws:SessionCreateRQ xmlns:sws="http://webservices.sabre.com" Version="1.0.0">
            <POS>
                <Source PseudoCityCode="{$this->sabrePcc}"/>
            </POS>
        </sws:SessionCreateRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'SessionCreateRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        $body = $response->body();

        if (!$response->successful()) {
            Log::error('Sabre SessionCreate HTTP failure', [
                'status' => $response->status(),
                'body'   => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre session create failed: ' . $body);
        }

        $token = $this->extractBinarySecurityToken($body);

        if (!$token) {
            // 200 OK but no token  -  usually a SOAP Fault (bad credentials / PCC / IP not whitelisted)
            $fault = $this->extractSoapFault($body);
            Log::error('Sabre SessionCreate returned no token', [
                'fault' => $fault,
                'body'  => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre session create failed: ' . ($fault ?: 'no BinarySecurityToken in response'));
        }

        return [
            'success'  => true,
            'token'    => $token,
            'response' => $body,
        ];
    }

    private function getReservation(string $binaryToken, string $locator, ?string $bearerToken = null): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:v1="http://webservices.sabre.com/pnrbuilder/v1_19" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soapenv:Header>
        <eb:MessageHeader eb:version="1.0" soapenv:mustUnderstand="1">
            <eb:From><eb:PartyId>Postman</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>FlightCancel</eb:ConversationId>
            <eb:Service>getReservationRQ</eb:Service>
            <eb:Action>getReservationRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>get-{$locator}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security soapenv:mustUnderstand="1">
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soapenv:Header>
    <soapenv:Body>
        <v1:GetReservationRQ Version="1.19.22">
            <v1:Locator>{$locator}</v1:Locator>
            <v1:RequestType>Stateful</v1:RequestType>
            <v1:ReturnOptions UnmaskCreditCard="false">
                <v1:ViewName>Full</v1:ViewName>
                <v1:ResponseFormat>STL</v1:ResponseFormat>
            </v1:ReturnOptions>
        </v1:GetReservationRQ>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'getReservationRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://webservices.cert.platform.sabre.com');

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'GetReservation failed: ' . $response->body(),
            ];
        }

        if (str_contains($response->body(), 'PNR not found')) {
            return [
                'success' => false,
                'error' => 'PNR not found',
            ];
        }

        return [
            'success' => true,
            'response' => $response->body(),
        ];
    }

    private function cancelItinerary(string $binaryToken, ?string $bearerToken = null): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>Cancel_Itinerary_Test</eb:ConversationId>
            <eb:Service>OTA_CancelLLSRQ</eb:Service>
            <eb:Action>OTA_CancelLLSRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>cancel-{$timestamp}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <OTA_CancelRQ xmlns="http://webservices.sabre.com/sabreXML/2011/10" Version="2.0.3">
            <Segment Type="entire"/>
        </OTA_CancelRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'OTA_CancelLLSRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'Cancel failed: ' . $response->body(),
            ];
        }

        return [
            'success' => true,
            'response' => $response->body(),
        ];
    }

    private function endTransaction(string $binaryToken, ?string $bearerToken = null): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>End_Transaction_Final_V2.2.0</eb:ConversationId>
            <eb:Service>EndTransactionLLSRQ</eb:Service>
            <eb:Action>EndTransactionLLSRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>end-{$timestamp}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <EndTransactionRQ xmlns="http://webservices.sabre.com/sabreXML/2011/10" Version="2.2.0">
            <EndTransaction Ind="true"/>
            <Source ReceivedFrom="SP_POSTMAN_V2"/>
        </EndTransactionRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'EndTransactionLLSRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'EndTransaction failed: ' . $response->body(),
            ];
        }

        return [
            'success' => true,
            'response' => $response->body(),
        ];
    }

    /**
     * @param  list<array{
     *     route_label: string,
     *     fare_basis: string,
     *     fare_rule: ?string,
     *     airline: string,
     *     origin: string,
     *     destination: string,
     *     departure_date: string
     * }>  $ruleRequests
     * @return list<array{
     *     route: string,
     *     fare_basis: string,
     *     sections: list<array{title: string, paragraphs: list<string>}>,
     *     text: string
     * }>
     */
    public function fetchFareRulesText(array $ruleRequests, ?array $structuredFallback = null): array
    {
        if ($ruleRequests === []) {
            return [];
        }

        $bearerToken = $this->getSabreToken();
        $session = $this->createSoapSession($bearerToken);
        $binaryToken = $session['token'];
        $components = [];

        try {
            foreach ($ruleRequests as $request) {
                if (trim((string) ($request['airline'] ?? '')) === '') {
                    throw new \InvalidArgumentException('Validating carrier is required to load fare rules.');
                }

                try {
                    $sections = $this->resolveAirRulesSections($binaryToken, $request, $bearerToken);
                } catch (\Throwable $e) {
                    $sections = $this->structuredFareRulesSectionsForRequest($structuredFallback, $request);

                    if ($sections === []) {
                        throw $e;
                    }

                    Log::warning('Sabre OTA_AirRulesLLS unavailable; using structured shop fallback', [
                        'route' => $request['route_label'] ?? null,
                        'fare_basis' => $request['fare_basis'] ?? null,
                        'airline' => $request['airline'] ?? null,
                        'message' => $e->getMessage(),
                    ]);
                }

                $components[] = [
                    'route' => (string) ($request['route_label'] ?? ''),
                    'fare_basis' => (string) ($request['fare_basis'] ?? ''),
                    'sections' => $sections,
                    'text' => $this->flattenFareRuleSections($sections),
                ];
            }
        } finally {
            $this->closeSession($binaryToken, $bearerToken);
        }

        return $components;
    }

    /**
     * @param  array<string, mixed>|null  $structuredFallback
     * @param  array<string, mixed>  $request
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    private function structuredFareRulesSectionsForRequest(?array $structuredFallback, array $request): array
    {
        $fallbackComponents = SabreStructuredFareRulesFallback::toComponents($structuredFallback);
        if ($fallbackComponents === []) {
            return [];
        }

        $route = strtoupper(trim((string) ($request['route_label'] ?? '')));
        $basis = strtoupper(trim((string) ($request['fare_basis'] ?? '')));

        foreach ($fallbackComponents as $component) {
            $componentRoute = strtoupper(trim((string) ($component['route'] ?? '')));
            $componentBasis = strtoupper(trim((string) ($component['fare_basis'] ?? '')));

            if ($route !== '' && $componentRoute !== '' && $route !== $componentRoute) {
                continue;
            }

            if ($basis !== '' && $componentBasis !== '' && $basis !== $componentBasis) {
                continue;
            }

            $sections = is_array($component['sections'] ?? null) ? $component['sections'] : [];

            return $sections !== [] ? $sections : [];
        }

        $first = $fallbackComponents[0] ?? null;

        return is_array($first) && is_array($first['sections'] ?? null) ? $first['sections'] : [];
    }

    /**
     * @param  array{
     *     route_label: string,
     *     fare_basis: string,
     *     fare_rule: ?string,
     *     airline: string,
     *     origin: string,
     *     destination: string,
     *     departure_date: string
     * }  $request
     *
     * @return list<array{title: string, paragraphs: list<string>}>
     */
    private function resolveAirRulesSections(string $binaryToken, array $request, ?string $bearerToken = null): array
    {
        $responseBody = $this->callAirRulesLLS($binaryToken, $request, $bearerToken);

        if (SabreAirRulesResponseParser::needsRoutingSelection($responseBody)) {
            $rph = SabreAirRulesResponseParser::resolveRoutingRph(
                $responseBody,
                $request['fare_rule'] ?? null,
            );
            $responseBody = $this->callAirRulesSelectRouting($binaryToken, $rph, $bearerToken);
        }

        return SabreAirRulesResponseParser::parse($responseBody);
    }

    /**
     * @param  array{
     *     route_label: string,
     *     fare_basis: string,
     *     fare_rule: ?string,
     *     airline: string,
     *     origin: string,
     *     destination: string,
     *     departure_date: string
     * }  $request
     */
    private function callAirRulesLLS(string $binaryToken, array $request, ?string $bearerToken = null): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $messageId = 'mid:airrules-' . now()->format('Ymd-His') . '-' . rand(1000, 9999);
        $fareBasis = htmlspecialchars((string) $request['fare_basis'], ENT_XML1);
        $origin = htmlspecialchars((string) $request['origin'], ENT_XML1);
        $destination = htmlspecialchars((string) $request['destination'], ENT_XML1);
        $airline = htmlspecialchars((string) $request['airline'], ENT_XML1);
        $departureMmDd = htmlspecialchars($this->formatAirRulesDepartureDate((string) $request['departure_date']), ENT_XML1);

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>OTA_AirRulesLLS</eb:ConversationId>
            <eb:Service>OTA_AirRulesLLSRQ</eb:Service>
            <eb:Action>OTA_AirRulesLLSRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>{$messageId}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <OTA_AirRulesRQ ReturnHostCommand="true" Version="2.3.0" xmlns="http://webservices.sabre.com/sabreXML/2011/10">
            <OriginDestinationInformation>
                <FlightSegment DepartureDateTime="{$departureMmDd}">
                    <DestinationLocation LocationCode="{$destination}"/>
                    <MarketingCarrier Code="{$airline}"/>
                    <OriginLocation LocationCode="{$origin}"/>
                </FlightSegment>
            </OriginDestinationInformation>
            <RuleReqInfo>
                <FareBasis Code="{$fareBasis}"/>
            </RuleReqInfo>
        </OTA_AirRulesRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'OTA_AirRulesLLSRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        $body = $response->body();

        if (! $response->successful()) {
            Log::error('Sabre OTA_AirRulesLLS HTTP failure', [
                'status' => $response->status(),
                'route' => $request['route_label'] ?? null,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre fare rules request failed.');
        }

        $applicationError = $this->extractAirRulesApplicationError($body);
        if ($applicationError !== null) {
            Log::error('Sabre OTA_AirRulesLLS application error', [
                'error' => $applicationError,
                'route' => $request['route_label'] ?? null,
                'fare_basis' => $request['fare_basis'] ?? null,
                'airline' => $request['airline'] ?? null,
                'departure_date' => $request['departure_date'] ?? null,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \RuntimeException('Sabre fare rules request failed: ' . $applicationError);
        }

        $fault = $this->extractSoapFault($body);
        if ($fault !== null) {
            Log::error('Sabre OTA_AirRulesLLS SOAP fault', [
                'fault' => $fault,
                'route' => $request['route_label'] ?? null,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre fare rules request failed: ' . $fault);
        }

        return $body;
    }

    private function callAirRulesSelectRouting(string $binaryToken, string $rph, ?string $bearerToken = null): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $messageId = 'mid:airrules-rph-' . now()->format('Ymd-His') . '-' . rand(1000, 9999);
        $rph = htmlspecialchars(trim($rph) !== '' ? trim($rph) : '1', ENT_XML1);

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>OTA_AirRulesLLS</eb:ConversationId>
            <eb:Service>OTA_AirRulesLLSRQ</eb:Service>
            <eb:Action>OTA_AirRulesLLSRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>{$messageId}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <OTA_AirRulesRQ ReturnHostCommand="true" Version="2.3.0" xmlns="http://webservices.sabre.com/sabreXML/2011/10">
            <RuleReqInfo RPH="{$rph}"/>
        </OTA_AirRulesRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'OTA_AirRulesLLSRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        $body = $response->body();

        if (! $response->successful()) {
            Log::error('Sabre OTA_AirRulesLLS routing-select HTTP failure', [
                'status' => $response->status(),
                'rph' => $rph,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre fare rules routing selection failed.');
        }

        $applicationError = $this->extractAirRulesApplicationError($body);
        if ($applicationError !== null) {
            Log::error('Sabre OTA_AirRulesLLS routing-select application error', [
                'error' => $applicationError,
                'rph' => $rph,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre fare rules routing selection failed: ' . $applicationError);
        }

        $fault = $this->extractSoapFault($body);
        if ($fault !== null) {
            Log::error('Sabre OTA_AirRulesLLS routing-select SOAP fault', [
                'fault' => $fault,
                'rph' => $rph,
                'body' => mb_substr($body, 0, 4000),
            ]);
            throw new \Exception('Sabre fare rules routing selection failed: ' . $fault);
        }

        return $body;
    }

    private function formatAirRulesDepartureDate(string $departureDate): string
    {
        try {
            return \Carbon\Carbon::parse($departureDate)->format('m-d');
        } catch (\Throwable) {
            return '01-01';
        }
    }

    private function extractAirRulesApplicationError(string $xml): ?string
    {
        if (preg_match('/<(?:\w+:)?ApplicationResults\b[^>]*\bstatus="([^"]+)"/i', $xml, $statusMatch)) {
            $status = strtolower(trim($statusMatch[1]));
            if ($status !== '' && $status !== 'complete') {
                return 'Application status: ' . $statusMatch[1];
            }
        }

        if (! preg_match('/<(?:\w+:)?Error\b/i', $xml)) {
            return null;
        }

        if (preg_match('/<(?:\w+:)?Error\b[^>]*>(.*?)<\/(?:\w+:)?Error>/is', $xml, $errorBlock)) {
            if (preg_match('/<(?:\w+:)?Message[^>]*>([^<]+)<\/(?:\w+:)?Message>/i', $errorBlock[1], $messageMatch)) {
                return trim(html_entity_decode($messageMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        if (preg_match('/<(?:\w+:)?ShortText[^>]*>([^<]+)<\/(?:\w+:)?ShortText>/i', $xml, $shortTextMatch)) {
            return trim(html_entity_decode($shortTextMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return 'Sabre returned an application error for fare rules.';
    }

    /**
     * @param  list<array{title: string, paragraphs: list<string>}>  $sections
     */
    private function flattenFareRuleSections(array $sections): string
    {
        $chunks = [];

        foreach ($sections as $section) {
            $title = trim((string) ($section['title'] ?? ''));
            $paragraphs = $section['paragraphs'] ?? [];

            if ($title !== '') {
                $chunks[] = $title;
            }

            foreach ($paragraphs as $paragraph) {
                $paragraph = trim((string) $paragraph);
                if ($paragraph !== '') {
                    $chunks[] = $paragraph;
                }
            }
        }

        return implode("\n\n", $chunks);
    }

    private function closeSession(string $binaryToken, ?string $bearerToken = null): array
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $xml = <<<XML
<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" xmlns:eb="http://www.ebxml.org/namespaces/messageHeader" xmlns:wsse="http://schemas.xmlsoap.org/ws/2002/12/secext">
    <soap-env:Header>
        <eb:MessageHeader soap-env:mustUnderstand="1" eb:version="1.0.0">
            <eb:From><eb:PartyId>WebServiceClient</eb:PartyId></eb:From>
            <eb:To><eb:PartyId>Sabre</eb:PartyId></eb:To>
            <eb:CPAId>{$this->sabrePcc}</eb:CPAId>
            <eb:ConversationId>End_Session_001</eb:ConversationId>
            <eb:Service>SessionCloseRQ</eb:Service>
            <eb:Action>SessionCloseRQ</eb:Action>
            <eb:MessageData>
                <eb:MessageId>close-{$timestamp}</eb:MessageId>
                <eb:Timestamp>{$timestamp}</eb:Timestamp>
            </eb:MessageData>
        </eb:MessageHeader>
        <wsse:Security>
            <wsse:BinarySecurityToken valueType="String" EncodingType="wsse:Base64Binary">{$binaryToken}</wsse:BinarySecurityToken>
        </wsse:Security>
    </soap-env:Header>
    <soap-env:Body>
        <SessionCloseRQ xmlns="http://www.opentravel.org/OTA/2002/11" version="1.0.0">
            <POS>
                <Source PseudoCityCode="{$this->sabrePcc}"/>
            </POS>
        </SessionCloseRQ>
    </soap-env:Body>
</soap-env:Envelope>
XML;

        $headers = [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => 'SessionCloseRQ',
        ];

        if ($bearerToken) {
            $headers['Authorization'] = 'Bearer ' . $bearerToken;
        }

        $response = $this->sabreHttp()->withHeaders($headers)
            ->withBody($xml, 'text/xml')
            ->post('https://sws-crt.cert.sabre.com');

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'SessionClose failed: ' . $response->body(),
            ];
        }

        return [
            'success' => true,
            'response' => $response->body(),
        ];
    }

    private function extractBinarySecurityToken(string $xml): ?string
    {
        // Regex first  -  works regardless of namespace prefixes (soap-env / soapenv / S:)
        // or wsse URI variants. Matches: <prefix:BinarySecurityToken ...>VALUE</prefix:BinarySecurityToken>
        if (preg_match('/<[^>:]*:?BinarySecurityToken[^>]*>([^<]+)<\/[^>:]*:?BinarySecurityToken>/i', $xml, $m)) {
            $token = trim($m[1]);
            if ($token !== '') {
                return $token;
            }
        }

        // SimpleXML fallback (handles edge cases where token has CDATA / entities)
        try {
            $xmlObject = @simplexml_load_string($xml);
            if (!$xmlObject) {
                return null;
            }

            $namespaces = $xmlObject->getNamespaces(true);
            $soapNs = $namespaces['soap-env'] ?? $namespaces['soapenv'] ?? $namespaces['S'] ?? 'http://schemas.xmlsoap.org/soap/envelope/';
            $wsseNs = $namespaces['wsse']    ?? 'http://schemas.xmlsoap.org/ws/2002/12/secext';

            $security = $xmlObject->children($soapNs)->Header->children($wsseNs);
            $token    = (string) ($security->BinarySecurityToken ?? '');
            return $token !== '' ? $token : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractSoapFault(string $xml): ?string
    {
        if (! preg_match('/<(?:\w+:)?Fault\b/i', $xml)) {
            return null;
        }

        if (preg_match('/<(?:\w+:)?faultstring[^>]*>([^<]+)<\/(?:\w+:)?faultstring>/i', $xml, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<(?:\w+:)?Fault\b[^>]*>(.*?)<\/(?:\w+:)?Fault>/is', $xml, $faultBlock)) {
            if (preg_match('/<(?:\w+:)?faultstring[^>]*>([^<]+)<\/(?:\w+:)?faultstring>/i', $faultBlock[1], $m)) {
                return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        return 'SOAP fault returned by Sabre.';
    }
}
