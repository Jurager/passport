<?php

namespace Jurager\Passport\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class Authenticated
{
    use SerializesModels;

    /**
     * The authenticated user.
     */
    public Authenticatable $user;

    /**
     * The authenticated user.
     */
    public Request $request;

    /**
     * Create a new event instance.
     */
    public function __construct(Authenticatable $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
