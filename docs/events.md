# Events

Jurager/Passport fires several events during the authentication lifecycle, allowing you to hook into the SSO process and perform custom actions.

## Available Events

The package fires both Laravel's standard authentication events and custom package-specific events.

### Package Events

**Namespace:** `Jurager\Passport\Events`

1. **Authenticated** - Fired when a user successfully authenticates
2. **Logout** - Fired when a user logs out
3. **Unauthenticated** - Fired when authentication fails

### Laravel Events

The package also fires Laravel's built-in authentication events:

1. **Attempting** - `Illuminate\Auth\Events\Attempting`
2. **Authenticated** - `Illuminate\Auth\Events\Authenticated`
3. **Login** - `Illuminate\Auth\Events\Login`
4. **Logout** - `Illuminate\Auth\Events\Logout`
5. **Failed** - `Illuminate\Auth\Events\Failed`

## Event Details

### Authenticated Event

**Class:** `Jurager\Passport\Events\Authenticated`

Fired when a user successfully authenticates via SSO.

**Properties:**
```php
public $user;      // Authenticated user model
public $request;   // HTTP request instance
```

**Example:**

```php
namespace Jurager\Passport\Events;

use Illuminate\Http\Request;

class Authenticated
{
    public $user;
    public $request;

    public function __construct($user, Request $request)
    {
        $this->user = $user;
        $this->request = $request;
    }
}
```

### Logout Event

**Class:** `Jurager\Passport\Events\Logout`

Fired when a user logs out.

**Properties:**
```php
public $user;      // User being logged out
```

**Example:**

```php
namespace Jurager\Passport\Events;

class Logout
{
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
```

### Unauthenticated Event

**Class:** `Jurager\Passport\Events\Unauthenticated`

Fired when authentication fails.

**Properties:**
```php
public $credentials;  // Login credentials attempted
public $request;      // HTTP request instance
```

**Example:**

```php
namespace Jurager\Passport\Events;

use Illuminate\Http\Request;

class Unauthenticated
{
    public $credentials;
    public $request;

    public function __construct(array $credentials, Request $request)
    {
        $this->credentials = $credentials;
        $this->request = $request;
    }
}
```

## Creating Event Listeners

### Generate a Listener

```bash
php artisan make:listener LogSuccessfulLogin
```

### Implement the Listener

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function handle(Authenticated $event)
    {
        Log::info('User authenticated', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
        ]);
    }
}
```

### Register the Listener

**In EventServiceProvider:**

```php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Jurager\Passport\Events\Authenticated;
use Jurager\Passport\Events\Logout;
use Jurager\Passport\Events\Unauthenticated;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogUserLogout;
use App\Listeners\LogFailedLogin;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Authenticated::class => [
            LogSuccessfulLogin::class,
        ],
        Logout::class => [
            LogUserLogout::class,
        ],
        Unauthenticated::class => [
            LogFailedLogin::class,
        ],
    ];
}
```

## Common Use Cases

### Send Welcome Email on First Login

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Authenticated $event)
    {
        $user = $event->user;

        // Check if this is the first login
        if ($user->history()->count() === 1) {
            Mail::to($user->email)->send(new WelcomeMail($user));
        }
    }
}
```

### Track Failed Login Attempts

```php
namespace App\Listeners;

use Jurager\Passport\Events\Unauthenticated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackFailedLogins
{
    public function handle(Unauthenticated $event)
    {
        $email = $event->credentials['email'] ?? 'unknown';
        $ip = $event->request->ip();

        $key = "failed_login:{$email}:{$ip}";
        $attempts = Cache::increment($key);

        Cache::put($key, $attempts, now()->addHours(1));

        if ($attempts >= 5) {
            Log::warning('Multiple failed login attempts', [
                'email' => $email,
                'ip' => $ip,
                'attempts' => $attempts,
            ]);

            // Optionally send notification
            // Notification::route('mail', 'security@example.com')
            //     ->notify(new SuspiciousActivity($email, $ip, $attempts));
        }
    }
}
```

### Notify User of New Login

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;
use App\Notifications\NewLoginNotification;

class NotifyNewLogin
{
    public function handle(Authenticated $event)
    {
        $user = $event->user;
        $request = $event->request;

        // Get current session info
        $currentSession = $user->current();

        // Check if login from new device or location
        $isNewDevice = $this->isNewDevice($user, $currentSession);

        if ($isNewDevice) {
            $user->notify(new NewLoginNotification($currentSession));
        }
    }

    protected function isNewDevice($user, $currentSession)
    {
        // Check if we've seen this device before
        $existingDevice = $user->history()
            ->where('device', $currentSession->device)
            ->where('platform', $currentSession->platform)
            ->where('id', '!=', $currentSession->id)
            ->exists();

        return !$existingDevice;
    }
}
```

### Update User Last Login

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;

class UpdateLastLogin
{
    public function handle(Authenticated $event)
    {
        $user = $event->user;

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $event->request->ip(),
        ]);
    }
}
```

### Clear User Cache on Logout

```php
namespace App\Listeners;

use Jurager\Passport\Events\Logout;
use Illuminate\Support\Facades\Cache;

class ClearUserCache
{
    public function handle(Logout $event)
    {
        $user = $event->user;

        // Clear user-specific cache
        Cache::forget("user:{$user->id}:permissions");
        Cache::forget("user:{$user->id}:settings");
        Cache::tags(["user:{$user->id}"])->flush();
    }
}
```

## Event Listeners with Queued Jobs

For time-consuming tasks, use queued event listeners:

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessUserAnalytics implements ShouldQueue
{
    public function handle(Authenticated $event)
    {
        // This runs in the background
        // Heavy analytics processing
        $this->updateLoginStatistics($event->user);
        $this->calculateUserMetrics($event->user);
    }

    protected function updateLoginStatistics($user)
    {
        // ...
    }

    protected function calculateUserMetrics($user)
    {
        // ...
    }
}
```

## Event Broadcasting

Broadcast events to notify frontend applications:

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $session;

    public function __construct($user, $session)
    {
        $this->user = $user;
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('user.' . $this->user->id);
    }

    public function broadcastWith()
    {
        return [
            'device' => $this->session->device,
            'location' => $this->session->location,
            'time' => now()->toIso8601String(),
        ];
    }
}
```

**Listener:**

```php
namespace App\Listeners;

use Jurager\Passport\Events\Authenticated;
use App\Events\UserLoggedIn;

class BroadcastLogin
{
    public function handle(Authenticated $event)
    {
        $session = $event->user->current();

        broadcast(new UserLoggedIn($event->user, $session));
    }
}
```

## Testing Events

```php
use Tests\TestCase;
use App\Models\User;
use Jurager\Passport\Events\Authenticated;
use Illuminate\Support\Facades\Event;

class AuthenticationTest extends TestCase
{
    public function test_authenticated_event_is_fired()
    {
        Event::fake();

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Event::assertDispatched(Authenticated::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }
}
```
