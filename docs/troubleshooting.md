# Troubleshooting

Common issues and solutions for Jurager/Passport.

## Redirect Loops

### Symptom

Browser keeps redirecting between broker and server, eventually showing "Too many redirects" error.

### Common Causes

1. **AttachBroker middleware running twice**
2. **Session not persisting**
3. **Middleware order incorrect**
4. **Rapid re-attaching**

### Solutions

**Check middleware configuration:**

```php
// Laravel 12+: bootstrap/app.php
$middleware->web(prepend: [
    \Illuminate\Session\Middleware\StartSession::class,
    \Jurager\Passport\Http\Middleware\AttachBroker::class,  // Only once!
]);

// Laravel 11-: app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        \Illuminate\Session\Middleware\StartSession::class,
        \Jurager\Passport\Http\Middleware\AttachBroker::class,  // Only once!
        // ...
    ],
];
```

**Verify session is working:**

```php
// Test route
Route::get('/test-session', function () {
    session(['test' => 'value']);
    return session('test'); // Should return 'value'
});
```

**Check session driver:**

```env
# .env
SESSION_DRIVER=file  # or database, redis, etc.
```

**Increase attach throttle:**

```env
PASSPORT_ATTACH_THROTTLE=10  # Increase from 5 to 10 seconds
```

**Clear sessions:**

```bash
php artisan session:clear
```

---

## Session Not Found

### Symptom

Error: "Client broker not attached" or "Session not found"

### Common Causes

1. **Session expired**
2. **Session token cleared**
3. **Wrong session driver**
4. **Session data not persisting**

### Solutions

**Check session TTL:**

```env
PASSPORT_STORAGE_TTL=3600  # Increase TTL
```

**Verify session configuration:**

```php
// config/session.php
'lifetime' => 120,  // Increase if too short
'expire_on_close' => false,
```

**Check session driver:**

```env
SESSION_DRIVER=file
# Or use database for better reliability
SESSION_DRIVER=database
```

**For database driver, run migrations:**

```bash
php artisan session:table
php artisan migrate
```

**Clear browser cookies and try again**

---

## Invalid Checksum

### Symptom

Error: "Checksum failed" or "Invalid checksum"

### Common Causes

1. **Client secret mismatch**
2. **Broker not registered on server**
3. **Clock skew between servers**

### Solutions

**Verify broker credentials:**

On server:
```php
$broker = \Jurager\Passport\Models\Broker::where('client_id', 'your-client-id')->first();
echo $broker->secret;
```

On broker:
```env
PASSPORT_BROKER_CLIENT_ID=your-client-id
PASSPORT_BROKER_CLIENT_SECRET=exact-secret-from-server
```

**Check broker field configuration:**

```env
# On server
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret
```

**Verify broker exists:**

```php
// On server
$broker = \Jurager\Passport\Models\Broker::where('client_id', 'your-id')->first();

if (!$broker) {
    // Broker not found - create it
}
```

**Sync server clocks** (if using multiple servers)

---

## Broker Not Attached

### Symptom

Error: "Client broker not attached" when accessing protected routes

### Common Causes

1. **AttachBroker middleware not registered**
2. **Session not persisting**
3. **Wrong broker credentials**

### Solutions

**Verify middleware is registered:**

Check `bootstrap/app.php` (Laravel 12+) or `app/Http/Kernel.php` (Laravel 11-)

**Test attach manually:**

```
Visit: https://your-broker.com/sso/client/attach
```

Should redirect to server, then back to broker.

**Enable debug mode:**

```env
PASSPORT_DEBUG=true
```

Check `storage/logs/laravel.log` for details.

**Clear sessions and cookies:**

```bash
php artisan session:clear
```

Clear browser cookies and try again.

---

## CORS Errors

### Symptom

Browser console shows CORS errors when broker communicates with server.

### Solution

CORS is not an issue for Jurager/Passport because:
- Communication happens server-to-server (not browser-to-server)
- User is redirected between applications (not AJAX calls)

If you see CORS errors, you may be using the package incorrectly.

**Correct flow:**
1. User visits broker
2. Broker redirects to server (full page redirect)
3. User authenticates on server
4. Server redirects back to broker (full page redirect)

