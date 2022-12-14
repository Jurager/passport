<?php

namespace Jurager\Passport\Parsers;

use Illuminate\Support\Facades\Request;
use Jurager\Passport\Interfaces\UserAgentParser;
use Jenssegers\Agent\Agent as Parser;

class Agent implements UserAgentParser
{
    /**
     * @var Parser
     */
    protected Parser $parser;

    /**
     * Agent constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser();
        $this->parser->setUserAgent(Request::header('Passport-User-Agent'));
    }

    /**
     * Get the device name.
     *
     * @return string|null
     */
    public function getDevice(): ?string
    {
        $device = $this->parser->device();

        return $device && $device !== 'WebKit' ? $device : null;
    }

    /**
     * Get the device type.
     *
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        if ($this->parser->isDesktop()) {
            return 'desktop';
        }

        if ($this->parser->isMobile()) {
            return $this->parser->isTablet() ? 'tablet' : ($this->parser->isPhone() ? 'phone' : 'mobile');
        }

        return null;
    }

    /**
     * Get the platform name.
     *
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->parser->platform() ?: null;
    }

    /**
     * Get the browser name.
     *
     * @return string|null
     */
    public function getBrowser(): ?string
    {
        return $this->parser->browser() ?: null;
    }
}