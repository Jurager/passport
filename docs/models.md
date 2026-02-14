# Models

The package provides three Eloquent models for managing brokers, session history, and API tokens.

## Broker Model

**Class:** `Jurager\Passport\Models\Broker`

Represents a registered broker application on the SSO server.

### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| client_id | string | Unique broker identifier |
| secret | string | Secret key for checksum validation |
| name | string | Human-readable broker name (optional) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |
| deleted_at | timestamp | Soft delete timestamp |

### Configuration

Configure the table name:

```env
PASSPORT_BROKERS_TABLE=brokers
```

Or in `config/passport.php`:

```php
'brokers_table_name' => 'brokers',
```

### Fields Configuration

Configure which model fields are used for ID and secret:

```env
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret
```

### Usage

**Create a broker:**

```php
use Jurager\Passport\Models\Broker;
use Illuminate\Support\Str;

$broker = Broker::create([
    'client_id' => 'my-app',
    'secret' => Str::random(40),
    'name' => 'My Application',
]);
```

**Find a broker:**

```php
$broker = Broker::where('client_id', 'my-app')->first();
```

**Update a broker:**

```php
$broker->update(['name' => 'New Name']);
```

**Delete a broker (soft delete):**

```php
$broker->delete();
```

**Restore a deleted broker:**

```php
$broker->restore();
```

### Hidden Attributes

The `secret` field is automatically hidden when the model is serialized to JSON:

```php
$broker->toArray(); // secret is not included
```

### Custom Broker Model

Extend the base model to add custom fields and relationships:

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
        'allowed_ips',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_ips' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAllowedIp($ip)
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }
}
```

Update configuration:

```env
PASSPORT_SERVER_MODEL=App\Models\Broker
```

---

## History Model

**Class:** `Jurager\Passport\Models\History`

Stores session history for authenticated users across all brokers.

### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| authenticatable_id | integer | User ID |
| authenticatable_type | string | User model class |
| session_id | string | Session identifier |
| user_agent | string | Browser user agent |
| ip | string | IP address |
| device_type | string | Device type (desktop, mobile, tablet) |
| device | string | Device name |
| platform | string | OS platform |
| browser | string | Browser name |
| city | string | City (from IP geolocation) |
| region | string | Region/state |
| country | string | Country |
| remember_token | string | Remember me token |
| expires_at | timestamp | Session expiration |
| created_at | timestamp | Login timestamp |
| updated_at | timestamp | Update timestamp |
| deleted_at | timestamp | Logout timestamp (soft delete) |

### Configuration

```env
PASSPORT_HISTORY_TABLE=history
```

### Relationships

**Polymorphic relationship to User:**

```php
public function authenticatable(): MorphTo
{
    return $this->morphTo();
}
```

**Usage:**

```php
$history = History::find(1);
$user = $history->authenticatable; // User model
```

### Attributes

**location** (accessor):

Combines city, region, and country:

```php
$history->location; // "New York, NY, United States"
```

**is_current** (accessor):

Checks if this is the current session:

```php
if ($history->is_current) {
    echo "This is your current session";
}
```

### Methods

**revoke()**

Revokes (logs out) this session:

```php
$history->revoke();
```

This:
- Destroys the server session
- Soft deletes the history record

### Automatic Cleanup

The model uses Laravel's `Prunable` trait for automatic cleanup:

```php
public function prunable(): Builder
{
    return $this->where('expires_at', '<=', now());
}
```

**Schedule the pruning:**

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    $schedule->command('model:prune')->daily();
}
```

### Usage

**Get user's login history:**

```php
$user = Auth::user();
$history = $user->history()->orderBy('created_at', 'desc')->get();
```

**Get active sessions only:**

```php
$activeSessions = $user->history()
    ->whereNull('deleted_at')
    ->where('expires_at', '>', now())
    ->get();
```

**Logout from specific session:**

```php
$history = $user->history()->find($id);
$history->revoke();
```

---

## Token Model

**Class:** `Jurager\Passport\Models\Token`

Stores personal access tokens for API authentication.

### Table Structure

| Column | Type | Description |
|--------|------|-------------|
| id | integer | Primary key |
| tokenable_id | integer | User ID |
| tokenable_type | string | User model class |
| name | string | Token name/description |
| token | string | Hashed token value |
| last_used_at | timestamp | Last usage timestamp |
| expires_at | timestamp | Expiration timestamp |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |
| deleted_at | timestamp | Deletion timestamp |

### Configuration

```env
PASSPORT_TOKENS_TABLE=access_tokens
```

### Relationships

**Polymorphic relationship to User:**

```php
public function tokenable(): MorphTo
{
    return $this->morphTo('tokenable');
}
```

**Usage:**

```php
$token = Token::find(1);
$user = $token->tokenable; // User model
```

### Token Storage

Tokens are hashed using SHA-256 before storage:

```php
$plainTextToken = Str::random(40);
$hashedToken = hash('sha256', $plainTextToken);

Token::create([
    'name' => 'api-token',
    'token' => $hashedToken,
]);
```

> **Important:** The plain-text token is only available when created. It cannot be retrieved later.

### Casts

```php
protected $casts = [
    'last_used_at' => 'datetime',
    'expires_at' => 'datetime',
];
```

### Automatic Cleanup

Like History, Token uses the `Prunable` trait:

```php
public function prunable(): Builder
{
    return $this->where('expires_at', '<=', now());
}
```

Schedule pruning to clean up expired tokens daily.

### Usage

Typically accessed through the `HasTokens` trait on the User model:

```php
$user = Auth::user();

// Create token
$token = $user->createToken('api-token', 60); // expires in 60 minutes

// Get all tokens
$tokens = $user->tokens;

// Remove token
$user->removeToken($tokenId);
```

See [Tokens](tokens.md) for complete documentation.

---

## Extending Models

You can extend any of the package models to add custom functionality:

### Example: Extended History Model

```php
namespace App\Models;

use Jurager\Passport\Models\History as BaseHistory;

class History extends BaseHistory
{
    public function getCountryFlagAttribute()
    {
        $flags = [
            'US' => '🇺🇸',
            'GB' => '🇬🇧',
            'DE' => '🇩🇪',
            // ...
        ];

        return $flags[$this->country] ?? '🏳️';
    }

    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
```

**Update configuration:**

```php
// config/passport.php

'history_model' => App\Models\History::class,
```
