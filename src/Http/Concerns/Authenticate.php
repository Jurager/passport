<?php

namespace Jurager\Passport\Http\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Jurager\Passport\Events;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;

trait Authenticate
{
    /**
     * Authenticate user from request
     *
     * @throws JsonException
     */
    protected function authenticate(Request $request, $broker): bool
    {
        // Attempt to authenticate
        //
        if ($user = $this->attemptLogin($request)) {

            //  Succeeded auth event
            //
            event(new Events\Authenticated($user, $request));

            // Retrieve broker session
            //
            $sid = $this->server->getBrokerSessionId($request);

            // Retrieve credentials from session
            //
            $credentials = json_encode($this->sessionCredentials($request), JSON_THROW_ON_ERROR);

            // @todo: Manage to use remember $request->has('remember')
            //
            $this->storage->setUserData($sid, $credentials);

            // Success
            //
            return true;
        }

        return false;
    }

    /**
     * Attempt login
     *
     * @param Request $request
     * @return Authenticatable|null
     */
    protected function attemptLogin(Request $request): ?Authenticatable
    {
        // Trying to authenticate
        //
        if (Auth::guard()->once($this->loginCredentials($request))) {

            // Retrieve current user
            //
            $user = Auth::guard()->user();

            // Return with additional verification
            //
            return $this->afterAuthenticatingUser($user, $request);
        }

        return null;
    }

    /**
     * Return login credentials
     *
     * @param Request $request
     * @return array
     */
    protected function loginCredentials(Request $request): array
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
     * @return array
     */
    protected function sessionCredentials(Request $request): array
    {
        $field = $this->username($request);
        $value = $request->input($field);

        return [$field => $value];
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
        // Retrieve user_info closure from configuration
        //
        $closure = config('passport.user_info');

        // Return closure if it is callable
        //
        if (is_callable($closure)) {

            // Retrieve broker model from request
            //
            $broker = $this->server->getBrokerFromRequest($request);

            // Return closure if it is callable
            //
            return $closure($user, $broker, $request);
        }

        // Return current user
        //
        return $user;
    }

    /**
     * Do additional verification by calling after_authenticating closure.
     *
     * @param Authenticatable $user
     * @param Request $request
     * @return Authenticatable|null
     */
    protected function afterAuthenticatingUser(Authenticatable $user, Request $request): ?Authenticatable
    {
        // Retrieve after_authenticating closure from configuration
        //
        $closure = config('passport.after_authenticating');

        // Retrieve broker model from request
        //
        $broker = $this->server->getBrokerFromRequest($request);

        // Return closure if it is callable
        //
        if (is_callable($closure) && !$closure($user, $broker, $request)) {

            // Reset user to null if closure return false
            //
            return null;
        }

        // Return current user
        //
        return $user;
    }
}
