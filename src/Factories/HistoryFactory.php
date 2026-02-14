<?php

namespace Jurager\Passport\Factories;

use Jurager\Passport\Models\History;
use Jurager\Passport\RequestContext;

class HistoryFactory
{
    /**
     * Build a new Login.
     */
    public static function build(RequestContext $context): History
    {
        // Get the first history record matching the session_id or instantiate it.
        $history = History::query()
            ->firstOrNew(['session_id' => session()->getId()]);

        // Parse the User-Agent header.
        $parser = $context->parser();

        // Fill in the common attributes
        $attributes = [
            'user_agent' => $context->userAgent,
            'ip' => $context->ip,
            'device_type' => $parser->getDeviceType(),
            'device' => $parser->getDevice(),
            'platform' => $parser->getPlatform(),
            'browser' => $parser->getBrowser(),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+'.config('session.lifetime').' minutes')),
        ];

        // If geolocation data was received
        if ($geo = $context->ip()) {

            // Fill in the geolocation attributes
            $attributes = [
                ...$attributes,
                'city' => $geo->getCity(),
                'region' => $geo->getRegion(),
                'country' => $geo->getCountry()
            ];
        }

        return $history->fill($attributes);
    }
}
