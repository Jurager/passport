<?php

namespace Jurager\Passport\Events;

use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public \Illuminate\Contracts\Auth\Authenticatable $user;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     */
    public function __construct(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;
    }
}
