<?php

namespace Jurager\Passport\Providers;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Request;
use Jurager\Passport\Interfaces\Provider;
use Jurager\Passport\Traits\MakesApiCalls;

class IpApi implements Provider
{
    use MakesApiCalls;

    /**
     * Get the Guzzle request.
     */
    public function getRequest(): GuzzleRequest
    {
        return new GuzzleRequest('GET', 'http://ip-api.com/json/'.Request::header('Passport-Remote-Address').'?fields=25');
    }

    /**
     * Get the country name.
     */
    public function getCountry(): string
    {
        return $this->result->get('country');
    }

    /**
     * Get the region name.
     */
    public function getRegion(): string
    {
        return $this->result->get('regionName');
    }

    /**
     * Get the city name.
     */
    public function getCity(): string
    {
        return $this->result->get('city');
    }
}
