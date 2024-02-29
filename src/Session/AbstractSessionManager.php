<?php

namespace Jurager\Passport\Session;

use Illuminate\Support\Facades\Cache;

abstract class AbstractSessionManager
{
    /**
     * Return The session store
     */
    abstract protected function store();

    /**
     * Return the session configuration ttl
     */
    protected function getSessionTTL(): int
    {
        return config('passport.storage_ttl');
    }

    /**
     * Check if session ttl is forever, means if its value is null
     */
    protected function isTTLForever(): bool
    {
        return is_null($this->getSessionTTL());
    }

    /**
     * Set session value in the cache
     *
     * @param  $key  string
     * @param  $value  string
     * @param  $forever  bool
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
     * @return string|array|null $key
     */
    public function get(string $key, string|array|null $default = null): string|array|null
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Check session exists in storage
     */
    public function has($key): bool
    {
        return $this->store()->has($key);
    }

    /**
     * Delete session value of the key $key
     */
    public function forget($key): void
    {
        $this->store()->forget($key);
    }
}
