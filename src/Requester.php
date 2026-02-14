<?php

namespace Jurager\Passport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Illuminate\Support\Facades\Log;
use JsonException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Jurager\Passport\Exceptions\UnauthorizedException;
use RuntimeException;

class Requester
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Make HTTP request to SSO server
     *
     * @throws GuzzleException
     * @throws InvalidSessionIdException
     * @throws InvalidClientException
     * @throws NotAttachedException
     * @throws UnauthorizedException
     * @throws RuntimeException
     * @throws JsonException
     */
    public function request($sid, $method, $url, array $params = [], array $headers = []): bool|string|array
    {
        try {
            $headers = array_merge($headers, [
                'Authorization' => 'Bearer '.$sid,
                'Accept' => 'application/json',
            ]);

            $response = $this->client->request($method, $url, [
                'query' => $method === 'GET' ? $params : [],
                'form_params' => $method === 'POST' ? $params : [],
                'headers' => $headers,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {

            $req = $e->getRequest();
            $res = $e->getResponse();

            // If debug is enabled in configuration
            if (config('passport.debug')) {
                Log::debug(Psr7\Message::toString($req));

                if ($res) {
                    Log::debug(Psr7\Message::toString($res));
                }
            }

            if ($res) {
                $this->throwException($req, $res);
            }

            return false;
        }
    }

    /**
     * Throw exception based on request exception
     *
     * @throws InvalidSessionIdException
     * @throws InvalidClientException
     * @throws NotAttachedException
     * @throws UnauthorizedException
     * @throws RuntimeException
     * @throws JsonException
     */
    protected function throwException($request, $response): void
    {
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $body->rewind();

        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if (is_array($data) && array_key_exists('code', $data)) {

            throw match ($data['code']) {
                'invalid_session_id' => new InvalidSessionIdException($data['message'], $status),
                'invalid_client_id' => new InvalidClientException($data['message']),
                'not_attached' => new NotAttachedException($status, $data['message']),
                'unauthorized' => new UnauthorizedException($status, $data['message']),
                default => new RuntimeException($data['message']),
            };
        }
    }
}
