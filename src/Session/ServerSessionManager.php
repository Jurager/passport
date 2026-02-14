<?php

namespace Jurager\Passport\Session;

use Illuminate\Support\Facades\Log;
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
     * Return the session configuration ttl
     */
    protected function getSessionTTL(): int
    {
        $ttl = config('passport.storage_ttl');

        if (is_null($ttl)) {
            return config('session.lifetime') * 60;
        }

        // Ensure TTL is at least as long as session lifetime to prevent desync
        $sessionLifetime = config('session.lifetime') * 60;

        return max($ttl, $sessionLifetime);
    }

    /**
     * Set user session data
     */
    public function setUserData(string $sid, array|string $value): void
    {
        $id = $this->get($sid);

        // Check if session still exists in cache
        if (!$id) {
            if (config('passport.debug')) {
                Log::warning('SSO session expired in cache', ['sid' => $sid]);
            }
            return;
        }

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

        // Check if session still exists in cache
        if (!$id) {
            if (config('passport.debug')) {
                Log::warning('SSO session expired in cache', ['sid' => $sid]);
            }
            return null;
        }

        Session::setId($id);
        Session::start();

        return Session::get('sso_user');
    }

    /**
     * Remove user data from session
     */
    public function deleteUserData(string $id): void
    {
        Session::setId($id);
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

        // Check if session still exists in cache
        if (!$id) {
            if (config('passport.debug')) {
                Log::warning('Cannot refresh expired SSO session', ['sid' => $sid]);
            }
            return;
        }

        $this->set($sid, $id);
    }
}
