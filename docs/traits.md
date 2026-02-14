# Traits

The package provides three traits that add functionality to your User model.

## Passport Trait

**Trait:** `Jurager\Passport\Traits\Passport`

Provides session management functionality for the User model.

### Installation

Add the trait to your User model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\Passport;

class User extends Authenticatable
{
    use Passport;

    // ... rest of your model
}
```

### Relationship

**history()**

Returns a morphMany relationship to the History model:

```php
$user = Auth::user();
$sessions = $user->history;

// With query constraints
$activeSessions = $user->history()
    ->whereNull('deleted_at')
    ->where('expires_at', '>', now())
    ->get();
```

### Methods

**current()**

Get the current user's active session:

```php
$currentSession = $user->current();

if ($currentSession) {
    echo "Logged in from: {$currentSession->location}";
    echo "Device: {$currentSession->device}";
}
```

This returns the History record where `session_id` matches the current Laravel session ID.

**logoutById($history_id)**

Logout from a specific session by its ID:

```php
// Logout from a specific session
$success = $user->logoutById(5);

if ($success) {
    echo "Session terminated";
}
```

Parameters:
- `$history_id` (int|null) - History record ID. If null, logs out current session.

Returns:
- `bool` - True if logout successful

**logoutOthers()**

Logout from all sessions except the current one:

```php
$success = $user->logoutOthers();

if ($success) {
    echo "Logged out from all other devices";
}
```

This is useful for a "Logout from other devices" feature.

Returns:
- `bool` - True if logout successful

**logoutAll()**

Logout from all sessions, including the current one:

```php
$success = $user->logoutAll();

if ($success) {
    echo "Logged out from all devices";
}
```

Returns:
- `bool` - True if logout successful

### Usage Example

**Session management page:**

```php
public function sessions()
{
    $user = Auth::user();
    $sessions = $user->history()
        ->whereNull('deleted_at')
        ->orderBy('created_at', 'desc')
        ->get();

    return view('sessions', compact('sessions'));
}

public function revokeSession(Request $request)
{
    $user = Auth::user();

    if ($user->logoutById($request->session_id)) {
        return back()->with('success', 'Session revoked');
    }

    return back()->with('error', 'Failed to revoke session');
}

public function revokeOthers()
{
    $user = Auth::user();

    if ($user->logoutOthers()) {
        return back()->with('success', 'Logged out from other devices');
    }

    return back()->with('error', 'Failed to logout');
}
```

**View example:**

```blade
<h2>Active Sessions</h2>

@foreach($sessions as $session)
    <div class="session">
        <strong>{{ $session->device }} - {{ $session->browser }}</strong>
        <p>{{ $session->location }}</p>
        <p>{{ $session->created_at->diffForHumans() }}</p>

        @if($session->is_current)
            <span class="badge">Current Session</span>
        @else
            <form method="POST" action="/sessions/{{ $session->id }}/revoke">
                @csrf
                <button type="submit">Revoke</button>
            </form>
        @endif
    </div>
@endforeach

<form method="POST" action="/sessions/revoke-others">
    @csrf
    <button type="submit">Logout from all other devices</button>
</form>
```

---

## HasTokens Trait

**Trait:** `Jurager\Passport\Traits\HasTokens`

Provides API token management functionality for the User model.

### Installation

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use HasTokens;

    // ... rest of your model
}
```

### Relationship

**tokens()**

Returns a morphMany relationship to the Token model:

```php
$user = Auth::user();
$tokens = $user->tokens;

// Active tokens only
$activeTokens = $user->tokens()
    ->where(function ($query) {
        $query->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
    })
    ->get();
```

### Methods

**createToken($name, $expires)**

Create a new access token:

```php
$token = $user->createToken('api-token', 60);

// Save this token - it cannot be retrieved later!
echo "Token: {$token}";
```

Parameters:
- `$name` (string) - Token name/description
- `$expires` (int|null) - Expiration time in minutes. Null = never expires

Returns:
- `string` - Plain-text token (only time it's accessible)

The token is hashed using SHA-256 before storage.

**removeToken($token_id)**

Delete a token by its ID:

```php
$success = $user->removeToken(5);

if ($success) {
    echo "Token removed";
}
```

Parameters:
- `$token_id` (int) - Token ID

Returns:
- `bool` - True if deletion successful

### Usage Example

**API token management:**

```php
public function createApiToken(Request $request)
{
    $user = Auth::user();

    $token = $user->createToken(
        $request->name,
        $request->expires_in_minutes
    );

    return response()->json([
        'token' => $token,
        'message' => 'Save this token - it will not be shown again',
    ]);
}

public function listTokens()
{
    $user = Auth::user();
    $tokens = $user->tokens()
        ->orderBy('created_at', 'desc')
        ->get();

    return view('api-tokens', compact('tokens'));
}

public function revokeToken($id)
{
    $user = Auth::user();

    if ($user->removeToken($id)) {
        return back()->with('success', 'Token revoked');
    }

    return back()->with('error', 'Failed to revoke token');
}
```

**View example:**

```blade
<h2>API Tokens</h2>

<form method="POST" action="/api/tokens">
    @csrf
    <input type="text" name="name" placeholder="Token name" required>
    <input type="number" name="expires_in_minutes" placeholder="Expires in (minutes)">
    <button type="submit">Create Token</button>
</form>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Last Used</th>
            <th>Expires</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tokens as $token)
            <tr>
                <td>{{ $token->name }}</td>
                <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                <td>{{ $token->expires_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                <td>
                    <form method="POST" action="/api/tokens/{{ $token->id }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Revoke</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

---

## MakesApiCalls Trait

**Trait:** `Jurager\Passport\Traits\MakesApiCalls`

Provides methods for making HTTP requests to external IP geolocation providers.

### Purpose

This trait is used internally by IP provider classes. You typically won't use it directly unless creating a custom IP provider.

### Methods

**getJson($url, $params = [])**

Makes a GET request and returns JSON response:

```php
$data = $this->getJson('https://api.example.com/endpoint', [
    'param1' => 'value1',
    'param2' => 'value2',
]);
```

### Creating Custom IP Providers

If creating a custom IP geolocation provider:

```php
namespace App\Services;

use Jurager\Passport\Traits\MakesApiCalls;

class CustomIpProvider
{
    use MakesApiCalls;

    public function locate(string $ip): ?array
    {
        $data = $this->getJson('https://api.example.com/locate', [
            'ip' => $ip,
        ]);

        return [
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'country' => $data['country'] ?? null,
        ];
    }
}
```

Register in configuration:

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'custom',
        'custom_providers' => [
            'custom' => \App\Services\CustomIpProvider::class,
        ],
    ],
],
```

See [History](history.md) for complete IP provider documentation.

---

## Combining Traits

You can use multiple traits on your User model:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\Passport;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use Passport;
    use HasTokens;

    // Now you have access to:
    // - history(), current(), logoutById(), logoutOthers(), logoutAll()
    // - tokens(), createToken(), removeToken()
}
```