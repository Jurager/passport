<?php

namespace Jurager\Passport;

use GuzzleHttp\Exception\GuzzleException;
use Jurager\Passport\Factories\ProviderFactory;
use Jurager\Passport\Factories\ParserFactory;
use Jurager\Passport\Interfaces\Provider;
use Jurager\Passport\Interfaces\UserAgentParser;
use Illuminate\Support\Facades\Request;

class RequestContext
{
    /**
     * @var UserAgentParser $parser
     */
    protected UserAgentParser $parser;

    /**
     * @var Provider $provider
     */
    protected $provider;

    /**
     * @var string $userAgent
     */
    public string $userAgent;

    /**
     * @var string|null $ip
     */
    public ?string $ip;

    /**
     * RequestContext constructor.
     *
     * @throws \Exception|GuzzleException
     */
    public function __construct()
    {
        // Initialize the parser
        //
        $this->parser = ParserFactory::build(config('passport.server.parser'));

        // Initialize the provider
        //
        $this->provider = ProviderFactory::build(config('passport.server.lookup.provider'));

        // Detect User-Agent
        //
        $this->userAgent = Request::header('Passport-User-Agent');

        // Detect Remote IP
        //
        $this->ip = Request::header('Passport-Remote-Address');
    }

    /**
     * Get the parser used to parse the User-Agent header.
     *
     * @return UserAgentParser
     */
    public function parser(): UserAgentParser
    {
        return $this->parser;
    }

    /**
     * Get the IP lookup result.
     *
     * @return Provider|null
     */
    public function ip(): ?Provider
    {
        if ($this->provider && $this->provider->getResult()) {
            return $this->provider;
        }

        return null;
    }
}