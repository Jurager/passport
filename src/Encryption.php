<?php

namespace Jurager\Passport;

use Illuminate\Support\Str;

/**
 * Encryption class
 */
class Encryption
{
    /**
     * Generate new checksum
     */
    public function generateChecksum(string $type, string $token, string $secret): string
    {
        return hash('sha256', $type.$token.$secret);
    }

    /**
     * Verify if attach checksum matches
     */
    public function verifyChecksum(string $type, string $token, string $secret, string $checksum): bool
    {
        return $checksum && $checksum === $this->generateChecksum($type, $token, $secret);
    }

    /**
     * Generate a random token
     */
    public function randomToken(): string
    {
        return hash('sha256', Str::random(40));
    }
}
