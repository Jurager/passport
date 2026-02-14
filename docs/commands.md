# Custom Commands

Jurager/Passport allows you to define custom commands on the SSO server that brokers can execute. This is useful for implementing server-side authorization checks, retrieving user-specific data, or executing server-only operations.

## Overview

Custom commands allow brokers to:
- Check user permissions or roles
- Retrieve server-side user data
- Execute server-only business logic
- Perform authorization checks

Commands are defined on the **server** and called from **brokers**.

## Defining Commands

Commands are defined as closures in the server's configuration file.

### Basic Command

```php
// config/passport.php (on server)

'commands' => [
    'hasRole' => function($server, $request) {
        $user = Auth::guard()->user();
        $role = $request->input('role');

        return [
            'success' => $user->hasRole($role),
        ];
    },
],
```

### Command Parameters

Commands receive two parameters:

1. **$server** - Server instance (`Jurager\Passport\Server`)
2. **$request** - HTTP request instance (`Illuminate\Http\Request`)

### Return Value

Commands must return an array that will be JSON-encoded and sent to the broker:

```php
return [
    'success' => true,
    'data' => [...],
    'message' => '...',
];
```

## Command Examples

### Check User Role

```php
'hasRole' => function($server, $request) {
    $user = Auth::guard()->user();
    $role = $request->input('role');

    return [
        'has_role' => $user->roles->contains('name', $role),
    ];
},
```

### Get User Permissions

```php
'getPermissions' => function($server, $request) {
    $user = Auth::guard()->user();

    return [
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ];
},
```

### Check Subscription Status

```php
'checkSubscription' => function($server, $request) {
    $user = Auth::guard()->user();

    return [
        'active' => $user->hasActiveSubscription(),
        'plan' => $user->subscription?->plan,
        'expires_at' => $user->subscription?->expires_at,
    ];
},
```

### Get Broker-Specific Data

```php
'getBrokerData' => function($server, $request) {
    $user = Auth::guard()->user();
    $broker = $server->getBrokerFromRequest($request);

    // Get user data specific to this broker
    $brokerData = $user->brokerData()
        ->where('broker_id', $broker->id)
        ->first();

    return [
        'settings' => $brokerData?->settings ?? [],
        'preferences' => $brokerData?->preferences ?? [],
    ];
},
```

### Update User Profile

```php
'updateProfile' => function($server, $request) {
    $user = Auth::guard()->user();

    $user->update([
        'name' => $request->input('name'),
        'phone' => $request->input('phone'),
    ]);

    return [
        'success' => true,
        'user' => $user->fresh()->toArray(),
    ];
},
```

### Complex Authorization

```php
'canAccessResource' => function($server, $request) {
    $user = Auth::guard()->user();
    $resourceId = $request->input('resource_id');
    $action = $request->input('action');

    $resource = Resource::find($resourceId);

    if (!$resource) {
        return ['can_access' => false, 'reason' => 'Resource not found'];
    }

    if ($user->can($action, $resource)) {
        return ['can_access' => true];
    }

    return ['can_access' => false, 'reason' => 'Insufficient permissions'];
},
```

## Calling Commands from Brokers

Use the `Broker::commands()` method to call server commands:

```php
use Jurager\Passport\Broker;

$broker = app(Broker::class);

$result = $broker->commands('hasRole', [
    'role' => 'admin',
], $request);

if ($result['has_role']) {
    // User has admin role
}
```

### Complete Example

**Server configuration:**

```php
// config/passport.php (on server)

'commands' => [
    'hasRole' => function($server, $request) {
        $user = Auth::guard()->user();
        $role = $request->input('role');

        return [
            'has_role' => $user->hasRole($role),
        ];
    },
],
```

**Broker usage:**

```php
// app/Http/Controllers/AdminController.php (on broker)

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Jurager\Passport\Broker;

class AdminController extends Controller
{
    protected Broker $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function index(Request $request)
    {
        // Check if user has admin role
        $result = $this->broker->commands('hasRole', [
            'role' => 'admin',
        ], $request);

        if (!$result['has_role']) {
            abort(403, 'Access denied');
        }

        return view('admin.dashboard');
    }
}
```

