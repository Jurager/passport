---
title: Broker Setup
weight: 50
---

# Broker Setup

A broker is a client app that delegates auth to the server and keeps a local user session.

> [!NOTE]
> Account-center apps are typical brokers that talk to the server.

> [!WARNING]
> Broker routes and migrations are loaded only when `PASSPORT_BROKER_CLIENT_ID` is set.

## Environment

```env
PASSPORT_BROKER_CLIENT_ID=my-app
PASSPORT_BROKER_CLIENT_SECRET=your-secret
PASSPORT_BROKER_SERVER_URL=https://sso-server.com/sso/server
PASSPORT_BROKER_CLIENT_USERNAME=email
PASSPORT_BROKER_AUTH_URL=
PASSPORT_BROKER_CLOUDFLARE=false
PASSPORT_ROUTES_PREFIX_CLIENT=sso/client
```

## Broker Credentials

Create the broker on the server and copy `client_id` and `secret` into `.env`.

```php
use Illuminate\Support\Str;
use Jurager\Passport\Models\Broker;

$broker = Broker::create([
    'client_id' => 'my-app',
    'secret' => Str::random(40),
]);
```

## Server URL

`PASSPORT_BROKER_SERVER_URL` must include the server route prefix.

Example:

```
https://sso-server.com/sso/server
```

> [!WARNING]
> If the URL is wrong or missing the prefix, attach and profile requests will fail.

## Auth URL (Optional)

If you host the login UI on a dedicated broker, set:

```
PASSPORT_BROKER_AUTH_URL=https://auth.myapp.com
```

Unauthenticated users will be redirected there instead of the server.

> [!NOTE]
> If you set `PASSPORT_BROKER_AUTH_URL`, the login UI must exist at that broker.

> [!NOTE]
> The redirect includes a `continue` query parameter with the original URL.

> [!NOTE]
> If you do not have a separate auth UI service, leave this empty and use the broker's own `/login`.

## User Sync

Define how users are created or updated on the broker.

```php
// config/passport.php
'user_create_strategy' => function ($data) {
    return \App\Models\User::create([
        'email' => $data['email'],
        'name' => $data['name'],
        'password' => '',
    ]);
},

'user_update_strategy' => function ($user, $data) {
    $user->update([
        'email' => $data['email'],
        'name' => $data['name'],
    ]);

    return $user;
},
```

## Routes

Default prefix: `sso/client`.

- `GET /attach`
- `POST /logout/{id}`
- `POST /logout/all`
- `POST /logout/others`

## Middleware

- `AttachBroker` goes after `StartSession` in the `web` group.
- `ClientAuthenticate` replaces the `auth` alias.
