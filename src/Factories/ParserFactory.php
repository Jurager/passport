<?php

namespace Jurager\Passport\Factories;

use Jurager\Passport\Parsers\Agent;
use Jurager\Passport\Parsers\WhichBrowser;
use Exception;

class ParserFactory
{
    /**
     * Build a new user-agent parser.
     *
     * @param string $name
     * @return Agent|WhichBrowser
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