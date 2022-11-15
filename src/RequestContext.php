<?php

namespace Jurager\Passport;

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
    protected $provider = null;

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
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public function __construct()
    {
        // Initialize the parser
        $this->parser = ParserFactory::build(config('auth_tracker.parser'));

        // Initialize the IP provider
        $this->provider = ProviderFactory::build(config('auth_tracker.ip_lookup.provider'));

        $this->userAgent = Request::userAgent();
        $this->ip = Request::ip();
    }

    /**
     * Get the parser used to parse the User-Agent header.
     *
     * @return UserAgentParser
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * Get the IP lookup result.
     *
     * @return IpProvider
     */
    public function ip()
    {
        if ($this->ipProvider && $this->ipProvider->getResult()) {
            return $this->ipProvider;
        }

        return null;
    }
}