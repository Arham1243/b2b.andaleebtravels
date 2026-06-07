<?php

$src = file_get_contents(__DIR__ . '/../app/Http/Controllers/User/FlightController.php');
$lines = explode("\n", $src);
// getSabreToken (406) through listingTimestamp (1756); exclude buildFilterCatalog+
$chunk = array_slice($lines, 405, 1351);
$body = implode("\n", $chunk);

$header = <<<'PHP'
<?php

namespace App\Services\FlightProviders;

use App\Support\FlightCabinPreference;
use App\Support\SabreBaggagePresenter;
use App\Support\SabreFareAmountPresenter;
use App\Support\SabreFareBrandPresenter;
use App\Support\SabreFareRulesPresenter;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SabreFlightSearchService
{
    private string $sabreBasicAuth = 'VmpFNk1qVTROak13T2poT1NrdzZRVUU9OlJtRnBjMkZzTVRBPQ==';

    /**
     * @param  array<string, mixed>  $searchData
     * @return array{results: list<array<string, mixed>>, messages: list<array{severity: string, text: string}>, itineraryCount: int, rawResponse: array<string, mixed>, requestPayload: array<string, mixed>}
     */
    public function search(array $searchData): array
    {
        $token = $this->getSabreToken();
        $payload = $this->buildSabrePayload($searchData);
        $response = $this->sabreHttp()->withToken($token)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://api.cert.platform.sabre.com/v5/offers/shop', $payload);

        if (!$response->successful()) {
            return [
                'results' => [],
                'messages' => [['severity' => 'Error', 'text' => 'Sabre flight search failed.']],
                'itineraryCount' => 0,
                'rawResponse' => [],
                'requestPayload' => $payload,
            ];
        }

        $data = $response->json();
        $grouped = $data['groupedItineraryResponse'] ?? [];
        $messages = $grouped['messages'] ?? [];
        $itineraryCount = (int) ($grouped['statistics']['itineraryCount'] ?? 0);
        $results = $this->extractItineraries($grouped, $searchData);

        return [
            'results' => $results,
            'messages' => $messages,
            'itineraryCount' => $itineraryCount,
            'rawResponse' => $grouped,
            'requestPayload' => $payload,
        ];
    }

    private function sabreHttp(): PendingRequest
    {
        return Http::timeout((int) config('services.sabre.http_timeout', 90))
            ->connectTimeout((int) config('services.sabre.http_connect_timeout', 30));
    }

PHP;

$out = $header . $body . "\n}\n";
file_put_contents(__DIR__ . '/../app/Services/FlightProviders/SabreFlightSearchService.php', $out);
echo "OK: " . strlen($out) . " bytes\n";
