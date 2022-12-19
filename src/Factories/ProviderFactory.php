<?php

namespace Jurager\Passport\Factories;

use GuzzleHttp\Exception\GuzzleException;
use Jurager\Passport\Exceptions\CustomProviderException;
use Jurager\Passport\Exceptions\ProviderException;
use Jurager\Passport\Interfaces\Provider;
use Jurager\Passport\Providers\Ip2LocationLite;
use Jurager\Passport\Providers\IpApi;
use Illuminate\Support\Facades\App;

class ProviderFactory
{
    /**
     * Build a new IP provider.
     *
     * @param string $name
     * @return IpApi|object|void
     * @throws \Exception|GuzzleException
     */
    public static function build(string $name)
    {
        if (config('passport.server.lookup.provider')) {
            
            $custom = config('passport.server.lookup.custom_providers');

            if ($custom && array_key_exists($name, $custom)) {

                // Use of a custom IP address lookup provider
                //
                if (!in_array(Provider::class, class_implements($custom[$name]), true)) {

                    // The custom IP provider class doesn't
                    // implement the required interface

                    throw new CustomProviderException;
                }

                return new $custom[$name];

            }

            // Use of an officially supported address lookup provider
            //
            return match ($name) {
                'ip2location-lite' => new Ip2LocationLite,
                'ip-api' => new IpApi,
                default => throw new ProviderException(trans('passport::errors.provider_not_selected')),
            };
        }
    }
}