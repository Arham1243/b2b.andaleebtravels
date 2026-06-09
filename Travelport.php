<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'libraries/TravelportAPI.php';

/**
 * =============================================================================
 * Travelport Controller
 * =============================================================================
 *
 * BOOKING FLOW URLS:
 *
 * 1. /travelport/airSearch?origin=DXB&destination=LHE&date=2026-05-25&pax=ADT
 *
 * 2. /travelport/airPrice?segment_key=KEY==&carrier=SV&flight_no=734
 *      &origin=JED&destination=LHE
 *      &dep_time=2026-05-26T01:45:00.000+03:00
 *      &arr_time=2026-05-26T08:50:00.000+05:00
 *      &class=V&equipment=777&provider=1G&pax=ADT
 *
 * 3. /travelport/airBook?first_name=John&last_name=Doe&phone=03001234567
 *      &email=john@example.com&dob=1990-01-15&gender=M
 *      &passport=AB1234567&nationality=PK&passport_expiry=2030-01-01
 *
 * 4. /travelport/airRetrieve?locator=XXXXXX
 *
 * 5. /travelport/airTicket?locator=XXXXXX&payment_type=Credit&card_type=VI
 *      &card_number=4111111111111111&exp_date=2027-01&cvv=123&card_holder=John+Doe
 *
 * 6. /travelport/airCancel?locator=XXXXXX
 * =============================================================================
 */
class Travelport extends CI_Controller
{
    private $api;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->api = new TravelportAPI();
    }


    // =========================================================================
    // HELPER: Stop with error
    // =========================================================================
    private function error(string $message, string $raw = '')
    {
        header('Content-Type: text/plain');
        echo "ERROR: {$message}\n";
        if (!empty($raw)) {
            echo "\nRAW RESPONSE:\n" . htmlspecialchars($raw);
        }
        exit;
    }


    // =========================================================================
    // HELPER: Require GET params
    // =========================================================================
    private function requireGet(array $params): array
    {
        $values  = [];
        $missing = [];

        foreach ($params as $param) {
            $val = $this->input->get($param);
            if ($val === null || $val === '') {
                $missing[] = $param;
            } else {
                $values[$param] = $val;
            }
        }

        if (!empty($missing)) {
            $this->error('Missing required query string parameter(s): ' . implode(', ', $missing));
        }

        return $values;
    }


    // =========================================================================
    // 1. AIR SEARCH
    // /travelport/airSearch?origin=DXB&destination=LHE&date=2026-05-25&pax=ADT
    // =========================================================================
    public function airSearch()
    {
        $p   = $this->requireGet(['origin', 'destination', 'date']);
        $pax = $this->input->get('pax') ?: 'ADT';

        $result = $this->api->airSearch($p['origin'], $p['destination'], $p['date'], $pax);

        header('Content-Type: text/plain');

        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }

        $data          = $result['parsed'];
        $flightDetails = $data['Body']['LowFareSearchRsp']['FlightDetailsList']['FlightDetails'] ?? [];
        $pricePoints   = $data['Body']['LowFareSearchRsp']['AirPricePointList']['AirPricePoint'] ?? [];

        echo "=== FLIGHT DETAILS ===\n";
        echo "Use Key, Carrier, FlightNumber, DepartureTime, ArrivalTime, Equipment for airPrice\n\n";
        echo '<pre>'; print_r($flightDetails); echo '</pre>';

        echo "\n=== PRICE POINTS ===\n\n";
        echo '<pre>'; print_r($pricePoints); echo '</pre>';

        echo "\n=== FULL PARSED RESPONSE ===\n\n";
        echo '<pre>'; print_r($data); echo '</pre>';

        exit;
    }


    // =========================================================================
    // 2. AIR PRICE
    // =========================================================================
    public function airPrice()
    {
        $p = $this->requireGet([
            'segment_key', 'carrier', 'flight_no',
            'origin', 'destination',
            'dep_time', 'arr_time',
            'class', 'equipment', 'provider',
        ]);

        $pax = $this->input->get('pax') ?: 'ADT';

        $segments = [[
            'Key'            => $p['segment_key'],
            'Group'          => '0',
            'ProviderCode'   => $p['provider'],
            'Carrier'        => $p['carrier'],
            'FlightNumber'   => $p['flight_no'],
            'Origin'         => $p['origin'],
            'Destination'    => $p['destination'],
            'DepartureTime'  => str_replace(' ', '+', $p['dep_time']),
            'ArrivalTime'    => str_replace(' ', '+', $p['arr_time']),
            'ClassOfService' => $p['class'],
            'Equipment'      => $p['equipment'],
        ]];

        $result = $this->api->airPrice($segments, $pax);

        header('Content-Type: text/plain');

        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }

        // Extract structured pricing data from RAW XML (namespaces intact)
        $pricingData = $this->api->extractPricingData($result['raw'], $p['class']);
        
