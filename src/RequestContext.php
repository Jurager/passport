<?php

namespace Jurager\Passport;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Request;
use Jurager\Passport\Factories\ParserFactory;
use Jurager\Passport\Factories\ProviderFactory;
use Jurager\Passport\Interfaces\Provider;
use Jurager\Passport\Interfaces\Parser;

class RequestContext
{
    protected Parser $parser;

    /**
     * @var Provider
     */
    protected $provider;

    public string $userAgent;

    public ?string $ip;

    /**
     * RequestContext constructor.
     *
     * @throws Exception|GuzzleException
     */
    public function __construct()
    {
        // Initialize the parser
        $this->parser = ParserFactory::build(config('passport.server.parser'));

        // Initialize the provider
        $this->provider = ProviderFactory::build(config('passport.server.lookup.provider'));

        // Detect User-Agent
        $this->userAgent = Request::header('Passport-User-Agent');

        // Detect Remote IP
        $this->ip = Request::header('Passport-Remote-Address');
    }

    /**
     * Get the parser used to parse the User-Agent header.
     */
    public function parser(): Parser
    {
        return $this->parser;
    }

    /**
     * Get the IP lookup result.
     */
    public function ip(): ?Provider
    {
        if ($this->provider && $this->provider->getResult()) {
            return $this->provider;
        }

        return null;
    }
}
