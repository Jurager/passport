# Broker Setup

The SSO Broker (also called Client) is an application that delegates authentication to the SSO Server. Users log in through the server, and brokers receive user information to create local sessions.

## Environment Configuration

Configure your `.env` file for the broker application:

```env
# Broker credentials (get these from the server)
PASSPORT_BROKER_CLIENT_ID=my-app
PASSPORT_BROKER_CLIENT_SECRET=your-secret-from-server

# Server URL
PASSPORT_BROKER_SERVER_URL=https://sso-server.com/sso/server

# Username field (must match field in User model)
PASSPORT_BROKER_CLIENT_USERNAME=email

# Return URL behavior
PASSPORT_BROKER_RETURN_URL=true

# Auth URL (optional - for dedicated auth broker)
PASSPORT_BROKER_AUTH_URL=

# Cloudflare IP detection
PASSPORT_BROKER_CLOUDFLARE=false

# Route prefix
PASSPORT_ROUTES_PREFIX_CLIENT=sso/client
```

## Getting Broker Credentials

Before setting up a broker, you must register it on the SSO Server:

**On the server**, create a broker:

```php
use Jurager\Passport\Models\Broker;
use Illuminate\Support\Str;

$broker = Broker::create([
    'client_id' => 'my-app',
    'secret' => Str::random(40),
]);

// Share these credentials with the broker application
echo "Client ID: {$broker->client_id}\n";
echo "Secret: {$broker->secret}\n";
```

Copy the `client_id` and `secret` to your broker's `.env` file.

## Server URL Configuration

The `PASSPORT_BROKER_SERVER_URL` should point to your SSO server's endpoint:

```env
# Include the full URL with protocol and route prefix
PASSPORT_BROKER_SERVER_URL=https://sso-server.com/sso/server
```

The broker will append routes like `/login`, `/profile`, `/logout` to this base URL.

## Return URL Behavior

The `return_url` parameter controls redirect behavior after authentication:

```env
# true - Return to the original requested URL
PASSPORT_BROKER_RETURN_URL=true

# false - Don't use return URLs
PASSPORT_BROKER_RETURN_URL=false

# Custom URL - Always redirect to this URL after auth
PASSPORT_BROKER_RETURN_URL=https://my-app.com/dashboard
```

**How it works:**

- `true`: After login, user returns to the page they originally requested
- `false`: User stays on the server's login success page
- Custom URL: User is redirected to the specified URL

## Auth URL (Optional)

If you want to centralize the login UI on a single broker:

```env
# On the auth broker (where login UI lives)
PASSPORT_BROKER_AUTH_URL=

# On other brokers
PASSPORT_BROKER_AUTH_URL=https://auth.myapp.com
```

When set, unauthenticated users are redirected to the auth broker instead of the server.

## Username Field

Specify which field uniquely identifies users:

```env
# Use email (default)
PASSPORT_BROKER_CLIENT_USERNAME=email

# Or use another field
PASSPORT_BROKER_CLIENT_USERNAME=username
```

This field must:
- Exist in your User model
- Be unique
- Match the field returned by the server's `user_info` callback

## Cloudflare Support

If using Cloudflare proxy, enable IP detection:

```env
PASSPORT_BROKER_CLOUDFLARE=true
```

This instructs the broker to read the client IP from the `CF-Connecting-IP` header.

## User Model Configuration

Your User model can use the `Passport` and `HasTokens` traits:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\Passport;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use Passport;
    use HasTokens;

    // ... rest of your User model
}
```

**Passport trait** provides:
- Session management (`history()`, `current()`, `logoutById()`, etc.)

**HasTokens trait** provides:
- API token management (`tokens()`, `createToken()`, `removeToken()`)

## User Creation Strategy

Define how users are created on the broker when they don't exist locally:

```php
// config/passport.php

'user_create_strategy' => function ($data) {
    return \App\Models\User::create([
        'email' => $data['email'],
        'name' => $data['name'],
        'password' => '', // No password on broker
    ]);
},
```

**Important:** Brokers typically don't store passwords since authentication happens on the server.

## User Update Strategy

Define how users are updated when they log in:

```php
// config/passport.php

'user_update_strategy' => function ($user, $data) {
    $user->update([
        'name' => $data['name'],
        'email' => $data['email'],
        // Update other fields as needed
    ]);

    return $user;
},
```

This ensures broker user data stays synchronized with the server.

## Routes

The package automatically registers broker routes:

| Method | URI | Description |
|--------|-----|-------------|
| GET | `/sso/client/attach` | Attach broker to server |
| POST | `/sso/client/logout/id` | Logout by session ID |
| POST | `/sso/client/logout/all` | Logout all sessions |
| POST | `/sso/client/logout/others` | Logout all except current |

To customize the route prefix:

```env
PASSPORT_ROUTES_PREFIX_CLIENT=auth/sso
```

## Middleware Configuration

The broker uses two key middleware:

### AttachBroker Middleware

Automatically attached to the `web` middleware group (configured during installation).

**What it does:**
- Checks if the broker is attached to the server
- Redirects to attach endpoint if not attached
- Prevents redirect loops with throttling

### ClientAuthenticate Middleware

Replaces Laravel's default `auth` middleware (configured during installation).

**What it does:**
- Checks if user is authenticated via SSO
- Supports bearer token authentication
- Fetches user profile from server
- Creates/updates local user record

**Using in routes:**

```php
// Protect routes with SSO authentication
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## Authentication Guard

The package uses a custom guard called `PassportGuard`. It's automatically registered as the default guard when using the `ClientAuthenticate` middleware.

**Using the guard:**

```php
use Illuminate\Support\Facades\Auth;

// Get authenticated user
$user = Auth::guard()->user();

// Check authentication
if (Auth::guard()->check()) {
    // User is authenticated
}

// Attempt login (usually handled by server)
Auth::guard()->attempt($credentials);

// Logout
Auth::guard()->logout();
```

## Custom Commands

Call custom commands defined on the server:

```php
use Jurager\Passport\Broker;

$broker = app(Broker::class);

// Call a server command
$result = $broker->commands('hasRole', [
    'role' => 'admin',
], $request);

// Result: ['success' => true/false]
```

See [Commands](commands.md) for details.

## Testing the Broker

**1. Visit a protected route:**

```
https://your-broker.com/dashboard
```

**2. Expected behavior:**
- Redirected to `/sso/client/attach`
- Redirected to server `/sso/server/attach`
- Redirected back to broker
- Broker is now attached

**3. Login:**

Since the broker is attached but user is not authenticated, login through the server.

**4. Access protected routes:**

After successful authentication, you can access protected routes on the broker.

## Troubleshooting

**"Client broker not attached" error:**
- AttachBroker middleware not registered
- Session not persisting (check session configuration)

**"Invalid checksum" error:**
- Client secret doesn't match server
- Token was modified
- Clock skew between server and broker

**Redirect loop:**
- AttachBroker middleware running multiple times
- Check middleware order (must come after StartSession)
- Attach throttling too low

See [Troubleshooting](troubleshooting.md) for more solutions.
