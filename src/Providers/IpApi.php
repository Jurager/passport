<?php

namespace Jurager\Passport\Providers;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Request;
use Jurager\Passport\Interfaces\ProviderInterface;
use Jurager\Passport\Traits\MakesApiCalls;

class IpApi implements ProviderInterface
{
    use MakesApiCalls;

    /**
     * Get the Guzzle request.
     */
    public function getRequest(): GuzzleRequest
    {
        $ip = Request::header('Passport-Remote-Address') ?? '';
        return new GuzzleRequest('GET', 'http://ip-api.com/json/'.$ip.'?fields=25');
    }

    /**
     * Get the country name.
     */
    public function getCountry(): string
    {
        return $this->result?->get('country') ?? '';
    }

    /**
     * Get the region name.
     */
    public function getRegion(): string
    {
        return $this->result?->get('regionName') ?? '';
    }

    /**
     * Get the city name.
     */
    public function getCity(): string
    {
        return $this->result?->get('city') ?? '';
    }
}
