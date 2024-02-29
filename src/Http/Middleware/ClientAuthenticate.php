<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Jurager\Passport\Exceptions\UnauthorizedException;

class ClientAuthenticate implements AuthenticatesRequests
{
    /**
     * The authentication factory instance.
     */
    protected Auth $auth;

    /**
     * The group of routes that should be authorized
     */
    protected string $group;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->authenticate($request);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     *
     * @throws AuthenticationException
     */
    protected function authenticate(Request $request): mixed
    {
        if ($token = $request->bearerToken()) {
            $this->auth->guard()->loginFromToken($token);
        }

        try {
            if ($this->auth->guard()->check()) {
                return true;
            }
        } catch (InvalidSessionIdException) {
            throw new NotAttachedException(403, 'Client broker not attached.');
        } catch (UnauthorizedException) {
            throw new AuthenticationException('Unauthenticated.', redirectTo: $this->redirectTo($request));
        }

        return false;
    }

    /**
     * Handle an unauthenticated user.
     *
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated(Request $request): mixed
    {
        // Not authenticated message
        //
        throw new AuthenticationException('Unauthenticated.', redirectTo: $this->redirectTo($request));
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request)
    {
        if (! $request->expectsJson()) {

            // Redirect to authentication page
            //
            return redirect(config('passport.broker.auth_url').'?continue='.$request->fullUrl())->send();
        }
    }
}
