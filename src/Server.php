<?php

namespace Jurager\Passport;

use Illuminate\Http\Request;
use Jurager\Passport\Exceptions\InvalidServerException;
use Jurager\Passport\Exceptions\InvalidSessionIdException;

/**
 * Class Server
 */
class Server
{
    protected Encryption $encryption;

    private mixed $model;

    private string $id_field;

    private string $secret_field;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Encryption
        $this->encryption = new Encryption();

        // Broker model
        $this->model = config('passport.server.model');

        // Broker model id field
        $this->id_field = config('passport.server.id_field');

        // Broker model secret field
        $this->secret_field = config('passport.server.secret_field');

        // Model brokers not found
        if (! class_exists($this->model)) {
            throw new InvalidServerException("Class $this->model does not exist");
        }

        // Server model id field not found
        if (empty($this->id_field)) {
            throw new InvalidServerException(trans('passport::errors.invalid_server_model_field'));
        }

        // Server model secret field not found
        if (empty($this->secret_field)) {
            throw new InvalidServerException(trans('passport::errors.invalid_server_model_secret'));
        }
    }

    /**
     * Find broker by id
     *
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function findBrokerById($id): mixed
    {
        // Get broker model from database
        $model = $this->model::where($this->id_field, $id)->first();

        // Check if broker exists
        if (! $model) {

            // Broker not exists exception
            throw new InvalidSessionIdException("Broker with $this->id_field:$id not found");
        }

        // Return broker model
        return $model;
    }

    /**
     * Validate broker session id
     *
     *
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function validateBrokerSessionId(?string $sid): string
    {
        // Get broker and token from session
        [$broker_id, $token] = $this->getBrokerInfoFromSessionId($sid);

        // Find broker model once to avoid duplicate queries
        $broker = $this->findBrokerById($broker_id);

        // Compare checksum with session
        if ($this->generateSessionId($broker, $token) !== $sid) {
            throw new InvalidSessionIdException(trans('passport::errors.checksum_failed'));
        }

        // Return broker identification
        return $broker_id;
    }

    /**
     * Generate session id
     *
     * @param mixed $broker Broker model instance
     * @param string $token Session token
     * @return string Session identification
     */
    public function generateSessionId(mixed $broker, string $token): string
    {
        // Get broker id and secret from model
        $broker_id = $broker->{$this->id_field};
        $secret = $broker->{$this->secret_field};

        // Generate broker checksum
        $checksum = $this->encryption->generateChecksum('session', $token, $secret);

        // Return session identification
        return "Passport-$broker_id-$token-$checksum";
    }

    /**
     * Verify attach checksum
     *
     * @param mixed $broker Broker model instance
     * @param string $token Attach token
     * @param string $checksum Checksum to verify
     * @return bool Verification result
     */
    public function verifyAttachChecksum(mixed $broker, string $token, string $checksum): bool
    {
        // Get broker secret from model
        $secret = $broker->{$this->secret_field};

        // Return verification result
        return $this->encryption->verifyChecksum('attach', $token, $secret, $checksum);
    }

    /**
     * Return broker info from sid
     */
    public function getBrokerInfoFromSessionId(?string $sid): array
    {
        // Check if session id is null or empty
        if (empty($sid)) {
            throw new InvalidSessionIdException(trans('passport::errors.invalid_session_id'));
        }

        // Check session matching
        if (! preg_match('/^Passport-([\w\-]+)-([a-f\d]+)-([a-f\d]+)$/i', $sid, $matches)) {

            // Invalid session identification exception
            throw new InvalidSessionIdException(trans('passport::errors.invalid_session_id'));
        }

        // Get the first match
        array_shift($matches);

        // Return broker identification
        return $matches;
    }

    /**
     * Retrieve broker session id from request
     */
    public function getBrokerSessionId(Request $request): ?string
    {
        // Get bearer token from request
        $token = $request->bearerToken();

        if (! $token) {
            $token = $request->input('access_token');
        }

        if (! $token) {
            $token = $request->input('sso_session');
        }

        return $token;
    }

    /**
     * Return broker model from Http Request
     */
    public function getBrokerFromRequest(Request $request): mixed
    {
        // Retrieve broker session
        $sid = $this->getBrokerSessionId($request);

        // Return broker info from session identification
        [$broker_id] = $this->getBrokerInfoFromSessionId($sid);

        // Return broker model
        return $this->findBrokerById($broker_id);
    }
}
