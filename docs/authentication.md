---
title: Authentication
weight: 60
---

## Authentication

SSO auth is handled by the server, while brokers keep local sessions.

## Flow

1. Broker attaches to the server.
2. User logs in on the server.
3. Broker pulls the profile on each request.
4. Logout revokes the server session.

This flow is automated by `AttachBroker` and `ClientAuthenticate`.

## ClientAuthenticate Middleware

`ClientAuthenticate` replaces Laravel's `auth` middleware on brokers.

How it decides:

1. If the user is already authenticated in the broker, it passes the request.
2. If a bearer token exists, it tries token auth (`loginFromToken`).
3. If no token, it calls the server `/profile` to validate the session.
4. If the server returns a user payload, the broker syncs the user and proceeds.
5. If not authenticated, it redirects to the auth URL.

This means API calls can use bearer tokens, while browser requests use the server session.

Authentication behavior:

- If the broker is not attached, users are redirected to `/sso/client/attach` first.
- For non-JSON requests, unauthenticated users are redirected to `PASSPORT_BROKER_AUTH_URL` with a `continue` query parameter.
- For JSON requests (`expectsJson`), Laravel returns a 401 instead of redirecting.

> [!NOTE]
> If you do not have a separate auth UI service, leave `PASSPORT_BROKER_AUTH_URL` empty and use the broker's own `/login`.

## Route Examples

```php
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/sessions', [SessionController::class, 'index']);
});
```

```php
use Illuminate\Support\Facades\Auth;

Route::get('/api/user', function () {
    return Auth::user();
})->middleware('auth');
```

## Token Auth

Bearer tokens are supported by the same guard and bypass the SSO session.

```php
$token = $request->bearerToken();
$user = Auth::guard()->loginFromToken($token);
```

See [Tokens](tokens.md).

> [!WARNING]
> Token auth only works with tokens stored in the broker database. It does not call the SSO server.

## Login Field

By default, the server expects `email` and `password`. You can override the username field by sending a `login` parameter with the field name.

## Broker API

Direct calls to the server if you need them:

```php
use Jurager\Passport\Broker;

$broker = app(Broker::class);
$payload = $broker->login($credentials, $request);
$profile = $broker->profile($request);
$broker->logout($request, 'all');
```

## Logout Methods

On a broker:

- `POST /sso/client/logout/{id}`
- `POST /sso/client/logout/all`
- `POST /sso/client/logout/others`

## Remember Me

Pass the second argument to `attempt`:

```php
Auth::guard()->attempt($credentials, true);
```

## Events

Passport fires package events and Laravel auth events. See [Events](events.md).
