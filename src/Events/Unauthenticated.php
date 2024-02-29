<?php

namespace Jurager\Passport\Events;

use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class Unauthenticated
{
    use SerializesModels;

    /**
     * The credentials.
     */
    public array $credentials;

    /**
     * The request object.
     */
    public ?Request $request;

    /**
     * Create a new event instance.
     *
     * @param  null  $request
     */
    public function __construct($credentials, $request = null)
    {
        $this->credentials = $credentials;
        $this->request = $request;
    }
}
