# Authentication

## Overview

Jurager/Passport implements a multi-step authentication flow that ensures secure communication between the SSO server and broker applications.

## Authentication Flow

The complete authentication flow consists of three main phases:

### 1. Attach Phase

Before authentication can occur, the broker must attach to the server.

```
User → Broker (protected route)
  ↓
Broker generates token
  ↓
Broker → Server (/attach?broker=X&token=Y&checksum=Z)
  ↓
Server validates checksum
  ↓
Server creates session
  ↓
Server → Broker (redirect to return_url)
  ↓
Broker is now attached
```

**Code flow:**

```php
// Automatic via AttachBroker middleware
// 1. User visits protected route
// 2. AttachBroker checks if attached
// 3. If not, generates token and redirects to server
// 4. Server validates and creates session
// 5. Redirects back to broker
```

### 2. Login Phase

Once attached, users can authenticate with their credentials.

```
User submits login form
  ↓
Broker → Server (/login with credentials)
  ↓
Server validates credentials
  ↓
Server creates History record
  ↓
Server → Broker (user data JSON)
  ↓
Broker creates/updates local user
  ↓
User authenticated
```

**Code example:**

```php
use Illuminate\Support\Facades\Auth;

public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (Auth::guard()->attempt($credentials)) {
        // Login successful
        return redirect()->intended('/dashboard');
    }

    // Login failed
    return back()->withErrors([
        'email' => 'Invalid credentials',
    ]);
}
```

### 3. Profile Phase

On subsequent requests, the broker fetches the user profile to maintain the session.

```
User → Broker (any protected route)
  ↓
ClientAuthenticate middleware
  ↓
Broker → Server (/profile)
  ↓
Server validates session
  ↓
Server → Broker (user data JSON)
  ↓
Broker updates local user
  ↓
Request continues
```

**Automatic via middleware:**

```php
// This happens automatically when using ClientAuthenticate middleware
Route::middleware('auth')->get('/dashboard', function () {
    $user = Auth::user(); // User is already authenticated
    return view('dashboard', compact('user'));
});
```

## PassportGuard Methods

The `PassportGuard` class provides authentication methods compatible with Laravel's Auth system.

### user()

Get the currently authenticated user:

```php
use Illuminate\Support\Facades\Auth;

$user = Auth::guard()->user();

if ($user) {
    echo "Logged in as: {$user->email}";
}
```

### attempt()

Attempt to authenticate with credentials:

```php
$credentials = [
    'email' => 'user@example.com',
    'password' => 'password',
];

if (Auth::guard()->attempt($credentials)) {
    // Authentication successful
    $user = Auth::user();
}
```

**With remember me:**

```php
$remember = $request->boolean('remember');

if (Auth::guard()->attempt($credentials, $remember)) {
    // User will be remembered
}
```

### loginFromPayload()

Log a user in using server response data:

```php
$payload = [
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe',
];

$user = Auth::guard()->loginFromPayload($payload);
```

This method:
- Retrieves or creates the local user
- Fires the `Authenticated` event
- Returns the authenticated user

### loginFromToken()

Authenticate using a bearer token:

```php
$token = $request->bearerToken();

if ($user = Auth::guard()->loginFromToken($token)) {
    // Authenticated via token
}
```

This is useful for API authentication. See [Tokens](tokens.md) for details.

### logout()

Log the user out:

```php
public function logout(Request $request)
{
    Auth::guard()->logout();

    return redirect('/');
}
```

This method:
- Sends logout request to server
- Fires the `Logout` event
- Clears the user from memory

### validate()

Validate credentials without logging in:

```php
$credentials = [
    'email' => 'user@example.com',
    'password' => 'password',
];

if (Auth::guard()->validate($credentials)) {
    // Credentials are valid
}
```

## Broker Class Methods

The `Broker` class handles communication with the SSO server.

### login()

Send login request to server:

