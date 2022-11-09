<?php

namespace Jurager\Passport\Http\Controllers;

use Jurager\Passport\ClientBrokerManager;
use Jurager\Passport\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClientController extends Controller
{
    /**
     * @var ClientBrokerManager
     */
    protected ClientBrokerManager $broker;

    /**
     * @var SessionManager
     */
    protected SessionManager $session;

    /**
     * Constructor
     *
     * @param ClientBrokerManager $broker
     * @param SessionManager $session
     */
    public function __construct(ClientBrokerManager $broker, SessionManager $session)
    {
        $this->broker  = $broker;
        $this->session = $session;
        $this->session->type = 'session';
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
