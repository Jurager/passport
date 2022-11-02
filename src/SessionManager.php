<?php

namespace Jurager\Passport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class SessionManager
{
    /**
     * Return the session configuration TTL
     *
     * @return int
     */
    protected function getSessionTTL(): int
    {
        return config('passport.session_ttl');
    }

    /**
     * Check if session ttl is forever, means if its value is null
     *
     * @return bool
     */
    protected function isTTLForever(): bool
    {
        return is_null($this->getSessionTTL());
    }

    /**
     * Set session value in the cache
     *
     * @param $key string
     * @param $value string
     * @param $forever bool
     */
    public function set(string $key, string $value, bool $forever = false): void
    {
        if ($forever || $this->isTTLForever()) {
            Cache::forever($key, $value);
        } else {
            $ttl = $this->getSessionTTL();
            Cache::put($key, $value, $ttl);
        }
    }


    /**
     * Return session value of the key $key
     *
     * @param $key
     * @param null $default
     * @return string|array|null
     */
    public function get($key, $default = null): string|array|null
    {
        return Cache::get($key, $default);
    }

    /**
     * Check session exists in storage
     *
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return Cache::has($key);
    }

    /**
     * Delete session value of the key $key
     *
     * @param $key
     * @return void
     */
    public function forget($key): void
    {
        Cache::forget($key);
    }

    /**
     * Set user session data
     *
     * @param string $sid
     * @param $value
     * @return void
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
     * @return string
     */
    public function getUserData($sid): string
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Start a new session by resetting the session value
     *
     * @param $sid
     * @return void
     */
    public function start($sid): void
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }
}
