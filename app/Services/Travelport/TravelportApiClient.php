<?php

namespace App\Services\Travelport;

use App\Support\FlightCabinPreference;
use App\Support\Travelport\TravelportAirTicketingResult;
use App\Support\Travelport\TravelportContactSsrBuilder;
use App\Support\Travelport\TravelportDocsSsrBuilder;
use App\Support\Travelport\TravelportHoldPayloadBuilder;

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
    public function lowFareSearch(
        array $searchData,
        bool $returnBrandedFares = true,
        bool $returnUpsellFare = true,
    ): array {
        $searchData = TravelportHoldPayloadBuilder::ensureChildAgesInSearchData($searchData);
        $traceId = $this->generateTraceId();
        $legsXml = $this->buildSearchAirLegsXml($searchData);
        $passengersXml = $this->buildSearchPassengersXml($searchData);
        $modifiersXml = $this->buildSearchModifiersXml($searchData);

        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;
        $brandedAttr = $returnBrandedFares ? 'true' : 'false';
        $upsellAttr = $returnUpsellFare ? 'true' : 'false';

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
            ReturnBrandedFares="{$brandedAttr}"
            ReturnUpsellFare="{$upsellAttr}"
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
    /**
     * @param  list<array<string, mixed>>  $travelers  When provided, SearchPassenger order matches hold travelers.
     */
    public function airPrice(
        array $segments,
        array $passengerCounts,
        array $searchData = [],
        array $travelers = [],
    ): array {
        $searchData = TravelportHoldPayloadBuilder::ensureChildAgesInSearchData($searchData);

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

        $airSegmentsXml = $this->buildAirPriceSegmentsXml($segments);

        $passengersXml = $travelers !== []
            ? $this->buildSearchPassengersXmlFromTravelers($travelers)
            : $this->buildSearchPassengersXmlFromCounts($passengerCounts, $searchData);

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
     * Return all filed branded fares (e.g. ECO SAVER / FLEX / FLEXPLUS) for specific segments.
     *
     * @param  list<array<string, mixed>>  $segments
     * @param  array<string, int>  $passengerCounts
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airPriceFareFamily(array $segments, array $passengerCounts, array $searchData = []): array
    {
        $searchData = TravelportHoldPayloadBuilder::ensureChildAgesInSearchData($searchData);

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

        $airSegmentsXml = $this->buildAirPriceSegmentsXml($segments, true);

        $passengersXml = $this->buildSearchPassengersXmlFromCounts($passengerCounts, $searchData);

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
            <air:AirPricingModifiers ETicketability="Required" FaresIndicator="PublicFaresOnly">
                <air:BrandModifiers>
                    <air:FareFamilyDisplay ModifierType="FareFamily"/>
                </air:BrandModifiers>
            </air:AirPricingModifiers>
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

        $travelerAgeByRef = [];
        foreach ($travelers as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $travelerKey = (string) ($traveler['key'] ?? '');
            if ($travelerKey !== '' && array_key_exists('age', $traveler) && $traveler['age'] !== null && $traveler['age'] !== '') {
                $travelerAgeByRef[$travelerKey] = (int) $traveler['age'];
            }
        }

        $travelersXml = '';
        $contactSsrsAdded = false;
        $contactCarrier = TravelportContactSsrBuilder::resolveCarrierFromPricingData($pricingData);
        foreach ($travelers as $traveler) {
            $key = $this->xmlEsc((string) ($traveler['key'] ?? ''));
            $type = $this->xmlEsc(TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT'),
            ));
            $dob = $this->xmlEsc($this->normalizeTravelportDob((string) ($traveler['dob'] ?? '')));
            $ageAttr = '';
            $rawKey = (string) ($traveler['key'] ?? '');
            if (in_array($type, ['CNN', 'INF'], true) && isset($travelerAgeByRef[$rawKey])) {
                $ageAttr = ' Age="' . $travelerAgeByRef[$rawKey] . '"';
            }
            $gender = $this->xmlEsc((string) ($traveler['gender'] ?? 'M'));
            $first = $this->xmlEsc((string) ($traveler['firstName'] ?? ''));
            $last = $this->xmlEsc((string) ($traveler['lastName'] ?? ''));
            $country = $this->xmlEsc((string) ($traveler['phoneCountryCode'] ?? '971'));
            $area = $this->xmlEsc((string) ($traveler['phoneAreaCode'] ?? '50'));
            $number = $this->xmlEsc((string) ($traveler['phoneNumber'] ?? ''));
            $email = $this->xmlEsc((string) ($traveler['email'] ?? ''));

            $nameRemarksXml = '';
            foreach ($traveler['lap_infant_name_remarks'] ?? [] as $remark) {
                $remarkData = $this->xmlEsc((string) $remark);
                if ($remarkData !== '') {
                    $nameRemarksXml .= "\n                <NameRemark><RemarkData>{$remarkData}</RemarkData></NameRemark>";
                }
            }

            $infantRemark = (string) ($traveler['name_remark'] ?? '');
            if ($infantRemark !== '') {
                $nameRemarksXml .= "\n                <NameRemark><RemarkData>{$this->xmlEsc($infantRemark)}</RemarkData></NameRemark>";
            }

            $ssrsXml = '';
            $ssrs = [];

            if (! $contactSsrsAdded && $type === 'ADT') {
                foreach (TravelportContactSsrBuilder::contactSsrs(
                    (string) ($traveler['phoneCountryCode'] ?? ''),
                    (string) ($traveler['phoneAreaCode'] ?? ''),
                    (string) ($traveler['phoneNumber'] ?? ''),
                    (string) ($traveler['email'] ?? ''),
                ) as $ssr) {
                    $ssrs[] = $ssr;
                }

                if ($ssrs !== []) {
                    $contactSsrsAdded = true;
                }
            }

            $docsSsr = TravelportDocsSsrBuilder::docsSsr($traveler);
            if ($docsSsr !== null) {
                $ssrs[] = $docsSsr;
            }

            foreach ($ssrs as $ssr) {
                $ssrType = $this->xmlEsc((string) ($ssr['type'] ?? ''));
                $freeText = $this->xmlEsc((string) ($ssr['free_text'] ?? ''));
                if ($ssrType === '' || $freeText === '') {
                    continue;
                }

                $ssrsXml .= "\n                <SSR Type=\"{$ssrType}\" Status=\"HK\" FreeText=\"{$freeText}\" Carrier=\"{$this->xmlEsc($contactCarrier)}\"/>";
            }

            $travelersXml .= <<<XML

            <BookingTraveler
                xmlns="{$comNs}"
                Key="{$key}"
                TravelerType="{$type}"
                DOB="{$dob}"{$ageAttr}
                Gender="{$gender}">
                <BookingTravelerName First="{$first}" Last="{$last}"/>
                <PhoneNumber CountryCode="{$country}" AreaCode="{$area}" Number="{$number}"/>
                <Email EmailID="{$email}"/>{$ssrsXml}{$nameRemarksXml}
            </BookingTraveler>
XML;
        }

        $pricingSolutionXml = $this->buildAirPricingSolutionXml(
            $pricingData,
            $providerCode,
            $solutionKey,
            $totalPrice,
            $basePrice,
            $taxes,
            $pricingInfoKey,
            $pricingMethod,
            $travelerAgeByRef,
            $airNs,
            $comNs,
        );

        $fopXml = $this->buildFormOfPaymentXml($comNs, 'FOP1', '            ');

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
            {$fopXml}
            {$pricingSolutionXml}
            <ActionStatus xmlns="{$comNs}" Type="ACTIVE" TicketDate="T*" ProviderCode="{$providerCode}"/>

        </AirCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * Store priced fares on an existing universal record (PNR already held).
     *
     * @param  list<array<string, mixed>>  $travelers
     * @param  array<string, mixed>  $pricingData
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function storeFaresOnUniversalRecord(
        string $universalLocator,
        string $version,
        array $pricingData,
        array $travelers = [],
    ): array {
        $universalLocator = trim($universalLocator);
        if ($universalLocator === '' || ($pricingData['solution_key'] ?? '') === '') {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => 'Missing universal locator or pricing solution for fare storage.',
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
        $locator = $this->xmlEsc($universalLocator);
        $versionEsc = $this->xmlEsc($version !== '' ? $version : '0');

        $travelerAgeByRef = [];
        foreach ($travelers as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }
            $travelerKey = (string) ($traveler['key'] ?? '');
            if ($travelerKey !== '' && array_key_exists('age', $traveler) && $traveler['age'] !== null && $traveler['age'] !== '') {
                $travelerAgeByRef[$travelerKey] = (int) $traveler['age'];
            }
        }

        $pricingSolutionXml = $this->buildAirPricingSolutionXml(
            $pricingData,
            $providerCode,
            $solutionKey,
            $totalPrice,
            $basePrice,
            $taxes,
            $pricingInfoKey,
            $pricingMethod,
            $travelerAgeByRef,
            $airNs,
            $comNs,
            segmentStatus: 'HK',
        );

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
            UniversalRecordLocatorCode="{$locator}"
            Version="{$versionEsc}"
            RetainReservation="Both">

            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
            {$pricingSolutionXml}
            <ActionStatus xmlns="{$comNs}" Type="ACTIVE" TicketDate="T*" ProviderCode="{$providerCode}"/>

        </AirCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @param  list<string>  $airPricingInfoKeys
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string, error_code?: ?string, trace_id?: ?string}
     */
    public function airTicket(
        string $airReservationLocator,
        array $airPricingInfoKeys = [],
        string $platingCarrier = '',
        float $commissionPercentage = 0.0,
    ): array {
        $soap = $this->buildAirTicketRequestXml(
            $airReservationLocator,
            $airPricingInfoKeys,
            $platingCarrier,
            $commissionPercentage,
            $this->generateTraceId(),
        );

        $result = $this->sendRequest('AirService', $soap);

        return $this->finalizeAirTicketingResult($result);
    }

    /**
     * @param  list<string>  $airPricingInfoKeys
     */
    public function buildAirTicketRequestXml(
        string $airReservationLocator,
        array $airPricingInfoKeys,
        string $platingCarrier,
        float $commissionPercentage,
        string $traceId,
    ): string {
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;
        $locator = $this->xmlEsc($airReservationLocator);
        $carrier = $this->xmlEsc($platingCarrier);
        $commissionPct = $this->xmlEsc($this->formatTravelportCommissionPercentage($commissionPercentage));

        $uniqueKeys = [];
        foreach ($airPricingInfoKeys as $key) {
            $normalized = trim((string) $key);
            if ($normalized !== '' && ! in_array($normalized, $uniqueKeys, true)) {
                $uniqueKeys[] = $normalized;
            }
        }

        $pricingInfoRefXml = '';
        $modifierPricingRefsXml = '';
        foreach ($uniqueKeys as $key) {
            $escapedKey = $this->xmlEsc($key);
            if (count($uniqueKeys) === 1) {
                $pricingInfoRefXml = "\n            <AirPricingInfoRef Key=\"{$escapedKey}\"/>";
                break;
            }

            $modifierPricingRefsXml .= "\n                <AirPricingInfoRef Key=\"{$escapedKey}\"/>";
        }

        $platingCarrierAttr = $carrier !== '' ? " PlatingCarrier=\"{$carrier}\"" : '';
        $fopXml = $this->buildFormOfPaymentXml($comNs, 'FOP1', '                ');
        $modifiersXml = <<<XML

            <AirTicketingModifiers{$platingCarrierAttr}>{$modifierPricingRefsXml}
                <Commission xmlns="{$comNs}" Level="Fare" Type="PercentBase" Percentage="{$commissionPct}"/>
                {$fopXml}
            </AirTicketingModifiers>
XML;

        return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <AirTicketingReq
            xmlns="{$airNs}"
            TargetBranch="{$targetBranch}"
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            ReturnInfoOnFail="true"
            BulkTicket="false">
            <BillingPointOfSaleInfo xmlns="{$comNs}" OriginApplication="UAPI"/>
            <AirReservationLocatorCode>{$locator}</AirReservationLocatorCode>{$pricingInfoRefXml}{$modifiersXml}
        </AirTicketingReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;
    }

    /**
     * Retrieve issued ticket document(s) — SOAP equivalent of REST GET /air/ticket/tickets/{id}.
     *
     * @param  list<string>  $ticketNumbers
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function airRetrieveDocument(
        string $providerLocator,
        array $ticketNumbers = [],
        ?string $airReservationLocator = null,
    ): array {
        $providerLocator = trim($providerLocator);
        $airReservationLocator = trim((string) $airReservationLocator);

        if ($providerLocator === '' && $airReservationLocator === '' && $ticketNumbers === []) {
            return [
                'success' => false,
                'httpCode' => 0,
                'raw' => '',
                'parsed' => null,
                'error' => 'Missing locator or ticket number for document retrieve.',
            ];
        }

        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $airNs = self::AIR_NS;
        $comNs = self::COM_NS;
        $providerCode = self::PROVIDER_CODE;

        $ticketNumbersXml = '';
        foreach ($ticketNumbers as $number) {
            $ticket = $this->xmlEsc(preg_replace('/\D+/', '', (string) $number) ?? '');
            if ($ticket === '') {
                continue;
            }

            $ticketNumbersXml .= "\n            <TicketNumber xmlns=\"{$comNs}\">{$ticket}</TicketNumber>";
        }

        $locatorAttrs = '';
        if ($providerLocator !== '') {
            $locatorAttrs .= ' ProviderLocatorCode="' . $this->xmlEsc($providerLocator) . '"';
        }

        if ($airReservationLocator !== '') {
            $resLocator = $this->xmlEsc($airReservationLocator);
            $resLocatorXml = "\n            <AirReservationLocatorCode>{$resLocator}</AirReservationLocatorCode>";
        } else {
            $resLocatorXml = '';
        }

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirRetrieveDocumentReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            ProviderCode="{$providerCode}"
            ReturnPricing="true"{$locatorAttrs}
            xmlns:air="{$airNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>{$ticketNumbersXml}{$resLocatorXml}
        </air:AirRetrieveDocumentReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function finalizeAirTicketingResult(array $result): array
    {
        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $parsed = is_array($result['parsed'] ?? null) ? $result['parsed'] : [];
        $ticketingRsp = $parsed['Body']['AirTicketingRsp'] ?? [];
        if (! is_array($ticketingRsp)) {
            $ticketingRsp = [];
        }

        if (TravelportAirTicketingResult::hasFailure($ticketingRsp)) {
            $error = TravelportAirTicketingResult::failureMessage($ticketingRsp);
            $warnings = TravelportAirTicketingResult::warningMessages($ticketingRsp);
            if ($warnings !== []) {
                $error .= ' Warning: ' . implode(' ', $warnings);
            }

            return array_merge($result, [
                'success' => false,
                'error' => $error,
                'error_code' => data_get($ticketingRsp, 'TicketFailureInfo.@attributes.Code')
                    ?? data_get($ticketingRsp, 'TicketFailureInfo.Code'),
            ]);
        }

        return $result;
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
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function universalRecordRetrieve(string $universalLocator): array
    {
        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $uniNs = self::UNI_NS;
        $comNs = self::COM_NS;
        $locator = $this->xmlEsc($universalLocator);

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordRetrieveReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            xmlns:universal="{$uniNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:UniversalRecordLocatorCode>{$locator}</universal:UniversalRecordLocatorCode>
        </universal:UniversalRecordRetrieveReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('UniversalRecordService', $soap);
    }

    /**
     * @return array{success: bool, httpCode: int, raw: string, parsed: ?array, error: ?string}
     */
    public function universalRecordRetrieveByProvider(
        string $providerLocator,
        string $travelerLastName,
        string $providerCode = self::PROVIDER_CODE,
    ): array {
        $traceId = $this->generateTraceId();
        $authorizedBy = self::AUTHORIZED_BY;
        $targetBranch = self::TARGET_BRANCH;
        $uniNs = self::UNI_NS;
        $comNs = self::COM_NS;
        $locator = $this->xmlEsc($providerLocator);
        $lastName = $this->xmlEsc($travelerLastName);
        $provider = $this->xmlEsc($providerCode);

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordRetrieveReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
            TravelerLastName="{$lastName}"
            xmlns:universal="{$uniNs}"
            xmlns:com="{$comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:ProviderReservationInfo ProviderCode="{$provider}" ProviderLocatorCode="{$locator}"/>
        </universal:UniversalRecordRetrieveReq>
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
     * LFS and Air Price both require age-qualified child/infant passengers for discounted fares.
     *
     * @see https://support.travelport.com/webhelp/uapi/Content/Air/Low_Fare_Shopping/Low_Fare_Shopping_Air_Price_Modifiers.htm
     *
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchPassengersXml(array $searchData): string
    {
        return $this->buildSearchPassengersXmlFromCounts(
            TravelportHoldPayloadBuilder::passengerCounts($searchData),
            $searchData,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $travelers
     */
    private function buildSearchPassengersXmlFromTravelers(array $travelers): string
    {
        $xml = '';

        foreach ($travelers as $traveler) {
            if (! is_array($traveler)) {
                continue;
            }

            $ref = $this->xmlEsc((string) ($traveler['key'] ?? ''));
            if ($ref === '') {
                continue;
            }

            $code = $this->xmlEsc(TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($traveler['traveler_type'] ?? $traveler['traveler_type_code'] ?? 'ADT'),
            ));
            $attrs = [
                'BookingTravelerRef="' . $ref . '"',
                'Code="' . $code . '"',
            ];

            if (in_array($code, ['CNN', 'INF'], true)
                && array_key_exists('age', $traveler)
                && $traveler['age'] !== null
                && $traveler['age'] !== '') {
                $attrs[] = 'Age="' . (int) $traveler['age'] . '"';
            }

            $xml .= "\n            <com:SearchPassenger " . implode(' ', $attrs) . '/>';
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $pricingData
     * @param  array<string, int>  $travelerAgeByRef
     */
    private function buildAirPricingSolutionXml(
        array $pricingData,
        string $providerCode,
        string $solutionKey,
        string $totalPrice,
        string $basePrice,
        string $taxes,
        string $pricingInfoKey,
        string $pricingMethod,
        array $travelerAgeByRef,
        string $airNs,
        string $comNs,
        string $segmentStatus = 'NN',
    ): string {
        $segmentStatus = strtoupper(trim($segmentStatus));
        if (! in_array($segmentStatus, ['NN', 'HK'], true)) {
            $segmentStatus = 'NN';
        }
        $segmentStatusEsc = $this->xmlEsc($segmentStatus);
        $segmentsXml = '';
        $seenSegmentKeys = [];
        foreach ($pricingData['segments'] ?? [] as $segment) {
            if (! is_array($segment)) {
                continue;
            }
            $segKey = (string) ($segment['key'] ?? '');
            if ($segKey === '' || isset($seenSegmentKeys[$segKey])) {
                continue;
            }
            $seenSegmentKeys[$segKey] = true;
            $segKey = $this->xmlEsc($segKey);
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
            $equipment = trim((string) ($segment['equipment'] ?? ''));
            $equipmentAttr = $equipment !== '' ? ' Equipment="' . $this->xmlEsc($equipment) . '"' : '';

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
                    ClassOfService="{$bookingCode}"{$equipmentAttr}
                    Status="{$segmentStatusEsc}"/>
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
            $paxType = $this->xmlEsc(TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($fareInfo['passenger_type_code'] ?? 'ADT'),
            ));
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
            $code = $this->xmlEsc(TravelportHoldPayloadBuilder::normalizeHoldPassengerTypeCode(
                (string) ($passengerType['code'] ?? 'ADT'),
            ));
            $ref = $this->xmlEsc((string) ($passengerType['traveler_ref'] ?? ''));
            $rawRef = (string) ($passengerType['traveler_ref'] ?? '');
            $ageAttr = '';
            if (in_array($code, ['CNN', 'INF'], true) && isset($travelerAgeByRef[$rawRef])) {
                $ageAttr = ' Age="' . $travelerAgeByRef[$rawRef] . '"';
            }
            $passengerTypesXml .= "\n                    <PassengerType Code=\"{$code}\" BookingTravelerRef=\"{$ref}\"{$ageAttr}/>";
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

        return <<<XML
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
XML;
    }

    /**
     * @param  array<string, int>  $passengerCounts
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchPassengersXmlFromCounts(
        array $passengerCounts,
        array $searchData = [],
    ): string {
        $xml = '';
        $travelerIdx = 1;
        $childIdx = 0;
        $infantIdx = 0;

        foreach (['ADT', 'CNN', 'INF'] as $code) {
            $count = max(0, (int) ($passengerCounts[$code] ?? 0));

            for ($i = 0; $i < $count; $i++) {
                $ref = 'traveler_' . $travelerIdx;
                $attrs = [
                    'BookingTravelerRef="' . $ref . '"',
                ];

                if ($code === 'CNN') {
                    $attrs[] = 'Code="CNN"';
                    $attrs[] = 'Age="' . $this->childAgeForIndex($searchData, $childIdx) . '"';
                    $childIdx++;
                } elseif ($code === 'INF') {
                    $attrs[] = 'Code="INF"';
                    $attrs[] = 'Age="' . $this->infantAgeForIndex($searchData, $infantIdx) . '"';
                    $infantIdx++;
                } else {
                    $attrs[] = 'Code="ADT"';
                }

                $xml .= "\n            <com:SearchPassenger " . implode(' ', $attrs) . '/>';
                $travelerIdx++;
            }
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function childAgeForIndex(array $searchData, int $childIndex): int
    {
        $ages = $searchData['child_ages'] ?? null;
        if (is_array($ages) && array_key_exists($childIndex, $ages)) {
            return max(2, min(11, (int) $ages[$childIndex]));
        }

        return max(2, min(11, (int) ($searchData['child_age'] ?? 8)));
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function infantAgeForIndex(array $searchData, int $infantIndex): int
    {
        $ages = $searchData['infant_ages'] ?? null;
        if (is_array($ages) && array_key_exists($infantIndex, $ages)) {
            return max(0, min(1, (int) $ages[$infantIndex]));
        }

        $fallback = (int) ($searchData['infant_age'] ?? 1);

        return max(0, min(1, $fallback));
    }

    /**
     * @param  array<string, mixed>  $searchData
     */
    private function buildSearchModifiersXml(array $searchData): string
    {
        $provider = htmlspecialchars(self::PROVIDER_CODE, ENT_XML1);
        $direct = ! empty($searchData['direct_flight']);
        $cabin = FlightCabinPreference::normalizeUiLabel($searchData['onward_cabin_class'] ?? 'Economy');
        $cabinCode = $this->travelportCabinCode($cabin);

        $cabinXml = $cabinCode !== ''
            ? "<air:PermittedCabins><com:CabinClass Type=\"{$cabinCode}\"/></air:PermittedCabins>"
            : '';

        $flightTypeXml = $direct
            ? '<air:FlightType NonStopDirects="true"/>'
            : '<air:FlightType MaxConnections="1"/>';

        return <<<XML

            <air:AirSearchModifiers>
                <air:PreferredProviders>
                    <com:Provider Code="{$provider}"/>
                </air:PreferredProviders>
                {$cabinXml}
                {$flightTypeXml}
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
        $faultMeta = $isFault
            ? $this->parseSoapFault($parsed, is_string($response) ? $response : '')
            : [
                'message' => null,
                'code' => null,
                'trace_id' => null,
                'description' => null,
                'segment_errors' => [],
            ];

        return [
            'success' => !$isFault,
            'httpCode' => $httpCode,
            'raw' => is_string($response) ? $response : '',
            'parsed' => $parsed,
            'error' => $isFault ? ($faultMeta['message'] ?? 'SOAP Fault') : null,
            'error_code' => $faultMeta['code'] ?? null,
            'trace_id' => $faultMeta['trace_id'] ?? null,
            'error_details' => $isFault ? [
                'description' => $faultMeta['description'] ?? null,
                'segment_errors' => $faultMeta['segment_errors'] ?? [],
            ] : null,
        ];
    }

    /**
     * @return array{
     *     message: ?string,
     *     code: ?string,
     *     trace_id: ?string,
     *     description: ?string,
     *     segment_errors: list<string>
     * }
     */
    private function parseSoapFault(?array $parsed, string $raw): array
    {
        $message = $parsed['Body']['Fault']['faultstring'] ?? 'SOAP Fault';
        $code = data_get($parsed, 'Body.Fault.detail.ErrorInfo.Code')
            ?? data_get($parsed, 'Body.Fault.detail.AvailabilityErrorInfo.Code')
            ?? data_get($parsed, 'Body.Fault.detail.common_v52_0:ErrorInfo.common_v52_0:Code');
        $traceId = data_get($parsed, 'Body.Fault.detail.ErrorInfo.TraceId')
            ?? data_get($parsed, 'Body.Fault.detail.AvailabilityErrorInfo.TraceId')
            ?? data_get($parsed, 'Body.Fault.detail.common_v52_0:ErrorInfo.common_v52_0:TraceId');
        $description = data_get($parsed, 'Body.Fault.detail.ErrorInfo.Description')
            ?? data_get($parsed, 'Body.Fault.detail.AvailabilityErrorInfo.Description');

        if (! $code && preg_match('/<(?:common_v52_0:)?Code>(\d+)<\/(?:common_v52_0:)?Code>/i', $raw, $m)) {
            $code = $m[1];
        }
        if (! $traceId && preg_match('/<(?:common_v52_0:)?TraceId>([^<]+)<\/(?:common_v52_0:)?TraceId>/i', $raw, $m)) {
            $traceId = trim($m[1]);
        }
        if (! is_string($description) || trim($description) === '') {
            if (preg_match('/<(?:common_v52_0:)?Description>([^<]+)<\/(?:common_v52_0:)?Description>/i', $raw, $m)) {
                $description = trim($m[1]);
            }
        }

        $segmentErrors = [];
        if (preg_match_all(
            '/<(?:air:)?ErrorMessage>([^<]+)<\/(?:air:)?ErrorMessage>/i',
            $raw,
            $segmentMatches,
        )) {
            foreach ($segmentMatches[1] as $segmentError) {
                $segmentError = trim(html_entity_decode((string) $segmentError, ENT_QUOTES | ENT_XML1));
                if ($segmentError !== '') {
                    $segmentErrors[] = $segmentError;
                }
            }
        }

        return [
            'message' => is_string($message) ? $message : 'SOAP Fault',
            'code' => is_string($code) ? $code : null,
            'trace_id' => is_string($traceId) ? $traceId : null,
            'description' => is_string($description) ? trim($description) : null,
            'segment_errors' => array_values(array_unique($segmentErrors)),
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

    private function normalizeTravelportDob(string $dob): string
    {
        $dob = trim($dob);
        if ($dob === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($dob)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $dob;
        }
    }

    private function normalizeTravelportTime(string $time): string
    {
        if ($time === '') {
            return $time;
        }

        return (string) preg_replace('/\.\d{3}(\+|-)/', '$1', $time);
    }

    private function formatTravelportCommissionPercentage(float $percentage): string
    {
        $percentage = max(0.0, $percentage);

        if (abs($percentage - round($percentage)) < 0.00001) {
            return (string) (int) round($percentage);
        }

        return number_format($percentage, 2, '.', '');
    }

    /**
     * @return array{type: string, card_type: string, card_number: string, card_exp: string, card_cvv: string, card_holder: string}
     */
    private function formOfPaymentConfig(): array
    {
        $type = strtolower(trim((string) config('services.travelport.fop_type', 'Credit')));

        return [
            'type' => $type === 'check' ? 'Check' : 'Credit',
            'card_type' => trim((string) config('services.travelport.card_type', 'VI')),
            'card_number' => trim((string) config('services.travelport.card_number', '4111111111111111')),
            'card_exp' => trim((string) config('services.travelport.card_exp', '2028-01')),
            'card_cvv' => trim((string) config('services.travelport.card_cvv', '123')),
            'card_holder' => trim((string) config('services.travelport.card_holder', 'Andaleeb Travel Agency')),
        ];
    }

    private function buildFormOfPaymentXml(string $comNs, string $key, string $indent): string
    {
        $config = $this->formOfPaymentConfig();
        $fopKey = $this->xmlEsc($key);

        if ($config['type'] === 'Check') {
            return <<<XML

{$indent}<FormOfPayment xmlns="{$comNs}" Key="{$fopKey}" Type="Check"/>
XML;
        }

        $cardType = $this->xmlEsc($config['card_type']);
        $cardNumber = $this->xmlEsc($config['card_number']);
        $cardExp = $this->xmlEsc($config['card_exp']);
        $cardCvv = $this->xmlEsc($config['card_cvv']);
        $cardHolder = $this->xmlEsc($config['card_holder']);

        return <<<XML

{$indent}<FormOfPayment xmlns="{$comNs}" Key="{$fopKey}" Type="Credit">
{$indent}    <CreditCard Type="{$cardType}" Number="{$cardNumber}" ExpDate="{$cardExp}" CVV="{$cardCvv}" Name="{$cardHolder}"/>
{$indent}</FormOfPayment>
XML;
    }

    /**
     * @param  list<array<string, mixed>>  $segments
     */
    private function buildAirPriceSegmentsXml(array $segments, bool $normalizeTimes = false): string
    {
        $airSegmentsXml = '';
        $seenKeys = [];
        foreach ($segments as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $key = (string) ($seg['Key'] ?? '');
            if ($key === '' || isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            $key = $this->xmlEsc($key);
            $group = $this->xmlEsc((string) ($seg['Group'] ?? '0'));
            $provider = $this->xmlEsc((string) ($seg['ProviderCode'] ?? self::PROVIDER_CODE));
            $carrier = $this->xmlEsc((string) ($seg['Carrier'] ?? ''));
            $flightNumber = $this->xmlEsc((string) ($seg['FlightNumber'] ?? ''));
            $origin = $this->xmlEsc((string) ($seg['Origin'] ?? ''));
            $destination = $this->xmlEsc((string) ($seg['Destination'] ?? ''));
            $departureRaw = (string) ($seg['DepartureTime'] ?? '');
            $arrivalRaw = (string) ($seg['ArrivalTime'] ?? '');
            $departure = $this->xmlEsc($normalizeTimes ? $this->normalizeTravelportTime($departureRaw) : $departureRaw);
            $arrival = $this->xmlEsc($normalizeTimes ? $this->normalizeTravelportTime($arrivalRaw) : $arrivalRaw);
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

        return $airSegmentsXml;
    }

    private function xmlEsc(string $value, bool $escapeQuotes = true): string
    {
        $flags = ENT_XML1 | ($escapeQuotes ? ENT_QUOTES : 0);

        return htmlspecialchars($value, $flags);
    }
}
