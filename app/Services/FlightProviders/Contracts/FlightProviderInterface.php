<?php

namespace App\Services\FlightProviders\Contracts;

use App\Services\FlightProviders\DTO\FlightProviderSearchResult;

interface FlightProviderInterface
{
    public function key(): string;

    /**
     * @param  array<string, mixed>  $searchData
     */
    public function search(array $searchData): FlightProviderSearchResult;
}
