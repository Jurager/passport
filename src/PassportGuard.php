<?php

namespace Jurager\Passport;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Facades\Config;

class PassportGuard implements Guard
{
    use GuardHelpers, Macroable;

    /**
     * The user provider implementation.
     *
     * @var ClientBrokerManager
     */
    protected ClientBrokerManager $broker;

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
     * @param UserProvider $provider
     * @param ClientBrokerManager $broker
     * @param Request|null $request
     */
    public function __construct(UserProvider $provider, ClientBrokerManager $broker, Request $request = null)
    {
        $this->provider = $provider;
        $this->broker = $broker;
        $this->request = $request;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|RedirectResponse|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function user() : Authenticatable|RedirectResponse|null
    {
        $auth_url = Config::get('passport.broker.auth_url');

        if (! is_null($this->user)) {
            return $this->user;
        }
        
        if(!$this->broker->isAttached()) {
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function attempt(array $credentials = [], bool $remember = false): Authenticatable|bool|null
    {
        $login_params = $credentials;

        if ($remember) {
            $login_params['remember'] = true;
        }

        if (($payload = $this->broker->login($login_params, $this->request)) && $user = $this->loginFromPayload($payload)) {

            if (isset($this->events)) {
                $this->events->dispatch(new Events\AuthSucceeded($user, $this->request));
            }

            return $user;
        }

        if (isset($this->events)) {
            $this->events->dispatch(new Events\AuthFailed($credentials, $this->request));
        }

        return false;
    }

    /**
     * Log a user using the payload.
     *
     * @param array $payload
     * @return Authenticatable|bool|null
     */
    public function loginFromPayload(array $payload): Authenticatable|bool|null
    {
        $this->user = $this->retrieveFromPayload($payload);

        $this->updatePayload($payload);

        if ($this->user) {
            //$this->fireAuthenticatedEvent($this->user);
        }

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
    protected function updatePayload(array $payload)
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
        $user = $this->provider->retrieveByCredentials($credentials);

        return ! is_null($user);
    }

    /**
     * Set the current request instance.
     *
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Logout user.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($this->broker->logout($this->request)) {

            if (isset($this->events)) {
                $this->events->dispatch(new Events\Logout($user));
            }

            $this->user = null;
        }
    }
}
