<?php

namespace Jurager\Passport\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use JsonException;
use Jurager\Passport\Broker;

class BrokerController extends Controller
{
    protected Broker $broker;

    /**
     * Constructor
     */
    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Attach client to server
     */
    public function attach(Request $request): RedirectResponse
    {
        $params = $request->except(['broker', 'token', 'checksum']);

        // Generate an unique session token
        $token = $this->broker->generateClientToken();

        // Save session token in storage
        $this->broker->saveClientToken($token);

        // Generate the attachment checksum
        $checksum = $this->broker->generateAttachChecksum($token);

        // Get the server attachment route
        $attach_url = $this->broker->server_url.'/attach?'.http_build_query([
            'broker' => $this->broker->client_id,
            'token' => $token,
            'checksum' => $checksum,
            ...$params,
        ]);

        // Redirect to server attachment route
        return redirect()->away($attach_url, 307);
    }

    /**
     * Destroy a session / Revoke an access token by its ID.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function logoutById(Request $request): RedirectResponse
    {
        // Response failed status
        $status = ['type' => 'error', 'message' => trans('passport::errors.error_while_trying_logout')];

        // Trying to log out broker
        if ($this->broker->logout($request, 'id')) {

            // Response success status
            $status = ['type' => 'success', 'message' => trans('passport::messages.session_successfully_logout')];
        }

        // Redirect with status message
        return redirect()->back()->with('status', $status);
    }

    /**
     * Destroy all sessions / Revoke all access tokens.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function logoutAll(Request $request): RedirectResponse
    {
        // Response failed status
        $status = ['type' => 'error', 'message' => trans('passport::errors.error_while_trying_logout')];

        // Trying to log out all devices on broker
        if ($this->broker->logout($request, 'all')) {

            // Response success status
            $status = ['type' => 'success', 'message' => trans('passport::messages.session_successfully_logout')];
        }

        // Redirect with status message
        return redirect()->back()->with('status', $status);
    }

    /**
     * Destroy all sessions / Revoke all access tokens, except the current one.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function logoutOthers(Request $request): RedirectResponse
    {
        // Response failed status
        $status = ['type' => 'error', 'message' => trans('passport::errors.error_while_trying_logout')];

        // Trying to log out other devices on broker
        if ($this->broker->logout($request, 'others')) {

            // Response success status
            $status = ['type' => 'success', 'message' => trans('passport::messages.session_successfully_logout')];
        }

        // Redirect with status message
        return redirect()->back()->with('status', $status);
    }
}
