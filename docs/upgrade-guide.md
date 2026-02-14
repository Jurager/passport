# Upgrade Guide

This guide covers breaking changes and migration steps between major versions of Jurager/Passport.

## General Upgrade Steps

1. **Update composer dependency:**
   ```bash
   composer update jurager/passport
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate
   ```

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

4. **Publish updated configuration (optional):**
   ```bash
   php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider" --force
   ```

5. **Review configuration changes:**
   Compare your current `config/passport.php` with the published version

## Version-Specific Changes

### Upgrading to v2.x (Future)

> This section will be populated when v2.x is released.

**Breaking Changes:**
- TBD

**Migration Steps:**
- TBD

---

### Upgrading to v1.x (Current)

If you're starting fresh, you're already on v1.x. No migration needed.

**Key Features:**
- Server-broker SSO architecture
- Session management
- API token support
- IP geolocation
- Custom commands
- Multiple middleware options

---

## Laravel Version Compatibility

| Package Version | Laravel Version | PHP Version |
|----------------|-----------------|-------------|
| 1.x | 9.x, 10.x, 11.x, 12.x | >= 8.1 |

### Upgrading Laravel

When upgrading your Laravel application, follow these additional steps:

#### From Laravel 11 to Laravel 12

**Middleware registration changed:**

**Before (Laravel 11):**
```php
// app/Http/Kernel.php

protected $middlewareGroups = [
    'web' => [
        \Illuminate\Session\Middleware\StartSession::class,
        \Jurager\Passport\Http\Middleware\AttachBroker::class,
    ],
];

protected $routeMiddleware = [
    'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
];
```

**After (Laravel 12):**
```php
// bootstrap/app.php

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Jurager\Passport\Http\Middleware\AttachBroker::class
        ]);

        $middleware->alias([
            'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
        ]);
    });
```

See the [Installation](installation.md) guide for complete middleware configuration.

---

## Configuration Changes

### Environment Variables

If environment variables are renamed or added in new versions, update your `.env` file:

**Current environment variables:**

```env
# Server
PASSPORT_SERVER_DRIVER=model
PASSPORT_SERVER_MODEL=Jurager\Passport\Models\Broker
PASSPORT_SERVER_ID_FIELD=client_id
PASSPORT_SERVER_SECRET_FIELD=secret

# Broker
PASSPORT_BROKER_CLIENT_ID=
PASSPORT_BROKER_CLIENT_SECRET=
PASSPORT_BROKER_CLIENT_USERNAME=email
PASSPORT_BROKER_SERVER_URL=
PASSPORT_BROKER_AUTH_URL=
PASSPORT_BROKER_RETURN_URL=true
PASSPORT_BROKER_CLOUDFLARE=false

# Tables
PASSPORT_BROKERS_TABLE=brokers
PASSPORT_HISTORY_TABLE=history
PASSPORT_TOKENS_TABLE=access_tokens

# General
PASSPORT_DEBUG=false
PASSPORT_STORAGE_TTL=600
PASSPORT_ATTACH_THROTTLE=5
PASSPORT_MAX_REDIRECT_ATTEMPTS=3

# Routes
PASSPORT_ROUTES_PREFIX_CLIENT=sso/client
PASSPORT_ROUTES_PREFIX_SERVER=sso/server

# Security
PASSPORT_ALLOWED_REDIRECT_HOSTS=
```

---

## Database Migrations

### Running New Migrations

When upgrading, always run migrations:

```bash
php artisan migrate
```

### Rolling Back Migrations

If you need to rollback:

```bash
# Rollback last migration batch
php artisan migrate:rollback

# Rollback specific package migrations
php artisan migrate:rollback --path=vendor/jurager/passport/database/migrations
```

---

## API Changes

### Method Signature Changes

Check if any method signatures changed in new versions.

**Example (hypothetical):**

**Before:**
```php
$broker->login($credentials);
```

**After:**
```php
$broker->login($credentials, $request);
```

**Migration:**
```php
// Update your code to include $request parameter
$broker->login($credentials, request());
```

---

## Deprecated Features

Features that are deprecated but still functional:

> None currently. This section will be updated as features are deprecated.

**Using deprecated features:**
- Continue using them for now
- Plan to migrate before they're removed
- Check deprecation warnings in logs

---

## Breaking Changes Checklist

When upgrading to a new major version, check:

- [ ] Middleware configuration updated
- [ ] Environment variables updated
- [ ] Configuration file reviewed
- [ ] Migrations run successfully
- [ ] Cache cleared
- [ ] Tests passing
- [ ] Custom code updated for API changes
- [ ] Production deployment tested
- [ ] Rollback plan prepared

---

## Testing After Upgrade

### Test SSO Flow

1. **Test broker attach:**
   ```
   Visit: https://your-broker.com/sso/client/attach
   ```

2. **Test authentication:**
   ```
   Visit: https://your-broker.com/login
   Login with test credentials
   ```

3. **Test profile:**
   ```
   Visit any protected route
   Verify user is authenticated
   ```

4. **Test logout:**
   ```
   Logout from broker
   Verify session is terminated
   ```

### Test API Tokens

```php
$user = User::first();
$token = $user->createToken('test-token', 60);

// Test API request
$response = Http::withToken($token)->get('/api/user');
```

### Test Session Management

```php
$user = User::first();

// Test logout methods
$user->logoutById($historyId);
$user->logoutOthers();
$user->logoutAll();
```

---

## Rollback Plan

If the upgrade causes issues:

### 1. Rollback Composer

```bash
composer require jurager/passport:^1.0  # Specify previous version
```

### 2. Rollback Migrations

```bash
php artisan migrate:rollback
```

### 3. Restore Configuration

Restore your previous `config/passport.php` from version control.

### 4. Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
```

### 5. Test

Verify the application works with the previous version.

---

## Getting Help

If you encounter issues during upgrade:

1. **Check the changelog:** Review all changes in the new version
2. **Search issues:** Look for similar upgrade issues on GitHub
3. **Enable debug mode:** `PASSPORT_DEBUG=true`
4. **Check logs:** Review `storage/logs/laravel.log`
5. **Create an issue:** [GitHub Issues](https://github.com/jurager/passport/issues)

Include in your report:
- Current package version
- Target package version
- Laravel version
- PHP version
- Error messages
- Steps to reproduce

---

## Changelog

For a complete list of changes in each version, see the [CHANGELOG.md](../CHANGELOG.md) file in the package root.

