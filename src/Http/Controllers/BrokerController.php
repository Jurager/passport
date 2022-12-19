<?php

namespace Jurager\Passport\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Jurager\Passport\Broker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Jurager\Passport\Session\ClientSessionManager;

class BrokerController extends Controller
{
    /**
     * @var Broker
     */
    protected Broker $broker;

    /**
     * @var ClientSessionManager
     */
    protected ClientSessionManager $storage;

    /**
     * Constructor
     *
     * @param Broker $broker
     * @param ClientSessionManager $storage
     */
    public function __construct(Broker $broker, ClientSessionManager $storage)
    {
        $this->broker  = $broker;
        $this->storage = $storage;
    }

    /**
     * Attach client to server
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function attach(Request $request): RedirectResponse
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
        return redirect()->away($attach_url, 307);
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function logoutById(Request $request): RedirectResponse
    {
        // Response failed status
        //
        $status = [ 'type' => 'error', 'message' => 'Error while trying to logout session'];

        // Trying to log out broker
        //
        if($this->broker->logout($request, 'id')){

            // Response success status
            //
            $status = [ 'type' => 'success', 'message' => 'Session successfully logout'];
        }

        // Redirect with status message
        //
        return redirect()->back()->with('status', $status);
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function logoutAll(Request $request): RedirectResponse
    {
        // Response failed status
        //
        $status = [ 'type' => 'error', 'message' => 'Error while trying to logout session'];

        // Trying to log out all devices on broker
        //
        if($this->broker->logout($request, 'all')) {

            // Response success status
            //
            $status = [ 'type' => 'success', 'message' => 'Session successfully logout'];
        }

        // Redirect with status message
        //
        return redirect()->back()->with('status', $status);
    }


    /**
     * Destroy all sessions / Revoke all access tokens, except the current one.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function logoutOthers(Request $request): RedirectResponse
    {
        // Response failed status
        //
        $status = [ 'type' => 'error', 'message' => 'Error while trying to logout session'];

        // Trying to log out other devices on broker
        //
        if($this->broker->logout($request, 'others')) {

            // Response success status
            //
            $status = [ 'type' => 'success', 'message' => 'Session successfully logout'];
        }

        // Redirect with status message
        //
        return redirect()->back()->with('status', $status);
    }
}