//         // TEMP: dump raw HostToken from raw XML
// preg_match('/<common_v52_0:HostToken[^>]*>([^<]+)<\/common_v52_0:HostToken>/i', $result['raw'], $htMatch);
// echo "=== RAW HOST TOKEN FROM PRICE RESPONSE ===\n\n";
// echo isset($htMatch[1]) ? htmlspecialchars($htMatch[1]) : 'NOT FOUND';
// echo "\n\n=== SESSION HOST TOKEN ===\n\n";
// echo htmlspecialchars($pricingData['host_token_value']);
// exit;

        $this->session->unset_userdata('pricing_data');
        // Save to session
        $this->session->set_userdata('pricing_data', $pricingData);

        echo "=== PRICE CONFIRMED ===\n";
        echo "Pricing data saved to session — call /travelport/airBook next\n\n";

        echo "=== EXTRACTED PRICING DATA (what will be used in booking) ===\n\n";
        echo '<pre>'; print_r($pricingData); echo '</pre>';

        echo "\n=== FULL PRICE RESPONSE ===\n\n";
        echo '<pre>'; print_r($result['parsed']); echo '</pre>';

        exit;
    }


    // =========================================================================
    // 3. AIR BOOK
    // =========================================================================
    public function airBook()
    {
        $pricingData = $this->session->userdata('pricing_data');

        if (empty($pricingData) || empty($pricingData['total_price'])) {
            $this->error('No pricing data in session. Call /travelport/airPrice first.');
        }
        $p = $this->requireGet([
            'first_name', 'last_name', 'phone', 'email',
            'dob', 'gender', 'passport', 'nationality', 'passport_expiry',
            'card_type', 'card_number', 'exp_date', 'cvv', 'card_holder',
        ]);

        $traveler = [
            'firstName'      => $p['first_name'],
            'lastName'       => $p['last_name'],
            'phoneNumber' => substr(preg_replace('/^(92|0)3\d{2}/', '', $p['phone']), 0, 7), 
            'email'          => $p['email'],
            'dob'            => $p['dob'],
            'gender'         => strtoupper($p['gender']),
            'passportNumber' => $p['passport'],
            'nationality'    => strtoupper($p['nationality']),
            'passportExpiry' => $p['passport_expiry'],
            'cardType'       => strtoupper($p['card_type']),
            'cardNumber'     => $p['card_number'],
            'expDate'        => $p['exp_date'],
            'cvv'            => $p['cvv'],
            'cardHolder'     => $p['card_holder'],
        ];

        // DEBUG: uncomment to verify pricing data before sending
        // header('Content-Type: text/plain');
        // echo "=== PRICING DATA BEING SENT ===\n\n";
        // echo '<pre>'; print_r($pricingData); echo '</pre>';
        // exit;

        sleep(1);
        $result = $this->api->airBook($traveler, $pricingData);

        header('Content-Type: text/plain');

        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }
        
        // // TEMP: dump raw response to see structure
        // echo "=== RAW RESPONSE ===\n\n";
        // echo htmlspecialchars($result['raw']);
        // echo "\n\n=== PARSED RESPONSE ===\n\n";
        // echo '<pre>'; print_r($result['parsed']); echo '</pre>';
        // exit;

        $data = $result['parsed'];

        $universalLocator = $data['Body']['AirCreateReservationRsp']['UniversalRecord']['@attributes']['LocatorCode']
                            ?? ($data['Body']['AirCreateReservationRsp']['UniversalRecord']['LocatorCode'] ?? '');

        $airReservationLocator = $data['Body']['AirCreateReservationRsp']['UniversalRecord']['AirReservation']['@attributes']['LocatorCode']
                                 ?? '';

        $this->session->set_userdata('universal_locator', $universalLocator);
        $this->session->set_userdata('air_reservation_locator', $airReservationLocator);

        echo "=== BOOKING SUCCESSFUL ===\n\n";
        echo "Universal Locator Code  : {$universalLocator}\n";
        echo "Air Reservation Locator : {$airReservationLocator}\n\n";
        echo "--- Next step URLs ---\n";
        echo "Retrieve : /travelport/airRetrieve?locator={$universalLocator}\n";
        echo "Ticket   : /travelport/airTicket?locator={$airReservationLocator}&payment_type=Credit&card_type=VI&card_number=4111111111111111&exp_date=2027-01&cvv=123&card_holder=John+Doe\n";
        echo "Cancel   : /travelport/airCancel?locator={$universalLocator}\n\n";

        echo "=== FULL BOOK RESPONSE ===\n\n";
        echo '<pre>'; print_r($data); echo '</pre>';

        exit;
    }


    // =========================================================================
    // 4. AIR RETRIEVE
    // /travelport/airRetrieve?locator=XXXXXX
    // =========================================================================
    public function airRetrieve()
    {
        $p = $this->requireGet(['locator']);

        $result = $this->api->airRetrieve($p['locator']);

        header('Content-Type: text/plain');

        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }

        echo "=== BOOKING DETAILS (Locator: {$p['locator']}) ===\n\n";
        echo '<pre>'; print_r($result['parsed']); echo '</pre>';

        exit;
    }


    // =========================================================================
    // 5. AIR TICKET
    // /travelport/airTicket?locator=XXXXXX&payment_type=Credit&card_type=VI
    //   &card_number=4111111111111111&exp_date=2027-01&cvv=123&card_holder=John+Doe
    // =========================================================================
    public function airTicket()
    {
        $p = $this->requireGet(['locator']);
    
        $pricingData = $this->session->userdata('pricing_data');
        if (empty($pricingData) || empty($pricingData['total_price'])) {
            $this->error('No pricing data in session. Call /travelport/airPrice first.');
        }
    
        sleep(2);
        $result = $this->api->airTicket($p['locator'], $pricingData);
    
        header('Content-Type: text/plain');
    
        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }
    
        echo "=== TICKET ISSUED SUCCESSFULLY ===\n\n";
        echo "Air Reservation Locator: {$p['locator']}\n\n";
        echo '<pre>'; print_r($result['parsed']); echo '</pre>';
        exit;
    }


    // =========================================================================
    // 6. AIR CANCEL
    // /travelport/airCancel?locator=XXXXXX
    // =========================================================================
    public function airCancel()
    {
        $p = $this->requireGet(['locator']);

        $result = $this->api->airCancel($p['locator']);

        header('Content-Type: text/plain');

        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }

        $this->session->unset_userdata('universal_locator');
        $this->session->unset_userdata('air_reservation_locator');
        $this->session->unset_userdata('pricing_data');

        echo "=== BOOKING CANCELLED SUCCESSFULLY ===\n\n";
        echo "Cancelled Locator: {$p['locator']}\n\n";
        echo '<pre>'; print_r($result['parsed']); echo '</pre>';

        exit;
    }
    
    // =========================================================================
