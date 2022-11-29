<?php

namespace Jurager\Passport\Session;

use Illuminate\Support\Facades\Session;

class ServerSessionManager extends AbstractSessionManager
{
    /**
     * Use Laravel cache as store
     */
    protected function store()
    {
        return app()->cache;
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
     * Remove user data from session
     *
     * @param $sid
     * @return void
     */
    public function deleteUserData($sid)
    {
        Session::setId($sid);
        Session::start();

        Session::forget('sso_user');
        Session::save();
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