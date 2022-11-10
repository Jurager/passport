<?php

namespace Jurager\Passport\Http\Controllers;

use Jurager\Passport\Broker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BrokerController extends Controller
{
    /**
     * @var Broker
     */
    protected Broker $broker;

    /**
     * Constructor
     *
     * @param Broker $broker
     */
    public function __construct(Broker $broker)
    {
        $this->broker  = $broker;
    }

    /**
     * Attach client to server
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function attach(Request $request): \Illuminate\Http\RedirectResponse
    {
        $params = $request->except(['broker', 'token', 'checksum']);

        // Generate an unique session token
        //
        $token = $this->broker->generateClientToken();

        // Save session token in storage
        //
        $this->broker->saveClientToken($token);

        // Generate the attachment checksum
        //
        $checksum = $this->broker->generateAttachChecksum($token);

        // Get the server attachment route
        //
        $attach_url = $this->broker->server_url . '/attach?' . http_build_query([
            'broker'   => $this->broker->client_id,
            'token'    => $token,
            'checksum' => $checksum,
            ...$params
        ]);

        // Redirect to server attachment route
        //
        return redirect()->away($attach_url);
    }
}
