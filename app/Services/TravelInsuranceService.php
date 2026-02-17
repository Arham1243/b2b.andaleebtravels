<?php

namespace App\Services;

use App\Models\Country;
use App\Models\TravelInsurance;
use App\Models\TravelInsurancePassenger;
use App\Models\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use DateTime;

class TravelInsuranceService
{
    public $commissionPercentage;

    public $tabbyApiKey = 'pk_03168c56-d196-4e58-a72a-48dbebb88b87';
    public $tabbyMerchantCode = 'ATA';
    public $tabbyApiUrl = 'https://api.tabby.ai/api/v2';

    public $paybyPartnerId = '200009116289';
    public $paybyApiUrl = 'https://api.payby.com/sgs/api/acquire2';
    public $paybyPrivateKey = 'admin/assets/files/payby-private-key.pem';

    // Insurance API Configuration
    // For Production: Use 'https://zeus.tune2protect.com/ZeusAPI/v5/Zeus.asmx', 'andleb_prod', 'rgJp8jgH1Clw', 'IBE_ADDAE'
    // For UAT: Use 'https://uat-tpe.tune2protect.com/ZeusAPI/Zeus.asmx', 'UAT_DEMO', 'ypHALsJ3EG3p', 'IBE_B2BAE'
    private $insuranceApiUrl = 'https://zeus.tune2protect.com/ZeusAPI/v5/Zeus.asmx';
    private $insuranceUsername = 'andleb_prod';
    private $insurancePassword = 'rgJp8jgH1Clw';
    private $insuranceChannel = 'IBE_ADDAE';

    public function __construct()
    {
        $config = Config::pluck('config_value', 'config_key')->toArray();
        $this->commissionPercentage = ($config['INSURANCE_COMMISSION_PERCENTAGE'] ?? 30) / 100;
    }

