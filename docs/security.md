# Security

This document covers security features and best practices for Jurager/Passport.

## Built-in Security Features

### 1. Checksum Validation

Every broker-server communication includes a checksum to prevent tampering.

**How it works:**

```php
// Session ID format: Passport-{broker_id}-{token}-{checksum}
$checksum = hash_hmac('sha256', "session:{token}", $broker_secret);
```

The checksum ensures:
- The broker is legitimate
- The token hasn't been modified
- The secret matches

**Configuration:**

No configuration needed - checksums are always validated.

### 2. Open Redirect Protection

Prevents attackers from using your SSO server to redirect users to malicious sites.

**Configure allowed hosts:**

```env
PASSPORT_ALLOWED_REDIRECT_HOSTS=myapp.com,admin.myapp.com,partner.com
```

Or in config:

```php
'allowed_redirect_hosts' => [
    'myapp.com',
    'admin.myapp.com',
    'partner.com',
],
```

**How it works:**

```php
// Validates return_url parameter
if (!$this->isAllowedRedirectUrl($return_url)) {
    return response('Invalid return URL', 400);
}
```

**Best practices:**
- Always configure allowed hosts in production
- Use specific domains, not wildcards
- Subdomains are automatically allowed (e.g., `app.example.com` matches `example.com`)

### 3. Attach Throttling

Prevents rapid re-attaching that can cause redirect loops or DoS attacks.

**Configure throttle time:**

```env
PASSPORT_ATTACH_THROTTLE=5  # seconds
```

**How it works:**

```php
$lastAttachTime = session('sso_last_attach_time', 0);
$throttleSeconds = config('passport.attach_throttle_seconds', 5);

if ((time() - $lastAttachTime) < $throttleSeconds) {
    abort(429, 'Too many attach attempts');
}
```

### 4. Redirect Loop Protection

Detects and prevents infinite redirect loops.

**Configure max attempts:**

```env
PASSPORT_MAX_REDIRECT_ATTEMPTS=3
```

**How it works:**

```php
$redirectCount = session('sso_attach_redirect_count', 0);
$maxAttempts = config('passport.max_redirect_attempts', 3);

if ($redirectCount >= $maxAttempts) {
    throw new RedirectLoopException();
}
```

### 5. Secret Management

Broker secrets are never exposed in responses.

```php
// Broker model automatically hides secrets
protected $hidden = ['secret'];
```

**Best practices:**
- Use strong, random secrets (40+ characters)
- Rotate secrets periodically
- Store secrets securely (environment variables, secret managers)

```php
use Illuminate\Support\Str;

$secret = Str::random(40);
```

### 6. Token Hashing

API tokens are hashed before storage using SHA-256.

```php
$token = Str::random(40);
$hashedToken = hash('sha256', $token);

Token::create(['token' => $hashedToken]);
```

Plain-text tokens are only shown once during creation.

### 7. Session Expiration

Sessions automatically expire after configured TTL.

```env
PASSPORT_STORAGE_TTL=3600  # 1 hour
```

Expired sessions are automatically cleaned up using Laravel's model pruning.

## Security Best Practices

### 1. Use HTTPS

**Always use HTTPS in production:**

```env
# Force HTTPS
APP_URL=https://your-app.com
```

**Redirect HTTP to HTTPS:**

```php
// app/Http/Middleware/ForceHttps.php

public function handle($request, Closure $next)
{
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri());
    }

    return $next($request);
}
```

### 2. Secure Session Configuration

```php
// config/session.php

'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => true,
'same_site' => 'lax',
```

### 3. Validate User Input

Always validate user input in custom commands:

```php
'updateProfile' => function($server, $request) {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return ['success' => false, 'errors' => $validator->errors()];
    }

    // Safe to use validated data
},
```

### 4. Implement Rate Limiting

**For API routes:**

```php
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/api/user', [ApiController::class, 'user']);
});
```

**For login attempts:**

```php
use Illuminate\Support\Facades\RateLimiter;

if (RateLimiter::tooManyAttempts('login:' . $request->ip(), 5)) {
    return back()->withErrors(['email' => 'Too many login attempts']);
}

RateLimiter::hit('login:' . $request->ip());
```

### 5. Email Verification

Require email verification before allowing full access:

