<?php

namespace Jurager\Passport\Interfaces;

interface ParserInterface
{
    /**
     * Get the device name.
     */
    public function getDevice(): ?string;

    /**
     * Get the device type.
     */
    public function getDeviceType(): ?string;

    /**
     * Get the platform name.
     */
    public function getPlatform(): ?string;

    /**
     * Get the browser name.
     */
    public function getBrowser(): ?string;
}
