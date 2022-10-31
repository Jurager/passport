<?php

namespace Jurager\Passport;

use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Session\ClientSessionManager;
use Illuminate\Http\Request;

/**
 * Class ClientBrokerManager
 */
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
     * Constructor
     *
     * @param Requester|null $httpClient
     */
    public function __construct(Requester $httpClient = null)
    {
        $this->encryption = new Encryption;
        $this->session = new ClientSessionManager;
        $this->requester = new Requester($httpClient);
    }

    /**
     * Return the client id
     *
     * @return string
     * @throw Jurager\Passport\Exceptions\InvalidClientException
     */
    public function clientId(): string
    {
        $client_id = config('passport.broker_client_id');

        if (empty($client_id)) {
            throw new InvalidClientException(
                'Invalid client id. Please make sure the client id is defined in config.'
            );
        }

        return $client_id;
    }

    /**
     * Return the client secret
     *
     * @return string
     * @throw Jurager\Passport\Exceptions\InvalidClientException
     */
    public function clientSecret(): string
    {
        $client_secret = config('passport.broker_client_secret');

        if (empty($client_secret)) {
            throw new InvalidClientException(
                'Invalid client secret. Please make sure the client secret is defined in config.'
            );
        }

        return $client_secret;
    }

    /**
     * Return the server url
     *
     * @param string $path
     * @return string
     * @throw Jurager\Passport\Exceptions\InvalidClientException
     */
    public function serverUrl(string $path = ''): string
    {
        $server_url = config('passport.broker_server_url');

        if (empty($server_url)) {
            throw new InvalidClientException(
                'Invalid server url. Please make sure the server url is defined in config.'
            );
        }

        return $server_url . $path;
    }

    /**
     * Generate an unique session token
     *
     * @return string
     */
    public function generateClientToken(): string
    {
        return $this->encryption->randomToken();
    }

    /**
     * Save session token
     */
    public function saveClientToken($token): void
    {
        $this->session->set($this->sessionName(), $token);
    }

    /**
     * Return session token
     *
     * @return string
     */
    public function getClientToken(): mixed
    {
        return $this->session->get($this->sessionName());
    }

    /**
     * Clear session token
     */
    public function clearClientToken(): void
    {
        $this->session->forget($this->sessionName());
    }

    /**
     * Return the session name used to store session id.
     *
     * @return string
     */
    public function sessionName(): string
    {
        return 'sso_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->clientId()));
    }

    /**
     * Check if session is attached
     *
     * @return bool
     */
    public function isAttached(): bool
    {
        return !is_null($this->getClientToken());
    }

    /**
     * Return the session id
     *
     * @param string $token The client generated token
     * @return string
     */
    public function sessionId(string $token): string
    {
        $checksum = $this->encryption->generateChecksum(
            'session', $token, $this->clientSecret()
        );

        return "SSO-{$this->clientId()}-$token-$checksum";
    }

    /**
     * Generate the attachment checksum. Use the encryption algorithm.
     *
     * @param string $token
     * @return string
     */
    public function generateAttachChecksum(string $token): string
    {
        return $this->encryption->generateChecksum(
            'attach', $token, $this->clientSecret()
        );
    }

    /**
     * Send login request
     *
     * @param array $credentials
     * @param Request|null $request
     *
     * @return bool|array
     */
    public function login(array $credentials, Request $request = null): bool|array
    {
        $url   = $this->serverUrl('/login');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'POST', $url, $credentials, $headers);
    }

    /**
     * Send profile request
     * @param Request|null $request
     *
     * @return false|string
     */
    public function profile(Request $request = null): bool|string
    {
        $url   = $this->serverUrl('/profile');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        return $this->requester->request($sid, 'GET', $url, [], $headers);
    }

    /**
     * Send logout request
     * @param Request|null $request
     *
     * @return bool
     */
    public function logout(Request $request = null): bool
    {
        $url   = $this->serverUrl('/logout');
        $token = $this->getClientToken();
        $sid   = $this->sessionId($token);
        $headers = $this->agentHeaders($request);

        $response = $this->requester->request($sid, 'POST', $url, [], $headers);

        return $response['success'] === true;
    }

    /**
     * Send a command request
     *
     * @param string $command
     * @param array $params
     * @param Request|null $request
     *
     * @return false|string
     */
    public function commands(string $command, array $params = [], Request $request = null): bool|string
    {
        $url   = $this->serverUrl("/commands/$command");
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
                'SSO-User-Agent' => $request->userAgent(),
                'SSO-REMOTE-ADDR' => $request->ip()
            ];
        }

        return $headers;
    }
}
