<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Jurager\Passport\Broker;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;

class ClientAuthenticate implements AuthenticatesRequests
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected Auth $auth;

    /**
     * The group of routes that should be authorized
     *
     * @var string
     */
    protected string $group;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate($request);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param Request $request
     * @return mixed
     *
     * @throws AuthenticationException
     */
    protected function authenticate($request): mixed
    {
        if($token = $request->bearerToken()) {
            $this->auth->guard()->loginFromToken($token);
        }
        
        try {
            if ($this->auth->guard()->check()) {
                return true;
            }
        }
        catch (InvalidSessionIdException $e) {
            throw new NotAttachedException(403, 'Client broker not attached.');
        }

        return $this->unauthenticated($request);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param Request $request
     * @return mixed
     *
     * @throws AuthenticationException
     */
    protected function unauthenticated($request): mixed
    {
        // Not authenticated message
        //
        throw new AuthenticationException('Unauthenticated.', redirectTo: $this->redirectTo($request));
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     */
    protected function redirectTo($request)
    {
        if(!$request->expectsJson()) {

            // Redirect to authentication page
            //
            return redirect(config('passport.broker.auth_url').'?continue='.$request->fullUrl())->send();
        }
    }
}
