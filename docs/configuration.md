# Configuration

This document describes all configuration options available in `config/passport.php`.

## Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```

This creates `config/passport.php` with all available options.

## Server Configuration

Options for the SSO Server.

### server.driver

**Type:** `string`
**Default:** `'model'`
**Env:** `PASSPORT_SERVER_DRIVER`
**Options:** `'model'`, `'array'`

Determines how brokers are stored and retrieved.

- `model` - Store brokers in database using Eloquent model
- `array` - Store brokers in config array (for simple setups)

```php
'driver' => env('PASSPORT_SERVER_DRIVER', 'model'),
```

### server.model

**Type:** `string`
**Default:** `'Jurager\Passport\Models\Broker'`
**Env:** `PASSPORT_SERVER_MODEL`

Broker model class (required for `model` driver).

```php
'model' => env('PASSPORT_SERVER_MODEL', 'Jurager\Passport\Models\Broker'),
```

### server.id_field

**Type:** `string`
**Default:** `'client_id'`
**Env:** `PASSPORT_SERVER_ID_FIELD`

Broker model field used for broker identification.

```php
'id_field' => env('PASSPORT_SERVER_ID_FIELD', 'client_id'),
```

### server.secret_field

**Type:** `string`
**Default:** `'secret'`
**Env:** `PASSPORT_SERVER_SECRET_FIELD`

Broker model field used for the secret key.

```php
'secret_field' => env('PASSPORT_SERVER_SECRET_FIELD', 'secret'),
```

### server.brokers

**Type:** `array`
**Default:** `[]`

Array of brokers (required for `array` driver). Format: `['broker_id' => 'secret']`

```php
'brokers' => [
    'app1' => 'secret-for-app1',
    'app2' => 'secret-for-app2',
],
```

### server.parser

**Type:** `string`
**Default:** `'agent'`
**Options:** `'agent'`, `'whichbrowser'`

User-Agent parser to extract device and browser information.

```php
'parser' => 'agent',
```

### server.lookup

Configuration for IP geolocation.

#### lookup.provider

**Type:** `string|false`
**Default:** `'ip-api'`
**Options:** `'ip-api'`, `'ip2location-lite'`, custom provider name, `false`

IP geolocation provider. Set to `false` to disable.

```php
'provider' => 'ip-api',
```

#### lookup.timeout

**Type:** `float`
**Default:** `1.0`

Timeout in seconds for IP lookup API calls.

```php
'timeout' => 1.0,
```

#### lookup.environments

**Type:** `array`
**Default:** `['production', 'local']`

Environments where IP lookup is enabled.

```php
'environments' => ['production', 'local'],
```

#### lookup.custom_providers

**Type:** `array`
**Default:** `[]`

Custom IP provider classes.

```php
'custom_providers' => [
    'my-provider' => \App\Services\MyIpProvider::class,
],
```

#### lookup.ip2location

Configuration for IP2Location Lite provider.

```php
'ip2location' => [
    'ipv4_table' => 'ip2location_db3',
    'ipv6_table' => 'ip2location_db3_ipv6',
],
```

## Broker Configuration

Options for SSO Brokers/Clients.

### broker.client_id

**Type:** `string`
**Env:** `PASSPORT_BROKER_CLIENT_ID`
**Required:** Yes (for brokers)

Broker identification registered on the server.

```php
'client_id' => env('PASSPORT_BROKER_CLIENT_ID'),
```

### broker.client_secret

**Type:** `string`
**Env:** `PASSPORT_BROKER_CLIENT_SECRET`
**Required:** Yes (for brokers)

Broker secret key from the server.

```php
'client_secret' => env('PASSPORT_BROKER_CLIENT_SECRET'),
```

### broker.client_username

**Type:** `string`
**Default:** `'email'`
**Env:** `PASSPORT_BROKER_CLIENT_USERNAME`

User model field used for unique identification.

