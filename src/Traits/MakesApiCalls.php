<?php

namespace Jurager\Passport\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Collection;
use JsonException;

trait MakesApiCalls
{
    protected Client $http;

    protected ?Collection $result;

    /**
     * MakesApiCalls constructor.
     *
     * @throws GuzzleException
     */
    public function __construct()
    {
        $this->http = new Client([
            'connect_timeout' => config('passport.server.lookup.timeout'),
        ]);

        $this->result = $this->makeApiCall();
    }

    /**
     * Make the API call and get the response as a Laravel collection.
     *
     * @throws GuzzleException
     */
    protected function makeApiCall(): ?Collection
    {
        try {

            $response = $this->http->send($this->getRequest());

            return collect(json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));

        } catch (TransferException|JsonException $e) {

            return null;
        }
    }

    /**
     * Get the result of the API call as a Laravel collection.
     */
    public function getResult(): ?Collection
    {
        return $this->result;
    }
}
