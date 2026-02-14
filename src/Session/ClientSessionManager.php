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

    public function purge(): bool
    {
        $this->store()->regenerateToken();
        $this->store()->invalidate();

        return true;
    }
}
