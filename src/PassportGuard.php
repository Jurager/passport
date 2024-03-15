<?php

namespace Jurager\Passport;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use JsonException;
use Jurager\Passport\Models\Token;

class PassportGuard implements Guard
{
    use GuardHelpers;

    /**
     * The name of the guard. Typically, "web".
     *
     * Corresponds to guard name in authentication configuration.
     */
    protected string $name;

    /**
     * The user provider implementation.
     */
    protected Broker $broker;

    /**
     * The request instance.
     */
    protected \Symfony\Component\HttpFoundation\Request|Request|null $request;

    /**
     * The event dispatcher instance.
     */
    protected Dispatcher $events;

    /**
     * Create a new authentication guard.
     */
    public function __construct(string $name, UserProvider $provider, Broker $broker, ?Request $request = null)
    {
        $this->name = $name;
        $this->provider = $provider;
        $this->broker = $broker;
        $this->request = $request;
    }

    /**
     * Get the currently authenticated user.
     *
     * @throws GuzzleException
     * @throws JsonException
     */
    public function user(): ?Authenticatable
    {
        // All routes that need to be authenticated should use AttachBroker middleware
        // Otherwise need a workaround with exception on pages, that not uses this middleware
        if (is_null($this->user) && ! $this->broker->isAttached()) {
            return null;
        }

        if (! is_null($this->user)) {
            return $this->user;
        }

        if ($payload = $this->broker->profile($this->request)) {
            $this->user = $this->loginFromPayload($payload);
        }

        return $this->user;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @throws GuzzleException|JsonException
     */
    public function attempt(array $credentials = [], bool $remember = false): Authenticatable|bool|null
    {
        // Call authentication attempting event
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
            return $user;
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
        //$this->fireFailedEvent($user, $credentials);

        // Auth attempting failed
        return false;
    }

    /**
     * Log a user into the application using the payload.
     */
    public function loginFromPayload(array $payload): Authenticatable|bool|null
    {
        // Retrieve user from payload
        $this->user = $this->retrieveFromPayload($payload);

        // Call authenticated event
        if ($this->user) {
            $this->fireAuthenticatedEvent($this->user);
        }

        return $this->user;
    }

    /**
     * Log a user into the application using the bearer token.
     */
    public function loginFromToken(string $token): Authenticatable|bool|null
    {
        // If there is an authorization header
        if ($token) {

            // Hash it
            $token = hash('sha256', $token);

            // First, look for the record with the token in the database
            $access_token = Token::query()->where('token', $token)->first();

            // Token found, trying to authenticate user by its id
            if ($access_token && (! $access_token->expires_at || ! $access_token->expires_at->isPast())) {

                // Update token last usage timestamp
                $access_token->forceFill(['last_used_at' => now()])->save();

                // Update actual user
                $this->user = $access_token->tokenable;

                // Successfully authenticated
                return $this->user;
            }
        }

        return false;
    }

    /**
     * Retrieve user from payload
     */
    protected function retrieveFromPayload(mixed $payload): Authenticatable|bool|null
    {
        if (! $this->usernameExistsInPayload($payload)) {
            return false;
        }

        $user = $this->retrieveByCredentials($payload);

        if (! $user) {
            $userCreateStrategy = config('passport.user_create_strategy');

            if (is_callable($userCreateStrategy) && $userCreateStrategy($payload)) {
                $user = $this->retrieveByCredentials($payload);
            }
        }

        return $user;
    }

    /**
     * Retrieve user by credentials from payload
     */
    protected function retrieveByCredentials(mixed $payload): ?Authenticatable
    {
        $username = config('passport.broker.client_username', 'email');

        return $this->provider->retrieveByCredentials([$username => $payload[$username]]);
    }

    /**
     * Check if config broker username exists in payload
     */
    protected function usernameExistsInPayload(mixed $payload): bool
    {
        $username = config('passport.broker.client_username', 'email');

        return array_key_exists($username, $payload);
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        // Retrieve a user by the given credentials.
        $user = $this->provider->retrieveByCredentials($credentials);

        // Exists
        return ! is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Logout user.
     *
     * @throws GuzzleException|JsonException
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

            // Once we have fired the logout event we will clear the users out of memory,
            // so they are no longer available as the user is no longer considered as
            // being signed in this application and should not be available here.
            $this->user = null;
        }
    }

    /**
     * Get the current request instance.
     */
    public function getRequest(): Request|\Symfony\Component\HttpFoundation\Request
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
     *
     * @return $this
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the event dispatcher instance.
     */
    public function getDispatcher(): Dispatcher
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     */
    public function setDispatcher(Dispatcher $events): void
    {
        $this->events = $events;
    }

    /**
     * Fire the attempt event with the arguments.
     */
    protected function fireAttemptEvent(array $credentials, bool $remember = false): void
    {
        $this->events->dispatch(new Attempting($this->name, $credentials, $remember));
    }

    /**
     * Fire the login event if the dispatcher is set.
     */
    protected function fireLoginEvent(Authenticatable $user, bool $remember = false): void
    {
        $this->events->dispatch(new Login($this->name, $user, $remember));
    }

    /**
     * Fire the authenticated event if the dispatcher is set.
     */
    protected function fireAuthenticatedEvent(Authenticatable $user): void
    {
        $this->events->dispatch(new Authenticated($this->name, $user));
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     */
    protected function fireFailedEvent(?Authenticatable $user, array $credentials): void
    {
        $this->events->dispatch(new Failed($this->name, $user, $credentials));
    }
}
