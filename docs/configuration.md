# Configuration

Publish the config file:

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```

Most options can be overridden with environment variables.

After publishing, decide which role this app plays and configure the matching section:

- Server: configure `server` options and server routes.
- Broker: configure `broker` options and broker routes.

## How Mode Is Chosen

The package does not have a single "mode" flag. Your app becomes a server or a broker based on what you configure:

- **Server** is enabled when you configure server options and expose server routes.
- **Broker** is enabled when you set broker credentials and server URL.

You can run only server, only broker, or both in the same codebase, but you still need a separate server instance for real SSO. An account-center app is a broker that talks to the server.

## Server Configuration

These options define how the server validates brokers and builds sessions.

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

See [Server Setup](server-setup.md) for full server configuration flow.

## Broker Configuration

These options make the app act as a broker and talk to the server.

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

See [Broker Setup](broker-setup.md) for full broker configuration flow.

## Tables

You can change table names if they conflict with your schema or naming rules.

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

Use callbacks for custom payloads and user sync. These are executed on the server
(`user_info`, `after_authenticating`) or broker (`user_create_strategy`, `user_update_strategy`).

```php
'user_info' => null,
'after_authenticating' => null,
'user_create_strategy' => null,
'user_update_strategy' => null,
```

Common cases:

- `user_info` to add roles/permissions for brokers.
- `after_authenticating` to block unverified users or inactive accounts.
- `user_create_strategy` to create local users on brokers.
- `user_update_strategy` to keep broker data in sync.

See [Callbacks](callbacks.md) for full usage.

## Commands

Commands are server-side endpoints that brokers can call for authorization checks
or server-only data. They receive the request and return a JSON payload.

```php
'commands' => [],
```

See [Commands](commands.md) for full usage.

## Security

`allowed_redirect_hosts` restricts where users can be redirected after auth and attach flows.

When a broker sends a `return_url` (the originally requested page), the server validates the host before redirecting back. If the host is not allowed, the server blocks the redirect.

```php
'allowed_redirect_hosts' => [],
```

If the host is not in the list, the server returns a 400 error instead of redirecting.

Example:

```php
'allowed_redirect_hosts' => [
    'app.com',
    'admin.app.com',
],
```
