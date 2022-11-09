<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Jurager\Passport\ServerBrokerManager;
use Jurager\Passport\Session\ServerSessionManager;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Events;

use Illuminate\Support\Facades\Auth;

class ServerAuthenticate
{
    protected ServerBrokerManager $broker;

    protected ServerSessionManager $session;

    public function __construct(ServerBrokerManager $broker, ServerSessionManager $session)
    {
        $this->broker = $broker;
        $this->session = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param null $guard
     * @return mixed
     * @throws \JsonException
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next, $guard = null): mixed
    {
        // Get Guard instance
        //
        $guard = $guard ?: Auth::guard();

        // Retrieve broker session
        //
        $sid = $this->broker->getBrokerSessionId($request);

        // Check if session exists in storage
        //
        if (!$this->session->has($sid)) {

            // Broker must be attached before authenticating users
            //
            return response()->json(['code' => 'not_attached', 'message' => 'Client broker not attached.'], 403);
        }

        try {
            // Validate broker session
            //
            $this->broker->validateBrokerSessionId($sid);

            // Check current user authorization
            //
            if ($user = $this->check($guard, $sid, $request)) {

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

    protected function check($guard, $sid)
    {
        // Decode account session data
        //
        $attributes = json_decode($this->session->getUserData($sid), true, 512, JSON_THROW_ON_ERROR);

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
