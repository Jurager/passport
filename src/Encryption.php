<?php

namespace Jurager\Passport;

/**
 * Encryption class
 */
class Encryption
{
    /**
     * Generate new checksum
     *
     * @param string $type
     * @param string $token
     * @param string $secret
     * @return string
     */
    public function generateChecksum(string $type, string $token, string $secret): string
    {
        return hash('sha256', $type . $token . $secret);
    }

    /**
     * Verify if attach checksum matches
     *
     * @param string $token
     * @param string $secret
     * @param string $checksum
     * @return bool
     */
    public function verifyChecksum(string $token, string $secret, string $checksum): bool
    {
        return $checksum && $checksum === $this->generateChecksum('attach', $token, $secret);
    }

    /**
     * Generate a random token
     * 
     * @return string
     */
    public function randomToken(): string
    {
        return base_convert(md5(uniqid(mt_rand(), true)), 16, 36);
    }
}
