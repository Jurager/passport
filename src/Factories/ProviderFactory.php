<?php

namespace Jurager\Passport\Factories;

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
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public static function build($name)
    {
        if (self::ipLookupEnabled()) {
            $customProviders = config('auth_tracker.ip_lookup.custom_providers');

            if ($customProviders && array_key_exists($name, $customProviders)) {

                // Use of a custom IP address lookup provider

                if (!in_array(Provider::class, class_implements($customProviders[$name]), true)) {

                    // The custom IP provider class doesn't
                    // implement the required interface

                    throw new CustomProviderException;
                }

                return new $customProviders[$name];

            } else {

                // Use of an officially supported IP address lookup provider

                switch ($name) {
                    case 'ip2location-lite':
                        return new Ip2LocationLite;
                    case 'ip-api':
                        return new IpApi;
                    default:
                        throw new ProviderException;
                }
            }
        }
    }

    /**
     * Check if the IP lookup feature is enabled.
     *
     * @return bool
     */
    public static function ipLookupEnabled()
    {
        return config('passport.server.lookup.provider') &&
            App::environment(config('passport.server.lookup.environments'));
    }
}