```php
'client_username' => env('PASSPORT_BROKER_CLIENT_USERNAME', 'email'),
```

### broker.server_url

**Type:** `string`
**Env:** `PASSPORT_BROKER_SERVER_URL`
**Required:** Yes (for brokers)

Full URL to the SSO server including route prefix.

```php
'server_url' => env('PASSPORT_BROKER_SERVER_URL'),
// Example: 'https://sso.example.com/sso/server'
```

### broker.auth_url

**Type:** `string|null`
**Env:** `PASSPORT_BROKER_AUTH_URL`

URL to dedicated authentication broker (optional).

```php
'auth_url' => env('PASSPORT_BROKER_AUTH_URL'),
```

### broker.return_url

**Type:** `bool|string`
**Default:** `true`
**Env:** `PASSPORT_BROKER_RETURN_URL`

Return URL behavior after authentication.

- `true` - Return to originally requested URL
- `false` - Don't use return URLs
- Custom URL string - Always redirect to this URL

```php
'return_url' => env('PASSPORT_BROKER_RETURN_URL', true),
```

### broker.uses_cloudflare

**Type:** `bool`
**Default:** `false`
**Env:** `PASSPORT_BROKER_CLOUDFLARE`

Enable Cloudflare IP detection (reads from `CF-Connecting-IP` header).

```php
'uses_cloudflare' => env('PASSPORT_BROKER_CLOUDFLARE', false),
```

## Database Tables

### brokers_table_name

**Type:** `string`
**Default:** `'brokers'`
**Env:** `PASSPORT_BROKERS_TABLE`

Database table name for brokers.

```php
'brokers_table_name' => env('PASSPORT_BROKERS_TABLE', 'brokers'),
```

### history_table_name

**Type:** `string`
**Default:** `'history'`
**Env:** `PASSPORT_HISTORY_TABLE`

Database table name for session history.

```php
'history_table_name' => env('PASSPORT_HISTORY_TABLE', 'history'),
```

### tokens_table_name

**Type:** `string`
**Default:** `'access_tokens'`
**Env:** `PASSPORT_TOKENS_TABLE`

Database table name for API tokens.

```php
'tokens_table_name' => env('PASSPORT_TOKENS_TABLE', 'access_tokens'),
```

## General Settings

### debug

**Type:** `bool`
**Default:** `false`
**Env:** `PASSPORT_DEBUG`

Enable debug mode for detailed logging.

```php
'debug' => env('PASSPORT_DEBUG', false),
```

### storage_ttl

**Type:** `int|null`
**Default:** `600`
**Env:** `PASSPORT_STORAGE_TTL`

Session TTL in seconds. Set to `null` for no expiration.

```php
'storage_ttl' => env('PASSPORT_STORAGE_TTL', 600),
```

### attach_throttle_seconds

**Type:** `int`
**Default:** `5`
**Env:** `PASSPORT_ATTACH_THROTTLE`

Minimum seconds between attach attempts (prevents rapid re-attaching).

```php
'attach_throttle_seconds' => env('PASSPORT_ATTACH_THROTTLE', 5),
```

### max_redirect_attempts

**Type:** `int`
**Default:** `3`
**Env:** `PASSPORT_MAX_REDIRECT_ATTEMPTS`

Maximum redirect attempts before showing error (prevents infinite loops).

```php
'max_redirect_attempts' => env('PASSPORT_MAX_REDIRECT_ATTEMPTS', 3),
```

### routes_prefix_client

**Type:** `string`
**Default:** `'sso/client'`
**Env:** `PASSPORT_ROUTES_PREFIX_CLIENT`

Route prefix for client/broker endpoints.

```php
'routes_prefix_client' => env('PASSPORT_ROUTES_PREFIX_CLIENT', 'sso/client'),
```

### routes_prefix_server

**Type:** `string`
**Default:** `'sso/server'`
**Env:** `PASSPORT_ROUTES_PREFIX_SERVER`

