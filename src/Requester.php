<?php

namespace Jurager\Passport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Illuminate\Support\Facades\Log;

class Requester
{
    protected mixed $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Client;
    }

    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public function debugEnabled(): bool
    {
        return config('passport.debug') === true;
    }

    /**
     * Generate new checksum
     *
     * @param $sid
     * @param $method
     * @param $url
     * @param array $params
     * @param array $headers
     * @return bool|string
     * @throws GuzzleException
     */
    public function request($sid, $method, $url, array $params = [], array $headers = []): bool|string|array
    {
        try {
            $headers = array_merge($headers, [
                'Authorization' => 'Bearer ' . $sid,
                'Accept' => 'application/json'
            ]);

            $response = $this->client->request($method, $url, [
                'query' => $method === 'GET' ? $params : [],
                'form_params' => $method === 'POST' ? $params : [],
                'headers' => $headers
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {

            $req = $e->getRequest();
            $res = $e->getResponse();

            if ($this->debugEnabled()) {
                if ($req) {
                    Log::debug(Psr7\Message::toString($req));
                }

                if ($res) {
                    Log::debug(Psr7\Message::toString($res));
                }
            }

            if ($req && $res) {
                $this->throwException($req, $res);
            }

            return false;
        }
    }

    /**
     * Trow exception base on request exception
     *
     * @throw Jurager\Passport\Exceptions\InvalidSessionIdException
     * @throw Jurager\Passport\Exceptions\InvalidClientException
     * @throw Jurager\Passport\Exceptions\UnauthorizedException
     * @throw Jurager\Passport\Exceptions\NotAttachedException
     */
    protected function throwException($request, $response): void
    {
        $status = $response->getStatusCode();
        $body   = $response->getBody();
        $body->rewind();

        $jsonResponse = json_decode($body->getContents(), true);

        if ($jsonResponse && array_key_exists('code', $jsonResponse)) {

            switch($jsonResponse['code']) {
                case 'invalid_session_id':
                    throw new InvalidSessionIdException($jsonResponse['message'], $status);
                    break;
                case 'invalid_client_id':
                    throw new InvalidClientException($jsonResponse['message']);
                    break;
                case 'not_attached':
                    throw new NotAttachedException($status, $jsonResponse['message']);
                    break;
            }
        }
    }
}
