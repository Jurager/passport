# Commands

Define server-side commands and call them from brokers. Useful for role checks or server-only data.

## Define Commands (server)

```php
// config/passport.php
'commands' => [
    'hasRole' => function ($server, $request) {
        $user = Auth::guard()->user();
        return ['has_role' => $user->hasRole($request->input('role'))];
    },
],
```

Commands receive the server instance and the request. Return an array (JSON response).

## Call Commands (broker)

```php
use Jurager\Passport\Broker;

$broker = app(Broker::class);
$result = $broker->commands('hasRole', ['role' => 'admin'], $request);
```

## Live Examples

### Check permissions

```php
// config/passport.php
'commands' => [
    'hasPermission' => function ($server, $request) {
        $user = Auth::guard()->user();
        $permission = $request->input('permission');

        return [
            'allowed' => $user->can($permission),
        ];
    },
],
```

```php
// broker
$result = $broker->commands('hasPermission', [
    'permission' => 'reports.view',
], $request);

if (!$result['allowed']) {
    abort(403);
}
```

### Return a broker-specific payload

```php
// config/passport.php
'commands' => [
    'getAccountSummary' => function ($server, $request) {
        $user = Auth::guard()->user();
        $broker = $server->getBrokerFromRequest($request);

        return [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
            'broker' => $broker->client_id,
        ];
    },
],
```

```php
// broker
$summary = $broker->commands('getAccountSummary', [], $request);
```

### Revoke a session by id

```php
// config/passport.php
'commands' => [
    'revokeSession' => function ($server, $request) {
        $user = Auth::guard()->user();
        $historyId = $request->input('history_id');

        return [
            'revoked' => (bool) $user->logoutById($historyId),
        ];
    },
],
```

```php
// broker
$result = $broker->commands('revokeSession', [
    'history_id' => $id,
], $request);
```

## Errors

- Unknown command: 404
- Non-callable command: 400
