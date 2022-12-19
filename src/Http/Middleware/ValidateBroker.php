<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Server;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;
use Jurager\Passport\Exceptions\UnauthorizedException;

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
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
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

        } catch (InvalidClientException) {

            // Invalid client exception
            //
            return response()->json(['code' => 'invalid_client_id', 'message' => trans('passport::errors.invalid_client_id')], 403);

        } catch (InvalidSessionIdException $e) {

            // Invalid session exception
            //
            return response()->json(['code' => 'invalid_session_id', 'message' => $e->getMessage()], 403);

        } catch (UnauthorizedException $e) {

            // Unauthorized exception
            //
            return response()->json(['code' => 'unauthorized', 'message' => $e->getMessage()], 401);
        }
    }
}