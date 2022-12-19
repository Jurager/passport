<?php

namespace Jurager\Passport\Traits;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Jurager\Passport\Events\FailedApiCall;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

trait MakesApiCalls
{
    /**
     * @var Client $httpClient
     */
    protected Client $httpClient;

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
        $this->httpClient = new Client([
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

            $response = $this->httpClient->send($this->getRequest());

            return collect(json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR));

        } catch (TransferException|\JsonException $e) {

            event(new FailedApiCall($e));

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