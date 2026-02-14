# Broker Setup

Configure a broker (client app) that delegates auth to the server.

Account-center apps (session management UI) are typical brokers that talk to the server.

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

## Auth URL (Optional)

If you host the login UI on a dedicated broker, set:

```
PASSPORT_BROKER_AUTH_URL=https://auth.myapp.com
```

Unauthenticated users will be redirected there.

## User Model

```php
use Jurager\Passport\Traits\Passport;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use Passport;
    use HasTokens;
}
```

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
- `POST /logout/id`
- `POST /logout/all`
- `POST /logout/others`

## Middleware

- `AttachBroker` goes after `StartSession` in the `web` group.
- `ClientAuthenticate` replaces the `auth` alias.

## Next

- [Authentication](authentication.md)
- [Sessions](sessions.md)
