<?php

namespace Jurager\Passport\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Jurager\Passport\Interfaces\Provider;

class Ip2LocationLite implements Provider
{
    /**
     * @var object|null
     */
    protected $result;

    /**
     * Ip2LocationLite constructor.
     */
    public function __construct()
    {
        $table = filter_var(Request::ip(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? config('passport.server.lookup.ip2location.ipv6_table')
            : config('passport.server.lookup.ip2location.ipv4_table');

        $this->result = DB::table($table)->whereRaw('INET_ATON(?) <= ip_to', [Request::ip()])->first();
    }

    /**
     * Get the Guzzle request.
     */
    public function getRequest(): \GuzzleHttp\Psr7\Request
    {
    }

    /**
     * Get the country name.
     */
    public function getCountry(): string
    {
        return $this->result->country_name;
    }

    /**
     * Get the region name.
     */
    public function getRegion(): string
    {
        return $this->result->region_name;
    }

    /**
     * Get the city name.
     */
    public function getCity(): string
    {
        return $this->result->city_name;
    }

    /**
     * Get the result of the query.
     */
    public function getResult(): ?Collection
    {
        return $this->result;
    }
}
