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
     *
     * @var Authenticatable
     */
    public Authenticatable $user;

    /**
     * The authenticated user.
     *
     * @var Request
     */
    public Request $request;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param Request $request
     */
    public function __construct(Authenticatable $user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}