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

## Add Traits

Add the `Passport` trait to your User model.  
If you use API tokens, also add `HasTokens`.

```php
use Jurager\Passport\Traits\Passport;
use Jurager\Passport\Traits\HasTokens;

class User extends Authenticatable
{
    use Passport;
    use HasTokens;
}
```

## Publish Config (Optional)

```bash
php artisan vendor:publish --provider="Jurager\Passport\PassportServiceProvider"
```
