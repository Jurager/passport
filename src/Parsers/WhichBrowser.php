<?php

namespace Jurager\Passport\Parsers;

use Jurager\Passport\Interfaces\UserAgentParser;
use Illuminate\Support\Facades\Request;
use WhichBrowser\Parser;

class WhichBrowser implements UserAgentParser
{
    /**
     * @var Parser
     */
    protected Parser $parser;

    /**
     * WhichBrowser constructor.
     */
    public function __construct()
    {
        $this->parser = new Parser(Request::header('Passport-User-Agent'));
    }

    /**
     * Get the device name.
     *
     * @return string|null
     */
    public function getDevice(): ?string
    {
        return trim($this->parser->device->toString()) ?: $this->getDeviceByManufacturerAndModel();
    }

    /**
     * Get the device name by manufacturer and model.
     *
     * @return string|null
     */
    protected function getDeviceByManufacturerAndModel(): ?string
    {
        return trim($this->parser->device->getManufacturer().' '.$this->parser->device->getModel()) ?: null;
    }

    /**
     * Get the device type.
     *
     * @return string|null
     */
    public function getDeviceType(): ?string
    {
        return trim($this->parser->device->type) ?: null;
    }

    /**
     * Get the platform name.
     *
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return trim($this->parser->os->toString()) ?: null;
    }

    /**
     * Get the browser name.
     *
     * @return string|null
     */
    public function getBrowser(): ?string
    {
        return $this->parser->browser->name;
    }
}