```php
// config/passport.php

'after_authenticating' => function($user, $request) {
    if (!$user->email_verified_at) {
        return false;
    }
    return true;
},
```

### 6. Monitor Suspicious Activity

**Log failed login attempts:**

```php
use Jurager\Passport\Events\Unauthenticated;

Event::listen(Unauthenticated::class, function ($event) {
    Log::warning('Failed login attempt', [
        'email' => $event->credentials['email'] ?? 'unknown',
        'ip' => $event->request->ip(),
    ]);
});
```

**Alert on new device logins:**

```php
use Jurager\Passport\Events\Authenticated;

Event::listen(Authenticated::class, function ($event) {
    $session = $event->user->current();

    if ($this->isNewDevice($event->user, $session)) {
        // Send notification
        $event->user->notify(new NewDeviceLogin($session));
    }
});
```

### 7. Implement 2FA

Add two-factor authentication for sensitive applications:

```php
// After initial SSO authentication
if ($user->two_factor_enabled && !session('2fa_verified')) {
    return redirect('/two-factor-challenge');
}
```

### 8. Audit Logging

Log important security events:

```php
// Create an audit log model
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];
}

// Log events
AuditLog::create([
    'user_id' => $user->id,
    'action' => 'login',
    'ip_address' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'metadata' => json_encode([
        'broker' => $broker->client_id,
        'session_id' => $session->id,
    ]),
]);
```

### 9. Regular Security Updates

Keep dependencies updated:

```bash
composer update
```

Monitor security advisories:
- Laravel security releases
- Package security issues
- PHP security updates

### 10. Secure Configuration

**Protect configuration files:**

```bash
# Never commit .env to version control
echo ".env" >> .gitignore
```

**Use environment variables for secrets:**

```env
PASSPORT_BROKER_CLIENT_SECRET=your-secret-here
APP_KEY=base64:...
DB_PASSWORD=...
```

## Common Security Pitfalls

### 1. Exposing Secrets

**❌ Don't:**

```php
// Returning broker with secret
return response()->json($broker);
```

**✅ Do:**

```php
// Secret is automatically hidden
return response()->json($broker->makeVisible([]));

// Or explicitly hide
return response()->json($broker->makeHidden(['secret']));
```

### 2. Trusting Client Input

**❌ Don't:**

```php
$userId = $request->input('user_id');
$user = User::find($userId); // Don't trust client-provided user ID
```

**✅ Do:**

```php
$user = Auth::user(); // Always use authenticated user
```

### 3. Weak Secrets

**❌ Don't:**

```php
$secret = 'password123';
$secret = md5($broker->client_id);
```

**✅ Do:**

```php
use Illuminate\Support\Str;

$secret = Str::random(40);
```

### 4. Missing HTTPS

**❌ Don't:**

```env
PASSPORT_BROKER_SERVER_URL=http://sso.example.com
```

**✅ Do:**

```env
PASSPORT_BROKER_SERVER_URL=https://sso.example.com
```

### 5. Ignoring Failed Authentication

**❌ Don't:**

```php
if (Auth::attempt($credentials)) {
    // Handle success
}
// Silently ignore failure
```

**✅ Do:**

```php
if (Auth::attempt($credentials)) {
    // Handle success
} else {
    // Log failure, rate limit, notify
    Log::warning('Failed login', ['email' => $credentials['email']]);
    RateLimiter::hit('login:' . $request->ip());
}
```

## Security Checklist

- [ ] HTTPS enabled on all applications
- [ ] Strong, random broker secrets (40+ characters)
- [ ] Allowed redirect hosts configured
- [ ] Session cookies secure and HTTP-only
- [ ] Rate limiting on authentication endpoints
- [ ] Email verification required (if applicable)
- [ ] Failed login attempts monitored
- [ ] New device notifications enabled
- [ ] Regular security updates applied
- [ ] Audit logging implemented
- [ ] Secrets stored in environment variables
- [ ] Debug mode disabled in production
- [ ] Session TTL configured appropriately
- [ ] Token expiration set for API tokens
- [ ] Custom commands validate input
- [ ] User permissions checked in commands

## Reporting Security Issues

If you discover a security vulnerability, please email security@example.com (or create a private security advisory on GitHub).

Do not publicly disclose security issues until they have been addressed.
