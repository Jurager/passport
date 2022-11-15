<?php

namespace Jurager\Passport\Providers;

use Jurager\Passport\Interfaces\Provider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

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
            ? config('passport.lookup.ip2location.ipv6_table')
            : config('passport.lookup.ip2location.ipv4_table');

        $this->result = DB::table($table)
            ->whereRaw('INET_ATON(?) <= ip_to', [Request::ip()])
            ->first();
    }

    /**
     * Get the Guzzle request.
     *
     * @return void
     */
    public function getRequest()
    {
    }

    /**
     * Get the country name.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->result->country_name;
    }

    /**
     * Get the region name.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->result->region_name;
    }

    /**
     * Get the city name.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->result->city_name;
    }

    /**
     * Get the result of the query.
     *
     * @return object|null
     */
    public function getResult()
    {
        return $this->result;
    }
}