```php
use Jurager\Passport\Broker;

$broker = app(Broker::class);

$credentials = [
    'email' => 'user@example.com',
    'password' => 'password',
];

$response = $broker->login($credentials, $request);

if ($response) {
    // $response contains user data
    $user = Auth::guard()->loginFromPayload($response);
}
```

### profile()

Fetch user profile from server:

```php
$broker = app(Broker::class);

$profile = $broker->profile($request);

if ($profile) {
    // User is authenticated
    $user = Auth::guard()->loginFromPayload($profile);
} else {
    // Not authenticated or session expired
}
```

### logout()

Send logout request to server:

```php
$broker = app(Broker::class);

// Logout current session only
$broker->logout($request);

// Logout by history ID
$broker->logout($request, 'id');

// Logout all sessions
$broker->logout($request, 'all');

// Logout all except current
$broker->logout($request, 'others');
```

## Remember Me

The package supports "Remember Me" functionality:

**In your login form:**

```html
<form method="POST" action="/login">
    @csrf
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="checkbox" name="remember" id="remember">
    <label for="remember">Remember Me</label>
    <button type="submit">Login</button>
</form>
```

**In your controller:**

```php
public function login(Request $request)
{
    $credentials = $request->only('email', 'password');
    $remember = $request->boolean('remember');

    if (Auth::guard()->attempt($credentials, $remember)) {
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors(['email' => 'Invalid credentials']);
}
```

When `remember` is true, the server creates a longer-lived session.

## Logout Methods

### Logout Current Session

```php
Auth::guard()->logout();
```

Or use the route:

```php
Route::post('/logout', function () {
    Auth::guard()->logout();
    return redirect('/');
});
```

### Logout by Session ID

```php
// Using route
POST /sso/client/logout/id

// Parameters
['id' => $history_id]
```

### Logout All Sessions

```php
// Using route
POST /sso/client/logout/all
```

This logs the user out from all devices.

### Logout All Except Current

```php
// Using route
POST /sso/client/logout/others
```

This logs the user out from all devices except the current one.

**Example in a controller:**

```php
public function logoutOthers(Request $request)
{
    $broker = app(\Jurager\Passport\Broker::class);

    if ($broker->logout($request, 'others')) {
        return back()->with('success', 'Logged out from other devices');
    }

    return back()->with('error', 'Failed to logout');
}
```

## Events

The authentication system fires several events:

### Authenticated

Fired when a user is successfully authenticated:

```php
use Illuminate\Auth\Events\Authenticated;

Event::listen(Authenticated::class, function ($event) {
    Log::info("User authenticated: {$event->user->email}");
});
```

### Login

Fired on successful login:

```php
use Illuminate\Auth\Events\Login;

Event::listen(Login::class, function ($event) {
    Log::info("User logged in: {$event->user->email}");
});
```

### Logout

Fired when a user logs out:

```php
use Illuminate\Auth\Events\Logout;

Event::listen(Logout::class, function ($event) {
    Log::info("User logged out");
});
```

### Package-Specific Events

The package also fires custom events:

```php
use Jurager\Passport\Events\Authenticated;
use Jurager\Passport\Events\Logout;
use Jurager\Passport\Events\Unauthenticated;
```

See [Events](events.md) for details.

## Authentication Verification

You can add post-authentication verification on the server:

```php
// config/passport.php (on server)

'after_authenticating' => function($user, $request) {
    // Verify email
    if (!$user->email_verified_at) {
        return false;
    }

    // Check subscription status
    if (!$user->hasActiveSubscription()) {
        return false;
    }

    return true;
},
```

If this callback returns `false`, the user will be denied access even with valid credentials.

## API Authentication

For API requests, use bearer tokens:

```php
// Create a token
$token = $user->createToken('api-token', 60); // expires in 60 minutes

// Use in API request
$response = Http::withToken($token)
    ->get('https://broker.com/api/user');
```

See [Tokens](tokens.md) for full API token documentation.
