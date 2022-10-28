<?php

namespace Jurager\Passport\Traits;

/**
 * PassportUser trait
 */
trait PassportUser
{
    /**
     * The sso payload data
     *
     * @var mixed
     */
    protected mixed $sso_payload;

    /**
     * Set sso payload data
     *
     * @param mixed $payload
     */
    public function setPayload(mixed $payload): void
    {
        $this->sso_payload = $payload;
    }

    /**
     * Return sso payload data
     *
     * @return mixed
     */
    public function getPayload(): mixed
    {
        return $this->sso_payload;
    }
}
