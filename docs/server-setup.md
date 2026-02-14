# Server Setup

The SSO Server is the central authentication authority that manages user accounts, validates credentials, and maintains sessions for all connected broker applications.

## Environment Configuration

Configure your `.env` file for the server application:

```env
# Server driver (model or array)
PASSPORT_SERVER_DRIVER=model

# Broker model class (for model driver)
PASSPORT_SERVER_MODEL=Jurager\Passport\Models\Broker

# Broker model fields
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret

# Session TTL in seconds (600 = 10 minutes, null = forever)
PASSPORT_STORAGE_TTL=600

# Route prefixes
PASSPORT_ROUTES_PREFIX_SERVER=sso/server

# Debug mode
PASSPORT_DEBUG=false
```

## Broker Management

The server needs to know which broker applications are authorized to connect. There are two methods to manage brokers:

### Method 1: Database Driver (Recommended)

The database driver stores brokers in the `brokers` table.

**Create a broker:**

```php
use Jurager\Passport\Models\Broker;

$broker = Broker::create([
    'client_id' => 'my-app',
    'secret' => 'random-secret-string-here',
    'name' => 'My Application',
]);
```

**Generate a secure secret:**

```php
use Illuminate\Support\Str;

$secret = Str::random(40);
```

**Using a custom Broker model:**

If you need additional fields or relationships, extend the base Broker model:

```php
namespace App\Models;

use Jurager\Passport\Models\Broker as BaseBroker;

class Broker extends BaseBroker
{
    protected $fillable = [
        'client_id',
        'secret',
        'name',
        'domain',
        'is_active',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

Update your `.env`:

```env
PASSPORT_SERVER_MODEL=App\Models\Broker
```

### Method 2: Array Driver

For simple setups or development, use the array driver to define brokers directly in the config file.

**Update `.env`:**

```env
PASSPORT_SERVER_DRIVER=array
```

**Update `config/passport.php`:**

```php
'server' => [
    'driver' => 'array',

    'brokers' => [
        'app1' => 'secret-for-app1',
        'app2' => 'secret-for-app2',
        'admin-panel' => 'secret-for-admin',
    ],
],
```

> **Warning:** Array driver doesn't support soft deletes or additional broker metadata. Use the database driver for production.

## User Model Configuration

Your User model should use the `Passport` trait to enable session management:

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\Passport;

class User extends Authenticatable
{
    use Passport;

    // ... rest of your User model
}
```

This trait provides methods like:
- `history()` - Get user's session history
- `current()` - Get current session
- `logoutById($id)` - Logout specific session
- `logoutOthers()` - Logout all except current
- `logoutAll()` - Logout all sessions

## Routes

The package automatically registers server routes under the configured prefix. Default routes:

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `/sso/server/attach` | Attach broker to server |
| POST | `/sso/server/login` | Authenticate user |
| GET | `/sso/server/profile` | Get user profile |
| POST | `/sso/server/logout` | Logout user |
| POST | `/sso/server/commands/{command}` | Execute custom command |

To customize the route prefix, update `.env`:

```env
PASSPORT_ROUTES_PREFIX_SERVER=api/sso
```

## User Information Callback

Customize the user data returned to brokers:

```php
// config/passport.php

'user_info' => function($user, $broker, $request) {
    $payload = $user->toArray();

    // Add custom fields
    $payload['roles'] = $user->roles->pluck('name');
    $payload['permissions'] = $user->getAllPermissions();

    // Broker-specific data
    if ($broker->client_id === 'admin-panel') {
        $payload['is_admin'] = $user->isAdmin();
    }

    return $payload;
},
```

## Post-Authentication Verification

Add additional verification after successful authentication:

```php
// config/passport.php

'after_authenticating' => function($user, $request) {
    // Only allow verified users
    if (!$user->email_verified_at) {
        return false;
    }

    // Check if user is active
    if (!$user->is_active) {
        return false;
    }

    return true;
},
```

> **Note:** Returning `false` will deny authentication even if credentials are valid.

## User-Agent Parsing

Configure User-Agent parsing for session history:

```env
PASSPORT_SERVER_PARSER=agent
```

**Supported parsers:**

- `agent` - [jenssegers/agent](https://github.com/jenssegers/agent)
- `whichbrowser` - [WhichBrowser/Parser-PHP](https://github.com/WhichBrowser/Parser-PHP)

**Install the parser:**

```bash
# For agent parser
composer require jenssegers/agent

# For whichbrowser parser
composer require whichbrowser/parser
```

## IP Geolocation

Enable IP geolocation to track user locations:

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'ip-api', // or 'ip2location-lite' or custom
        'timeout' => 1.0,
        'environments' => ['production', 'local'],
    ],
],
```

See [History & Geolocation](history.md) for detailed configuration.

## Security Configuration

**Allowed redirect hosts** (prevent open redirect attacks):

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=myapp.com,admin.myapp.com,partner-app.com
```

Or in config:

```php
'allowed_redirect_hosts' => [
    'myapp.com',
    'admin.myapp.com',
    'partner-app.com',
],
```

**Attach throttling:**

```env
PASSPORT_ATTACH_THROTTLE=5  # seconds between attach attempts
```

**Redirect loop protection:**

```env
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
```

## Custom Commands

Define custom commands that brokers can execute:

```php
// config/passport.php

'commands' => [
    'hasRole' => function($server, $request) {
        $user = Auth::guard()->user();
        $role = $request->input('role');

        return [
            'success' => $user->hasRole($role),
        ];
    },

    'getPermissions' => function($server, $request) {
        $user = Auth::guard()->user();

        return [
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    },
],
```

Brokers can call these commands via the Broker API. See [Commands](commands.md) for details.

## Testing the Server

**Create a test broker:**

```php
use Jurager\Passport\Models\Broker;
use Illuminate\Support\Str;

$broker = Broker::create([
    'client_id' => 'test-app',
    'secret' => Str::random(40),
]);

echo "Client ID: " . $broker->client_id . PHP_EOL;
echo "Secret: " . $broker->secret . PHP_EOL;
```

**Test the attach endpoint:**

```bash
curl -X POST "https://your-server.com/sso/server/attach?broker=test-app&token=random-token&checksum=calculated-checksum"
```
