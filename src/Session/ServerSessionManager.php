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
     * @param $value
     */
    public function setUserData(string $sid, $value)
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
     */
    public function start($sid)
    {
        $id = Session::getId();

        $this->set($sid, $id);
    }
}