Route prefix for server endpoints.

```php
'routes_prefix_server' => env('PASSPORT_ROUTES_PREFIX_SERVER', 'sso/server'),
```

## Callbacks

### user_info

**Type:** `callable|null`
**Default:** `null`

Customize user data returned to brokers.

```php
'user_info' => function($user, $broker, $request) {
    $payload = $user->toArray();
    $payload['roles'] = $user->roles->pluck('name');
    return $payload;
},
```

Parameters:
- `$user` - Authenticated user model
- `$broker` - Broker model making the request
- `$request` - HTTP request instance

### after_authenticating

**Type:** `callable|null`
**Default:** `null`

Additional verification after authentication. Return `false` to deny access.

```php
'after_authenticating' => function($user, $request) {
    return $user->email_verified_at !== null;
},
```

Parameters:
- `$user` - Authenticated user model
- `$request` - HTTP request instance

### user_create_strategy

**Type:** `callable|null`
**Default:** `null`

Custom logic for creating users on brokers.

```php
'user_create_strategy' => function ($data) {
    return \App\Models\User::create([
        'email' => $data['email'],
        'name' => $data['name'],
        'password' => '',
    ]);
},
```

### user_update_strategy

**Type:** `callable|null`
**Default:** `null`

Custom logic for updating users on brokers.

```php
'user_update_strategy' => function ($user, $data) {
    $user->update([
        'name' => $data['name'],
        'email' => $data['email'],
    ]);
    return $user;
},
```

## Commands

### commands

**Type:** `array`
**Default:** `[]`

Custom commands that brokers can execute on the server.

```php
'commands' => [
    'hasRole' => function($server, $request) {
        $user = Auth::guard()->user();
        $role = $request->input('role');
        return ['success' => $user->hasRole($role)];
    },
],
```

## Security

### allowed_redirect_hosts

**Type:** `array`
**Default:** `[]`
**Env:** `PASSPORT_ALLOWED_REDIRECT_HOSTS` (comma-separated)

Allowed hosts for return URL (prevents open redirect attacks).

```php
'allowed_redirect_hosts' => [
    'example.com',
    'app.example.com',
],
```

Or via environment:

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=example.com,app.example.com
```

## Complete Configuration Example

```php
return [
    'server' => [
        'driver' => 'model',
        'model' => 'Jurager\Passport\Models\Broker',
        'id_field' => 'client_id',
        'secret_field' => 'secret',
        'brokers' => [],
        'parser' => 'agent',
        'lookup' => [
            'provider' => 'ip-api',
            'timeout' => 1.0,
            'environments' => ['production'],
            'custom_providers' => [],
            'ip2location' => [
                'ipv4_table' => 'ip2location_db3',
                'ipv6_table' => 'ip2location_db3_ipv6',
            ],
        ],
    ],

    'broker' => [
        'client_id' => env('PASSPORT_BROKER_CLIENT_ID'),
        'client_secret' => env('PASSPORT_BROKER_CLIENT_SECRET'),
        'client_username' => 'email',
        'server_url' => env('PASSPORT_BROKER_SERVER_URL'),
        'auth_url' => env('PASSPORT_BROKER_AUTH_URL'),
        'return_url' => true,
        'uses_cloudflare' => false,
    ],

    'brokers_table_name' => 'brokers',
    'history_table_name' => 'history',
    'tokens_table_name' => 'access_tokens',

    'debug' => false,
    'storage_ttl' => 3600,
    'attach_throttle_seconds' => 5,
    'max_redirect_attempts' => 3,

    'routes_prefix_client' => 'sso/client',
    'routes_prefix_server' => 'sso/server',

    'user_info' => null,
    'after_authenticating' => null,
    'user_create_strategy' => null,
    'user_update_strategy' => null,

    'commands' => [],

    'allowed_redirect_hosts' => [],
];
```
