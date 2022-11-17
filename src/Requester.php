<?php

namespace Jurager\Passport;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Jurager\Passport\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Requester
{
    protected mixed $client;
    protected bool $debug;

    public function __construct($client = null)
    {
        $this->client = $client ?: new Client;
        $this->debug  = config('passport.debug');
    }

    /**
     * Generate new checksum
     *
     * @param $sid
     * @param $method
     * @param $url
     * @param array $params
     * @param array $headers
     * @return bool|string|array
     * @throws GuzzleException
     * @throws JsonException
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

            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        } catch (RequestException|JsonException $e) {

            $req = $e->getRequest();
            $res = $e->getResponse();

            if ($this->debug) {
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
     * @param $request
     * @param $response
     * @throws JsonException
     * @throws NotAttachedException
     * @throws UnauthorizedException
     */
    protected function throwException($request, $response): void
    {
        $status = $response->getStatusCode();
        $body   = $response->getBody();
        $body->rewind();

        $jsonResponse = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        if ($jsonResponse && array_key_exists('code', $jsonResponse)) {

            throw match ($jsonResponse['code']) {
                'invalid_session_id' => new InvalidSessionIdException($jsonResponse['message'], $status),
                'invalid_client_id' => new InvalidClientException($jsonResponse['message']),
                'unauthorized' => new UnauthorizedException($jsonResponse['message'], $status),
                'not_attached' => new NotAttachedException($jsonResponse['message'], $status),
                default => new RuntimeException($jsonResponse['message']),
            };
        }
    }
}
