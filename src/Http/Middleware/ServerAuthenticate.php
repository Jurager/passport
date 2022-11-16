<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Jurager\Passport\Events;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Server;
use Jurager\Passport\Storage;

class ServerAuthenticate
{
    protected Server $server;

    protected Storage $storage;

    public function __construct(Server $server, Storage $storage)
    {
        $this->server = $server;
        $this->storage = $storage;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param null $guard
     * @return mixed
     * @throws JsonException
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next, $guard = null): mixed
    {
        // Get Guard instance
        //
        $guard = $guard ?: Auth::guard();

        // Retrieve broker session
        //
        $sid = $this->server->getBrokerSessionId($request);

        // Check if session exists in storage
        //
        if (!$this->storage->has($sid)) {

            // Broker must be attached before authenticating users
            //
            return response()->json(['code' => 'not_attached', 'message' => 'Client broker not attached.'], 403);
        }

        try {
            // Validate broker session
            //
            $this->server->validateBrokerSessionId($sid);

            // Check current user authorization
            //
            if ($user = $this->check($guard, $sid)) {

                //  Succeeded auth event
                //
                event(new Events\Authenticated($user, $request));

                // Next
                //
                return $next($request);
            }

            // Unauthorized exception
            //
            return response()->json(['code' => 'unauthorized', 'message' => 'Unauthorized.'], 401);

        } catch(InvalidSessionIdException $e) {

            // Invalid session exception
            //
            return response()->json(['code' => 'invalid_session_id', 'message' => $e->getMessage()], 403);
        }
    }

    /**
     * @throws JsonException
     */
    protected function check($guard, $sid)
    {
        // Decode account session data
        //
        $attributes = json_decode($this->storage->getUserData($sid), true);

        if (!empty($attributes)) {

            // Retrieve user by credentials from payload
            //
            $user = $guard->getProvider()->retrieveByCredentials($attributes);

            if ($user && $guard->onceUsingId($user->id)) {
                return $user;
            }
        }

        return false;
    }
}