// 7. SEAT MAP
// /travelport/airSeatMap?carrier=EK&flight_no=622&origin=DXB&destination=LHE&date=2026-06-25&class=Q
// =========================================================================
public function airSeatMap()
{
    $p = $this->requireGet([
        'carrier', 'flight_no', 'origin', 'destination', 'date', 'class', 'provider'
    ]);

    $result = $this->api->airSeatMap(
        $p['carrier'],
        $p['flight_no'],
        $p['origin'],
        $p['destination'],
        $p['date'],
        $p['class'],
        $p['provider']
    );

    header('Content-Type: text/plain');

    if (!$result['success']) {
        $this->error($result['error'], $result['raw']);
    }

    echo "=== SEAT MAP ===\n\n";
    echo '<pre>'; print_r($result['parsed']); echo '</pre>';
    exit;
}


// =========================================================================
// 8. SEAT ASSIGN
// /travelport/airSeatAssign?locator=35OXTB&seat=12A&segment_ref=KEY==&carrier=EK&flight_no=622&origin=DXB&destination=LHE&dep_time=2026-06-25T21:40:00+04:00
// =========================================================================
public function airSeatAssign()
{
    $p = $this->requireGet([
        'locator', 'seat', 'segment_ref',
        'carrier', 'flight_no', 'origin', 'destination', 'dep_time'
    ]);

    $travelerKey = 'traveler_1';

    $result = $this->api->airSeatAssign(
        $p['locator'],
        $travelerKey,
        $p['seat'],
        $p['segment_ref'],
        $p['carrier'],
        $p['flight_no'],
        $p['origin'],
        $p['destination'],
        str_replace(' ', '+', $p['dep_time'])
    );

    header('Content-Type: text/plain');

    if (!$result['success']) {
        $this->error($result['error'], $result['raw']);
    }

    echo "=== SEAT ASSIGNED SUCCESSFULLY ===\n\n";
    echo "Seat    : {$p['seat']}\n";
    echo "Locator : {$p['locator']}\n\n";
    echo '<pre>'; print_r($result['parsed']); echo '</pre>';
    exit;
}