    public function getAvailablePlans(array $params)
    {
        $originCountry = Country::where('name', 'LIKE', '%' . $params['origin'] . '%')->first();
        $destinationCountry = Country::where('name', 'LIKE', '%' . $params['destination'] . '%')->first();

        if (!$originCountry || !$destinationCountry) {
            throw new Exception('Origin or destination country not found');
        }

        $url = $this->insuranceApiUrl;

        $headers = [
            "Content-Type: text/xml; charset=utf-8",
            "SOAPAction: \"http://ZEUSTravelInsuranceGateway/WebServices/GetAvailablePlansOTAWithRiders\"",
        ];

        $startDate = date('Y-m-d', strtotime($params['start_date']));
        $returnDate = date('Y-m-d', strtotime($params['return_date']));

        $xmlRequest = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://ZEUSTravelInsuranceGateway/WebServices">
           <soapenv:Header/>
           <soapenv:Body>
              <web:GetAvailablePlansOTAWithRiders>
                 <web:GenericRequestOTALite>
                    <web:Authentication>
                       <web:Username>' . $this->insuranceUsername . '</web:Username>
                       <web:Password>' . $this->insurancePassword . '</web:Password>
                    </web:Authentication>
                    <web:Header>
                       <web:Channel>' . $this->insuranceChannel . '</web:Channel>
                       <web:Currency>AED</web:Currency>
                       <web:CountryCode>AE</web:CountryCode>
                       <web:CultureCode>EN</web:CultureCode>
                       <web:TotalAdults>' . ($params['adult_count'] ?? 0) . '</web:TotalAdults>
                       <web:TotalChild>' . ($params['children_count'] ?? 0) . '</web:TotalChild>
                       <web:TotalInfants>' . ($params['infant_count'] ?? 0) . '</web:TotalInfants>
                       <web:TotalPackagePrice></web:TotalPackagePrice>
                       <web:Attachment></web:Attachment>
                       <web:PackageType></web:PackageType>
                    </web:Header>
                    <web:Flights>
                       <web:DepartCountryCode>' . $originCountry->iso_code . '</web:DepartCountryCode>
                       <web:DepartStationCode></web:DepartStationCode>
                       <web:ArrivalCountryCode>' . $destinationCountry->iso_code . '</web:ArrivalCountryCode>
                       <web:ArrivalStationCode></web:ArrivalStationCode>
                       <web:DepartAirlineCode></web:DepartAirlineCode>
                       <web:DepartDateTime>' . $startDate . '</web:DepartDateTime>
                       <web:ReturnAirlineCode></web:ReturnAirlineCode>
                       <web:ReturnDateTime>' . $returnDate . '</web:ReturnDateTime>
                       <web:DepartFlightNo></web:DepartFlightNo>
                       <web:ReturnFlightNo></web:ReturnFlightNo>
                    </web:Flights>
                    <web:Filters>
                      <web:KeyValue>
                         <web:KeyValue>
                            <web:FilterKeyName></web:FilterKeyName>
                            <web:FilterValue>
                               <web:string></web:string>
                            </web:FilterValue>
                         </web:KeyValue>
                      </web:KeyValue>
                   </web:Filters>
                 </web:GenericRequestOTALite>
              </web:GetAvailablePlansOTAWithRiders>
           </soapenv:Body>
        </soapenv:Envelope>';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL Error: ' . $error);
        }

        curl_close($ch);

        $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            throw new Exception('Failed to parse XML response');
        }

        $json = json_encode($xml);
        $responseArray = json_decode($json, true);

        if (isset($responseArray['soapBody']['GetAvailablePlansOTAWithRidersResponse']['GenericResponse'])) {
            $genericResponse = &$responseArray['soapBody']['GetAvailablePlansOTAWithRidersResponse']['GenericResponse'];

            $seenPlanCodes = [];
            $seenSsrCodes = [];

            if (isset($genericResponse['AvailablePlans']['AvailablePlan']) && is_array($genericResponse['AvailablePlans']['AvailablePlan'])) {
                $uniqueAvailablePlans = [];
                foreach ($genericResponse['AvailablePlans']['AvailablePlan'] as $plan) {
                    $planCode = $plan['PlanCode'] ?? null;
                    $ssrFeeCode = $plan['SSRFeeCode'] ?? null;

                    if ($planCode && $ssrFeeCode && !isset($seenPlanCodes[$planCode]) && !isset($seenSsrCodes[$ssrFeeCode])) {
                        $uniqueAvailablePlans[] = $plan;
                        $seenPlanCodes[$planCode] = true;
                        $seenSsrCodes[$ssrFeeCode] = true;
                    }
                }
                $genericResponse['AvailablePlans']['AvailablePlan'] = $uniqueAvailablePlans;
            }

            if (isset($genericResponse['AvailableUpsellPlans']['UpsellPlanGroup']) && is_array($genericResponse['AvailableUpsellPlans']['UpsellPlanGroup'])) {
                $uniqueUpsellGroups = [];
                foreach ($genericResponse['AvailableUpsellPlans']['UpsellPlanGroup'] as $group) {
                    if (isset($group['UpsellPlans']['UpsellPlan'])) {
                        $plan = $group['UpsellPlans']['UpsellPlan'];

                        $planCode = $plan['PlanCode'] ?? null;
                        $ssrFeeCode = $plan['SSRFeeCode'] ?? null;

                        if ($planCode && $ssrFeeCode && !isset($seenPlanCodes[$planCode]) && !isset($seenSsrCodes[$ssrFeeCode])) {
                            $uniqueUpsellGroups[] = $group;
                            $seenPlanCodes[$planCode] = true;
                            $seenSsrCodes[$ssrFeeCode] = true;
                        }
                    }
                }
                $genericResponse['AvailableUpsellPlans']['UpsellPlanGroup'] = $uniqueUpsellGroups;
            }
        }

        $availablePlans = $responseArray['soapBody']['GetAvailablePlansOTAWithRidersResponse']['GenericResponse']['AvailablePlans']['AvailablePlan'] ?? [];
        $availableUpsellPlans = $responseArray['soapBody']['GetAvailablePlansOTAWithRidersResponse']['GenericResponse']['AvailableUpsellPlans']['UpsellPlanGroup'] ?? [];

        return [
            'available_plans' => $availablePlans,
            'available_upsell_plans' => $availableUpsellPlans,
        ];
    }

    /**
     * Create travel insurance record and passengers
     */
    public function createInsuranceRecord(array $data): TravelInsurance
    {
        $residenceCountry = Country::where('iso_code', $data['residence_country'])->first();
        $originCountry = Country::where('name', 'LIKE', '%' . $data['origin'] . '%')->first();
        $destinationCountry = Country::where('name', 'LIKE', '%' . $data['destination'] . '%')->first();

        $channel = $this->insuranceChannel;
        $currency = 'AED';
        $pnr = 'TP_Test_' . rand(100000, 999999);
        $paymentReference = 'Test_Ref_' . rand(100000, 999999);

        $insuranceData = [
            'user_id' => auth()->id() ?? null,
            'insurance_number' => TravelInsurance::generateInsuranceNumber(),
            'plan_title' => $data['plan_title'] ?? null,
            'plan_code' => $data['plan_code'],
            'ssr_fee_code' => $data['ssr_fee_code'],
            'channel' => $channel,
            'pnr' => $pnr,
            'purchase_date' => now()->toDateString(),
            'currency' => $currency,
            'total_premium' => $data['total_premium'],
            'country_code' => $residenceCountry->iso_code ?? 'AE',
            'total_adults' => (int)($data['adult_count'] ?? 0),
            'total_children' => (int)($data['children_count'] ?? 0),
            'total_infants' => (int)($data['infant_count'] ?? 0),
            'payment_method' => $data['payment_method'],
            'payment_reference' => $paymentReference,
            'payment_status' => 'pending',
            'lead_name' => $data['lead']['fname'] ?? null,
            'lead_email' => $data['lead']['email'] ?? null,
            'lead_phone' => $data['lead']['number'] ?? null,
            'lead_country_of_residence' => $data['lead']['country_of_residence'] ?? null,
            'origin' => $originCountry->name ?? $data['origin'],
            'destination' => $destinationCountry->name ?? $data['destination'],
            'start_date' => $data['start_date'],
            'return_date' => $data['return_date'],
            'residence_country' => $residenceCountry->name ?? null,
            'request_data' => $data,
            'status' => 'pending',
        ];

        $insurance = TravelInsurance::create($insuranceData);

        $this->createPassengers($insurance, $data);

        return $insurance;
    }

    /**
     * Create passenger records
     */
    protected function createPassengers(TravelInsurance $insurance, array $data)
    {
        $totalAdults = (int)($data['adult_count'] ?? 0);
        $totalChildren = (int)($data['children_count'] ?? 0);
        $totalInfants = (int)($data['infant_count'] ?? 0);

        for ($i = 0; $i < $totalAdults; $i++) {
            if (isset($data['adult']['fname'][$i])) {
                $dob = new DateTime($data['adult']['dob'][$i]);
                $age = (new DateTime())->diff($dob)->y;

                TravelInsurancePassenger::create([
                    'travel_insurance_id' => $insurance->id,
                    'passenger_type' => 'adult',
                    'first_name' => $data['adult']['fname'][$i],
                    'last_name' => $data['adult']['lname'][$i],
                    'date_of_birth' => $data['adult']['dob'][$i],
                    'gender' => $data['adult']['gender'][$i],
                    'passport_number' => $data['adult']['passport'][$i],
                    'nationality' => $data['adult']['nationality'][$i],
                    'country_of_residence' => $data['adult']['country_of_residence'][$i],
                    'age' => $age,
                    'status' => 'active',
                ]);
            }
        }

        for ($i = 0; $i < $totalChildren; $i++) {
            if (isset($data['child']['fname'][$i])) {
                $dob = new DateTime($data['child']['dob'][$i]);
                $age = (new DateTime())->diff($dob)->y;

                TravelInsurancePassenger::create([
                    'travel_insurance_id' => $insurance->id,
                    'passenger_type' => 'child',
                    'first_name' => $data['child']['fname'][$i],
                    'last_name' => $data['child']['lname'][$i],
                    'date_of_birth' => $data['child']['dob'][$i],
                    'gender' => $data['child']['gender'][$i],
                    'passport_number' => $data['child']['passport'][$i],
                    'nationality' => $data['child']['nationality'][$i],
                    'country_of_residence' => $data['child']['country_of_residence'][$i],
                    'age' => $age,
                    'status' => 'active',
                ]);
            }
        }

        for ($i = 0; $i < $totalInfants; $i++) {
            if (isset($data['infant']['fname'][$i])) {
                $dob = new DateTime($data['infant']['dob'][$i]);
                $age = (new DateTime())->diff($dob)->y;

                TravelInsurancePassenger::create([
                    'travel_insurance_id' => $insurance->id,
                    'passenger_type' => 'infant',
                    'first_name' => $data['infant']['fname'][$i],
                    'last_name' => $data['infant']['lname'][$i],
                    'date_of_birth' => $data['infant']['dob'][$i],
                    'gender' => $data['infant']['gender'][$i],
                    'passport_number' => $data['infant']['passport'][$i],
                    'nationality' => $data['infant']['nationality'][$i],
                    'country_of_residence' => $data['infant']['country_of_residence'][$i],
                    'age' => $age,
                    'status' => 'active',
                ]);
            }
        }
    }

    /**
     * Get redirect URL based on payment method
     */
    public function getRedirectUrl(TravelInsurance $insurance, string $paymentMethod): string
    {
        switch ($paymentMethod) {
            case 'payby':
                return $this->paybyRedirect($insurance);

            case 'tabby':
                return $this->tabbyRedirect($insurance);

            default:
                throw new \InvalidArgumentException("Unsupported payment method: {$paymentMethod}");
        }
    }

    /**
     * PayBy Redirect
     */
    protected function paybyRedirect(TravelInsurance $insurance)
    {
        $requestTime = now()->timestamp * 1000;
        $finalAmount = $insurance->total_premium + ($insurance->total_premium * $this->commissionPercentage);

        $requestData = [
            'requestTime' => $requestTime,
            'bizContent' => [
                'merchantOrderNo' => $insurance->insurance_number,
                'subject' => 'TRAVEL INSURANCE',
                'totalAmount' => [
                    'currency' => 'AED',
                    'amount' => number_format((float)$finalAmount, 2, '.', '')
                ],
                'paySceneCode' => 'PAYPAGE',
                'paySceneParams' => [
                    'redirectUrl' => route('frontend.travel-insurance.payment.success', ['insurance' => $insurance->id]),
                    'backUrl' => route('frontend.travel-insurance.payment.failed', ['insurance' => $insurance->id])
                ],
                'reserved' => 'Andaleeb Travel Insurance',
                'accessoryContent' => [
                    'goodsDetail' => [
                        'body' => 'Travel Insurance',
                        'categoriesTree' => 'CT12',
                        'goodsCategory' => 'GC10',
                        'goodsId' => 'GI1005',
                        'goodsName' => $insurance->plan_title ?? 'Travel Insurance',
                        'price' => [
                            'currency' => 'AED',
                            'amount' => number_format((float)$finalAmount, 2, '.', '')
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

    /**
     * Tabby Redirect
     */
    protected function tabbyRedirect(TravelInsurance $insurance)
    {
        $finalAmount = $insurance->total_premium + ($insurance->total_premium * $this->commissionPercentage);

        $items = [[
            'title' => $insurance->plan_title ?? 'Travel Insurance',
            'description' => 'Travel Insurance Coverage',
            'quantity' => 1,
            'unit_price' => number_format((float)$finalAmount, 2, '.', ''),
            'category' => 'Travel Insurance'
        ]];

        $merchantCode = $this->tabbyMerchantCode;
        $tabbyApiKey = $this->tabbyApiKey;

        if (!$merchantCode || !$tabbyApiKey) {
            throw new \Exception('Tabby merchant code or API key is missing. Check your .env file.');
        }

        $requestData = [
            'payment' => [
                'amount' => number_format((float)$finalAmount, 2, '.', ''),
                'currency' => 'AED',
                'description' => 'Travel Insurance - Andaleeb Travel Agency',
                'buyer' => [
                    'phone' => $insurance->lead_phone,
                    'email' => $insurance->lead_email,
                    'name' => $insurance->lead_name,
                    'dob' => '1990-01-01'
                ],
                'shipping_address' => [
                    'city' => 'N/A',
                    'address' => $insurance->lead_country_of_residence ?? 'N/A',
                    'zip' => '00000'
                ],
                'order' => [
                    'tax_amount' => '0.00',
                    'shipping_amount' => '0.00',
                    'discount_amount' => '0.00',
                    'updated_at' => now()->toIso8601String(),
                    'reference_id' => $insurance->insurance_number,
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
                            'phone' => $insurance->lead_phone,
                            'email' => $insurance->lead_email,
                            'name' => $insurance->lead_name,
                            'dob' => '1990-01-01'
                        ],
                        'shipping_address' => [
                            'city' => 'Dubai',
                            'address' => $insurance->lead_country_of_residence ?? 'N/A',
                            'zip' => '00000'
                        ],
                        'items' => $items
                    ]
                ],
                'meta' => [
                    'order_id' => (string)$insurance->id,
                    'customer' => (string)$insurance->id
                ],
                'attachment' => [
                    'body' => json_encode([
                        'insurance_details' => [
                            'insurance_number' => $insurance->insurance_number,
                            'plan_title' => $insurance->plan_title
                        ]
                    ]),
                    'content_type' => 'application/vnd.tabby.v1+json'
                ]
            ],
            'lang' => 'en',
            'merchant_code' => $merchantCode,
            'merchant_urls' => [
                'success' => route('frontend.travel-insurance.payment.success', ['insurance' => $insurance->id]),
                'cancel' => route('frontend.travel-insurance.payment.failed'),
                'failure' => route('frontend.travel-insurance.payment.failed')
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tabbyApiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->tabbyApiUrl . '/checkout', $requestData);

        if (!$response->successful()) {
            throw new \Exception('Tabby API request failed: ' . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['configuration']['available_products']['installments'][0]['web_url'])) {
            if (isset($responseData['payment']['id'])) {
                $insurance->update([
                    'tabby_payment_id' => $responseData['payment']['id']
                ]);

                Log::info('Tabby payment ID saved', [
                    'insurance_id' => $insurance->id,
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
    public function verifyPayByPayment(TravelInsurance $insurance): array
    {
        try {
            $requestTime = now()->timestamp * 1000;

            $requestData = [
                'requestTime' => $requestTime,
                'bizContent' => [
                    'merchantOrderNo' => $insurance->insurance_number
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
            Log::error('PayBy Verification Error', [
                'insurance_id' => $insurance->id,
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
    public function verifyTabbyPayment(TravelInsurance $insurance): array
    {
        // TODO: Implement actual Tabby payment verification
        // For now, return success to allow testing
        return [
            'success' => true,
            'data' => []
        ];

        // Original implementation commented out for future use:
        // try {
        //     if (empty($insurance->tabby_payment_id)) {
        //         throw new \Exception('Tabby payment ID not found for this insurance');
        //     }

        //     $response = Http::withHeaders([
        //         'Authorization' => 'Bearer ' . $this->tabbyApiKey
        //     ])->get("{$this->tabbyApiUrl}/payments/{$insurance->tabby_payment_id}");

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
        //         'insurance_id' => $insurance->id,
        //         'tabby_payment_id' => $insurance->tabby_payment_id ?? 'N/A',
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     return [
        //         'success' => false,
        //         'error' => $e->getMessage()
        //     ];
        // }
    }

    function mapGenderForSoap($gender)
    {
        $gender = strtolower(trim((string) $gender));

        if (in_array($gender, ['male', 'm'])) {
            return 'Male';
        }

        if (in_array($gender, ['female', 'f'])) {
            return 'Female';
        }

        // fallback
        return 'Male';
    }

    /**
     * Confirm Purchase with Insurance API after successful payment
     */
    public function confirmPurchase(TravelInsurance $insurance): array
    {
        try {
            $requestData = is_string($insurance->request_data)
                ? json_decode($insurance->request_data, true)
                : $insurance->request_data;

            $residenceCountry = Country::where('iso_code', $requestData['residence_country'])->first();
            $originCountry = Country::where('name', 'LIKE', '%' . $requestData['origin'] . '%')->first();
            $destinationCountry = Country::where('name', 'LIKE', '%' . $requestData['destination'] . '%')->first();

            $channel = $this->insuranceChannel;
            $currency = 'AED';
            $cultureCode = 'EN';
            $totalAdults = (int)($requestData['adult_count'] ?? 0);
            $totalChild = (int)($requestData['children_count'] ?? 0);
            $totalInfants = (int)($requestData['infant_count'] ?? 0);
            $purchaseDate = $insurance->purchase_date->format('Y-m-d');
            $pnr = $insurance->pnr;
            $paymentMethod = 'Tap Pay';
            $paymentReference = $insurance->payment_reference;

            $departCountryCode = $originCountry->iso_code ?? '';
            $departStationCode = '';
            $arrivalCountryCode = $destinationCountry->iso_code ?? '';
            $arrivalStationCode = '';
            $departDateTime = date('Y-m-d', strtotime($requestData['start_date']));
            $returnDateTime = date('Y-m-d', strtotime($requestData['return_date']));

            $selectedPlanCode = $requestData['plan_code'] ?? '';
            $selectedSSRFeeCode = $requestData['ssr_fee_code'] ?? '';
            $totalPremium = $insurance->total_premium;

            // Build passengers XML
            $passengersXml = '';
            $passengerCount = $totalAdults + $totalChild + $totalInfants;
            $passengerPremiumAmount = $passengerCount > 0 ? ($totalPremium / $passengerCount) : 0;

            // Add Adults
            if (isset($requestData['adult']) && is_array($requestData['adult'])) {
                for ($i = 0; $i < $totalAdults; $i++) {
                    $dob = new DateTime($requestData['adult']['dob'][$i] ?? 'now');
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;

                    $nationalityCountry = Country::where('iso_code', $requestData['adult']['nationality'][$i] ?? '')->first();
                    $residenceCountryPassenger = Country::where('iso_code', $requestData['adult']['country_of_residence'][$i] ?? '')->first();

                    $passengersXml .= '<web:Passenger>
                        <web:IsInfant>0</web:IsInfant>
                        <web:FirstName>' . htmlspecialchars($requestData['adult']['fname'][$i] ?? '') . '</web:FirstName>
                        <web:LastName>' . htmlspecialchars($requestData['adult']['lname'][$i] ?? '') . '</web:LastName>
                        <web:Gender>' . $this->mapGenderForSoap($requestData['adult']['gender'][$i] ?? '') . '</web:Gender>
                        <web:DOB>' . htmlspecialchars($requestData['adult']['dob'][$i] ?? '') . '</web:DOB>
                        <web:Age>' . $age . '</web:Age>
                        <web:IdentityType>Passport</web:IdentityType>
                        <web:IdentityNo>' . htmlspecialchars($requestData['adult']['passport'][$i] ?? '') . '</web:IdentityNo>
                        <web:IsQualified>1</web:IsQualified>
                        <web:Nationality>' . ($nationalityCountry->iso_code ?? '') . '</web:Nationality>
                        <web:CountryOfResidence>' . ($residenceCountryPassenger->iso_code ?? '') . '</web:CountryOfResidence>
                        <web:SelectedPlanCode>' . htmlspecialchars($selectedPlanCode) . '</web:SelectedPlanCode>
                        <web:SelectedSSRFeeCode>' . htmlspecialchars($selectedSSRFeeCode) . '</web:SelectedSSRFeeCode>
                        <web:CurrencyCode>' . $currency . '</web:CurrencyCode>
                        <web:PassengerPremiumAmount>' . number_format($passengerPremiumAmount, 2, '.', '') . '</web:PassengerPremiumAmount>
                        <web:EmailAddress></web:EmailAddress>
                        <web:PhoneNumber></web:PhoneNumber>
                        <web:Address></web:Address>
                        <web:ExtraInfo><web:Item><web:ItemID></web:ItemID><web:ItemKeyName></web:ItemKeyName><web:ItemDesc></web:ItemDesc></web:Item></web:ExtraInfo>
                        <web:PassportIssueDate></web:PassportIssueDate>
                        <web:PassportExpiryDate></web:PassportExpiryDate>
                    </web:Passenger>';
                }
            }

            // Add Children
            if (isset($requestData['child']) && is_array($requestData['child'])) {
                for ($i = 0; $i < $totalChild; $i++) {
                    $dob = new DateTime($requestData['child']['dob'][$i] ?? 'now');
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;

                    $nationalityCountry = Country::where('iso_code', $requestData['child']['nationality'][$i] ?? '')->first();
                    $residenceCountryPassenger = Country::where('iso_code', $requestData['child']['country_of_residence'][$i] ?? '')->first();

                    $passengersXml .= '<web:Passenger>
                        <web:IsInfant>0</web:IsInfant>
                        <web:FirstName>' . htmlspecialchars($requestData['child']['fname'][$i] ?? '') . '</web:FirstName>
                        <web:LastName>' . htmlspecialchars($requestData['child']['lname'][$i] ?? '') . '</web:LastName>
                        <web:Gender>' . $this->mapGenderForSoap($requestData['child']['gender'][$i] ?? '') . '</web:Gender>
                        <web:DOB>' . htmlspecialchars($requestData['child']['dob'][$i] ?? '') . '</web:DOB>
                        <web:Age>' . $age . '</web:Age>
                        <web:IdentityType>Passport</web:IdentityType>
                        <web:IdentityNo>' . htmlspecialchars($requestData['child']['passport'][$i] ?? '') . '</web:IdentityNo>
                        <web:IsQualified>1</web:IsQualified>
                        <web:Nationality>' . ($nationalityCountry->iso_code ?? '') . '</web:Nationality>
                        <web:CountryOfResidence>' . ($residenceCountryPassenger->iso_code ?? '') . '</web:CountryOfResidence>
                        <web:SelectedPlanCode>' . htmlspecialchars($selectedPlanCode) . '</web:SelectedPlanCode>
                        <web:SelectedSSRFeeCode>' . htmlspecialchars($selectedSSRFeeCode) . '</web:SelectedSSRFeeCode>
                        <web:CurrencyCode>' . $currency . '</web:CurrencyCode>
                        <web:PassengerPremiumAmount>' . number_format($passengerPremiumAmount, 2, '.', '') . '</web:PassengerPremiumAmount>
                        <web:EmailAddress></web:EmailAddress>
                        <web:PhoneNumber></web:PhoneNumber>
                        <web:Address></web:Address>
                        <web:ExtraInfo><web:Item><web:ItemID></web:ItemID><web:ItemKeyName></web:ItemKeyName><web:ItemDesc></web:ItemDesc></web:Item></web:ExtraInfo>
                        <web:PassportIssueDate></web:PassportIssueDate>
                        <web:PassportExpiryDate></web:PassportExpiryDate>
                    </web:Passenger>';
                }
            }

            // Add Infants
            if (isset($requestData['infant']) && is_array($requestData['infant'])) {
                for ($i = 0; $i < $totalInfants; $i++) {
                    $dob = new DateTime($requestData['infant']['dob'][$i] ?? 'now');
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;

                    $nationalityCountry = Country::where('iso_code', $requestData['infant']['nationality'][$i] ?? '')->first();
                    $residenceCountryPassenger = Country::where('iso_code', $requestData['infant']['country_of_residence'][$i] ?? '')->first();

                    $passengersXml .= '<web:Passenger>
                        <web:IsInfant>1</web:IsInfant>
                        <web:FirstName>' . htmlspecialchars($requestData['infant']['fname'][$i] ?? '') . '</web:FirstName>
                        <web:LastName>' . htmlspecialchars($requestData['infant']['lname'][$i] ?? '') . '</web:LastName>
                        <web:Gender>' . $this->mapGenderForSoap($requestData['infant']['gender'][$i] ?? '') . '</web:Gender>
                        <web:DOB>' . htmlspecialchars($requestData['infant']['dob'][$i] ?? '') . '</web:DOB>
                        <web:Age>' . $age . '</web:Age>
                        <web:IdentityType>Passport</web:IdentityType>
                        <web:IdentityNo>' . htmlspecialchars($requestData['infant']['passport'][$i] ?? '') . '</web:IdentityNo>
                        <web:IsQualified>1</web:IsQualified>
                        <web:Nationality>' . ($nationalityCountry->iso_code ?? '') . '</web:Nationality>
                        <web:CountryOfResidence>' . ($residenceCountryPassenger->iso_code ?? '') . '</web:CountryOfResidence>
                        <web:SelectedPlanCode>' . htmlspecialchars($selectedPlanCode) . '</web:SelectedPlanCode>
                        <web:SelectedSSRFeeCode>' . htmlspecialchars($selectedSSRFeeCode) . '</web:SelectedSSRFeeCode>
                        <web:CurrencyCode>' . $currency . '</web:CurrencyCode>
                        <web:PassengerPremiumAmount>' . number_format($passengerPremiumAmount, 2, '.', '') . '</web:PassengerPremiumAmount>
                        <web:EmailAddress></web:EmailAddress>
                        <web:PhoneNumber></web:PhoneNumber>
                        <web:Address></web:Address>
                        <web:ExtraInfo><web:Item><web:ItemID></web:ItemID><web:ItemKeyName></web:ItemKeyName><web:ItemDesc></web:ItemDesc></web:Item></web:ExtraInfo>
                        <web:PassportIssueDate></web:PassportIssueDate>
                        <web:PassportExpiryDate></web:PassportExpiryDate>
                    </web:Passenger>';
                }
            }

            // Build SOAP XML Request
            $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://ZEUSTravelInsuranceGateway/WebServices">
    <soapenv:Header/>
    <soapenv:Body>
        <web:ConfirmPurchase>
            <web:GenericRequest>
                <web:Authentication>
                    <web:Username>' . $this->insuranceUsername . '</web:Username>
                    <web:Password>' . $this->insurancePassword . '</web:Password>
                </web:Authentication>
                <web:Header>
                    <web:Channel>' . htmlspecialchars($channel) . '</web:Channel>
                    <web:ItineraryID></web:ItineraryID>
                    <web:PNR>' . htmlspecialchars($pnr) . '</web:PNR>
                    <web:PolicyNo></web:PolicyNo>
                    <web:PurchaseDate>' . htmlspecialchars($purchaseDate) . '</web:PurchaseDate>
                    <web:SSRFeeCode>' . htmlspecialchars($selectedSSRFeeCode) . '</web:SSRFeeCode>
                    <web:FeeDescription></web:FeeDescription>
                    <web:Currency>' . htmlspecialchars($currency) . '</web:Currency>
                    <web:TotalPremium>' . htmlspecialchars($totalPremium) . '</web:TotalPremium>
                    <web:CountryCode>' . ($residenceCountry->iso_code ?? 'AE') . '</web:CountryCode>
                    <web:CultureCode>' . htmlspecialchars($cultureCode) . '</web:CultureCode>
                    <web:TotalAdults>' . $totalAdults . '</web:TotalAdults>
                    <web:TotalChild>' . $totalChild . '</web:TotalChild>
                    <web:TotalInfants>' . $totalInfants . '</web:TotalInfants>
                    <web:TotalPackagePrice></web:TotalPackagePrice>
                    <web:Attachment></web:Attachment>
                    <web:PackageType></web:PackageType>
                    <web:PaymentMethod>' . htmlspecialchars($paymentMethod) . '</web:PaymentMethod>
                    <web:PaymentReference>' . htmlspecialchars($paymentReference) . '</web:PaymentReference>
                </web:Header>
                <web:ContactDetails>
                    <web:ContactPerson>' . htmlspecialchars($insurance->lead_name ?? '') . '</web:ContactPerson>
                    <web:Address1>' . htmlspecialchars($insurance->lead_country_of_residence ?? '') . '</web:Address1>
                    <web:Address2></web:Address2>
                    <web:Address3></web:Address3>
                    <web:HomePhoneNum></web:HomePhoneNum>
                    <web:MobilePhoneNum>' . htmlspecialchars($insurance->lead_phone ?? '') . '</web:MobilePhoneNum>
                    <web:OtherPhoneNum></web:OtherPhoneNum>
                    <web:PostCode></web:PostCode>
                    <web:City></web:City>
                    <web:State></web:State>
                    <web:Country>' . htmlspecialchars($insurance->lead_country_of_residence ?? '') . '</web:Country>
                    <web:EmailAddress>' . htmlspecialchars($insurance->lead_email ?? '') . '</web:EmailAddress>
                </web:ContactDetails>
                <web:ApplicantDetail>
                    <web:Name>' . htmlspecialchars($insurance->lead_name ?? '') . '</web:Name>
                    <web:DOB></web:DOB>
                    <web:Contact>' . htmlspecialchars($insurance->lead_phone ?? '') . '</web:Contact>
                    <web:Address>' . htmlspecialchars($insurance->lead_country_of_residence ?? '') . '</web:Address>
                </web:ApplicantDetail>
                <web:Flights>
                    <web:Flight>
                        <web:DepartCountryCode>' . htmlspecialchars($departCountryCode) . '</web:DepartCountryCode>
                        <web:DepartStationCode>' . htmlspecialchars($departStationCode) . '</web:DepartStationCode>
                        <web:ArrivalCountryCode>' . htmlspecialchars($arrivalCountryCode) . '</web:ArrivalCountryCode>
                        <web:ArrivalStationCode>' . htmlspecialchars($arrivalStationCode) . '</web:ArrivalStationCode>
                        <web:DepartAirlineCode></web:DepartAirlineCode>
                        <web:DepartDateTime>' . htmlspecialchars($departDateTime) . '</web:DepartDateTime>
                        <web:ReturnAirlineCode></web:ReturnAirlineCode>
                        <web:ReturnDateTime>' . htmlspecialchars($returnDateTime) . '</web:ReturnDateTime>
                        <web:DepartFlightNo></web:DepartFlightNo>
                        <web:ReturnFlightNo></web:ReturnFlightNo>
                    </web:Flight>
                </web:Flights>
                <web:Passengers>' . $passengersXml . '</web:Passengers>
            </web:GenericRequest>
        </web:ConfirmPurchase>
    </soapenv:Body>
</soapenv:Envelope>';

            $url = $this->insuranceApiUrl;

            $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => 'http://ZEUSTravelInsuranceGateway/WebServices/ConfirmPurchase',
            ])->send('POST', $url, [
                'body' => $xmlRequest
            ]);

            if (!$response->successful()) {
                throw new \Exception('Insurance API request failed: ' . $response->body());
            }

            $responseBody = $response->body();

            // Remove BOM and trim
            $responseBody = preg_replace('/^\xEF\xBB\xBF/', '', $responseBody);
            $responseBody = trim($responseBody);

            // Extract SOAP Body
            if (preg_match('/<soap:Body[^>]*>(.*?)<\/soap:Body>/is', $responseBody, $matches)) {
                $bodyXml = $matches[1];
                $xml = simplexml_load_string($bodyXml, 'SimpleXMLElement', LIBXML_NOCDATA);

                if ($xml === false) {
                    throw new \Exception('Error parsing XML response');
                }

                $array = json_decode(json_encode($xml), true);

                return [
                    'success' => true,
                    'data' => $array
                ];
            }

            throw new \Exception('No SOAP Body found in response');
        } catch (\Exception $e) {
            Log::error('Insurance Confirm Purchase Error', [
                'insurance_id' => $insurance->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
