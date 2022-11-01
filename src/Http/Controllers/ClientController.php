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
        $params     = $request->except(['broker', 'token', 'checksum']);
        $attach_url = $this->getAttachUrl($params);

        return redirect()->away($attach_url);
    }

    /**
     * Return attack url with params
     *
     * @param array $params
     *
     * @return string
     */
    protected function getAttachUrl(array $params = []): string
    {
        $token = $this->generateNewToken();
        $checksum = $this->broker->generateAttachChecksum($token);

        $query = [
            'broker' => $this->broker->clientId(),
            'token' => $token,
            'checksum' => $checksum
        ] + $params;

        return $this->broker->serverUrl('/attach?' . http_build_query($query));
    }

    /**
     * Generate new client token
     *
     * @return string
     */
    protected function generateNewToken(): string
    {
        $token = $this->broker->generateClientToken();

        $this->broker->saveClientToken($token);

        return $token;
    }
}
