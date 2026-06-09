<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class TravelportAPI
{
    private $username     = 'Universal API/uAPI3803196999-ff9da8ef';
    private $password     = 'sR-9}8Pjr+';
    private $targetBranch = 'P7250866';
    private $authorizedBy = 'Zeeshan';
    private $baseEndpoint = 'https://emea.universal-api.pp.travelport.com/B2BGateway/connect/uAPI';
    private $airNs        = 'http://www.travelport.com/schema/air_v52_0';
    private $comNs        = 'http://www.travelport.com/schema/common_v52_0';
    private $uniNs        = 'http://www.travelport.com/schema/universal_v52_0';


    // =========================================================================
    // 1. AIR SEARCH
    // =========================================================================
    public function airSearch(
        string $origin       = 'DXB',
        string $destination  = 'LHE',
        string $date         = '2026-05-25',
        string $passengerType = 'ADT'
    ): array {
        $traceId = $this->generateTraceId();

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            xmlns:air="{$this->airNs}"
            xmlns:com="{$this->comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:SearchAirLeg>
                <air:SearchOrigin>
                    <com:CityOrAirport Code="{$origin}"/>
                </air:SearchOrigin>
                <air:SearchDestination>
                    <com:CityOrAirport Code="{$destination}"/>
                </air:SearchDestination>
                <air:SearchDepTime PreferredTime="{$date}"/>
            </air:SearchAirLeg>
            <air:AirSearchModifiers>
                <air:PreferredProviders>
                    <com:Provider Code="1G"/>
                </air:PreferredProviders>
            </air:AirSearchModifiers>
            <com:SearchPassenger Code="{$passengerType}"/>
        </air:LowFareSearchReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }


    // =========================================================================
    // 2. AIR PRICE
    // =========================================================================
    public function airPrice(array $segments, string $passengerType = 'ADT'): array
    {
        $traceId = $this->generateTraceId();

        $airSegmentsXml = '';
        foreach ($segments as $seg) {
            $airSegmentsXml .= <<<XML

                    <air:AirSegment
                        Key="{$seg['Key']}"
                        Group="{$seg['Group']}"
                        ProviderCode="{$seg['ProviderCode']}"
                        Carrier="{$seg['Carrier']}"
                        FlightNumber="{$seg['FlightNumber']}"
                        Origin="{$seg['Origin']}"
                        Destination="{$seg['Destination']}"
                        DepartureTime="{$seg['DepartureTime']}"
                        ArrivalTime="{$seg['ArrivalTime']}"
                        ClassOfService="{$seg['ClassOfService']}"
                        Status="SS"
                        SeatAvail="Available"
                        ETicketability="Yes"
                        Equipment="{$seg['Equipment']}"/>
XML;
        }

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirPriceReq
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            xmlns:air="{$this->airNs}"
            xmlns:com="{$this->comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirItinerary>
                {$airSegmentsXml}
            </air:AirItinerary>
            <air:AirPricingModifiers
                ETicketability="Required"
                FaresIndicator="PublicFaresOnly"/>
            <com:SearchPassenger Code="{$passengerType}" BookingTravelerRef="traveler_1"/>
            <air:AirPricingCommand/>
        </air:AirPriceReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('AirService', $soap);
    }


    // =========================================================================
    // 3. AIR BOOK
    // Receives structured $pricingData extracted from the raw price response.
    // Builds a clean minimal AirCreateReservationReq — no XML injection.
    // =========================================================================

public function airBook(array $traveler, array $pd): array
{
    $traceId     = $this->generateTraceId();
    $travelerKey = 'traveler_1';

    $hostTokenXml = '';
    if (!empty($pd['host_token_key']) && !empty($pd['host_token_value'])) {
        $hostTokenXml = "<HostToken xmlns=\"{$this->comNs}\" Key=\"{$pd['host_token_key']}\">{$pd['host_token_value']}</HostToken>";
    }

    $depTime = preg_replace('/\.\d{3}(\+|-)/', '$1', $pd['dep_time']);
    $arrTime = preg_replace('/\.\d{3}(\+|-)/', '$1', $pd['arr_time']);

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <AirCreateReservationReq
            xmlns="{$this->uniNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            ProviderCode="{$pd['provider_code']}"
            RetainReservation="Both">

            <BillingPointOfSaleInfo xmlns="{$this->comNs}" OriginApplication="UAPI"/>

            <BookingTraveler
                xmlns="{$this->comNs}"
                Key="{$travelerKey}"
                TravelerType="ADT"
                DOB="{$traveler['dob']}"
                Gender="{$traveler['gender']}">
                <BookingTravelerName
                    First="{$traveler['firstName']}"
                    Last="{$traveler['lastName']}"/>
                <PhoneNumber
                    CountryCode="92"
                    AreaCode="300"
                    Number="{$traveler['phoneNumber']}"/>
                <Email EmailID="{$traveler['email']}"/>
            </BookingTraveler>

            <FormOfPayment xmlns="{$this->comNs}" Type="Credit" Key="1">
                <CreditCard Type="{$traveler['cardType']}" Number="{$traveler['cardNumber']}" ExpDate="{$traveler['expDate']}" CVV="{$traveler['cvv']}" Name="{$traveler['cardHolder']}"/>
            </FormOfPayment>

            <AirPricingSolution
                xmlns="{$this->airNs}"
                Key="{$pd['solution_key']}"
                TotalPrice="{$pd['total_price']}"
                BasePrice="{$pd['base_price']}"
                Taxes="{$pd['taxes']}">

                <AirSegment
                    Key="{$pd['segment_ref_key']}"
                    Group="0"
                    Carrier="{$pd['carrier']}"
                    FlightNumber="{$pd['flight_number']}"
                    ProviderCode="{$pd['provider_code']}"
                    Origin="{$pd['origin']}"
                    Destination="{$pd['destination']}"
                    DepartureTime="{$depTime}"
                    ArrivalTime="{$arrTime}"
                    FlightTime="{$pd['flight_time']}"
                    TravelTime="{$pd['travel_time']}"
                    ClassOfService="{$pd['booking_code']}"
                    Status="NN"/>

                <AirPricingInfo
                    Key="{$pd['pricing_info_key']}"
                    TotalPrice="{$pd['total_price']}"
                    BasePrice="{$pd['base_price']}"
                    Taxes="{$pd['taxes']}"
                    PricingMethod="{$pd['pricing_method']}"
                    ProviderCode="{$pd['provider_code']}">
                <FareInfo
                    Key="{$pd['fare_info_key']}"
                    FareBasis="{$pd['fare_basis']}"
                    PassengerTypeCode="ADT"
                    Origin="{$pd['fare_origin']}"
                    Destination="{$pd['fare_destination']}"
                    DepartureDate="{$pd['departure_date']}"
                    EffectiveDate="{$pd['effective_date']}">
                        <FareRuleKey FareInfoRef="{$pd['fare_info_key']}" ProviderCode="{$pd['provider_code']}">{$pd['fare_rule_key']}</FareRuleKey>
                    </FareInfo>
                    <BookingInfo
                        BookingCode="{$pd['booking_code']}"
                        CabinClass="{$pd['cabin_class']}"
                        FareInfoRef="{$pd['fare_info_key']}"
                        SegmentRef="{$pd['segment_ref']}"
                        HostTokenRef="{$pd['host_token_ref']}"/>
                    {$pd['taxes_xml']}
                    <PassengerType Code="ADT" BookingTravelerRef="{$travelerKey}"/>
                </AirPricingInfo>

                {$hostTokenXml}

            </AirPricingSolution>

            <ActionStatus xmlns="{$this->comNs}" Type="ACTIVE" TicketDate="T*" ProviderCode="{$pd['provider_code']}"/>
           
        </AirCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    file_put_contents('/tmp/travelport_book_request.xml', $soap);
    // return $this->sendRequest('UniversalRecordService', $soap);
    return $this->sendRequest('AirService', $soap);
}


    // =========================================================================
    // 4. AIR RETRIEVE
    // =========================================================================
    public function airRetrieve(string $universalLocatorCode): array
    {
        $traceId = $this->generateTraceId();

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordRetrieveReq
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            xmlns:universal="{$this->uniNs}"
            xmlns:com="{$this->comNs}">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:UniversalRecordLocatorCode>{$universalLocatorCode}</universal:UniversalRecordLocatorCode>
        </universal:UniversalRecordRetrieveReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

        return $this->sendRequest('UniversalRecordService', $soap);
    }


    // =========================================================================
    // 5. AIR TICKET
    // =========================================================================
public function airTicket(string $airReservationLocatorCode, array $pd): array
{
    $traceId = $this->generateTraceId();

$soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:AirTicketingReq
            xmlns:air="{$this->airNs}"
            xmlns:com="{$this->comNs}"
            TargetBranch="{$this->targetBranch}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            ReturnInfoOnFail="true"
            BulkTicket="false">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirReservationLocatorCode>{$airReservationLocatorCode}</air:AirReservationLocatorCode>
            <air:AirTicketingModifiers PlatingCarrier="{$pd['carrier']}"/>
        </air:AirTicketingReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    return $this->sendRequest('AirService', $soap);
}

   // AIR HOLD
   
   public function airHold(array $traveler, array $pd): array
{
    $traceId     = $this->generateTraceId();
    $travelerKey = 'traveler_1';

    $hostTokenXml = '';
    if (!empty($pd['host_token_key']) && !empty($pd['host_token_value'])) {
        $hostTokenXml = "<HostToken xmlns=\"{$this->comNs}\" Key=\"{$pd['host_token_key']}\">{$pd['host_token_value']}</HostToken>";
    }

    $depTime = preg_replace('/\.\d{3}(\+|-)/', '$1', $pd['dep_time']);
    $arrTime = preg_replace('/\.\d{3}(\+|-)/', '$1', $pd['arr_time']);

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <AirCreateReservationReq
            xmlns="{$this->uniNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            ProviderCode="{$pd['provider_code']}"
            RetainReservation="Both">

            <BillingPointOfSaleInfo xmlns="{$this->comNs}" OriginApplication="UAPI"/>

            <BookingTraveler
                xmlns="{$this->comNs}"
                Key="{$travelerKey}"
                TravelerType="ADT"
                DOB="{$traveler['dob']}"
                Gender="{$traveler['gender']}">
                <BookingTravelerName
                    First="{$traveler['firstName']}"
                    Last="{$traveler['lastName']}"/>
                <PhoneNumber
                    CountryCode="92"
                    AreaCode="300"
                    Number="{$traveler['phoneNumber']}"/>
                <Email EmailID="{$traveler['email']}"/>
            </BookingTraveler>

            <AirPricingSolution
                xmlns="{$this->airNs}"
                Key="{$pd['solution_key']}"
                TotalPrice="{$pd['total_price']}"
                BasePrice="{$pd['base_price']}"
                Taxes="{$pd['taxes']}">

                <AirSegment
                    Key="{$pd['segment_ref_key']}"
                    Group="0"
                    Carrier="{$pd['carrier']}"
                    FlightNumber="{$pd['flight_number']}"
                    ProviderCode="{$pd['provider_code']}"
                    Origin="{$pd['origin']}"
                    Destination="{$pd['destination']}"
                    DepartureTime="{$depTime}"
                    ArrivalTime="{$arrTime}"
                    FlightTime="{$pd['flight_time']}"
                    TravelTime="{$pd['travel_time']}"
                    ClassOfService="{$pd['booking_code']}"
                    Status="NN"/>

                <AirPricingInfo
                    Key="{$pd['pricing_info_key']}"
                    TotalPrice="{$pd['total_price']}"
                    BasePrice="{$pd['base_price']}"
                    Taxes="{$pd['taxes']}"
                    PricingMethod="{$pd['pricing_method']}"
                    ProviderCode="{$pd['provider_code']}">
                    <FareInfo
                        Key="{$pd['fare_info_key']}"
                        FareBasis="{$pd['fare_basis']}"
                        PassengerTypeCode="ADT"
                        Origin="{$pd['fare_origin']}"
                        Destination="{$pd['fare_destination']}"
                        DepartureDate="{$pd['departure_date']}"
                        EffectiveDate="{$pd['effective_date']}">
                        <FareRuleKey FareInfoRef="{$pd['fare_info_key']}" ProviderCode="{$pd['provider_code']}">{$pd['fare_rule_key']}</FareRuleKey>
                    </FareInfo>
                    <BookingInfo
                        BookingCode="{$pd['booking_code']}"
                        CabinClass="{$pd['cabin_class']}"
                        FareInfoRef="{$pd['fare_info_key']}"
                        SegmentRef="{$pd['segment_ref']}"
                        HostTokenRef="{$pd['host_token_ref']}"/>
                    {$pd['taxes_xml']}
                    <PassengerType Code="ADT" BookingTravelerRef="{$travelerKey}"/>
                </AirPricingInfo>

                {$hostTokenXml}

            </AirPricingSolution>

            <ActionStatus xmlns="{$this->comNs}" Type="ACTIVE" TicketDate="T*" ProviderCode="{$pd['provider_code']}"/>

        </AirCreateReservationReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    return $this->sendRequest('AirService', $soap);
}

    // =========================================================================
    // 6. AIR CANCEL
    // =========================================================================
    public function airCancel(string $universalLocatorCode, string $version = '0'): array
    {
        $traceId = $this->generateTraceId();

        $soap = <<<XML
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Header/>
            <soapenv:Body>
                <UniversalRecordCancelReq
                    xmlns="{$this->uniNs}"
                    TraceId="{$traceId}"
                    AuthorizedBy="{$this->authorizedBy}"
                    TargetBranch="{$this->targetBranch}"
                    UniversalRecordLocatorCode="{$universalLocatorCode}"
                    Version="0">
                    <BillingPointOfSaleInfo xmlns="{$this->comNs}" OriginApplication="UAPI"/>
                </UniversalRecordCancelReq>
            </soapenv:Body>
        </soapenv:Envelope>
        XML;

        return $this->sendRequest('UniversalRecordService', $soap);
    }
    
    // =========================================================================
// 7. SEAT MAP  (SeatMapReq)
// =========================================================================
// Returns available seats on the plane for a specific flight.
// Call AFTER airBook/airHold, BEFORE airTicket.
//
// @param string $carrier       e.g. "EK"
// @param string $flightNumber  e.g. "622"
// @param string $origin        e.g. "DXB"
// @param string $destination   e.g. "LHE"
// @param string $departureDate e.g. "2026-06-25"
// @param string $classOfService e.g. "Q"
// =========================================================================
public function airSeatMap(
    string $carrier,
    string $flightNumber,
    string $origin,
    string $destination,
    string $departureDate,
    string $classOfService,
    string $providerCode = '1G'
): array {
    $traceId = $this->generateTraceId();
    $departureDate = str_replace(' ', '+', $departureDate);

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:SeatMapReq
            xmlns:air="{$this->airNs}"
            xmlns:com="{$this->comNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            ReturnSeatPricing="false">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <air:AirSegment
                Key="seg1"
                Carrier="{$carrier}"
                FlightNumber="{$flightNumber}"
                Origin="{$origin}"
                Destination="{$destination}"
                DepartureTime="{$departureDate}"
                ClassOfService="{$classOfService}"
                ProviderCode="{$providerCode}"
                Group="0"/>
        </air:SeatMapReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    return $this->sendRequest('AirService', $soap);
}


// =========================================================================
// 8. SEAT ASSIGN  (UniversalRecordModifyReq with AirSeatAssignment)
// =========================================================================
// Assigns a specific seat to a passenger on an existing booking.
// Call AFTER airBook/airHold.
//
// @param string $universalLocator  Universal Record locator from airBook
// @param string $travelerKey       Booking traveler key e.g. "traveler_1"
// @param string $seatNumber        Seat to assign e.g. "12A"
// @param string $segmentRef        AirSegment key from booking
// @param string $carrier           e.g. "EK"
// @param string $flightNumber      e.g. "622"
// @param string $origin            e.g. "DXB"
// @param string $destination       e.g. "LHE"
// @param string $departureTime     e.g. "2026-06-25T21:40:00+04:00"
// =========================================================================
public function airSeatAssign(
    string $universalLocator,
    string $travelerKey,
    string $seatNumber,
    string $segmentRef,
    string $carrier,
    string $flightNumber,
    string $origin,
    string $destination,
    string $departureTime
): array {
    $traceId = $this->generateTraceId();

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq
            xmlns:universal="{$this->uniNs}"
            xmlns:air="{$this->airNs}"
            xmlns:com="{$this->comNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            Version="0">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:UniversalRecordLocatorCode>{$universalLocator}</universal:UniversalRecordLocatorCode>
            <universal:AirAdd>
                <air:AirSegment
                    Key="{$segmentRef}"
                    Group="0"
                    Carrier="{$carrier}"
                    FlightNumber="{$flightNumber}"
                    Origin="{$origin}"
                    Destination="{$destination}"
                    DepartureTime="{$departureTime}"
                    Status="HK"/>
                <air:AirSeatAssignment
                    SeatAssigned="{$seatNumber}"
                    SegmentRef="{$segmentRef}"
                    BookingTravelerRef="{$travelerKey}"/>
            </universal:AirAdd>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    return $this->sendRequest('UniversalRecordService', $soap);
}


// =========================================================================
// 9. MEAL REQUEST / SSR  (UniversalRecordModifyReq with SSR)
// =========================================================================
// Adds a Special Service Request (meal preference, wheelchair, etc.)
// to an existing booking.
//
// Common meal SSR codes:
//   VGML = Vegetarian  |  MOML = Muslim meal  |  HNML = Hindu meal
//   KSML = Kosher      |  CHML = Child meal   |  SFML = Seafood
//   DBML = Diabetic    |  VLML = Vegan        |  SPML = Special meal
//
// @param string $universalLocator  Universal Record locator from airBook
// @param string $travelerKey       e.g. "traveler_1"
// @param string $ssrCode           e.g. "MOML"
// @param string $carrier           e.g. "EK"
// @param string $segmentRef        AirSegment key from booking
// =========================================================================
public function airMealRequest(
    string $universalLocator,
    string $travelerKey,
    string $ssrCode,
    string $carrier,
    string $segmentRef
): array {
    $traceId = $this->generateTraceId();

    $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <universal:UniversalRecordModifyReq
            xmlns:universal="{$this->uniNs}"
            xmlns:com="{$this->comNs}"
            TraceId="{$traceId}"
            AuthorizedBy="{$this->authorizedBy}"
            TargetBranch="{$this->targetBranch}"
            Version="0">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <universal:UniversalRecordLocatorCode>{$universalLocator}</universal:UniversalRecordLocatorCode>
            <universal:RecordStatus>
                <com:BookingTraveler Key="{$travelerKey}">
                    <com:SSR
                        Type="{$ssrCode}"
                        Status="NN"
                        Carrier="{$carrier}"
                        SegmentRef="{$segmentRef}"
                        BookingTravelerRef="{$travelerKey}"/>
                </com:BookingTraveler>
            </universal:RecordStatus>
        </universal:UniversalRecordModifyReq>
    </soapenv:Body>
</soapenv:Envelope>
XML;

    return $this->sendRequest('UniversalRecordService', $soap);
}


    // =========================================================================
    // HELPER: Extract structured pricing data from raw XML price response.
    // Uses regex on the RAW XML (with namespaces intact) so values are correct.
    // =========================================================================
    public function extractPricingData(string $rawXml, string $requestedClass = ''): array
    {
        $pd = [
            'provider_code' => '',
            'carrier'       => '',
            'flight_number' => '',
            'origin'        => '',
            'destination'   => '',
            'dep_time'      => '',
            'arr_time'      => '',
            'equipment'     => '',
            'solution_key'     => '',
            'total_price'      => '',
            'segment_ref_key'  => '',
            'pricing_info_key' => '',
            'booking_code'     => '',
            'cabin_class'      => '',
            'fare_info_ref'    => '',
            'segment_ref'      => '',
            'host_token_ref'   => '',
            'host_token_key'   => '',
            'host_token_value' => '',
            'pricing_method' => '',
            'base_price' => '',
            'taxes'      => '',
            'fare_info_key'  => '',
            'fare_basis'     => '',
            'fare_rule_key'  => '',
            'departure_date' => '',
            'fare_origin' => '',
            'fare_destination' => '',
            'effective_date' => '',
            'flight_time'  => '',
            'travel_time'  => '',
            'taxes_xml'    => '',
            'latest_ticketing_time' => '',
        ];
        
        if (preg_match('/<air:FareInfo[^>]+EffectiveDate="([^"]+)"/i', $rawXml, $m)) {
            $pd['effective_date'] = $m[1];
        }
    
        // AirPricingSolution — try both attribute orders
        if (preg_match('/<air:AirPricingSolution[^>]+Key="([^"]+)"[^>]+TotalPrice="([^"]+)"/i', $rawXml, $m)) {
            $pd['solution_key'] = $m[1];
            $pd['total_price']  = $m[2];
        } elseif (preg_match('/<air:AirPricingSolution[^>]+TotalPrice="([^"]+)"[^>]+Key="([^"]+)"/i', $rawXml, $m)) {
            $pd['total_price']  = $m[1];
            $pd['solution_key'] = $m[2];
        }
    
        // AirSegmentRef Key
        if (preg_match('/<air:AirSegmentRef\s+Key="([^"]+)"/i', $rawXml, $m)) {
            $pd['segment_ref_key'] = $m[1];
        }
    
        // AirPricingInfo Key
        if (preg_match('/<air:AirPricingInfo[^>]+Key="([^"]+)"/i', $rawXml, $m)) {
            $pd['pricing_info_key'] = $m[1];
        }
        if (preg_match('/<air:AirPricingInfo[^>]+PricingMethod="([^"]+)"/i', $rawXml, $m)) {
            $pd['pricing_method'] = $m[1];
        }
        
        if (preg_match('/<air:AirPricingInfo[^>]+BasePrice="([^"]+)"/i', $rawXml, $m)) {
            $pd['base_price'] = $m[1];
        }
        if (preg_match('/<air:AirPricingInfo[^>]+Taxes="([^"]+)"/i', $rawXml, $m)) {
            $pd['taxes'] = $m[1];
        }
        
        //air hold
        
        preg_match_all('/LatestTicketingTime="([^"]+)"/i', $rawXml, $lttMatches);
        foreach ($lttMatches[1] as $ltt) {
            $pd['latest_ticketing_time'] = $ltt; // keep overwriting — last one wins (matches requested class)
            if (!empty($requestedClass)) {
                // Find the AirPricingInfo block containing BookingCode matching requestedClass
                // and stop at the right LatestTicketingTime
                break; // Will improve below
            }
        }
        // Better approach: get all and pick last if class matched
        if (!empty($lttMatches[1])) {
            $pd['latest_ticketing_time'] = end($lttMatches[1]);
        }
    
        // BookingInfo — match the requested class if provided
        preg_match_all('/<air:BookingInfo\s+([^>\/]+)\/?>/i', $rawXml, $allBookings);
        $matched = false;
        foreach ($allBookings[1] as $attrs) {
            $code = '';
            if (preg_match('/BookingCode="([^"]+)"/i', $attrs, $a)) $code = $a[1];
        
            if (!empty($requestedClass) && strtoupper($code) !== strtoupper($requestedClass)) {
                continue;
            }
        
            $pd['booking_code']   = $code;
            if (preg_match('/CabinClass="([^"]+)"/i',   $attrs, $a)) $pd['cabin_class']    = $a[1];
            if (preg_match('/FareInfoRef="([^"]+)"/i',  $attrs, $a)) $pd['fare_info_ref']  = $a[1];
            if (preg_match('/SegmentRef="([^"]+)"/i',   $attrs, $a)) $pd['segment_ref']    = $a[1];
            if (preg_match('/HostTokenRef="([^"]+)"/i', $attrs, $a)) $pd['host_token_ref'] = $a[1];
            $matched = true;
            break;
        }
        if (!$matched && !empty($allBookings[1])) {
            $attrs = $allBookings[1][0];
        
            if (preg_match('/BookingCode="([^"]+)"/i',  $attrs, $a)) $pd['booking_code']   = $a[1];
            if (preg_match('/CabinClass="([^"]+)"/i',   $attrs, $a)) $pd['cabin_class']    = $a[1];
            if (preg_match('/FareInfoRef="([^"]+)"/i',  $attrs, $a)) $pd['fare_info_ref']  = $a[1];
            if (preg_match('/SegmentRef="([^"]+)"/i',   $attrs, $a)) $pd['segment_ref']    = $a[1];
            if (preg_match('/HostTokenRef="([^"]+)"/i', $attrs, $a)) $pd['host_token_ref'] = $a[1];
        }
        
        // FareInfo Key and FareBasis
        if (preg_match('/<air:FareInfo[^>]+Key="([^"]+)"/i', $rawXml, $m)) {
            $pd['fare_info_key'] = $m[1];
        }
        if (preg_match('/<air:FareInfo[^>]+FareBasis="([^"]+)"/i', $rawXml, $m)) {
            $pd['fare_basis'] = $m[1];
        }
        
        // FareInfo DepartureDate
        if (preg_match('/<air:FareInfo[^>]+DepartureDate="([^"]+)"/i', $rawXml, $m)) {
            $pd['departure_date'] = $m[1];
        }
        // FareInfo Origin
        if (preg_match('/<air:FareInfo[^>]+Origin="([^"]+)"/i', $rawXml, $m)) {
            $pd['fare_origin'] = $m[1];
        }
        // FareInfo Destination  
        if (preg_match('/<air:FareInfo[^>]+Destination="([^"]+)"/i', $rawXml, $m)) {
            $pd['fare_destination'] = $m[1];
        }
        
        // FareRuleKey value
        if (preg_match('/<air:FareRuleKey[^>]*>([^<]+)<\/air:FareRuleKey>/i', $rawXml, $m)) {
            $pd['fare_rule_key'] = trim($m[1]);
        }
    
        // HostToken under common_v52_0: namespace
        if (preg_match('/<common_v52_0:HostToken\s+Key="([^"]+)">([^<]+)<\/common_v52_0:HostToken>/i', $rawXml, $m)) {
            $pd['host_token_key']   = $m[1];
            $pd['host_token_value'] = trim($m[2]);
        }
        
        // AirSegment details — needed to include AirSegment in AirCreateReservationReq
        if (preg_match('/<air:AirSegment\s+([^>]+)>/i', $rawXml, $m) ||
            preg_match('/<air:AirSegment\s+([^>]+)\/>/i', $rawXml, $m)) {
            $attrs = $m[1];
            if (preg_match('/ProviderCode="([^"]+)"/i',  $attrs, $a)) $pd['provider_code'] = $a[1];
            if (preg_match('/Carrier="([^"]+)"/i',        $attrs, $a)) $pd['carrier']       = $a[1];
            if (preg_match('/FlightNumber="([^"]+)"/i',   $attrs, $a)) $pd['flight_number'] = $a[1];
            if (preg_match('/Origin="([^"]+)"/i',         $attrs, $a)) $pd['origin']        = $a[1];
            if (preg_match('/Destination="([^"]+)"/i',    $attrs, $a)) $pd['destination']   = $a[1];
            if (preg_match('/DepartureTime="([^"]+)"/i',  $attrs, $a)) $pd['dep_time']      = $a[1];
            if (preg_match('/ArrivalTime="([^"]+)"/i',    $attrs, $a)) $pd['arr_time']      = $a[1];
            if (preg_match('/Equipment="([^"]+)"/i',      $attrs, $a)) $pd['equipment']     = $a[1];
        }
        
        // FlightTime and TravelTime from AirSegment
        if (preg_match('/FlightTime="([^"]+)"/i', $rawXml, $m)) {
            $pd['flight_time'] = $m[1];
        }
        if (preg_match('/TravelTime="([^"]+)"/i', $rawXml, $m)) {
            $pd['travel_time'] = $m[1];
        }
        
        // TaxInfo blocks — extract all of them as raw XML to inject into booking
        $taxInfoXml = '';
        preg_match_all('/<air:TaxInfo[^>]+\/>/i', $rawXml, $taxMatches);
        $seenCategories = [];
        foreach ($taxMatches[0] as $taxTag) {
            preg_match('/Category="([^"]+)"/i', $taxTag, $cat);
            $category = $cat[1] ?? '';
            if ($category && !in_array($category, $seenCategories)) {
                $seenCategories[] = $category;
                $taxInfoXml .= "\n" . str_replace('air:', '', $taxTag);
            }
        }
        $pd['taxes_xml'] = $taxInfoXml;
    
        return $pd;
    }


    // =========================================================================
    // HELPER: Send SOAP Request
    // =========================================================================
    private function sendRequest(string $service, string $soapBody): array
    {
        $endpoint    = "{$this->baseEndpoint}/{$service}";
        $base64Creds = base64_encode(trim($this->username) . ':' . trim($this->password));

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $soapBody,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml;charset=UTF-8',
                'Authorization: Basic ' . $base64Creds,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'httpCode' => 0, 'raw' => '', 'parsed' => null, 'error' => "cURL Error: {$error}"];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'httpCode' => $httpCode, 'raw' => $response, 'parsed' => null, 'error' => "HTTP Error ({$httpCode})"];
        }

        $parsed  = $this->parseXmlResponse($response);
        $isFault = isset($parsed['Body']['Fault']);

        return [
            'success'  => !$isFault,
            'httpCode' => $httpCode,
            'raw'      => $response,
            'parsed'   => $parsed,
            'error'    => $isFault ? ($parsed['Body']['Fault']['faultstring'] ?? 'SOAP Fault') : null,
        ];
    }


    // =========================================================================
    // HELPER: Parse XML → PHP array (strips namespaces for easy access)
    // =========================================================================
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


    // =========================================================================
    // HELPER: Generate Trace ID
    // =========================================================================
    private function generateTraceId(): string
    {
        return 'trace_' . uniqid('', true);
    }
}
