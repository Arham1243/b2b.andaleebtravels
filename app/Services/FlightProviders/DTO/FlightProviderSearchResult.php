<?php

namespace App\Services\FlightProviders\DTO;

class FlightProviderSearchResult
{
    /**
     * @param  list<array<string, mixed>>  $results
     * @param  list<array{severity: string, text: string}>  $messages
     * @param  array<string, mixed>|null  $rawResponse
     * @param  array<string, mixed>|null  $requestPayload
     */
    public function __construct(
        public readonly string $provider,
        public readonly array $results = [],
        public readonly array $messages = [],
        public readonly int $itineraryCount = 0,
        public readonly ?array $rawResponse = null,
        public readonly ?array $requestPayload = null,
        public readonly bool $success = true,
    ) {
    }

    public static function failure(string $provider, string $message): self
    {
        return new self(
            provider: $provider,
            messages: [['severity' => 'Warning', 'text' => $message]],
            success: false,
        );
    }
}
