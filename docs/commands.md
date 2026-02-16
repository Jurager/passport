---
title: Commands
weight: 110
---

# Commands

Commands are defined on the server as closures and called from brokers. Use them for role checks or server-only data.

Typical real-world uses:

- Centralized authorization checks.
- Account-center actions (revoke sessions, tokens).
- Server-only data (billing, flags, roles).
- Consistent rules across all brokers.

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

> [!NOTE]
> Commands must return an array. The response is JSON-encoded for the broker.

> [!WARNING]
> The commands endpoint does not enforce user authentication by default. If you need a user, add `ServerAuthenticate` to the route or resolve the user from the server session.

> [!NOTE]
> The examples below assume an authenticated user is available.

## Call Commands (broker)

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jurager\Passport\Broker;

class AdminController extends Controller
{
    public function __construct(private Broker $broker)
    {
    }

    public function index(Request $request)
    {
        $result = $this->broker->commands('hasRole', [
            'role' => 'admin',
        ], $request);

        if (!($result['has_role'] ?? false)) {
            abort(403);
        }

        return view('admin.dashboard');
    }
}
```

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

if (!($result['allowed'] ?? false)) {
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
