<?php

namespace Jurager\Passport\Factories;

use Exception;
use Jurager\Passport\Parsers\Agent;
use Jurager\Passport\Parsers\WhichBrowser;

class ParserFactory
{
    /**
     * Build a new user-agent parser.
     *
     * @throws Exception
     */
    public static function build(string $name): Agent|WhichBrowser
    {
        return match ($name) {
            'agent' => new Agent(),
            'whichbrowser' => new WhichBrowser(),
            default => throw new Exception('Choose a supported User-Agent parser.'),
        };
    }
}