**Not AJAX:**
```javascript
// ❌ Don't do this
fetch('https://sso-server.com/sso/server/login', {
    method: 'POST',
    body: JSON.stringify(credentials),
});
```

---

## Authentication Not Working

### Symptom

User can't authenticate or authentication doesn't persist

### Common Causes

1. **Guard not configured**
2. **User model doesn't use traits**
3. **Wrong username field**
4. **Server URL incorrect**

### Solutions

**Verify guard configuration:**

The package automatically configures the guard when using `ClientAuthenticate` middleware.

**Check User model:**

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jurager\Passport\Traits\Passport;

class User extends Authenticatable
{
    use Passport;  // Required for session management
}
```

**Verify username field:**

```env
# On broker
PASSPORT_BROKER_CLIENT_USERNAME=email  # Must match server
```

**Check server URL:**

```env
PASSPORT_BROKER_SERVER_URL=https://sso-server.com/sso/server
# Must include protocol and route prefix
```

**Test server connection:**

```bash
curl https://sso-server.com/sso/server/attach
```

---

## User Not Created on Broker

### Symptom

Authentication works but user doesn't exist in broker database

### Solution

Configure user creation strategy:

```php
// config/passport.php (on broker)

'user_create_strategy' => function ($data) {
    return \App\Models\User::create([
        'email' => $data['email'],
        'name' => $data['name'],
        'password' => '',  // No password on broker
    ]);
},
```

Ensure email field is fillable:

```php
// app/Models/User.php

protected $fillable = [
    'name',
    'email',
    'password',
];
```

---

## Token Authentication Not Working

### Symptom

Bearer token returns 401 Unauthorized

### Common Causes

1. **Token expired**
2. **Token not found**
3. **Wrong token format**

### Solutions

**Check token exists:**

```php
$token = \Jurager\Passport\Models\Token::where('token', hash('sha256', $plainTextToken))->first();

if (!$token) {
    // Token not found
}
```

**Check expiration:**

```php
if ($token->expires_at && $token->expires_at->isPast()) {
    // Token expired
}
```

**Use correct format:**

```bash
# Correct
curl -H "Authorization: Bearer your-token-here" https://api.com/user

# Wrong
curl -H "Authorization: your-token-here" https://api.com/user
```

**Create new token:**

```php
$user = Auth::user();
$token = $user->createToken('api-token', 60);  // 60 minutes
echo $token;  // Save this!
```

---

## IP Geolocation Not Working

### Symptom

Location fields (city, region, country) are null

### Common Causes

1. **Guzzle not installed**
2. **Provider not configured**
3. **Request timeout**
4. **Wrong environment**

### Solutions

**Install Guzzle:**

```bash
composer require guzzlehttp/guzzle
```

**Configure provider:**

```php
// config/passport.php

'server' => [
    'lookup' => [
        'provider' => 'ip-api',  // or 'ip2location-lite'
    ],
],
```

**Increase timeout:**

```php
'lookup' => [
    'timeout' => 2.0,  // Increase from 1.0 to 2.0 seconds
],
```

**Check environment:**

```php
'lookup' => [
    'environments' => ['production', 'local'],  // Add current environment
],
```

**Test provider manually:**

```php
$provider = new \Jurager\Passport\IpLookup\IpApi('8.8.8.8');
$result = $provider->getResult();
dd($result);
```

---

## Debug Mode

Enable debug mode for detailed logging:

```env
PASSPORT_DEBUG=true
```

Check logs:

```bash
tail -f storage/logs/laravel.log
```

Debug output includes:
- Attach attempts and throttling
- Session validation
- Checksum verification
- Authentication attempts

## Getting Help

If you can't solve your issue:

1. **Check logs:** `storage/logs/laravel.log`
2. **Enable debug mode:** `PASSPORT_DEBUG=true`
3. **Clear cache:** `php artisan cache:clear`
4. **Clear sessions:** `php artisan session:clear`
5. **Check configuration:** Review all `.env` settings
6. **Test manually:** Use curl to test endpoints
7. **Create issue:** [GitHub Issues](https://github.com/jurager/passport/issues)

## Common Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear sessions
php artisan session:clear

# Run migrations
php artisan migrate

# Check routes
php artisan route:list | grep sso

# Check config
php artisan config:show passport
```
