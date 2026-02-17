<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrioTicketService
{
    private $baseUrl = 'https://distributor-api.prioticket.com/v3.5/distributor';
    private $authToken = 'YW5kYWxlZWIyMDIzMDFAcHJpb2FwaXMuY29tOkBBbmQwVHJhdjMkTEAhMiM=';
    private $distributorId = '49670';

    public function getAccessToken()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->authToken
            ])->post($this->baseUrl . '/oauth2/token');

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('PrioTicket: Failed to get access token', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PrioTicket: Exception getting access token', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function fetchProduct(string $productId, string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($this->baseUrl . "/products/{$productId}");

            if (! $response->successful()) {
                Log::error('PrioTicket: Product fetch failed', [
                    'product_id' => $productId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Product not found or unavailable'
                ];
            }

            return [
                'success' => true,
                'data' => $response->json('data')
            ];
        } catch (\Exception $e) {
            Log::error('PrioTicket: Exception fetching product', [
                'product_id' => $productId,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function validateProductTypes(array $cartProductTypes, array $liveProductTypes): bool
    {
        foreach ($cartProductTypes as $cartType) {
            $exists = collect($liveProductTypes)->contains(
                fn($liveType) => $liveType['id'] === $cartType['id']
            );

            if (! $exists) {
                return false;
            }
        }

        return true;
    }

    public function createReservation($orderData, $accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ])->post($this->baseUrl . '/reservations', $orderData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['error_description'] ?? $errorBody['error_message'] ?? 'Failed to create reservation';
            
            // If there are specific errors array, use those
            if (!empty($errorBody['errors']) && is_array($errorBody['errors'])) {
                $errorMessage = implode(' ', $errorBody['errors']);
            }

            Log::error('PrioTicket: Failed to create reservation', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_details' => $errorBody
            ];
        } catch (\Exception $e) {
            Log::error('PrioTicket: Exception creating reservation', [
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function buildReservationPayload($order, $orderItems, $passengerData)
    {
        $reservationDetails = [];

        // Get country name from country ID
        $country = Country::find($passengerData['country_code']);
        $countryName = $country ? $country->name : 'United Arab Emirates';
        $countryCode = $country ? $country->iso_code : 'AE';

        // Use prio_booking_reference if available, otherwise use order_number
        $baseReference = $order->prio_booking_reference ?? $order->order_number;

        foreach ($orderItems as $index => $item) {
            // Generate unique booking_external_reference for each item
            $bookingReference = $baseReference . '-' . ($index + 1);

            $reservationDetails[] = [
                'booking_external_reference' => $bookingReference,
                'booking_language' => 'en',
                'product_availability_id' => $item['availability_id'],
                'product_id' => (string) $item['product_id_prio'],
                'product_type_details' => $item['product_type_details'],
                'booking_reservation_reference' => $bookingReference
            ];
        }

        return [
            'data' => [
                'reservation' => [
                    'reservation_distributor_id' => $this->distributorId,
                    'reservation_external_reference' => $baseReference,
                    'reservation_details' => $reservationDetails,
                    'reservation_contacts' => [
                        [
                            'contact_uid' => '',
                            'contact_number' => $passengerData['phone'],
                            'contact_name_first' => $passengerData['first_name'],
                            'contact_name_last' => $passengerData['last_name'],
                            'contact_email' => $passengerData['email'],
                            'contact_phone' => $passengerData['phone'],
                            'contact_mobile' => $passengerData['phone'],
                            'contact_address' => [
                                'name' => $passengerData['address'] ?? '',
                                'city' => $passengerData['city'] ?? 'N/A',
                                'region' => $passengerData['region'] ?? 'N/A',
                                'postal_code' => $passengerData['postal_code'] ?? 'N/A',
                                'country' => $countryName,
                                'country_code' => $countryCode
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Confirm PrioTicket orders after payment is successful
     */
    public function confirmOrder($order)
    {
        $hasFailures = false;
        $errorDetails = [];

        try {
            $token = $this->getAccessToken();

            if (!$token) {
                $error = 'Failed to get PrioTicket access token';
                Log::error($error, ['order_id' => $order->id]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            if (empty($order->reservation_data)) {
                $error = 'No reservation data found for order';
                Log::warning($error, ['order_id' => $order->id]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            $reservationData = json_decode($order->reservation_data, true);

            if (!isset($reservationData['data']['reservation'])) {
                $error = 'Invalid reservation data structure';
                Log::warning($error, ['order_id' => $order->id]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            $reservation = $reservationData['data']['reservation'];
            $reservationDetails = $reservation['reservation_details'] ?? [];

            $orderItemsGrouped = $order->orderItems->groupBy('product_id_prio');

            $allOrderResponses = [];
            $successCount = 0;
            $totalProducts = $orderItemsGrouped->count();

            foreach ($orderItemsGrouped as $productId => $items) {
                $firstItem = $items->first();
                $reservationDetail = collect($reservationDetails)->firstWhere('product_id', $productId);

                if (!$reservationDetail) {
                    $hasFailures = true;
                    $errorDetails[] = "No reservation detail found for product ID: {$productId}";
                    Log::warning('No reservation detail found for product', [
                        'order_id' => $order->id,
                        'product_id' => $productId
                    ]);
                    continue;
                }

                $reservationReference = $reservationDetail['booking_reservation_reference'] ?? null;
                $externalReference = $reservation['reservation_external_reference'] ?? null;

                if (!$reservationReference) {
                    $hasFailures = true;
                    $errorDetails[] = "Missing reservation reference for product ID: {$productId}";
                    Log::warning('Missing reservation reference for product', [
                        'order_id' => $order->id,
                        'product_id' => $productId
                    ]);
                    continue;
                }

                $country = Country::where('name', $order->passenger_country)
                    ->orWhere('iso_code', $order->passenger_country)
                    ->first();

                $countryName = $country->name ?? $order->passenger_country;
                $countryCode = $country->iso_code ?? 'AE';
                $orderData = [
                    'data' => [
                        'order' => [
                            'order_distributor_id' => $this->distributorId,
                            'order_external_reference' => $externalReference,
                            'order_settlement_type' => 'EXTERNAL',
                            'order_language' => 'en',
                            'order_contacts' => [
                                [
                                    'contact_uid' => '',
                                    'contact_number' => $order->passenger_phone,
                                    'contact_name_first' => $order->passenger_first_name,
                                    'contact_name_last' => $order->passenger_last_name,
                                    'contact_email' => $order->passenger_email,
                                    'contact_phone' => $order->passenger_phone,
                                    'contact_mobile' => $order->passenger_phone,
                                    'contact_address' => [
                                        'name' => $order->passenger_address ?? '',
                                        'city' => $countryName,
                                        'postal_code' => '00000',
                                        'region' => $countryName,
                                        'country' => $countryName,
                                        'country_code' => $countryCode
                                    ]
                                ]
                            ],
                            'order_options' => [
                                'email_options' => [
                                    'email_types' => [
                                        'send_tickets' => true,
                                        'send_receipt' => true,
                                        'send_marketing' => true,
                                        'send_offers' => true,
                                        'send_notification' => true
                                    ]
                                ],
                                'price_on_voucher' => true
                            ],
                            'order_activity_url' => url('/admin/orders/' . $order->id),
                            'order_view_type' => 'DISTRIBUTOR',
                            'order_bookings' => [
                                [
                                    'booking_option_type' => 'CONFIRM_RESERVATION',
                                    'reservation_reference' =>  preg_replace('/\D/', '', $reservationReference)
                                ]
                            ]
                        ]
                    ]
                ];

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->post($this->baseUrl . '/orders', $orderData);

                if ($response->successful()) {
                    $responseBody = $response->body();
                    $allOrderResponses[] = json_decode($responseBody, true);
                    $successCount++;

                    foreach ($items as $orderItem) {
                        $orderItem->update([
                            'order_data' => $responseBody
                        ]);
                    }

                    Log::info('PrioTicket order confirmed successfully', [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'reservation_reference' => $reservationReference
                    ]);
                } else {
                    $hasFailures = true;
                    $errorBody = $response->body();
                    $errorDetails[] = "Product ID {$productId}: HTTP {$response->status()} - {$errorBody}";

                    Log::error('PrioTicket order confirmation failed', [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'status' => $response->status(),
                        'response' => $errorBody
                    ]);
                }
            }

            if (!empty($allOrderResponses)) {
                $order->update([
                    'prio_order_response' => json_encode($allOrderResponses)
                ]);
            }

            if ($hasFailures) {
                return [
                    'success' => false,
                    'partial' => $successCount > 0,
                    'error' => implode(' | ', $errorDetails),
                    'success_count' => $successCount,
                    'total_count' => $totalProducts
                ];
            }

            return [
                'success' => true,
                'success_count' => $successCount,
                'total_count' => $totalProducts
            ];
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('PrioTicket Order Confirmation Error', [
                'order_id' => $order->id,
                'error' => $error,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $error
            ];
        }
    }

    public function checkAvailability(
        string $productId,
        string $availabilityId,
        string $date,
        int $pax,
        string $accessToken,
        bool $hasCapacity = true
    ): array {
        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($this->baseUrl . "/products/{$productId}/availability", [
                    'distributor_id' => $this->distributorId,
                    'from_date'      => $date,
                ]);
            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Availability fetch failed',
                ];
            }

            $availabilities = $response->json('data.items', []);

            $availability = collect($availabilities)
                ->firstWhere('availability_id', $availabilityId);

            if (! $availability) {
                return [
                    'success' => false,
                    'error' => 'Selected time slot is no longer available',
                ];
            }

            // Only check spots if tour has capacity constraints
            if ($hasCapacity) {
                if (($availability['availability_spots']['availability_spots_open'] ?? 0) < $pax) {
                    return [
                        'success' => false,
                        'error' => 'Not enough seats available',
                    ];
                }
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('PrioTicket availability check failed', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Availability check failed',
            ];
        }
    }

    public function cancelOrder($order)
    {
        try {
            $token = $this->getAccessToken();

            if (!$token) {
                $error = 'Failed to get PrioTicket access token';
                Log::error($error, ['order_id' => $order->id]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            $prioOrderResponse = is_string($order->prio_order_response) 
                ? json_decode($order->prio_order_response, true) 
                : $order->prio_order_response;

            if (empty($prioOrderResponse)) {
                $error = 'No PrioTicket order data found';
                Log::warning($error, ['order_id' => $order->id]);
                return [
                    'success' => false,
                    'error' => $error
                ];
            }

            $allCancelResponses = [];
            $hasFailures = false;
            $errorDetails = [];

            foreach ($prioOrderResponse as $prioOrder) {
                $orderData = $prioOrder['data']['order'] ?? [];
                $orderReference = $orderData['order_reference'] ?? null;

                if (!$orderReference) {
                    $hasFailures = true;
                    $errorDetails[] = "Missing order reference in PrioTicket data";
                    continue;
                }

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])->delete($this->baseUrl . '/orders/' . $orderReference);

                if ($response->successful()) {
                    $responseBody = $response->json();
                    $allCancelResponses[] = $responseBody;

                    Log::info('PrioTicket order cancelled successfully', [
                        'order_id' => $order->id,
                        'order_reference' => $orderReference
                    ]);
                } else {
                    $hasFailures = true;
                    $errorBody = $response->body();
                    $errorDetails[] = "Order Reference {$orderReference}: HTTP {$response->status()} - {$errorBody}";
                    
                    Log::error('PrioTicket order cancellation failed', [
                        'order_id' => $order->id,
                        'order_reference' => $orderReference,
                        'status' => $response->status(),
                        'response' => $errorBody
                    ]);
                }
            }

            if ($hasFailures) {
                return [
                    'success' => false,
                    'error' => implode(' | ', $errorDetails),
                    'cancel_responses' => $allCancelResponses
                ];
            }

            return [
                'success' => true,
                'cancel_responses' => $allCancelResponses
            ];

        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::error('PrioTicket Order Cancellation Error', [
                'order_id' => $order->id,
                'error' => $error,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $error
            ];
        }
    }
}
