<?php

namespace Jurager\Passport\Http\Concerns;

use Jurager\Passport\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait Authenticate
{
    /**
     * Authenticate user from request
     */
    protected function authenticate(Request $request, $broker): bool
    {
        if ($user = $this->attemptLogin($request)) {

            event(new Events\Authenticated($user, $request));

            $sid = $this->broker->getBrokerSessionId($request);
            $credentials = json_encode($this->sessionCredentials($request), JSON_THROW_ON_ERROR);

            // @todo: Manage to use remember $request->has('remember')
            //
            $this->session->setUserData($sid, $credentials);

            return true;
        }

        return false;
    }

    /**
     * Attempt login
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function attemptLogin(Request $request)
    {
        if ($this->guard()->once(
            $this->loginCredentials($request)
        )) {
            $user = $this->guard()->user();
            return $this->afterAuthenticatingUser($user, $request);
        }

        return null;
    }

    /**
     * Return login credentials
     *
     * @param Request $request
     *
     * @return array
     */
    protected function loginCredentials(Request $request)
    {
        return $request->only($this->username($request), 'password');
    }

     /**
     * Return username
     *
     * @param Request $request
     *
     * @return string
     */
    protected function username(Request $request): string
    {
        return $request->input('login', 'email');
    }

     /**
     * Return session credentials
     *
     * @param Request $request
     *
     * @return array
     */
    protected function sessionCredentials(Request $request)
    {
        $field = $this->username($request);
        $value = $request->input($field);

        return [$field => $value];
    }

    /**
     * Return default guard
     *
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Return user info
     *
     * @param mixed $user
     * @param Request $request
     * @return mixed
     */
    protected function userInfo(mixed $user, Request $request): mixed
    {
        $closure = config('passport.user_info');

        if (is_callable($closure)) {
            $broker = $this->broker->getBrokerFromRequest($request);

            return $closure($user, $broker, $request);
        }

        return $user->toArray();
    }

    /**
     * Do additional verification by calling after_authenticating closure.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  \Symfony\Component\HttpFoundation\Request|null $request
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function afterAuthenticatingUser($user, $request)
    {
        $closure = config('passport.after_authenticating');
        $broker = $this->broker->getBrokerFromRequest($request);

        if ($user && is_callable($closure) && !$closure($user, $broker, $request)) {

            // Reset user to null if closure return false
            //
            return null;
        }

        return $user;
    }
}
