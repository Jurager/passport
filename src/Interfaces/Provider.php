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
    public function getRequest();

    /**
     * Get the country name.
     *
     * @return string
     */
    public function getCountry();

    /**
     * Get the region name.
     *
     * @return string
     */
    public function getRegion();

    /**
     * Get the city name.
     *
     * @return string
     */
    public function getCity();
}