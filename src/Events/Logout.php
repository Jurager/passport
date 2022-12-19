<?php

namespace Jurager\Passport\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Queue\SerializesModels;

class Logout
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var Authenticatable
     */
    public Authenticatable $user;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     */
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
