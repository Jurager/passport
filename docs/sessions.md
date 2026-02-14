# Sessions

Passport stores session history for each login and provides helpers to revoke sessions.

## What Is Stored

Each history record includes session id, user agent info, IP, location (optional), and timestamps.

## Where Sessions Live

Passport uses two stores:

- **Server store:** maps SSO session id to the Laravel session id using the Laravel cache store.
- **Broker store:** stores the broker token in the Laravel session.

This means server sessions rely on your cache driver, while broker sessions rely on your session driver.

## Supported Drivers

Server side (cache): any Laravel cache driver (`file`, `redis`, `database`, `memcached`, etc.).  
Broker side (session): any Laravel session driver (`file`, `database`, `redis`, `cookie`, etc.).

Configure these in Laravel's `config/cache.php` and `config/session.php`.

## TTL

```env
PASSPORT_STORAGE_TTL=600
```

Set to `null` for no expiration.

Note: on the server, TTL is never shorter than Laravel's session lifetime to avoid desync.

## Access

```php
$user = Auth::user();
$sessions = $user->history()->latest()->get();
$current = $user->current();
```

## Logout Helpers

```php
$user->logoutById($historyId);
$user->logoutOthers();
$user->logoutAll();
```

## Broker Routes

Default prefix: `sso/client`.

- `POST /logout/id`
- `POST /logout/all`
- `POST /logout/others`

## Cleanup

Expired sessions are pruned via Laravel:

```bash
php artisan model:prune
```
