<?php

namespace Jurager\Passport\Traits;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use JsonException;

trait MakesApiCalls
{
    /**
     * @var Client $http
     */
    protected Client $http;

    /**
     * @var Collection|null
     */
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
     * @return Collection|null
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
     *
     * @return Collection|null
     */
    public function getResult(): ?Collection
    {
        return $this->result;
    }
}