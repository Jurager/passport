<?php

namespace Jurager\Passport\Session;

use Illuminate\Support\Facades\Session;

class ServerSessionManager extends AbstractSessionManager
{
    /**
     * Use Laravel caches as store
     */
    protected function store()
    {
        return app()->cache;
    }

    /**
     * Set user session data
     */
    public function setUserData(string $sid, array|string $value): void
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        Session::put('sso_user', $value);
        Session::save();
    }

    /**
     * Retrieve user session data
     */
    public function getUserData(string $sid): array|string|null
    {
        $id = $this->get($sid);

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Remove user data from session
     */
    public function deleteUserData(string $sid): void
    {
        Session::setId($sid);
        Session::start();

        Session::forget('sso_user');
        Session::save();
    }

    /**
     * Start a new session by resetting the session value
     */
    public function start(string $sid): void
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }

    /**
     * Update expiration date by updating session
     */
    public function refresh(string $sid): void
    {
        $id = $this->get($sid);

        $this->set($sid, $id);
    }
}
