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

        $soap = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <air:LowFareSearchReq
            TraceId="{$traceId}"
            AuthorizedBy="{$authorizedBy}"
            TargetBranch="{$targetBranch}"
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
}
