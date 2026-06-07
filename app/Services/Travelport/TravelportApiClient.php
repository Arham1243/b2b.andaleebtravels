<?php

namespace App\Services\Travelport;

use App\Support\FlightCabinPreference;

class TravelportApiClient
{
    /** @see libraries/TravelportAPI.php */
    private const USERNAME = 'Universal API/uAPI3803196999-ff9da8ef';
    private const PASSWORD = 'sR-9}8Pjr+';
    private const TARGET_BRANCH = 'P7250866';
    private const AUTHORIZED_BY = 'Zeeshan';
    private const BASE_ENDPOINT = 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI';
    private const PROVIDER_CODE = '1G';
    private const HTTP_TIMEOUT = 90;

    private const AIR_NS = 'http://www.travelport.com/schema/air_v52_0';
    private const COM_NS = 'http://www.travelport.com/schema/common_v52_0';
    private const UNI_NS = 'http://www.travelport.com/schema/universal_v52_0';

    /**
     * @param  array<string, mixed>  $searchData
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function lowFareSearch(array $searchData): array
    {
        $traceId = $this->generateTraceId();
        $legsXml = $this->buildSearchAirLegsXml($searchData);
        $passengersXml = $this->buildSearchPassengersXml($searchData);
        $modifiersXml = $this->buildSearchModifiersXml($searchData);

        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;

        // ReturnUpsellFare returns additional branded fares (e.g. Economy Light + Smart)
        // for the same routing so cards can show "+N More Fares" like Sabre.
        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            ReturnBrandedFares="true"
            ReturnUpsellFare="true"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            {$legsXml}
            {$modifiersXml}
            {$passengersXml}
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @param  array{
     *     fare_info_ref: string,
     *     fare_rule_key: string,
     *     provider_code?: string
     * }  $request
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airFareRules(array $request): array
    {
        $fareInfoRef = htmlspecialchars(trim((string) ($request['fare_info_ref'] ?? '')), ENT_XML1);
        $fareRuleKey = htmlspecialchars(trim((string) ($request['fare_rule_key'] ?? '')), ENT_XML1);
        $providerCode = htmlspecialchars(trim((string) ($request['provider_code'] ?? self::PROVIDER_CODE)), ENT_XML1);

        if ($fareInfoRef === '' || $fareRuleKey === '') {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => 'Fare rule reference is missing.',
            ];
        }

        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirFareRulesReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:FareRuleKey FareInfoRef="{$fareInfoRef}" ProviderCode="{$providerCode}">{$fareRuleKey}</air:FareRuleKey>
        </air:AirFareRulesReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     * @param  array<string, int>  $passengerCounts  e.g. ['ADT' => 1, 'CNN' => 0, 'INF' => 0]
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airPrice(array $segments, array $passengerCounts): array
    {
        if ($segments === []) {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => 'No flight segments provided for pricing.',
            ];
        }

        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;

        $airSegmentsXml = '';
        foreach ($segments as $seg) {
            $key = $this->xmlEsc((string) ($seg['Key'] ?? ''));
            $group = $this->xmlEsc((string) ($seg['Group'] ?? '0'));
            $provider = $this->xmlEsc((string) ($seg['ProviderCode'] ?? self::PROVIDER_CODE));
            $carrier = $this->xmlEsc((string) ($seg['Carrier'] ?? ''));
            $flightNumber = $this->xmlEsc((string) ($seg['FlightNumber'] ?? ''));
            $origin = $this->xmlEsc((string) ($seg['Origin'] ?? ''));
            $destination = $this->xmlEsc((string) ($seg['Destination'] ?? ''));
            $departure = $this->xmlEsc((string) ($seg['DepartureTime'] ?? ''));
            $arrival = $this->xmlEsc((string) ($seg['ArrivalTime'] ?? ''));
            $classOfService = $this->xmlEsc((string) ($seg['ClassOfService'] ?? ''));
            $equipment = $this->xmlEsc((string) ($seg['Equipment'] ?? '320'));

            $airSegmentsXml .= <<<XML

                    <air:AirSegment
                        Key="{$key}"
                        Group="{$group}"
                        ProviderCode="{$provider}"
                        Carrier="{$carrier}"
                        FlightNumber="{$flightNumber}"
                        Origin="{$origin}"
                        Destination="{$destination}"
                        DepartureTime="{$departure}"
                        ArrivalTime="{$arrival}"
                        ClassOfService="{$classOfService}"
                        Status="SS"
                        SeatAvail="Available"
                        ETicketability="Yes"
                        Equipment="{$equipment}"/>
XML;
        }

        $passengersXml = '';
        $travelerIdx = 1;
        foreach (['ADT', 'CNN', 'INF'] as $code) {
            $count = max(0, (int) ($passengerCounts[$code] ?? 0));
            for ($i = 0; $i < $count; $i++) {
                $ref = "traveler_{$travelerIdx}";
                $passengersXml .= "\n            <com:SearchPassenger Code=\"{$code}\" BookingTravelerRef=\"{$ref}\"/>";
                $travelerIdx++;
            }
        }

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirPriceReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirItinerary>
                {$airSegmentsXml}
            </air:AirItinerary>
            <air:AirPricingModifiers
                ETicketability="Required"
                FaresIndicator="PublicFaresOnly"/>
            {$passengersXml}
            <air:AirPricingCommand/>
        </air:AirPriceReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     * @param  array<string, mixed>  $pricingData
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airHold(array $travelers, array $pricingData): array
    {
        if ($travelers === [] || ($pricingData['segments'] ?? []) === []) {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => 'Missing travelers or segment data for hold.',
            ];
        }

        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;
        $uniNs = self::UNI_NS;

        $providerCode = $this->xmlEsc((string) ($pricingData['provider_code'] ?? self::PROVIDER_CODE));
        $solutionKey = $this->xmlEsc((string) ($pricingData['solution_key'] ?? ''));
        $totalPrice = $this->xmlEsc((string) ($pricingData['total_price'] ?? ''));
        $basePrice = $this->xmlEsc((string) ($pricingData['base_price'] ?? ''));
        $taxes = $this->xmlEsc((string) ($pricingData['taxes'] ?? ''));
        $pricingInfoKey = $this->xmlEsc((string) ($pricingData['pricing_info_key'] ?? ''));
        $pricingMethod = $this->xmlEsc((string) ($pricingData['pricing_method'] ?? 'Auto'));

        $travelersXml = '';
        foreach ($travelers as $traveler) {
            $key = $this->xmlEsc((string) ($traveler['key'] ?? ''));
            $type = $this->xmlEsc((string) ($traveler['traveler_type'] ?? 'ADT'));
            $dob = $this->xmlEsc((string) ($traveler['dob'] ?? ''));
            $gender = $this->xmlEsc((string) ($traveler['gender'] ?? 'M'));
            $first = $this->xmlEsc((string) ($traveler['firstName'] ?? ''));
            $last = $this->xmlEsc((string) ($traveler['lastName'] ?? ''));
            $country = $this->xmlEsc((string) ($traveler['phoneCountryCode'] ?? '971'));
            $area = $this->xmlEsc((string) ($traveler['phoneAreaCode'] ?? '50'));
            $number = $this->xmlEsc((string) ($traveler['phoneNumber'] ?? ''));
            $email = $this->xmlEsc((string) ($traveler['email'] ?? ''));

            $travelersXml .= <<<XML

            <BookingTraveler
                xmlns="{$comNs}"
                Key="{$key}"
                TravelerType="{$type}"
                DOB="{$dob}"
                Gender="{$gender}">
                <BookingTravelerName First="{$first}" Last="{$last}"/>
                <PhoneNumber CountryCode="{$country}" AreaCode="{$area}" Number="{$number}"/>
                <Email EmailID="{$email}"/>
            </BookingTraveler>
XML;
        }

        $segmentsXml = '';
        foreach ($pricingData['segments'] as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $segKey = $this->xmlEsc((string) ($segment['key'] ?? ''));
            $group = $this->xmlEsc((string) ($segment['group'] ?? '0'));
            $carrier = $this->xmlEsc((string) ($segment['carrier'] ?? ''));
            $flightNumber = $this->xmlEsc((string) ($segment['flight_number'] ?? ''));
            $segProvider = $this->xmlEsc((string) ($segment['provider_code'] ?? $providerCode));
            $origin = $this->xmlEsc((string) ($segment['origin'] ?? ''));
            $destination = $this->xmlEsc((string) ($segment['destination'] ?? ''));
            $depTime = $this->xmlEsc($this->normalizeTravelportTime((string) ($segment['dep_time'] ?? '')));
            $arrTime = $this->xmlEsc($this->normalizeTravelportTime((string) ($segment['arr_time'] ?? '')));
            $flightTime = $this->xmlEsc((string) ($segment['flight_time'] ?? ''));
            $travelTime = $this->xmlEsc((string) ($segment['travel_time'] ?? ''));
            $bookingCode = $this->xmlEsc((string) ($segment['booking_code'] ?? ''));

            $segmentsXml .= <<<XML

                <AirSegment
                    Key="{$segKey}"
                    Group="{$group}"
                    Carrier="{$carrier}"
                    FlightNumber="{$flightNumber}"
                    ProviderCode="{$segProvider}"
                    Origin="{$origin}"
                    Destination="{$destination}"
                    DepartureTime="{$depTime}"
                    ArrivalTime="{$arrTime}"
                    FlightTime="{$flightTime}"
                    TravelTime="{$travelTime}"
                    ClassOfService="{$bookingCode}"
                    Status="NN"/>
XML;
        }

        $fareInfosXml = '';
        $fareInfosByKey = [];
        foreach ($pricingData['fare_infos'] ?? [] as $fareInfo) {
            if (! is_array($fareInfo)) {
                continue;
            }
            $fKey = (string) ($fareInfo['key'] ?? '');
            if ($fKey === '' || isset($fareInfosByKey[$fKey])) {
                continue;
            }
            $fareInfosByKey[$fKey] = true;
            $fareKey = $this->xmlEsc($fKey);
            $fareBasis = $this->xmlEsc((string) ($fareInfo['fare_basis'] ?? ''));
            $paxType = $this->xmlEsc((string) ($fareInfo['passenger_type_code'] ?? 'ADT'));
            $fareOrigin = $this->xmlEsc((string) ($fareInfo['origin'] ?? ''));
            $fareDestination = $this->xmlEsc((string) ($fareInfo['destination'] ?? ''));
            $departureDate = $this->xmlEsc((string) ($fareInfo['departure_date'] ?? ''));
            $effectiveDate = $this->xmlEsc((string) ($fareInfo['effective_date'] ?? ''));
            $fareRuleKey = (string) ($fareInfo['fare_rule_key'] ?? '');

            $fareInfosXml .= <<<XML
                    <FareInfo
                        Key="{$fareKey}"
                        FareBasis="{$fareBasis}"
                        PassengerTypeCode="{$paxType}"
                        Origin="{$fareOrigin}"
                        Destination="{$fareDestination}"
                        DepartureDate="{$departureDate}"
                        EffectiveDate="{$effectiveDate}">
                        <FareRuleKey FareInfoRef="{$fareKey}" ProviderCode="{$providerCode}">{$fareRuleKey}</FareRuleKey>
                    </FareInfo>
XML;
        }

        $bookingInfosXml = '';
        foreach ($pricingData['booking_infos'] ?? [] as $bookingInfo) {
            if (! is_array($bookingInfo)) {
                continue;
            }
            $bookingCode = $this->xmlEsc((string) ($bookingInfo['booking_code'] ?? ''));
            $cabinClass = $this->xmlEsc((string) ($bookingInfo['cabin_class'] ?? ''));
            $fareInfoRef = $this->xmlEsc((string) ($bookingInfo['fare_info_ref'] ?? ''));
            $segmentRef = $this->xmlEsc((string) ($bookingInfo['segment_ref'] ?? ''));
            $hostTokenRef = $this->xmlEsc((string) ($bookingInfo['host_token_ref'] ?? ''));

            $bookingInfosXml .= <<<XML
                    <BookingInfo
                        BookingCode="{$bookingCode}"
                        CabinClass="{$cabinClass}"
                        FareInfoRef="{$fareInfoRef}"
                        SegmentRef="{$segmentRef}"
                        HostTokenRef="{$hostTokenRef}"/>
XML;
        }

        $passengerTypesXml = '';
        foreach ($pricingData['passenger_types'] ?? [] as $passengerType) {
            if (! is_array($passengerType)) {
                continue;
            }
            $code = $this->xmlEsc((string) ($passengerType['code'] ?? 'ADT'));
            $ref = $this->xmlEsc((string) ($passengerType['traveler_ref'] ?? ''));
            $passengerTypesXml .= "\n                    <PassengerType Code=\"{$code}\" BookingTravelerRef=\"{$ref}\"/>";
        }

        $hostTokensXml = '';
        foreach ($pricingData['host_tokens'] ?? [] as $hostToken) {
            if (! is_array($hostToken)) {
                continue;
            }
            $htKey = $this->xmlEsc((string) ($hostToken['key'] ?? ''));
            $htValue = (string) ($hostToken['value'] ?? '');
            if ($htKey !== '' && $htValue !== '') {
                $hostTokensXml .= "\n                <HostToken xmlns=\"{$comNs}\" Key=\"{$htKey}\">{$htValue}</HostToken>";
            }
        }

        $taxesXml = (string) ($pricingData['taxes_xml'] ?? '');

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <AirCreateReservationReq
            xmlns="{$uniNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            ProviderCode="{$providerCode}"
            RetainReservation="Both">

            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
            {$travelersXml}

            <AirPricingSolution
                xmlns="{$airNs}"
                Key="{$solutionKey}"
                TotalPrice="{$totalPrice}"
                BasePrice="{$basePrice}"
                Taxes="{$taxes}">
                {$segmentsXml}

                <AirPricingInfo
                    Key="{$pricingInfoKey}"
                    TotalPrice="{$totalPrice}"
                    BasePrice="{$basePrice}"
                    Taxes="{$taxes}"
                    PricingMethod="{$pricingMethod}"
                    ProviderCode="{$providerCode}">
                    {$fareInfosXml}
                    {$bookingInfosXml}
                    {$taxesXml}{$passengerTypesXml}
                </AirPricingInfo>
                {$hostTokensXml}

            </AirPricingSolution>

            <ActionStatus xmlns="{$comNs}" Type="ACTIVE" TicketDate="T*" ProviderCode="{$providerCode}"/>

        </AirCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airTicket(string $airReservationLocator, string $platingCarrier): array
    {
        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;
        $locator = $this->xmlEsc($airReservationLocator);
        $carrier = $this->xmlEsc($platingCarrier);

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirTicketingReq
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}"
            TargetBranch="{$targetBranch}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            ReturnInfoOnFail="true"
            BulkTicket="false">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirReservationLocatorCode>{$locator}</air:AirReservationLocatorCode>
            <air:AirTicketingModifiers PlatingCarrier="{$carrier}"/>
        </air:AirTicketingReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airCancel(string $universalLocator, string $version = '0'): array
    {
        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $uniNs = self::UNI_NS;
        $comNs = self::COM_NS;
        $locator = $this->xmlEsc($universalLocator);
        $versionEsc = $this->xmlEsc($version);

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <UniversalRecordCancelReq
            xmlns="{$uniNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            UniversalRecordLocatorCode="{$locator}"
            Version="{$versionEsc}">
            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
        </UniversalRecordCancelReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('UniversalRecordService', $soap);
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchAirLegsXml(array $searchData): string
    {
        $tripType = (string) ($searchData['trip_type'] ?? 'one_way');
        $legs = [];

        if ($tripType === 'multi_city') {
            foreach ($searchData['segments'] ?? [] as $segment) {
                $from = strtoupper(trim((string) ($segment['from'] ?? '')));
                $to = strtoupper(trim((string) ($segment['to'] ?? '')));
                $date = trim((string) ($segment['departure_date'] ?? ''));

                if ($from !== '' && $to !== '' && $date !== '') {
                    $legs[] = ['from' => $from, 'to' => $to, 'date' => $date];
                }
            }
        } else {
            $from = strtoupper(trim((string) ($searchData['from'] ?? '')));
            $to = strtoupper(trim((string) ($searchData['to'] ?? '')));
            $departure = trim((string) ($searchData['departure_date'] ?? ''));

            if ($from !== '' && $to !== '' && $departure !== '') {
                $legs[] = ['from' => $from, 'to' => $to, 'date' => $departure];
            }

            if ($tripType === 'round_trip') {
                $returnDate = trim((string) ($searchData['return_date'] ?? ''));
                if ($returnDate !== '') {
                    $legs[] = ['from' => $to, 'to' => $from, 'date' => $returnDate];
                }
            }
        }

        $xml = '';
        foreach ($legs as $leg) {
            $xml .= <<<XML

            <air:SearchAirLeg>
                <air:SearchOrigin>
                    <com:CityOrAirport Code="{$leg['from']}"/>
                </air:SearchOrigin>
                <air:SearchDestination>
                    <com:CityOrAirport Code="{$leg['to']}"/>
                </air:SearchDestination>
                <air:SearchDepTime PreferredTime="{$leg['date']}"/>
            </air:SearchAirLeg>
XML;
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchPassengersXml(array $searchData): string
    {
        $xml = '';
        $adults = max(1, (int) ($searchData['adults'] ?? 1));
        $children = max(0, (int) ($searchData['children'] ?? 0));
        $infants = max(0, (int) ($searchData['infants'] ?? 0));

        for ($i = 0; $i < $adults; $i++) {
            $xml .= "\n            <com:SearchPassenger Code=\"ADT\"/>";
        }
        for ($i = 0; $i < $children; $i++) {
            $xml .= "\n            <com:SearchPassenger Code=\"CNN\"/>";
        }
        for ($i = 0; $i < $infants; $i++) {
            $xml .= "\n            <com:SearchPassenger Code=\"INF\"/>";
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchModifiersXml(array $searchData): string
    {
        $provider = htmlspecialchars(self::PROVIDER_CODE, ENT_XML1);
        $direct = !empty($searchData['direct_flight']);
        $maxStops = $direct ? 0 : 2;
        $cabin = FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy');
        $cabinCode = $this->travelportCabinCode($cabin);

        $cabinXml = $cabinCode !== ''
            ? "<air:PermittedCabins><com:CabinClass Type=\"{$cabinCode}\"/></air:PermittedCabins>"
            : '';

        return <<<XML

            <air:AirSearchModifiers MaxStops="{$maxStops}">
                <air:PreferredProviders>
                    <com:Provider Code="{$provider}"/>
                </air:PreferredProviders>
                {$cabinXml}
            </air:AirSearchModifiers>
XML;
    }

    private function travelportCabinCode(string $cabin): string
    {
        return match ($cabin) {
            'Premium Economy' => 'PremiumEconomy',
            'Business' => 'Business',
            'First' => 'First',
            default => 'Economy',
        };
    }

    /**
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    private function sendRequest(string $service, string $soapBody): array
    {
        $endpoint = self::BASE_ENDPOINT . '/' . $service;
        $base64Creds = base64_encode(trim(self::USERNAME) . ':' . trim(self::PASSWORD));

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $soapBody,
            CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml;charset=UTF-8',
                'Authorization: Basic ' . $base64Creds,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => "cURL Error: {$error}",
            ];
        }

        if ($httpCode !== 200) {
            return [
                'success' => false,
                'httpCode' => $httpCode,
                'raw' => is_string($response) ? $response : '',
                'parsed' => null,
                'error' => "HTTP Error ({$httpCode})",
            ];
        }

        $parsed = $this->parseXmlResponse(is_string($response) ? $response : '');
        $isFault = isset($parsed['Body']['Fault']);

        return [
            'success' => !$isFault,
            'httpCode' => $httpCode,
            'raw' => is_string($response) ? $response : '',
            'parsed' => $parsed,
            'error' => $isFault ? ($parsed['Body']['Fault']['faultstring'] ?? 'SOAP Fault') : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseXmlResponse(string $xmlString): ?array
    {
        $cleanXml = str_ireplace(
            ['soapenv:', 'SOAP:', 'soap:', 'air:', 'com:', 'universal:', 'common_v52_0:'],
            '',
            $xmlString
        );

        $xml = @simplexml_load_string($cleanXml);
        if ($xml === false) {
            return null;
        }

        return json_decode(json_encode($xml), true);
    }

    private function generateTraceId(): string
    {
        return 'trace_' . uniqid('', true);
    }

    private function normalizeTravelportTime(string $time): string
    {
        if ($time === '') {
            return $time;
        }

        return (string) preg_replace('/\.\d{3}(\+|-)/', '$1', $time);
    }

    private function xmlEsc(string $value, bool $escapeQuotes = true): string
    {
        $flags = ENT_XML1 | ($escapeQuotes ? ENT_QUOTES : 0);

        return htmlspecialchars($value, $flags);
    }
}