// =========================================================================
// 9. MEAL REQUEST
// /travelport/airMealRequest?locator=35OXTB&ssr=MOML&carrier=EK&segment_ref=KEY==
// =========================================================================
public function airMealRequest()
{
    $p = $this->requireGet([
        'locator', 'ssr', 'carrier', 'segment_ref'
    ]);

    $travelerKey = 'traveler_1';

    $result = $this->api->airMealRequest(
        $p['locator'],
        $travelerKey,
        strtoupper($p['ssr']),
        $p['carrier'],
        $p['segment_ref']
    );

    header('Content-Type: text/plain');

    if (!$result['success']) {
        $this->error($result['error'], $result['raw']);
    }

    echo "=== MEAL REQUEST SUCCESSFUL ===\n\n";
    echo "SSR Code : " . strtoupper($p['ssr']) . "\n";
    echo "Carrier  : {$p['carrier']}\n";
    echo "Locator  : {$p['locator']}\n\n";
    echo '<pre>'; print_r($result['parsed']); echo '</pre>';
    exit;
}
    
    public function airHold()
    {
        $pricingData = $this->session->userdata('pricing_data');
    
        if (empty($pricingData) || empty($pricingData['total_price'])) {
            $this->error('No pricing data in session. Call /travelport/airPrice first.');
        }
    
        $p = $this->requireGet([
            'first_name', 'last_name', 'phone', 'email',
            'dob', 'gender', 'passport', 'nationality', 'passport_expiry',
        ]);
    
        $traveler = [
            'firstName'      => $p['first_name'],
            'lastName'       => $p['last_name'],
            'phoneNumber'    => substr(preg_replace('/^(92|0)3\d{2}/', '', $p['phone']), 0, 7),
            'email'          => $p['email'],
            'dob'            => $p['dob'],
            'gender'         => strtoupper($p['gender']),
            'passportNumber' => $p['passport'],
            'nationality'    => strtoupper($p['nationality']),
            'passportExpiry' => $p['passport_expiry'],
            // No card details — this is a hold, not a purchase
            'cardType'       => '',
            'cardNumber'     => '',
            'expDate'        => '',
            'cvv'            => '',
            'cardHolder'     => '',
        ];
    
        sleep(1);
        $result = $this->api->airHold($traveler, $pricingData);
    
        header('Content-Type: text/plain');
    
        if (!$result['success']) {
            $this->error($result['error'], $result['raw']);
        }
    
        $data = $result['parsed'];
    
        $universalLocator = $data['Body']['AirCreateReservationRsp']['UniversalRecord']['@attributes']['LocatorCode']
                            ?? '';
        $airReservationLocator = $data['Body']['AirCreateReservationRsp']['UniversalRecord']['AirReservation']['@attributes']['LocatorCode']
                                 ?? '';
    
        $this->session->set_userdata('universal_locator', $universalLocator);
        $this->session->set_userdata('air_reservation_locator', $airReservationLocator);
    
        echo "=== HOLD SUCCESSFUL ===\n\n";
        echo "Universal Locator Code  : {$universalLocator}\n";
        echo "Air Reservation Locator : {$airReservationLocator}\n\n";
        echo "Ticketing Deadline      : {$pricingData['latest_ticketing_time']}\n\n";
        echo "--- Next step URLs ---\n";
        echo "Issue Ticket : /travelport/airTicket?locator={$airReservationLocator}\n";
        echo "Cancel Hold  : /travelport/airCancel?locator={$universalLocator}\n\n";
        echo '<pre>'; print_r($data); echo '</pre>';
        exit;
    }


    // =========================================================================
    // DEBUG: View what's in session
    // /travelport/debugSession
    // =========================================================================
    public function debugSession()
    {
        header('Content-Type: text/plain');
        echo "=== PRICING DATA IN SESSION ===\n\n";
        echo '<pre>'; print_r($this->session->userdata('pricing_data')); echo '</pre>';
        exit;
    }
    
    public function debugBookXml()
    {
        header('Content-Type: text/plain');
        echo file_get_contents('/tmp/travelport_book_request.xml');
        exit;
    }
}
