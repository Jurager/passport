<?php

namespace Jurager\Passport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class Storage
{
    /**
     * Set session value in the cache
     *
     * @param $key string
     * @param string|array $value string
     */
    public function set(string $key, string|array $value): void
    {
        // If the storage time is not passed to the put method,
        // Item will be stored indefinitely
        //
        Cache::put($key, $value, config('passport.storage_ttl'));
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
     */
    public function forget($key): bool
    {
        return Cache::forget($key);
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