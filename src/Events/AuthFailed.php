<?php

namespace Jurager\Passport\Events;

use Illuminate\Queue\SerializesModels;

class AuthFailed
{
    use SerializesModels;

    /**
     * The credentials.
     *
     * @var array
     */
    public array $credentials;

    /**
     * The request object.
     *
     * @var \Illuminate\Http\Request|null
     */
    public ?\Illuminate\Http\Request $request;

    /**
     * Create a new event instance.
     *
     * @param $credentials
     * @param null $request
     */
    public function __construct($credentials, $request = null)
    {
        $this->credentials = $credentials;
        $this->request = $request;
    }
}
