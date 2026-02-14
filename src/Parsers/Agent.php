<?php

namespace Jurager\Passport\Parsers;

use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent as Parser;
use Jurager\Passport\Interfaces\Parser;

class Agent implements Parser
{
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
     */
    public function getDevice(): ?string
    {
        $device = $this->parser->device();

        return $device && $device !== 'WebKit' ? $device : null;
    }

    /**
     * Get the device type.
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
     */
    public function getPlatform(): ?string
    {
        return $this->parser->platform() ?: null;
    }

    /**
     * Get the browser name.
     */
    public function getBrowser(): ?string
    {
        return $this->parser->browser() ?: null;
    }
}
