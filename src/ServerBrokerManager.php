<?php

namespace Jurager\Passport;

use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Illuminate\Http\Request;

/**
 * Class ServerBrokerManager
 */
class ServerBrokerManager
{
    /**
     * @var Encryption
     */
    protected Encryption $encryption;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->encryption = new Encryption;
    }
    /**
     * Return broker model
     *
     * @return mixed
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function brokerModel(): mixed
    {
        $class = config('passport.server.model');

        if (!class_exists($class)) {
            throw new InvalidSessionIdException("Class $class does not exist");
        }

        return $class;
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
        $class    = $this->brokerModel();
        $id_field = config('passport.server.id_field');
        $model    = $class::where($id_field, $id)->first();

        if (!$model) {
            throw new InvalidSessionIdException("Model $class with $id_field:$id not found");
        }

        return $model;
    }

    /**
     * Find broker secret
     *
     * @param $model
     * @return string
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function findBrokerSecret($model): string
    {
        $secret_field = config('passport.server.secret_field');

        return $model->$secret_field;
    }

    /**
     * Validate broker session id
     *
     * @param string $sid
     *
     * @return string
     * @throw \Jurager\Passport\Exceptions\InvalidSessionIdException
     */
    public function validateBrokerSessionId(string $sid): string
    {
        list($broker_id, $token) = $this->getBrokerInfoFromSessionId($sid);

        if ($this->generateSessionId($broker_id, $token) != $sid) {
            throw new InvalidSessionIdException('Checksum failed: Client IP address may have changed');
        }

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
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);
        $checksum = $this->encryption->generateChecksum(
            'session', $token, $secret
        );

        return "Passport-$broker_id-$token-$checksum";
    }

    /**
     * Generate attach checksum
     *
     * @param string $broker_id
     * @param string $token
     *
     * @return string
     */
    public function generateAttachChecksum(string $broker_id, string $token): string
    {
        $model  = $this->findBrokerById($broker_id);
        $secret = $this->findBrokerSecret($model);

        return $this->encryption->generateChecksum('attach', $token, $secret);
    }

    /**
     * Return broker info from sid
     *
     * @param string $sid
     * @return array
     */
    public function getBrokerInfoFromSessionId(string $sid): array
    {
        if (!preg_match('/^Passport-(\w*+)-(\w*+)-([a-z\d]*+)$/', $sid, $matches)) {
            throw new InvalidSessionIdException('Invalid session id');
        }

        array_shift($matches);

        return $matches;
    }

    /**
     * Retrieve broker session id from request
     *
     * @param $request
     * @return string
     */
    public function getBrokerSessionId($request): string
    {
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
        $sid = $this->getBrokerSessionId($request);

        [$broker_id] = $this->getBrokerInfoFromSessionId($sid);

        return $this->findBrokerById($broker_id);
    }
}
