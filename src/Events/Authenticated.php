<?php

namespace Jurager\Passport\Events;

use Illuminate\Queue\SerializesModels;

class Authenticated
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public ?\Illuminate\Contracts\Auth\Authenticatable $user;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Http\Request|null
     */
    public ?\Illuminate\Http\Request $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct($user, $request = null)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
