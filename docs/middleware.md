# Middleware

The package provides four middleware classes that handle different aspects of SSO functionality.

## AttachBroker

**Class:** `Jurager\Passport\Http\Middleware\AttachBroker`

Automatically attaches the broker to the SSO server before processing requests.

### Purpose

- Ensures the broker has a valid session token
- Redirects to attach endpoint if not attached
- Prevents redirect loops with built-in protection

### Configuration

Add to the `web` middleware group (after `StartSession`):

```php
// Laravel 12+
$middleware->web(prepend: [
    \Illuminate\Session\Middleware\StartSession::class,
    \Jurager\Passport\Http\Middleware\AttachBroker::class
]);

// Laravel 11-
protected $middlewareGroups = [
    'web' => [
        \Illuminate\Session\Middleware\StartSession::class,
        \Jurager\Passport\Http\Middleware\AttachBroker::class,
        // ...
    ],
];
```

### How It Works

1. Checks if broker is attached using `$broker->isAttached()`
2. If not attached:
   - Increments redirect counter
   - Checks if max attempts exceeded (prevents loops)
   - Redirects to `sso.broker.attach` route with return URL
3. If attached:
   - Resets redirect counter
   - Continues with request

### Redirect Loop Protection

The middleware tracks redirect attempts in the session:

```php
$redirectCount = session('sso_attach_redirect_count', 0);
$maxAttempts = config('passport.max_redirect_attempts', 3);

if ($redirectCount >= $maxAttempts) {
    throw new RedirectLoopException('SSO attach', $redirectCount);
}
```

Configure max attempts:

```env
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
```

### Attach Throttling

Prevents rapid re-attaching that can cause loops:

```env
PASSPORT_ATTACH_THROTTLE=5  # seconds between attach attempts
```

This is enforced in the `BrokerController::attach()` method.

---

## ClientAuthenticate

**Class:** `Jurager\Passport\Http\Middleware\ClientAuthenticate`

Replaces Laravel's default `auth` middleware to provide SSO-based authentication.

### Purpose

- Authenticates users via SSO server
- Supports bearer token authentication
- Fetches and syncs user profile
- Redirects unauthenticated users to login

### Configuration

Replace the `auth` middleware alias:

```php
// Laravel 12+
$middleware->alias([
    'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
]);

// Laravel 11-
protected $routeMiddleware = [
    'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
];
```

### How It Works

1. Checks if user is already authenticated
2. If not, tries bearer token authentication:
   ```php
   if ($token = $request->bearerToken()) {
       $user = Auth::guard()->loginFromToken($token);
   }
   ```
3. If no token, fetches profile from server:
   ```php
   $user = Auth::guard()->user(); // Calls $broker->profile()
   ```
4. If authenticated, continues with request
5. If not authenticated:
   - Redirects to auth URL (if configured)
   - Or redirects to server login URL

### Usage

Use like Laravel's standard `auth` middleware:

```php
// Protect routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'show']);
});

// Single route
Route::get('/admin', [AdminController::class, 'index'])->middleware('auth');
```

### Redirect Behavior

**With auth_url configured:**

```env
PASSPORT_BROKER_AUTH_URL=https://auth.myapp.com
```

Unauthenticated users are redirected to:
```
https://auth.myapp.com/login?return_url=https://current-broker.com/requested-page
```

**Without auth_url:**

Users are redirected to the server login URL:
```
https://sso-server.com/login?return_url=https://current-broker.com/requested-page
```

---

## ServerAuthenticate

**Class:** `Jurager\Passport\Http\Middleware\ServerAuthenticate`

Ensures the user is authenticated on the server for protected endpoints.

### Purpose

- Used on server-side protected routes (`profile`, `logout`)
- Validates session exists in storage
- Returns 401 if not authenticated

### Configuration

Automatically applied to server controller methods:

```php
public function __construct(Server $server, ServerSessionManager $storage)
{
    $this->middleware(ServerAuthenticate::class)->only(['profile', 'logout']);
}
```

### How It Works

1. Gets broker session ID from request
2. Validates session ID format
3. Checks if session exists in storage
4. Retrieves authenticated user from session
5. If any step fails, returns 401

### Usage

You typically don't use this middleware directly - it's automatically applied to server endpoints.

---

## ValidateBroker

**Class:** `Jurager\Passport\Http\Middleware\ValidateBroker`

Validates the broker's session ID and ensures the broker is authorized.

### Purpose

- Validates session ID format and checksum
- Ensures broker is registered on the server
- Prevents unauthorized broker access

### Configuration

Automatically applied to server controller methods (except `attach`):

```php
public function __construct(Server $server, ServerSessionManager $storage)
{
    $this->middleware(ValidateBroker::class)->except('attach');
}
```

### How It Works

1. Extracts session ID from request:
   - Bearer token
   - `access_token` parameter
   - `sso_session` parameter
2. Parses session ID format: `Passport-{broker}-{token}-{checksum}`
3. Validates broker exists in database/config
4. Verifies checksum matches
5. Stores broker info in request for later use

### Checksum Validation

The checksum is generated using:

```php
hash_hmac('sha256', "session:{token}", $broker_secret)
```

This ensures:
- The token hasn't been tampered with
- The broker has the correct secret
- The request is legitimate

---

## Middleware Execution Order

On a typical broker request to a protected route:

```
1. StartSession (Laravel)
   ↓
2. AttachBroker (Package)
   ↓
3. ClientAuthenticate (Package, replaces 'auth')
   ↓
4. Your route logic
```

On a server request:

```
1. ValidateBroker (Package)
   ↓
2. ServerAuthenticate (Package, for protected endpoints)
   ↓
3. Controller logic
```

## Custom Middleware

You can create custom middleware that uses the SSO system:

### Example: Require Email Verification

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard()->user();

        if (!$user || !$user->email_verified_at) {
            return redirect('/email/verify');
        }

        return $next($request);
    }
}
```

### Example: Broker-Specific Access

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jurager\Passport\Broker;

class CheckBrokerPermissions
{
    public function handle(Request $request, Closure $next, $requiredBroker)
    {
        $broker = app(Broker::class);

        if ($broker->client_id !== $requiredBroker) {
            abort(403, 'Access denied from this broker');
        }

        return $next($request);
    }
}
```

**Usage:**

```php
Route::middleware(['auth', 'check.broker:admin-panel'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
```

## Debugging Middleware

Enable debug mode to see middleware execution:

```env
PASSPORT_DEBUG=true
```

This logs:
- Attach attempts and throttling
- Authentication attempts
- Session validation
- Checksum failures

Check `storage/logs/laravel.log` for debug information.
