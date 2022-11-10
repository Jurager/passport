<?php

namespace Jurager\Passport;

use Illuminate\Support\Facades\Session;
use function Jurager\Passport\Session\app;
use function Jurager\Passport\Session\config;

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
    public function getSessionTTL()
    {
        return config('passport.session_ttl');
    }

    /**
     * Check if session ttl is forever, means if it value is null
     * @return bool
     */
    protected function isTTLForever()
    {
        return is_null($this->getSessionTTL());
    }

    /**
     * Set session value in the cache
     * @param $key string
     * @param $value string
     * @param $forever bool
     */
    public function set($key, $value, $forever = false)
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
    public function get($key, $default = null)
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
    public function forget($key)
    {
        $this->store()->forget($key);
    }

    /**
     * Set user session data
     *
     * @param string $sid
     */
    public function setUserData($sid, $value)
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
     * @return string
     */
    public function getUserData($sid)
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Start a new session by resetting the session value
     */
    public function start($sid)
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }
}