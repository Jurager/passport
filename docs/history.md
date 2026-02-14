# History and Geolocation

Passport records login history with device and IP details. Geo lookup is optional.

History records are created when the user logs in on the server.

## What Is Stored

- Session id
- Device and browser info
- IP address
- Optional geo fields
- Timestamps and expiration

## User-Agent Parsing

```env
PASSPORT_SERVER_PARSER=agent
```

Install a parser:

```bash
composer require jenssegers/agent
# or
composer require whichbrowser/parser
```

## IP Lookup

Requires Guzzle:

```bash
composer require guzzlehttp/guzzle
```

Basic config:

```php
// config/passport.php
'server' => [
    'lookup' => [
        'provider' => 'ip-api',
        'timeout' => 1.0,
        'environments' => ['production'],
    ],
],
```

Disable lookup:

```php
'lookup' => ['provider' => false],
```

> [!NOTE]
> The `server.lookup.environments` option is not enforced in code. If a provider is set, lookup runs.

## Cloudflare IP

Enable this when the broker is behind Cloudflare, otherwise you will see the proxy IP instead of the real client IP.

> [!NOTE]
> The server reads IP and User-Agent from headers sent by the broker (`Passport-Remote-Address`, `Passport-User-Agent`).


```env
PASSPORT_BROKER_CLOUDFLARE=true
```

## Access History

```php
$user = Auth::user();
$history = $user->history()->latest()->get();
$all = $user->history()->withTrashed()->latest()->get();
```

## Privacy

Store only what you need and document it in your privacy policy.
