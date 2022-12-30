<?php

namespace Jurager\Passport\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class ClientAuthenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('common.index');
        }
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        $auth_url = config('passport.broker.auth_url');

        // If request has bearer token
        //
        if($request->bearerToken()) {

            // Not authenticated message
            //
            throw new AuthenticationException('Unauthenticated.', $guards, $this->redirectTo($request));
        }

        // Redirect to authentication page
        //
        return redirect($auth_url.'?continue='.$request->fullUrl())->send();
    }
}
