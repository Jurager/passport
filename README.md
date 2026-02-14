# Jurager/Passport
[![Latest Stable Version](https://poser.pugx.org/jurager/passport/v/stable)](https://packagist.org/packages/jurager/passport)
[![Total Downloads](https://poser.pugx.org/jurager/passport/downloads)](https://packagist.org/packages/jurager/passport)
[![PHP Version Require](https://poser.pugx.org/jurager/passport/require/php)](https://packagist.org/packages/jurager/passport)
[![License](https://poser.pugx.org/jurager/passport/license)](https://packagist.org/packages/jurager/passport)

This Laravel package simplifies the implementation of single sign-on authentication. It features a centralized user repository and enables the creation of user models in brokers without disrupting app logic. Additionally, it provides methods for incorporating authentication history pages and terminating sessions for either all users or specific ones with ease

Documentation: see `docs/index.md`.

- [Requirements](#requirements)
- [Installation](#installation)
- [License](#license)

Requirements
-------------------------------------------
`PHP >= 8.1` and `Laravel 9.x or higher`

Installation
-------------------------------------------

```sh
composer require jurager/passport
```

Run migrations:

```sh
php artisan migrate
```

Register the middleware:


<details>
  <summary>Laravel 12 and newer</summary>
  <p>In Laravel 12, middleware is registered in `bootstrap/app.php` using the `withMiddleware` method.</p>

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
</details>

<details>
  <summary>Laravel 11 and earlier</summary>
  <p>If your application still uses the classic HTTP kernel `app/Http/Kernel.php`, register middleware there.</p>

Add middleware to the `web` group:

```php
protected $middlewareGroups = [
    'web' => [
        \Illuminate\Session\Middleware\StartSession::class,
        \Jurager\Passport\Http\Middleware\AttachBroker::class,
        // ...
     
    ],
];
```
   
Replace the `auth` middleware alias with the package's version:

```php
protected $routeMiddleware = [
    'auth' => \Jurager\Passport\Http\Middleware\ClientAuthenticate::class,
    // ...
];
```
</details>

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
