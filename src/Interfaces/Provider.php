<?php

namespace Jurager\Passport\Interfaces;

use GuzzleHttp\Psr7\Request;

interface Provider
{
    /**
     * Get the Guzzle request.
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Get the result of the query.
     *
     * @return Provider|null
     */
    public function getResult(): ?Provider;

    /**
     * Get the country name.
     *
     * @return string
     */
    public function getCountry(): string;

    /**
     * Get the region name.
     *
     * @return string
     */
    public function getRegion(): string;

    /**
     * Get the city name.
     *
     * @return string
     */
    public function getCity(): string;
}