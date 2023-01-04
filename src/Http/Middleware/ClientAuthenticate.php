<?php

namespace Jurager\Passport\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory as Auth;
use Jurager\Passport\Broker;
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
     * Determine if the user is logged in to any of the given guards.
     *
     * @param Request $request
     * @param array $guards
     * @return mixed
     *
     * @throws AuthenticationException
     */
    protected function authenticate($request, array $guards): mixed
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {

                $this->auth->shouldUse($guard);

                return true;
            }
        }

        return $this->unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param Request $request
     * @param array $guards
     * @return mixed
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated($request, array $guards): mixed
    {
        // If request has bearer token
        //
        if($request->bearerToken()) {

            // Not authenticated message
            //
            throw new AuthenticationException('Unauthenticated.', $guards, $this->redirectTo($request));
        }

        // Redirect to authentication page
        //
        return redirect(config('passport.broker.auth_url').'?continue='.$request->fullUrl())->send();
    }
}
