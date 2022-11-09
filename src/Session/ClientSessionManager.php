<?php

namespace Jurager\Passport\Session;


class ClientSessionManager extends AbstractSessionManager
{
    /**
     * Use session as store
     */
    protected function store()
    {
        return app()->session;
    }
}