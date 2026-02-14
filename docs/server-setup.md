# Server Setup

Configure the central SSO server: broker registry, user payload, and security.

The server is a dedicated auth authority. Even if you build an account-center UI, it should be a broker that talks to this server.

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

## Broker Registry

### Database Driver (default)

Store brokers in the `brokers` table.

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

Good for local dev, not ideal for production.

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

## User Model

Add the `Passport` trait to enable session helpers.

```php
use Jurager\Passport\Traits\Passport;

class User extends Authenticatable
{
    use Passport;
}
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

- `GET|POST /attach`
- `POST /login`
- `GET /profile`
- `POST /logout`
- `POST /commands/{command}`

## Optional: History Enrichment

- User-Agent parser: `PASSPORT_SERVER_PARSER=agent|whichbrowser`
- IP lookup: see `history.md` for `server.lookup` options.

## Security

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=app.com,admin.app.com
PASSPORT_ATTACH_THROTTLE=5
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
```

## Custom Commands

Define commands on the server and call them from brokers. See [Commands](commands.md).
