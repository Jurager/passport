<?php

namespace Jurager\Passport\Interfaces;

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Collection;

interface Provider
{
    /**
     * Get the Guzzle request.
     */
    public function getRequest(): Request;

    /**
     * Get the result of the query.
     *
     * @return Provider|null
     */
    public function getResult(): ?Collection;

    /**
     * Get the country name.
     */
    public function getCountry(): string;

    /**
     * Get the region name.
     */
    public function getRegion(): string;

    /**
     * Get the city name.
     */
    public function getCity(): string;
}
