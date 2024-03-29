<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Jurager\Passport\Events;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\UnauthorizedException;
use Jurager\Passport\Models\History;
use Jurager\Passport\Server;
use Jurager\Passport\Session\ServerSessionManager;

class ServerAuthenticate
{
    protected Server $server;

    protected ServerSessionManager $storage;

    public function __construct(Server $server, ServerSessionManager $storage)
    {
        $this->server = $server;
        $this->storage = $storage;
    }

    /**
     * Handle an incoming request.
     *
     * @param  null  $guard
     *
     * @throws JsonException
     */
    public function handle(Request $request, Closure $next, $guard = null): mixed
    {
        // Get Guard instance
        $guard = $guard ?: Auth::guard();

        // Retrieve broker session
        $sid = $this->server->getBrokerSessionId($request);

        // Check if session exists in storage
        if (! $this->storage->has($sid)) {

            // Broker must be attached before authenticating users
            return response()->json(['code' => 'not_attached', 'message' => trans('passport::errors.not_attached')], 403);
        }

        try {
            // Validate broker session
            $this->server->validateBrokerSessionId($sid);

            // Check current user authorization
            if ($user = $this->check($guard, $sid)) {

                //  Succeeded auth event
                event(new Events\Authenticated($user, $request));

                // Update session expiration date
                $this->storage->refresh($sid);

                // Update expires_at date in history table
                History::where('session_id', $this->storage->get($sid))
                    ->update(['expires_at' => date('Y-m-d H:i:s', strtotime('+'.config('session.lifetime').' minutes'))]);
            }

            // Next
            return $next($request);

        } catch (InvalidSessionIdException $e) {

            // Invalid session exception
            return response()->json(['code' => 'invalid_session_id', 'message' => $e->getMessage()], 403);

        } catch (UnauthorizedException $e) {

            // Unauthorized exception
            return response()->json(['code' => 'unauthorized', 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * @return false|mixed
     */
    protected function check($guard, $sid): mixed
    {
        // Decode account session data
        $attributes = json_decode($this->storage->getUserData($sid), true);

        if (! empty($attributes)) {

            // Retrieve user by credentials from payload
            $user = $guard->getProvider()->retrieveByCredentials($attributes);

            if ($user && $guard->onceUsingId($user->id)) {
                return $user;
            }
        }

        return false;
    }
}
