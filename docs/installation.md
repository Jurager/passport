# Installation

You may install Passport using Composer, run migrations, and register middleware.

## Install

```bash
composer require jurager/passport
```

## Migrate

```bash
php artisan migrate
```

This creates tables for brokers, history, and API tokens.

> [!NOTE]
> Migrations are loaded based on `PASSPORT_BROKER_CLIENT_ID`. Keep it empty on the server, set it on brokers.

## Register Middleware

Passport requires two middleware registrations:

- `AttachBroker` in the `web` group.
- `ClientAuthenticate` as the `auth` alias.

> [!WARNING]
> `AttachBroker` must run after `StartSession`. If sessions do not persist, the broker will never attach.

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

## Add Traits

Add the `Passport` trait to your User model. If you use API tokens, also add `HasTokens`.

```php
use Jurager\Passport\Traits\Passport;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use Passport;
    use HasTokens;
}
```

> [!NOTE]
> Use your existing User model. In most Laravel apps it extends `Illuminate\Foundation\Auth\User`.
