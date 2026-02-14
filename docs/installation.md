# Installation

## Install

```bash
composer require jurager/passport
```

## Migrate

```bash
php artisan migrate
```

Creates tables for brokers, history, and API tokens.

## Register Middleware

The package needs two middleware registrations:

- `AttachBroker` in the `web` group (after `StartSession`).
- `ClientAuthenticate` as the `auth` alias.

### Laravel 12+

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Jurager\Passport\Http\Middleware\AttachBroker::class,
        ]);

        $middleware->alias([
            'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
        ]);
    });
```

### Laravel 11 and earlier

```php
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

## Publish Config (Optional)

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```