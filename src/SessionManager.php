<?php

namespace Jurager\Passport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Jurager\Passport\Exceptions\InvalidClientException;

class SessionManager
{
    public string $type;

    /**
     * Return the storage driver
     *
     * @return int
     */
    protected function store() {
        if(isset($this->type)) {
            return app()->{$this->type};
        }

        throw new \Exception('Invalid storage type. Please make sure the storage type is defined.');
    }

    /**
     * Return the session configuration TTL
     *
     * @return int
     */
    protected function getSessionTTL(): int
    {
        return Config::get('passport.session_ttl');
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
            $this->store()->forever($key, $value);
        } else {
            $ttl = $this->getSessionTTL();
            $this->store()->put($key, $value, $ttl);
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
     *
     * @param $key
     * @return void
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
     * @return void
     */
    public function setUserData(string $sid, $value): void
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        Session::put('passport_user', $value);
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

        return Session::get('passport_user');
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
