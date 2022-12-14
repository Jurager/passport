<?php

namespace Jurager\Passport\Factories;

use Jurager\Passport\Models\History;
use Jurager\Passport\RequestContext;
use Illuminate\Auth\Events\Login as LoginEvent;
use Illuminate\Support\Facades\Log;

class HistoryFactory
{
    /**
     * Build a new Login.
     *
     * @param RequestContext $context
     * @return History
     */
    public static function build(RequestContext $context): History
    {
        $history = new History();

        $parser = $context->parser();

        // Fill in the common attributes
        //
        $history->fill([
            'user_agent' => $context->userAgent,
            'ip' => $context->ip,
            'device_type' => $parser->getDeviceType(),
            'device' => $parser->getDevice(),
            'platform' => $parser->getPlatform(),
            'browser' => $parser->getBrowser(),
            //'expires_at' => date("Y-m-d H:i:s", strtotime('+'.config('passport.storage_ttl').' seconds')),
            'session_id' => session()->getId(),
        ]);

        // If geolocation data was received
        //
        if ($geo = $context->ip()) {

            // Fill in the geolocation attributes
            //
            $history->fill([
                'city' => $geo->getCity(),
                'region' => $geo->getRegion(),
                'country' => $geo->getCountry(),
            ]);
        }

        return $history;
    }
}