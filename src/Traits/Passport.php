<?php

namespace Jurager\Passport\Traits;

trait Passport
{
    /**
     * The passport payload data
     *
     * @var mixed
     */
    protected mixed $passport_payload;

    /**
     * Set payload data
     *
     * @param mixed $payload
     */
    public function setPayload(mixed $payload): void
    {
        $this->passport_payload = $payload;
    }

    /**
     * Return payload data
     *
     * @return mixed
     */
    public function getPayload(): mixed
    {
        return $this->passport_payload;
    }
}
