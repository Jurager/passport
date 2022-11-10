<?php

namespace Jurager\Passport;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class Storage
{
    /**
     * Use cache as store
     */
    protected function store()
    {
        return app()->cache;
    }

    /**
     * Return the session configuration ttl
     * @return int
     */
    private function getSessionTTL(): int
    {
        return Config::get('passport.session_ttl');
    }

    /**
     * Check if session ttl is forever, means if it value is null
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
    public function get($key, $default = null): string
    {
        return $this->store()->get($key, $default);
    }

    /**
     * Check session exists in storage
     *
     * @param $key
     * @return bool
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

    /**
     * Set user session data
     *
     * @param string $sid
     * @param $value
     */
    public function setUserData(string $sid, $value): void
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        Session::put('sso_user', $value);
        Session::save();
    }

    /**
     * Retrieve user session data
     *
     * @param $sid
     * @return string|null
     */
    public function getUserData($sid): string|null
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Start a new session by resetting the session value
     */
    public function start($sid): void
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }
}