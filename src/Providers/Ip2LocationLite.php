<?php

namespace Jurager\Passport\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Jurager\Passport\Interfaces\ProviderInterface;

class Ip2LocationLite implements ProviderInterface
{
    /**
     * @var object|null
     */
    protected ?object $result;

    /**
     * Ip2LocationLite constructor.
     */
    public function __construct()
    {
        $ip = Request::header('Passport-Remote-Address');

        $table = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? config('passport.server.lookup.ip2location.ipv6_table')
            : config('passport.server.lookup.ip2location.ipv4_table');

        $this->result = DB::table($table)->whereRaw('INET_ATON(?) <= ip_to', [$ip])->first();
    }

    /**
     * Get the Guzzle request.
     *
     * Note: This method is not used by Ip2LocationLite as it uses direct database queries.
     */
    public function getRequest(): \GuzzleHttp\Psr7\Request
    {
        return new \GuzzleHttp\Psr7\Request('GET', '');
    }

    /**
     * Get the country name.
     */
    public function getCountry(): string
    {
        return $this->result?->country_name ?? '';
    }

    /**
     * Get the region name.
     */
    public function getRegion(): string
    {
        return $this->result?->region_name ?? '';
    }

    /**
     * Get the city name.
     */
    public function getCity(): string
    {
        return $this->result?->city_name ?? '';
    }

    /**
     * Get the result of the query.
     */
    public function getResult(): ?Collection
    {
        return $this->result ? collect((array) $this->result) : null;
    }
}
