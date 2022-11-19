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

    /**
     * Destroy all sessions / Revoke all access tokens.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logoutAll(Request $request)
    {
        dd('logout all');
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function logoutById(Request $request, $id)
    {
        dd('logout by id ' . $id);

        $this->broker->logout($request);

        return redirect()->route('account.activity')->with([
            'status' => [
                'type' => 'success',
                'message' => 'Accesses have been updated.'
            ]
        ]);
    }

    /**
     * Destroy all sessions / Revoke all access tokens, except the current one.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logoutOthers(Request $request)
    {
        dd('logout others');

        $request->user()->logoutOthers();

        return redirect()->route('account.activity')->with([
            'status' => [
                'type' => 'success',
                'message' => 'Accesses have been updated.'
            ]
        ]);
    }
}
