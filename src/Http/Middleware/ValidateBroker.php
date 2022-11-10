<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Jurager\Passport\Server;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;

class ValidateBroker
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next): mixed
    {
        try {

            // Retrieve broker session
            //
            $sid = $this->server->getBrokerSessionId($request);

            // Validate broker session
            //
            $this->server->validateBrokerSessionId($sid);

            // Next
            //
            return $next($request);

        } catch(InvalidClientException) {

            // Invalid client exception
            //
            return response()->json(['code' => 'invalid_client_id', 'message' => 'Invalid client id.'], 403);

        } catch(InvalidSessionIdException $e) {

            // Invalid session exception
            //
            return response()->json(['code' => 'invalid_session_id', 'message' => $e->getMessage()], 403);
        }
    }
}
