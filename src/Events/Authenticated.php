<?php

namespace Jurager\Passport\Events;

use Illuminate\Queue\SerializesModels;

class Authenticated
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public \Illuminate\Contracts\Auth\Authenticatable $user;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Http\Request
     */
    public \Illuminate\Http\Request $request;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(\Illuminate\Contracts\Auth\Authenticatable $user, \Illuminate\Http\Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}