# Configuration

After publishing the config file, decide whether the app is a server or a broker and configure the matching section.

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```

> [!NOTE]
> There is no single "mode" flag. The role is determined by what you configure and which routes you expose.

## Server Configuration

These options define how the server validates brokers and builds sessions.

> [!WARNING]
> If `driver=array` is used, brokers are loaded from config only. This is not recommended for production.

```php
'server' => [
    'driver' => 'model', // model|array
    'model' => Jurager\Passport\Models\Broker::class,
    'id_field' => 'client_id',
    'secret_field' => 'secret',
    'brokers' => [], // for array driver
    'parser' => 'agent',
    'lookup' => [
        'provider' => 'ip-api', // ip2location-lite|false
        'timeout' => 1.0,
        'environments' => ['production'],
    ],
],
```

- `driver=model` uses the brokers table; `driver=array` uses the config list.
- `id_field` and `secret_field` must match your broker model fields.
- `parser` and `lookup` only affect history enrichment.

See [Server Setup](server-setup.md) for the full flow.

## Broker Configuration

These options make the app act as a broker and talk to the server.

> [!WARNING]
> `client_id`, `client_secret`, and `server_url` are required for brokers. Missing values will throw an exception at runtime.

```php
'broker' => [
    'client_id' => env('PASSPORT_BROKER_CLIENT_ID'),
    'client_secret' => env('PASSPORT_BROKER_CLIENT_SECRET'),
    'client_username' => 'email',
    'server_url' => env('PASSPORT_BROKER_SERVER_URL'),
    'auth_url' => env('PASSPORT_BROKER_AUTH_URL'),
    'uses_cloudflare' => false,
],
```

- `server_url` must include the server prefix, for example `https://sso.example.com/sso/server`.
- `client_id` and `client_secret` must match a broker registered on the server.
- `auth_url` routes users to a dedicated login broker, if used.

> [!NOTE]
> The broker user payload must include the configured `client_username` field (default: `email`), otherwise user sync will fail.

See [Broker Setup](broker-setup.md) for the full flow.

## Tables

You may change table names to fit your schema conventions.

```php
'brokers_table_name' => 'brokers',
'history_table_name' => 'history',
'tokens_table_name' => 'access_tokens',
```

## Routes and Storage

Route prefixes let you mount SSO endpoints under custom paths.

```php
'routes_prefix_client' => 'sso/client',
'routes_prefix_server' => 'sso/server',
'storage_ttl' => 600,
'attach_throttle_seconds' => 5,
'max_redirect_attempts' => 3,
'debug' => false,
```

## Callbacks

Callbacks customize payloads and user sync.

```php
'user_info' => null,
'after_authenticating' => null,
'user_create_strategy' => null,
'user_update_strategy' => null,
```

See [Callbacks](callbacks.md) for full usage.

## Commands

Commands are server-side closures that brokers can call.

```php
'commands' => [],
```

See [Commands](commands.md) for full usage.

## Security

`allowed_redirect_hosts` restricts where users can be redirected after auth and attach flows.

When a broker sends a `return_url`, the server validates the host before redirecting. If the host is not allowed, the server returns a 400 error.

```php
'allowed_redirect_hosts' => [],
```

Example:

```php
'allowed_redirect_hosts' => [
    'app.com',
    'admin.app.com',
],
```
