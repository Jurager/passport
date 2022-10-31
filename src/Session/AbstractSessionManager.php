<?php

namespace Jurager\Passport\Session;

abstract class AbstractSessionManager
{
    /**
     * Return The session store
     */
    abstract protected function store();

    /**
     * Return the session configuration ttl
     * @return int
     */
    protected function getSessionTTL(): int
    {
        return config('passport.session_ttl');
    }

    /**
     * Check if session ttl is forever, means if its value is null
     * @return bool
     */
    protected function isTTLForever(): bool
    {
        return is_null($this->getSessionTTL());
    }

    /**
     * Set session value in the cache
     * @param $key string
     * @param $value string
     * @param $forever bool
     */
    public function set(string $key, string $value, bool $forever = false): void
    {
        if (($forever || $this->isTTLForever()) && is_callable([$this->store(), 'forever'])) {
            $this->store()->forever($key, $value);
        } else {
            $ttl = $this->getSessionTTL();
            $this->store()->put($key, $value, $ttl);
        }
    }

    /**
     * Return session value of the key $key
     *
     * @return string $key
     * @return mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Delete session value of the key $key
     */
    public function forget($key): void
    {
        $this->store()->forget($key);
    }
}
