# Session Management

Jurager/Passport provides comprehensive session management, allowing users to view their active sessions and terminate them individually or in bulk.

## How Sessions Work

When a user authenticates, the package creates a History record that tracks:

- Session ID
- Device information (device type, platform, browser)
- IP address and geolocation
- Login timestamp
- Expiration timestamp
- Remember me status

## Session TTL

Configure session Time-To-Live in seconds:

```env
# 10 minutes (600 seconds)
PASSPORT_STORAGE_TTL=600

# 1 hour
PASSPORT_STORAGE_TTL=3600

# Never expire (use with caution)
PASSPORT_STORAGE_TTL=null
```

Or in `config/passport.php`:

```php
'storage_ttl' => 600,
```

Sessions are automatically expired after the TTL period. Expired sessions can be cleaned up using Laravel's model pruning.

## Viewing Active Sessions

**Get all user sessions:**

```php
$user = Auth::user();
$sessions = $user->history()
    ->whereNull('deleted_at')
    ->orderBy('created_at', 'desc')
    ->get();
```

**Get only non-expired sessions:**

```php
$activeSessions = $user->history()
    ->whereNull('deleted_at')
    ->where(function ($query) {
        $query->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
    })
    ->get();
```

**Get current session:**

```php
$currentSession = $user->current();

if ($currentSession) {
    echo "Current device: {$currentSession->device}";
    echo "Location: {$currentSession->location}";
}
```

## Session Information

Each History record provides detailed session information:

```php
$session = $user->current();

// Device information
echo $session->device_type;  // desktop, mobile, tablet
echo $session->device;        // iPhone, Samsung Galaxy, etc.
echo $session->platform;      // iOS, Android, Windows, macOS, Linux
echo $session->browser;       // Chrome, Firefox, Safari, etc.

// Location information
echo $session->ip;           // 192.168.1.1
echo $session->city;         // New York
echo $session->region;       // NY
echo $session->country;      // United States
echo $session->location;     // "New York, NY, United States" (computed)

// Session metadata
echo $session->session_id;   // Laravel session ID
echo $session->user_agent;   // Full user agent string
echo $session->created_at;   // Login timestamp
echo $session->expires_at;   // Expiration timestamp

// Computed attributes
echo $session->is_current ? 'Current session' : 'Other session';
```

## Terminating Sessions

### Terminate Current Session

```php
use Illuminate\Support\Facades\Auth;

// Standard logout
Auth::guard()->logout();
```

Or using the Passport trait:

```php
$user->logoutById(); // Null parameter = current session
```

### Terminate Specific Session

```php
$user->logoutById($historyId);
```

Or directly on the History model:

```php
$session = $user->history()->find($historyId);
$session->revoke();
```

### Terminate All Other Sessions

```php
$user->logoutOthers();
```

This logs the user out from all devices except the current one - useful for security purposes.

### Terminate All Sessions

```php
$user->logoutAll();
```

Logs the user out from all devices, including the current session.

## Using Broker Routes

The package provides routes for session management:

### Logout by Session ID

```
POST /sso/client/logout/id
```

**Parameters:**
- `id` - History record ID

**Example:**

```html
<form method="POST" action="{{ route('sso.broker.logout.id') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $session->id }}">
    <button type="submit">Revoke Session</button>
</form>
```

### Logout All Sessions

```
POST /sso/client/logout/all
```

**Example:**

```html
<form method="POST" action="{{ route('sso.broker.logout.all') }}">
    @csrf
    <button type="submit">Logout from All Devices</button>
</form>
```

### Logout All Except Current

```
POST /sso/client/logout/others
```

**Example:**

```html
<form method="POST" action="{{ route('sso.broker.logout.others') }}">
    @csrf
    <button type="submit">Logout from Other Devices</button>
</form>
```

## Complete Session Management Example

**Controller:**

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $sessions = $user->history()
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sessions.index', compact('sessions'));
    }

    public function revoke(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->logoutById($id)) {
            return back()->with('success', 'Session successfully terminated');
        }

        return back()->with('error', 'Failed to terminate session');
    }

    public function revokeOthers()
    {
        $user = Auth::user();

        if ($user->logoutOthers()) {
            return back()->with('success', 'Logged out from all other devices');
        }

        return back()->with('error', 'Failed to logout from other devices');
    }

    public function revokeAll()
    {
        $user = Auth::user();

        $user->logoutAll();

        Auth::guard()->logout();

        return redirect('/')->with('success', 'Logged out from all devices');
    }
}
```

**Routes:**

```php
Route::middleware('auth')->group(function () {
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{id}', [SessionController::class, 'revoke'])->name('sessions.revoke');
    Route::post('/sessions/revoke-others', [SessionController::class, 'revokeOthers'])->name('sessions.revoke-others');
    Route::post('/sessions/revoke-all', [SessionController::class, 'revokeAll'])->name('sessions.revoke-all');
});
```

**View (resources/views/sessions/index.blade.php):**

```blade
<h1>Active Sessions</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="sessions-list">
    @forelse($sessions as $session)
        <div class="session-card">
            <div class="session-header">
                <div class="device-icon">
                    @if($session->device_type === 'mobile')
                        📱
                    @elseif($session->device_type === 'tablet')
                        📱
                    @else
                        💻
                    @endif
                </div>
                <div class="session-info">
                    <h3>{{ $session->device }} - {{ $session->browser }}</h3>
                    <p>{{ $session->platform }}</p>
                </div>
                @if($session->is_current)
                    <span class="badge badge-success">Current Session</span>
                @endif
            </div>

            <div class="session-details">
                <p><strong>Location:</strong> {{ $session->location ?? 'Unknown' }}</p>
                <p><strong>IP Address:</strong> {{ $session->ip }}</p>
                <p><strong>Logged in:</strong> {{ $session->created_at->diffForHumans() }}</p>
                @if($session->expires_at)
                    <p><strong>Expires:</strong> {{ $session->expires_at->diffForHumans() }}</p>
                @endif
            </div>

            @unless($session->is_current)
                <form method="POST" action="{{ route('sessions.revoke', $session->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Revoke Session</button>
                </form>
            @endunless
        </div>
    @empty
        <p>No active sessions found.</p>
    @endforelse
</div>

<div class="session-actions">
    <form method="POST" action="{{ route('sessions.revoke-others') }}">
        @csrf
        <button type="submit" class="btn btn-warning">Logout from Other Devices</button>
    </form>

    <form method="POST" action="{{ route('sessions.revoke-all') }}">
        @csrf
        <button type="submit" class="btn btn-danger">Logout from All Devices</button>
    </form>
</div>
```

## Automatic Session Cleanup

Sessions are automatically cleaned up when expired using Laravel's model pruning:

**Schedule the command:**

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    $schedule->command('model:prune')->daily();
}
```

This will soft-delete expired History records daily.

## Session Security

### Best Practices

1. **Set appropriate TTL:**
   ```env
   # For sensitive applications
   PASSPORT_STORAGE_TTL=600  # 10 minutes

   # For regular applications
   PASSPORT_STORAGE_TTL=3600  # 1 hour
   ```

2. **Monitor active sessions:**
   - Allow users to view their active sessions
   - Send notifications on new logins from unfamiliar devices

3. **Provide logout options:**
   - Always offer "Logout from other devices"
   - Consider forcing re-authentication for sensitive actions

4. **Track login history:**
   - Keep deleted (logged out) sessions for audit purposes
   - Don't hard delete session records
