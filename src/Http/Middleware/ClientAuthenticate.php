<?php

namespace Jurager\Passport\Http\Middleware;

use Closure;
use Jurager\Passport\Exceptions\InvalidSessionIdException;
use Jurager\Passport\Exceptions\NotAttachedException;
use Jurager\Passport\Exceptions\RedirectLoopException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;

class ClientAuthenticate implements AuthenticatesRequests
{
    /**
     * The authentication factory instance.
     *
     * @var Auth
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
     * @param Auth $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
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
     * @param Request $request
     * @return mixed
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

                // Reset auth redirect counter on successful authentication
                session()->forget('sso_auth_redirect_count');

                return true;
            }
        } catch (InvalidSessionIdException) {
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
    protected function unauthenticated(Request $request): mixed
    {
        // Not authenticated message
        throw new AuthenticationException('Unauthenticated.', redirectTo: $this->redirectTo($request));
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            $authUrl = config('passport.broker.auth_url');

            if (empty($authUrl)) {
                return null;
            }

            // Prevent infinite redirect loops
            $authRedirectCount = session('sso_auth_redirect_count', 0);
            $maxAttempts = config('passport.max_redirect_attempts', 3);

            if ($authRedirectCount >= $maxAttempts) {
                session()->forget('sso_auth_redirect_count');
                throw new RedirectLoopException('SSO authentication', $authRedirectCount);
            }

            session(['sso_auth_redirect_count' => $authRedirectCount + 1]);

            return $authUrl.'?continue='.$request->fullUrl();
        }

        return null;
    }
}
