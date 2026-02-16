---
title: Server Setup
---

# Server Setup

The server is the central auth authority. It registers brokers, validates sessions, and returns user payloads.

> [!WARNING]
> Server routes and migrations are loaded only when `PASSPORT_BROKER_CLIENT_ID` is empty. Do not set broker credentials in the server app.

## Environment

```env
PASSPORT_SERVER_DRIVER=model
PASSPORT_SERVER_MODEL=Jurager\Passport\Models\Broker
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret
PASSPORT_STORAGE_TTL=600
PASSPORT_ROUTES_PREFIX_SERVER=sso/server
PASSPORT_DEBUG=false
```

> [!NOTE]
> Set `PASSPORT_SERVER_ID_FIELD` to the actual broker id column (commonly `client_id`).

## Drivers

### Database Driver

Store brokers in the `brokers` table. This is the default driver.

```php
use Illuminate\Support\Str;
use Jurager\Passport\Models\Broker;

$broker = Broker::create([
    'client_id' => 'my-app',
    'secret' => Str::random(40),
    'name' => 'My Application',
]);
```

### Array Driver

Useful for local development and very small setups.

> [!WARNING]
> The array driver does not support soft deletes or broker metadata. Prefer the database driver for production.

```env
PASSPORT_SERVER_DRIVER=array
```

```php
// config/passport.php
'server' => [
    'driver' => 'array',
    'brokers' => [
        'app1' => 'secret-for-app1',
        'app2' => 'secret-for-app2',
    ],
],
```

## User Payload

The server decides which fields the broker receives.

```php
// config/passport.php
'user_info' => function ($user, $broker, $request) {
    return [
        'id' => $user->id,
        'email' => $user->email,
    ];
},
```

## Post-Auth Checks

Use this to block access after credentials are valid.

```php
// config/passport.php
'after_authenticating' => function ($user, $request) {
    return (bool) $user->email_verified_at;
},
```

## Routes

Default prefix: `sso/server`.

- `GET /attach`
- `POST /login`
- `GET /profile`
- `POST /logout`
- `POST /commands/{command}`

## History Enrichment (Optional)

- User-Agent parser: `PASSPORT_SERVER_PARSER=agent|whichbrowser`
- IP lookup: see [History](history.md)

## Security

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=app.com,admin.app.com
PASSPORT_ATTACH_THROTTLE=5
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
```

> [!NOTE]
> If `allowed_redirect_hosts` is empty, all hosts are allowed (backwards compatible, but not recommended for production).
