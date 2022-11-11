<?php

namespace Jurager\Passport;

use Jurager\Passport\Exceptions\InvalidClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class Broker
{
    /**
     * @var Encryption
     */
    protected Encryption $encryption;

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
        // Get expires from config
        //
        $ttl = Config::get('passport.session_ttl') / 60;

        // Create new cookie
        //
        $cookie = Cookie::make($this->sessionName(), $token, $ttl);

        // Save client token in cookie
        //
        Cookie::queue($cookie);
    }

    /**
     * Return session token
     *
     * @param Request $request
     * @return string|array|null
     */
    public function getClientToken(Request $request): string|array|null
    {
        // Get client token from storage
        //
        return $request->cookie($this->sessionName());
    }

    /**
     * Clear session token
     */
    public function clearClientToken(): void
    {
        // Clear client token in storage
        //
        Cookie::forget($this->sessionName());
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
        $params = $request->except(['broker', 'token', 'checksum']);

        // Generate an unique session token
        //
        $token = $this->generateClientToken();

        // Save session token in storage
        //
        $this->saveClientToken($token);

        // Generate the attachment checksum
        //
        $checksum = $this->generateAttachChecksum($token);

        // Get the server attachment route
        //
        $attach_url = $this->server_url . '/attach?' . http_build_query([
                'broker'   => $this->client_id,
                'token'    => $token,
                'checksum' => $checksum,
                ...$params
            ]);

        // Redirect to server attachment route
        //
        return redirect()->away($attach_url)->send();
    }

    /**
     * Check if session is attached
     *
     * @param Request $request
     * @return bool
     */
    public function isAttached(Request $request): bool
    {
        // Check if client token is exists
        //
        return !is_null($this->getClientToken($request));
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
     * @param Request $request
     *
     * @return bool|array
     * @throws GuzzleException
     * @throws JsonException
     */
    public function login(array $credentials, Request $request): bool|array
    {
        $url   = $this->server_url . '/login';
        $token = $this->getClientToken($request);
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $credentials, $headers);
    }

    /**
     * Send profile request
     * @param Request $request
     *
     * @return bool|string|array
     * @throws GuzzleException
     * @throws JsonException
     */
    public function profile(Request $request): bool|string|array
    {
        $url     = $this->server_url . '/profile';
        $token   = $this->getClientToken($request);
        $sid     = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'GET', $url, [], $headers);
    }

    /**
     * Send logout request
     * @param Request $request
     *
     * @return bool
     * @throws GuzzleException
     * @throws JsonException
     */
    public function logout(Request $request): bool
    {
        $url   = $this->server_url . '/logout';
        $token = $this->getClientToken($request);
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
     * @param Request $request
     *
     * @return false|string
     * @throws GuzzleException
     * @throws JsonException
     */
    public function commands(string $command, array $params, Request $request): bool|string
    {
        $url   = $this->server_url . '/commands/' .$command;
        $token = $this->getClientToken($request);
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $params ?? [], $headers);
    }

    /**
     * Add agent headers
     *
     * @param Request $request
     * @return array
     */
    protected function agentHeaders(Request $request): array
    {
        return [
            'Passport-User-Agent'     => $request->userAgent(),
            'Passport-Remote-Address' => $request->ip()
        ];
    }
}