<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Jurager\Passport\ServerBrokerManager;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\InvalidClientException;

class ValidateBroker
{
    protected ServerBrokerManager $broker;

    public function __construct(ServerBrokerManager $broker)
    {
        $this->broker = $broker;
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
            $sid = $this->broker->getBrokerSessionId($request);

            // Validate broker session
            //
            $this->broker->validateBrokerSessionId($sid);

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
