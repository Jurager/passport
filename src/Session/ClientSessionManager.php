<?php

namespace Jurager\Passport\Session;

class ClientSessionManager extends AbstractSessionManager
{
    /**
     * Use Laravel session as store
     */
    protected function store()
    {
        return app()->session;
    }

    public function purge()
    {
        $this->store()->regenerateToken();
        $this->store()->invalidate();

        return true;
    }
}
