<?php

namespace Jurager\Passport;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Exception\GuzzleException;

class PassportGuard implements Guard
{
    use GuardHelpers, Macroable;

    /**
     * The name of the guard. Typically "web".
     *
     * Corresponds to guard name in authentication configuration.
     *
     * @var string
     */
    public readonly string $name;

    /**
     * The user provider implementation.
     *
     * @var Broker
     */
    protected Broker $broker;

    /**
     * The request instance.
     *
     * @var \Symfony\Component\HttpFoundation\Request|Request|null
     */
    protected \Symfony\Component\HttpFoundation\Request|Request|null $request;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected \Illuminate\Contracts\Events\Dispatcher $events;

    /**
     * Create a new authentication guard.
     *
     * @param $name
     * @param UserProvider $provider
     * @param Broker $broker
     * @param Request|null $request
     */
    public function __construct($name, UserProvider $provider, Broker $broker, Request $request = null)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->broker = $broker;
        $this->request = $request;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return false;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|RedirectResponse|null
     * @throws GuzzleException|\JsonException
     */
    public function user() : Authenticatable|RedirectResponse|null
    {
        $auth_url = Config::get('passport.broker.auth_url');

        if (! is_null($this->user)) {
            return $this->user;
        }

        if(!$this->broker->isAttached($this->request)) {
            $this->broker->sessionAttach($this->request);
        }

        if ($payload = $this->broker->profile($this->request)) {
            $this->user = $this->loginFromPayload($payload);
        }

        if (is_null($this->user) && $auth_url) {
            return redirect($auth_url.'?continue='.$this->request->fullUrl())->send();
        }

        return $this->user;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return Authenticatable|bool|null
     * @throws GuzzleException|\JsonException
     */
    public function attempt(array $credentials = [], bool $remember = false): Authenticatable|bool|null
    {
        // Call authentification attempting event
        //
        $this->fireAttemptEvent($credentials, $remember);

        if ($remember) {
            $credentials['remember'] = true;
        }

        if (($payload = $this->broker->login($credentials, $this->request)) && $user = $this->loginFromPayload($payload)) {

            // If we have an event dispatcher instance set we will fire an event so that
            // any listeners will hook into the authentication events and run actions
            // based on the login and logout events fired from the guard instances.
            $this->fireLoginEvent($user);

            // Succeeded
            //
            return $user;
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
        //$this->fireFailedEvent($user, $credentials);

        // Auth attempting failed
        //
        return false;
    }

    /**
     * Log a user into the application using the payload.
     *
     * @param array $payload
     * @return Authenticatable|bool|null
     */
    public function loginFromPayload(array $payload): Authenticatable|bool|null
    {
        // Retrieve user from payload
        //
        $this->user = $this->retrieveFromPayload($payload);

        // Update actual user payload
        //
        $this->updatePayload($payload);

        // Call authenticated event
        //
        $this->fireAuthenticatedEvent($this->user);

        // Succeeded
        //
        return $this->user;
    }

    /**
     * Retrieve user from payload
     *
     * @param mixed $payload
     * @return Authenticatable|bool|null
     */
    protected function retrieveFromPayload(mixed $payload): Authenticatable|bool|null
    {
        if (!$this->usernameExistsInPayload($payload)) {
            return false;
        }

        $user = $this->retrieveByCredentials($payload);

        if (!$user) {
            $userCreateStrategy = Config::get('passport.user_create_strategy');

            if (is_callable($userCreateStrategy) && $userCreateStrategy($payload)) {
                $user = $this->retrieveByCredentials($payload);
            }
        }

        return $user;
    }

    /**
     * Retrieve user by credentials from payload
     *
     * @param mixed $payload
     * @return Authenticatable|null
     */
    protected function retrieveByCredentials(mixed $payload): ?Authenticatable
    {
        $username = Config::get('passport.broker.client_username', 'email');

        return $this->provider->retrieveByCredentials([$username => $payload[$username]]);
    }

    /**
     * Check if config broker username exists in payload
     *
     * @param mixed $payload
     * @return bool
     */
    protected function usernameExistsInPayload(mixed $payload): bool
    {
        $username = Config::get('passport.broker.client_username', 'email');

        return array_key_exists($username, $payload);
    }

    /**
     * Update user payload
     *
     * @param array $payload
     */
    protected function updatePayload(array $payload): void
    {
        if ($this->user && method_exists($this->user, 'setPayload')) {
            $this->user->setPayload($payload);
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        // Retrieve a user by the given credentials.
        //
        $user = $this->provider->retrieveByCredentials($credentials);

        // Exists
        //
        return ! is_null($user);
    }

    /**
     * Logout user.
     *
     * @return void
     * @throws GuzzleException|\JsonException
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($this->broker->logout($this->request)) {

            // If we have an event dispatcher instance, we can fire off the logout event
            // so any further processing can be done. This allows the developer to be
            // listening for anytime a user signs out of this application manually.
            if (isset($this->events)) {
                $this->events->dispatch(new Logout($this->name, $user));
            }

            // Once we have fired the logout event we will clear the users out of memory
            // so they are no longer available as the user is no longer considered as
            // being signed into this application and should not be available here.
            $this->user = null;
        }
    }

    /**
     * Get the current request instance.
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setDispatcher($events)
    {
        $this->events = $events;
    }

    /**
     * Fire the attempt event with the arguments.
     *
     * @param  array  $credentials
     * @param  bool  $remember
     * @return void
     */
    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        $this->events?->dispatch(new Attempting($this->name, $credentials, $remember));
    }

    /**
     * Fire the login event if the dispatcher is set.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $remember
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        $this->events?->dispatch(new Login($this->name, $user, $remember));
    }

    /**
     * Fire the authenticated event if the dispatcher is set.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        $this->events?->dispatch(new Authenticated($this->name, $user));
    }

    /**
     * Fire the other device logout event if the dispatcher is set.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    protected function fireOtherDeviceLogoutEvent($user)
    {
        $this->events?->dispatch(new OtherDeviceLogout($this->name, $user));
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        $this->events?->dispatch(new Failed($this->name, $user, $credentials));
    }
}
