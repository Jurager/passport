<?php

namespace Jurager\Passport;

use Jurager\Passport\Session\ClientSessionManager;
use Jurager\Passport\Exceptions\InvalidClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ClientBrokerManager
{
    /**
     * @var Encryption
     */
    protected Encryption $encryption;

    /**
     * @var ClientSessionManager
     */
    protected ClientSessionManager $session;

    /**
     * @var Requester
     */
    protected Requester $requester;

    /**
     * @var string client_id
     */
    public string $client_id;

    /**
     * @var string client_secret
     */
    public string $client_secret;

    /**
     * @var string client_secret
     */
    public string $server_url;

    /**
     * Constructor
     *
     * @param Requester|null $requester
     */
    public function __construct(Requester $requester = null)
    {
        $this->encryption = new Encryption;
        $this->session    = new ClientSessionManager;
        $this->requester  = new Requester($requester);

        $this->client_id     = Config::get('passport.broker.client_id');
        $this->client_secret = Config::get('passport.broker.client_secret');
        $this->server_url    = Config::get('passport.broker.server_url');

        if (empty($this->client_id)) {
            throw new InvalidClientException('Invalid client id. Please make sure the client id is defined in config.');
        }

        if (empty($this->client_secret)) {
            throw new InvalidClientException('Invalid client secret. Please make sure the client secret is defined in config.');
        }

        if (empty($this->server_url)) {
            throw new InvalidClientException('Invalid server url. Please make sure the server url is defined in config.');
        }
    }

    /**
     * Generate an unique session token
     *
     * @return string
     */
    public function generateClientToken(): string
    {
        // Return random client token
        //
        return $this->encryption->randomToken();
    }

    /**
     * Save session token
     */
    public function saveClientToken($token): void
    {
        // Save client token in storage
        //
        $this->session->set($this->sessionName(), $token);
    }

    /**
     * Return session token
     *
     * @return string|array|null
     */
    public function getClientToken(): string|array|null
    {
        // Get client token from storage
        //
        return $this->session->get($this->sessionName());
    }

    /**
     * Clear session token
     */
    public function clearClientToken(): void
    {
        // Clear client token in storage
        //
        $this->session->forget($this->sessionName());
    }

    /**
     * Return the session name used to store session id.
     *
     * @return string
     */
    public function sessionName(): string
    {
        // Return session name based on client id
        //
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->client_id));
    }

    /**
     * Return the session id
     *
     * @param string $token The client generated token
     * @return string
     */
    public function sessionId(string $token): string
    {
        // Generate new client checksum
        //
        $checksum = $this->encryption->generateChecksum('session', $token, $this->client_secret);

        // Return session id string
        //
        return "Passport-$this->client_id-$token-$checksum";
    }

    /**
     * Attach session to client
     *
     * @param $request
     * @return mixed
     */
    public function sessionAttach($request): mixed
    {
        // Redirect to client attachment with return route
        //
        return redirect()->route('sso.client.attach', ['return_url' => $request->fullUrl()])->send();
    }

    /**
     * Check if session is attached
     *
     * @return bool
     */
    public function isAttached(): bool
    {
        // Check if client token is exists
        //
        return !is_null($this->getClientToken());
    }

    /**
     * Generate the attachment checksum. Use the encryption algorithm.
     *
     * @param string $token
     * @return string
     */
    public function generateAttachChecksum(string $token): string
    {
        // Create new checksum based on client secret and token
        //
        return $this->encryption->generateChecksum('attach', $token, $this->client_secret);
    }

    /**
     * Send login request
     *
     * @param array $credentials
     * @param Request|null $request
     *
     * @return bool|array
     * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
     */
    public function login(array $credentials, Request $request = null): bool|array
    {
        $url   = $this->server_url . '/login';
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $credentials, $headers);
    }

    /**
     * Send profile request
     * @param Request|null $request
     *
     * @return bool|string|array
     * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
     */
    public function profile(Request $request = null): bool|string|array
    {
        $url     = $this->server_url . '/profile';
        $token   = $this->getClientToken();
        $sid     = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'GET', $url, [], $headers);
    }

    /**
     * Send logout request
     * @param Request|null $request
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
     */
    public function logout(Request $request = null): bool
    {
        $url   = $this->server_url . '/logout';
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        $response = $this->requester->request($sid, 'POST', $url, [], $headers);

        if($response['success'] === true) {

            // Clear current client session on broker
            //
            $this->clearClientToken();

            // Success
            //
            return true;
        }

        // Error when attempting logout on server
        //
        return false;
    }

    /**
     * Send a command request
     *
     * @param string $command
     * @param array $params
     * @param Request|null $request
     *
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
     */
    public function commands(string $command, array $params = [], Request $request = null): bool|string
    {
        $url   = $this->server_url . '/commands/' .$command;
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $params, $headers);
    }

    /**
     * Add agent headers
     *
     * @param Request|null $request
     * @return array
     */
    protected function agentHeaders(Request $request = null): array
    {
        $headers = [];

        if ($request) {
            $headers = [
                'Passport-User-Agent'     => $request->userAgent(),
                'Passport-Remote-Address' => $request->ip()
            ];
        }

        return $headers;
    }
}