## Middleware for Commands

Create middleware that uses commands for authorization:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Broker;

class CheckServerRole
{
    protected Broker $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function handle(Request $request, Closure $next, $role)
    {
        $result = $this->broker->commands('hasRole', [
            'role' => $role,
        ], $request);

        if (!$result['has_role']) {
            abort(403, 'Insufficient permissions');
        }

        return $next($request);
    }
}
```

**Register middleware:**

```php
// Laravel 12+
$middleware->alias([
    'server.role' => \App\Http\Middleware\CheckServerRole::class,
]);
```

**Use in routes:**

```php
Route::middleware(['auth', 'server.role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
```

## Error Handling

### Command Not Found

If a command doesn't exist:

```json
{
    "message": "Command not found"
}
```

HTTP status: 404

### Command Not Callable

If the command is not a callable:

```json
{
    "message": "Command is not callable"
}
```

HTTP status: 400

### Handle Errors in Commands

```php
'safeCommand' => function($server, $request) {
    try {
        $user = Auth::guard()->user();
        $data = $request->input('data');

        // Process data
        $result = processData($user, $data);

        return [
            'success' => true,
            'result' => $result,
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
},
```

## Best Practices

### 1. Validate Input

```php
'updateSettings' => function($server, $request) {
    $validator = Validator::make($request->all(), [
        'setting_key' => 'required|string',
        'setting_value' => 'required',
    ]);

    if ($validator->fails()) {
        return [
            'success' => false,
            'errors' => $validator->errors(),
        ];
    }

    // Process valid data...
},
```

### 2. Check Authentication

```php
'sensitiveCommand' => function($server, $request) {
    $user = Auth::guard()->user();

    if (!$user) {
        return [
            'success' => false,
            'error' => 'Not authenticated',
        ];
    }

    // Proceed with command...
},
```

### 3. Return Consistent Structure

```php
// Success response
return [
    'success' => true,
    'data' => $result,
];

// Error response
return [
    'success' => false,
    'error' => 'Error message',
];
```

### 4. Use Authorization

```php
'deleteResource' => function($server, $request) {
    $user = Auth::guard()->user();
    $resourceId = $request->input('resource_id');

    $resource = Resource::find($resourceId);

    if (!$user->can('delete', $resource)) {
        return [
            'success' => false,
            'error' => 'Unauthorized',
        ];
    }

    $resource->delete();

    return [
        'success' => true,
    ];
},
```

### 5. Log Command Execution

```php
'auditedCommand' => function($server, $request) {
    $user = Auth::guard()->user();
    $broker = $server->getBrokerFromRequest($request);

    Log::info('Command executed', [
        'command' => 'auditedCommand',
        'user_id' => $user->id,
        'broker_id' => $broker->client_id,
        'params' => $request->all(),
    ]);

    // Execute command logic...
},
```

## Advanced Usage

### Command with Multiple Parameters

```php
'complexQuery' => function($server, $request) {
    $user = Auth::guard()->user();

    $filters = [
        'status' => $request->input('status'),
        'category' => $request->input('category'),
        'date_from' => $request->input('date_from'),
        'date_to' => $request->input('date_to'),
    ];

    $results = $user->items()
        ->when($filters['status'], fn($q, $v) => $q->where('status', $v))
        ->when($filters['category'], fn($q, $v) => $q->where('category', $v))
        ->when($filters['date_from'], fn($q, $v) => $q->where('created_at', '>=', $v))
        ->when($filters['date_to'], fn($q, $v) => $q->where('created_at', '<=', $v))
        ->get();

    return [
        'results' => $results,
        'count' => $results->count(),
    ];
},
```

**Calling from broker:**

```php
$result = $broker->commands('complexQuery', [
    'status' => 'active',
    'category' => 'electronics',
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
], $request);
```
