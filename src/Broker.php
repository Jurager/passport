<?php

namespace Jurager\Passport;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use JsonException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Jurager\Passport\Session\ClientSessionManager;

class Broker
{
    protected Encryption $encryption;

    protected ClientSessionManager $storage;

    protected Requester $requester;

    protected Request $request;

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
     */
    public function __construct(Request $request)
    {
        $this->encryption = new Encryption;
        $this->storage = new ClientSessionManager;
        $this->requester = new Requester();

        $this->request = $request;

        $this->client_id = config('passport.broker.client_id');
        $this->client_secret = config('passport.broker.client_secret');
        $this->server_url = config('passport.broker.server_url');

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
     */
    public function generateClientToken(): string
    {
        // Return random client token
        return $this->encryption->randomToken();
    }

    /**
     * Save session token
     */
    public function saveClientToken($token): void
    {
        $key = $this->sessionName();

        // Save client token in cookie
        $this->storage->set($key, $token);
    }

    /**
     * Return session token
     */
    public function getClientToken(): string|array|null
    {
        return $this->request->bearerToken() ?? $this->storage->get($this->sessionName());
    }

    /**
     * Clear session token
     */
    public function clearClientToken(): void
    {
        $key = $this->sessionName();

        $this->storage->forget($key);
    }

    /**
     * Return the session name used to store session id.
     */
    public function sessionName(): string
    {

        // Return session name based on client id
        return 'sso_token_'.preg_replace('/[^a-z\d\-_:]/', '_', strtolower($this->client_id));
    }

    /**
     * Return the session id
     *
     * @param  string|null  $token  The client generated token
     */
    public function sessionId(?string $token): string
    {
        // Client must be attached
        if (! $token) {

            // Throw not attached exception with 403 status code
            throw new NotAttachedException(403, 'Client broker not attached.');
        }

        // Generate new client checksum
        $checksum = $this->encryption->generateChecksum('session', $token, $this->client_secret);

        // Return session id string
        return "Passport-$this->client_id-$token-$checksum";
    }

    /**
     * Check if session is attached
     */
    public function isAttached(): bool
    {
        // Check if client token is exists
        return ! is_null($this->getClientToken());
    }

    /**
     * Generate the attachment checksum. Use the encryption algorithm.
     */
    public function generateAttachChecksum(string $token): string
    {
        // Create new checksum based on client secret and token
        return $this->encryption->generateChecksum('attach', $token, $this->client_secret);
    }

    /**
     * Send login request
     *
     *
     * @throws GuzzleException
     */
    public function login(array $credentials, Request $request): bool|array
    {
        $url = $this->server_url.'/login';
        $token = $this->getClientToken();
        $sid = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $credentials, $headers);
    }

    /**
     * Send profile request
     *
     * @throws GuzzleException
     */
    public function profile(Request $request): bool|string|array
    {
        $url = $this->server_url.'/profile';
        $token = $this->getClientToken();
        $sid = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        try {
            $request = $this->requester->request($sid, 'GET', $url, [], $headers);
        } catch (NotAttachedException|InvalidSessionIdException $e) {

            if ($this->request->bearerToken()) {
                throw $e;
            }

            // Clear only SSO token, preserve other session data
            $this->clearClientToken();

            // Request not completed
            return false;
        }

        return $request;
    }

    /**
     * Send logout request
     *
     * @throws GuzzleException
     */
    public function logout(Request $request, $method = null): bool
    {
        $url = $this->server_url.'/logout';
        $token = $this->getClientToken();
        $sid = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        // Addition request parameters
        $params = [];

        // If trying to log out user by history id
        if ($method === 'id') {

            // Append history id to request
            $params['id'] = $request->id;
        }

        // Make request to the authorisation server
        $response = $this->requester->request($sid, 'POST', $url, array_merge(['method' => $method], $params), $headers);

        // Successfully logged out
        return array_key_exists('success', $response);
    }

    /**
     * Send a command request
     *
     *
     * @return false|string
     *
     * @throws GuzzleException
     */
    public function commands(string $command, array $params, Request $request): bool|string
    {
        $url = $this->server_url.'/commands/'.$command;
        $token = $this->getClientToken();
        $sid = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $params, $headers);
    }

    /**
     * Add agent headers
     */
    protected function agentHeaders(Request $request): array
    {
        return [
            'Passport-User-Agent' => $request->userAgent(),
            'Passport-Remote-Address' => config('passport.broker.uses_cloudflare') ? $request->header('CF-Connecting-IP') : $request->ip(),
        ];
    }
}
