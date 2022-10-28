<?php

namespace Jurager\Passport;

use Illuminate\Support\ServiceProvider;

class PassportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */

     /**
     * The middleware aliases.
     *
     * @var array
     */
    protected array $routeMiddleware = [];

    /**
     * The middleware groups.
     *
     * @var array
     */
    protected array $middlewareGroups = [
        'sso-api' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class
        ]
    ];

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/passport.php' => config_path('passport.php'),
        ]);

        // Add Guard
        $this->extendAuthGuard();

        // Register Middlewares
        $this->registerRouteMiddlewares();

        // Register Middleware Groups
        $this->registerMiddlewareGroups();

        // Attach Routes
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/passport.php', 'passport');
    }

    /**
     * Extend Laravel Auth.
     *
     * @return void
     */
    protected function extendAuthGuard(): void
    {
        $this->app['auth']->extend('sso', function ($app, $name, array $config) {
            $guard = new PassportGuard(
                $app['auth']->createUserProvider($config['provider']),
                new ClientBrokerManager,
                $app['request']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Register route middlewares.
     *
     * @return void
     */
    protected function registerRouteMiddlewares(): void
    {
        $router = $this->app['router'];

        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->routeMiddleware as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }

    /**
     * Register middleware groups.
     *
     * @return void
     */
    protected function registerMiddlewareGroups(): void
    {
        $router = $this->app['router'];

        foreach ($this->middlewareGroups as $group => $middlewares) {
            $router->middlewareGroup($group, $middlewares);
        }
    }
}
