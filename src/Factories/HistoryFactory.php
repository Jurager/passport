<?php

namespace Jurager\Passport\Factories;

use Jurager\Passport\Models\History;
use Jurager\Passport\RequestContext;
use Illuminate\Auth\Events\Login as LoginEvent;

class HistoryFactory
{
    /**
     * Build a new Login.
     *
     * @param RequestContext $context
     * @return History
     */
    public static function build(RequestContext $context)
    {
        $history = new History();

        // Fill in the common attributes
        //
        $history->fill([
            'user_agent' => $context->userAgent,
            'ip' => $context->ip,
            'device_type' => $context->parser()->getDeviceType(),
            'device' => $context->parser()->getDevice(),
            'platform' => $context->parser()->getPlatform(),
            'browser' => $context->parser()->getBrowser(),
        ]);

        // If geolocation data was received
        //
        if ($context->ip()) {

            // Fill in the geolocation attributes
            //
            $history->fill([
                'city' => $context->ip()->getCity(),
                'region' => $context->ip()->getRegion(),
                'country' => $context->ip()->getCountry(),
            ]);
        }

        return $history;
    }
}