# API Tokens

Jurager/Passport supports personal access tokens for API authentication, allowing users to authenticate API requests without username and password.

## Overview

API tokens provide:
- **Stateless authentication** - No session required
- **Revocable access** - Tokens can be deleted at any time
- **Scoped expiration** - Set expiration per token
- **Usage tracking** - Last used timestamp

## Setup

Add the `HasTokens` trait to your User model:

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

## Creating Tokens

### Using the createToken Method

```php
$user = Auth::user();

// Create token with expiration (in minutes)
$token = $user->createToken('api-token', 60);

// Create token without expiration
$token = $user->createToken('api-token', null);
```

**Parameters:**
- `$name` (string) - Token name/description
- `$expires` (int|null) - Expiration in minutes. Null = never expires

**Returns:**
- `string` - Plain-text token (only time it's accessible)

> **Important:** Save the returned token immediately. It cannot be retrieved later as it's hashed before storage.

### Complete Example

```php
public function createToken(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'expires_in' => 'nullable|integer|min:1',
    ]);

    $user = Auth::user();

    $token = $user->createToken(
        $request->name,
        $request->expires_in
    );

    return response()->json([
        'token' => $token,
        'message' => 'Token created successfully. Save it now - it will not be shown again.',
    ]);
}
```

## Using Tokens

### Bearer Token Authentication

Include the token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     https://your-app.com/api/user
```

Using Laravel HTTP client:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken($token)
    ->get('https://your-app.com/api/user');
```

Using JavaScript:

```javascript
fetch('https://your-app.com/api/user', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### How Token Authentication Works

The `ClientAuthenticate` middleware automatically detects bearer tokens:

```php
// 1. Middleware checks for bearer token
$token = $request->bearerToken();

// 2. If token exists, attempt authentication
if ($token) {
    $user = Auth::guard()->loginFromToken($token);
}

// 3. Token is hashed and looked up in database
$hashedToken = hash('sha256', $token);
$accessToken = Token::where('token', $hashedToken)->first();

// 4. Token is validated
if ($accessToken && !$accessToken->expires_at->isPast()) {
    // User is authenticated
    $user = $accessToken->tokenable;
}
```

## Managing Tokens

### List User Tokens

```php
$user = Auth::user();
$tokens = $user->tokens()->orderBy('created_at', 'desc')->get();

foreach ($tokens as $token) {
    echo "Name: {$token->name}\n";
    echo "Created: {$token->created_at}\n";
    echo "Last used: {$token->last_used_at}\n";
    echo "Expires: {$token->expires_at}\n";
}
```

### Revoke Token

```php
$user = Auth::user();
$success = $user->removeToken($tokenId);
```

Or directly delete the model:

```php
$token = $user->tokens()->find($tokenId);
$token->delete();
```

### Check Token Expiration

```php
$token = Token::find($id);

if ($token->expires_at && $token->expires_at->isPast()) {
    echo "Token expired";
} else {
    echo "Token is valid";
}
```

## Complete Token Management Example

**Controller:**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiTokenController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tokens = $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('api-tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'expires_in' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();
        $token = $user->createToken($request->name, $request->expires_in);

        return back()->with([
            'token' => $token,
            'message' => 'Token created successfully. Save it now - it will not be shown again.',
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        if ($user->removeToken($id)) {
            return back()->with('success', 'Token revoked successfully');
        }

        return back()->with('error', 'Failed to revoke token');
    }
}
```

**Routes:**

```php
Route::middleware('auth')->prefix('api-tokens')->group(function () {
    Route::get('/', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/{id}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
});
```

**View (resources/views/api-tokens/index.blade.php):**

```blade
<h1>API Tokens</h1>

@if(session('token'))
    <div class="alert alert-warning">
        <h3>Save Your Token</h3>
        <p>This is the only time you will see this token. Copy it now:</p>
        <code>{{ session('token') }}</code>
        <p class="mt-2">{{ session('message') }}</p>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<h2>Create New Token</h2>
<form method="POST" action="{{ route('api-tokens.store') }}">
    @csrf
    <div class="form-group">
        <label for="name">Token Name</label>
        <input type="text" name="name" id="name" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="expires_in">Expires In (minutes)</label>
        <input type="number" name="expires_in" id="expires_in" class="form-control"
               placeholder="Leave empty for no expiration">
    </div>
    <button type="submit" class="btn btn-primary">Create Token</button>
</form>

<h2>Active Tokens</h2>
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Created</th>
            <th>Last Used</th>
            <th>Expires</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tokens as $token)
            <tr>
                <td>{{ $token->name }}</td>
                <td>{{ $token->created_at->format('Y-m-d H:i') }}</td>
                <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    @if($token->expires_at)
                        {{ $token->expires_at->format('Y-m-d H:i') }}
                        @if($token->expires_at->isPast())
                            <span class="badge badge-danger">Expired</span>
                        @endif
                    @else
                        <span class="badge badge-success">Never</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('api-tokens.destroy', $token->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">No tokens created yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
```

## Automatic Token Cleanup

Expired tokens are automatically pruned using Laravel's model pruning:

**Schedule the command:**

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    $schedule->command('model:prune')->daily();
}
```

Or manually prune:

```bash
php artisan model:prune --model="Jurager\Passport\Models\Token"
```

## Token Security

### Best Practices

1. **Use HTTPS:**
   - Always use HTTPS to prevent token interception
   - Tokens sent over HTTP can be stolen

2. **Set expiration:**
   ```php
   // Short-lived token for temporary access
   $token = $user->createToken('temp-token', 60); // 1 hour

   // Long-lived token for integrations
   $token = $user->createToken('integration-token', 43200); // 30 days
   ```

3. **Limit token scope:**
   - Create separate tokens for different purposes
   - Revoke tokens when no longer needed

4. **Monitor token usage:**
   - Check `last_used_at` to detect unused tokens
   - Revoke tokens that haven't been used recently

5. **Rotate tokens:**
   - Periodically regenerate tokens
   - Notify users when tokens are created or revoked

## Rate Limiting

Apply rate limiting to API routes:

```php
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/api/user', [ApiController::class, 'user']);
    Route::get('/api/data', [ApiController::class, 'data']);
});
```

This limits requests to 60 per minute per user.

## Testing Token Authentication

```php
use Tests\TestCase;
use App\Models\User;

class ApiAuthTest extends TestCase
{
    public function test_token_authentication()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', 60);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    public function test_expired_token_fails()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token', -1); // Already expired

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->get('/api/user');

        $response->assertStatus(401);
    }
}
```
