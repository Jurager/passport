<?php

namespace Jurager\Passport;

use Jurager\Passport\Exceptions\InvalidServerException;
use Jurager\Passport\Exceptions\InvalidSessionIdException;

use Illuminate\Http\Request;

/**
 * Class Server
 */
class Server
{
    /**
     * @var Encryption
     */
    protected Encryption $encryption;

    /**
     * @var
     */
    private mixed $model;

    /**
     * @var string
     */
    private string $id_field;

    /**
     * @var string
     */
    private string $secret_field;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Encryption
        //
        $this->encryption = new Encryption;

        // Broker model
        //
        $this->model = config('passport.server.model');

        // Broker model id field
        //
        $this->id_field = config('passport.server.id_field');

        // Broker model secret field
        //
        $this->secret_field = config('passport.server.secret_field');

        // Model brokers not found
        //
        if (!class_exists($this->model)) {
            throw new InvalidSessionIdException("Class $this->model does not exist");
        }

        // Server model id field not found
        //
        if (empty($this->id_field)) {
            throw new InvalidServerException(trans('passport::errors.invalid_server_model_field'));
        }

        // Server model secret field not found
        //
        if (empty($this->secret_field)) {
            throw new InvalidServerException(trans('passport::errors.invalid_server_model_secret'));
        }
    }


    /**
     * Find broker by id
     *
     * @param $id
     * @return mixed
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function findBrokerById($id): mixed
    {
        // Get broker model from database
        //
        $model = $this->model::where($this->id_field, $id)->first();

        // Check if broker exists
        //
        if (!$model) {

            // Broker not exists exception
            //
            throw new InvalidSessionIdException("Model $this->model with $this->id_field:$id not found");
        }

        // Return broker model
        //
        return $model;
    }

    /**
     * Validate broker session id
     *
     * @param string $sid
     *
     * @return string
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function validateBrokerSessionId(string|null $sid): string
    {
        // Get broker and token from session
        //
        [$broker_id, $token] = $this->getBrokerInfoFromSessionId($sid);

        // Compare checksum with session
        //
        if ($this->generateSessionId($broker_id, $token) !== $sid) {
            throw new InvalidSessionIdException(trans('passport::errors.checksum_failed'));
        }

        // Return broker identification
        //
        return $broker_id;
    }

    /**
     * Generate session id
     *
     * @param string $broker_id
     * @param string $token
     *
     * @return string
     */
    public function generateSessionId(string $broker_id, string $token): string
    {
        // Get broker secret field
        //
        $secret = $this->findBrokerById($broker_id)->{$this->secret_field};

        // Generate broker checksum
        //
        $checksum = $this->encryption->generateChecksum('session', $token, $secret);

        // Return session identification
        //
        return "Passport-$broker_id-$token-$checksum";
    }

    /**
     * Verify attach checksum
     *
     * @param string $broker_id
     * @param string $checksum
     * @param string $token
     *
     * @return string
     */
    public function verifyAttachChecksum(string $broker_id, string $token, string $checksum): string
    {
        // Get broker secret field
        //
        $secret = $this->findBrokerById($broker_id)->{$this->secret_field};

        // Return generated checksum
        //
        return $this->encryption->verifyChecksum('attach', $token, $secret, $checksum);
    }

    /**
     * Return broker info from sid
     *
     * @param string|null $sid
     * @return array
     */
    public function getBrokerInfoFromSessionId(string|null $sid): array
    {
        // Check session matching
        //
        if (!preg_match('/^Passport-(\w*+)-(\w*+)-([a-z\d]*+)$/', $sid, $matches)) {

            // Invalid session identification exception
            //
            throw new InvalidSessionIdException(trans('passport::errors.invalid_session_id'));
        }

        // Get the first match
        //
        array_shift($matches);

        // Return broker identification
        //
        return $matches;
    }

    /**
     * Retrieve broker session id from request
     *
     * @param $request
     * @return string|null
     */
    public function getBrokerSessionId($request): string|null
    {
        // Get bearer token from request
        //
        $token = $request->bearerToken();

        if (!$token) {
            $token = $request->input('access_token');
        }

        if (!$token) {
            $token = $request->input('sso_session');
        }

        return $token;
    }

    /**
     * Return broker model from Http Request
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getBrokerFromRequest(Request $request): mixed
    {
        // Retrieve broker session
        //
        $sid = $this->getBrokerSessionId($request);

        // Return broker info from session identification
        //
        [$broker_id] = $this->getBrokerInfoFromSessionId($sid);

        // Return broker model
        //
        return $this->findBrokerById($broker_id);
    }
}