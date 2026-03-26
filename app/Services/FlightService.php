<?php

namespace App\Services;

use App\Models\B2bFlightBooking;
use App\Models\Config;
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

    public function createBookingRecord(array $data): B2bFlightBooking
    {
        return B2bFlightBooking::create([
            'b2b_vendor_id' => auth()->id(),
            'booking_number' => B2bFlightBooking::generateBookingNumber(),
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
            $itineraryRaw = $this->findRawItinerary($searchResponse, (int) $booking->itinerary_id);

            if (!$itineraryRaw) {
                throw new \Exception('Unable to locate itinerary for booking.');
            }

            $payload = $this->buildPnrPayload($booking, $searchResponse, $itineraryRaw);

            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post('https://api.cert.platform.sabre.com/v2.5.0/passenger/records?mode=create', $payload);

            if (!$response->successful()) {
                throw new \Exception('Sabre PNR creation failed: ' . $response->body());
            }

            $responseData = $response->json();
            $locator = data_get($responseData, 'CreatePassengerNameRecordRS.ItineraryRef.ID')
                ?? data_get($responseData, 'CreatePassengerNameRecordRS.ItineraryRef[0].ID')
                ?? data_get($responseData, 'TravelItineraryReadRS.ItineraryRef.ID')
                ?? data_get($responseData, 'ItineraryRef.ID');

            $booking->update([
                'booking_request' => $payload,
                'booking_response' => $responseData,
            ]);

            if ($locator) {
                $booking->update([
                    'sabre_record_locator' => $locator,
                    'booking_status' => 'confirmed',
                ]);
            }

            return [
                'success' => (bool) $locator,
                'data' => $responseData,
                'locator' => $locator,
            ];
        } catch (\Exception $e) {
            Log::error('Sabre PNR creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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

            $payload = [
                'AirTicketRQ' => [
                    'version' => '1.3.0',
                    'DesignatePrinter' => [
                        'Printers' => [
                            'Hardcopy' => ['LNIATA' => 'FA8CFB'],
                            'Ticket' => ['CountryCode' => 'TG'],
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
                                'PriceQuote' => [
                                    [
                                        'Record' => [
                                            ['Number' => 1],
                                        ],
                                    ],
                                ],
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

            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/json'])
                ->post('https://api.cert.platform.sabre.com/v1.3.0/air/ticket', $payload);

            if (!$response->successful()) {
                throw new \Exception('Sabre ticketing failed: ' . $response->body());
            }

            $responseData = $response->json();

            $booking->update([
                'ticket_request' => $payload,
                'ticket_response' => $responseData,
                'ticket_status' => 'issued',
            ]);

            return [
                'success' => true,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
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

    public function cancelSabreBooking(B2bFlightBooking $booking): array
    {
        $locator = $booking->sabre_record_locator;
        if (empty($locator)) {
            throw new \Exception('Missing Sabre record locator.');
        }

        $session = $this->createSoapSession();
        $binaryToken = $session['token'] ?? null;

        if (!$binaryToken) {
            throw new \Exception('Unable to establish Sabre session.');
        }

        $reservation = $this->getReservation($binaryToken, $locator);
        if (!($reservation['success'] ?? false)) {
            throw new \Exception($reservation['error'] ?? 'Unable to retrieve reservation.');
        }

        $cancel = $this->cancelItinerary($binaryToken);
        $end = $this->endTransaction($binaryToken);
        $close = $this->closeSession($binaryToken);

        return [
            'success' => (bool) ($cancel['success'] ?? false),
            'reservation' => $reservation,
            'cancel' => $cancel,
            'end' => $end,
            'close' => $close,
        ];
    }

    private function getSabreToken(): string
    {
        if (empty($this->sabreBasicAuth)) {
            throw new \Exception('Sabre credentials are not configured.');
        }

        $response = Http::asForm()->withHeaders([
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

    private function findRawItinerary(array $grouped, int $itineraryId): ?array
    {
        $groups = $grouped['itineraryGroups'] ?? [];
        $firstGroup = $groups[0] ?? [];
        $itineraries = $firstGroup['itineraries'] ?? [];

        foreach ($itineraries as $itinerary) {
            if ((int) ($itinerary['id'] ?? 0) === $itineraryId) {
                return $itinerary;
            }
        }

        return null;
    }

    private function buildPnrPayload(B2bFlightBooking $booking, array $grouped, array $itineraryRaw): array
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

        return [
            'CreatePassengerNameRecordRQ' => [
                'version' => '2.5.0',
                'targetCity' => $this->sabrePcc,
                'TravelItineraryAddInfo' => [
                    'AgencyInfo' => [
                        'Address' => [
                            'AddressLine' => 'SABRE TRAVEL',
                            'CityName' => 'SOUTHLAKE',
                            'CountryCode' => 'US',
                            'PostalCode' => '76092',
                            'StateCountyProv' => [
                                'StateCode' => 'TX',
                            ],
                            'StreetNmbr' => '3150 SABRE DRIVE',
                            'VendorPrefs' => [
                                'Airline' => [
                                    'Hosted' => true,
                                ],
                            ],
                        ],
                        'Ticketing' => [
                            'TicketType' => '7TAW',
                        ],
                    ],
                    'CustomerInfo' => [
                        'ContactNumbers' => [
                            'ContactNumber' => [
                                [
                                    'NameNumber' => '1.1',
                                    'Phone' => $lead['phone'] ?? '',
                                    'PhoneUseType' => 'H',
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
                'AirPrice' => [
                    [
                        'PriceRequestInformation' => [
                            'Retain' => true,
                            'OptionalQualifiers' => [
                                'FOP_Qualifiers' => [
                                    'BasicFOP' => [
                                        'Type' => 'CA',
                                    ],
                                ],
                                'PricingQualifiers' => [
                                    'PassengerType' => $this->buildPassengerTypes($booking),
                                ],
                                'MiscQualifiers' => [
                                    'Commission' => [
                                        'Percent' => '0',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'PostProcessing' => [
                    'EndTransaction' => [
                        'Source' => [
                            'ReceivedFrom' => 'SP TEST',
                        ],
                    ],
                ],
            ],
        ];
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
        $scheduleById = collect($grouped['scheduleDescs'] ?? [])->keyBy('id');
        $legById = collect($grouped['legDescs'] ?? [])->keyBy('id');

        $group = $grouped['itineraryGroups'][0] ?? [];
        $legDescriptions = $group['groupDescription']['legDescriptions'] ?? [];

        $bookingCodes = $this->extractBookingCodes($itineraryRaw);
        $bookingCodeIndex = 0;

        $segments = [];
        $totalPassengers = max(1, (int) ($booking->adults + $booking->children + $booking->infants));

        foreach ($itineraryRaw['legs'] ?? [] as $legIndex => $legRef) {
            $leg = $legById->get($legRef['ref'] ?? null);
            if (!$leg) {
                continue;
            }

            $legDate = $legDescriptions[$legIndex]['departureDate'] ?? null;

            foreach ($leg['schedules'] ?? [] as $scheduleRef) {
                $schedule = $scheduleById->get($scheduleRef['ref'] ?? null);
                if (!$schedule) {
                    continue;
                }

                $departureTime = $schedule['departure']['time'] ?? '00:00:00';
                $departureDateTime = $legDate ? $legDate . 'T' . $departureTime : $departureTime;

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

    private function extractBookingCodes(array $itineraryRaw): array
    {
        $codes = [];
        $fareComponents = $itineraryRaw['pricingInformation'][0]['fare']['passengerInfoList'][0]['passengerInfo']['fareComponents'] ?? [];

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

    private function createSoapSession(): array
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

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->post('https://sws-crt.cert.sabre.com', $xml);

        if (!$response->successful()) {
            throw new \Exception('Sabre session create failed: ' . $response->body());
        }

        $token = $this->extractBinarySecurityToken($response->body());

        return [
            'success' => (bool) $token,
            'token' => $token,
            'response' => $response->body(),
        ];
    }

    private function getReservation(string $binaryToken, string $locator): array
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

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->post('https://webservices.cert.platform.sabre.com', $xml);

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

    private function cancelItinerary(string $binaryToken): array
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

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->post('https://sws-crt.cert.sabre.com', $xml);

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

    private function endTransaction(string $binaryToken): array
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

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->post('https://sws-crt.cert.sabre.com', $xml);

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

    private function closeSession(string $binaryToken): array
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

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
        ])->post('https://sws-crt.cert.sabre.com', $xml);

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
        try {
            $xmlObject = simplexml_load_string($xml);
            if (!$xmlObject) {
                return null;
            }

            $namespaces = $xmlObject->getNamespaces(true);
            $security = $xmlObject->children($namespaces['soap-env'] ?? 'http://schemas.xmlsoap.org/soap/envelope/')
                ->Header
                ->children($namespaces['wsse'] ?? 'http://schemas.xmlsoap.org/ws/2002/12/secext');

            $token = (string) ($security->BinarySecurityToken ?? '');
            return $token !== '' ? $token : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
