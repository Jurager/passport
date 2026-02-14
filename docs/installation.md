# Installation

## Composer Installation

Install the package via Composer:

```bash
composer require jurager/passport
```

## Running Migrations

The package includes migrations for brokers, session history, and access tokens. Run the migrations:

```bash
php artisan migrate
```

This will create three tables:
- `brokers` - Stores registered broker applications
- `history` - Stores user session history
- `access_tokens` - Stores API tokens

## Middleware Registration

The package requires middleware registration. The process differs between Laravel versions.

### Laravel 12 and Newer

In Laravel 12+, middleware is registered in `bootstrap/app.php` using the `withMiddleware` method:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        // ...
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        // Prepend session start and broker attach to the 'web' group
        $middleware->web(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Jurager\Passport\Http\Middleware\AttachBroker::class
        ]);

        // Replace the default auth alias with package's version
        $middleware->alias([
            'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        // ...
    });
```

### Laravel 11 and Earlier

In Laravel 11 and earlier, middleware is registered in `app/Http/Kernel.php`:

**Add middleware to the `web` group:**

```php
protected $middlewareGroups = [
    'web' => [
        \Illuminate\Session\Middleware\StartSession::class,
        \Jurager\Passport\Http\Middleware\AttachBroker::class,
        // ...
    ],
];
```

**Replace the `auth` middleware alias:**

```php
protected $routeMiddleware = [
    'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
    // ...
];
```

## Publishing Configuration (Optional)

To customize the package configuration, publish the config file:

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```

This creates a `config/passport.php` file with all available options.

## Important Notes

### AttachBroker Middleware

The `AttachBroker` middleware must be added to the `web` middleware group and should come **after** `StartSession`. This middleware:

- Automatically attaches the broker to the server on first request
- Prevents redirect loops with built-in throttling
- Handles session token management

### ClientAuthenticate Middleware

The `ClientAuthenticate` middleware **replaces** Laravel's default `auth` middleware. It provides:

- SSO-based authentication
- Bearer token authentication support
- Seamless integration with Laravel's Auth facade

### Server vs Broker Setup

After installation, you need to configure your application as either:

- **SSO Server** - The central authentication server ([Server Setup Guide](server-setup.md))
- **SSO Broker** - A client application ([Broker Setup Guide](broker-setup.md))

An application can function as both, but typically you'll have one server and multiple brokers.

## Verification

To verify the installation was successful:

1. Check that migrations ran:
   ```bash
   php artisan migrate:status
   ```

2. Check that the config file exists (if published):
   ```bash
   ls config/passport.php
   ```

3. Check that middleware is registered:
   - Laravel 12+: Review `bootstrap/app.php`
   - Laravel 11-: Review `app/Http/Kernel.php`
