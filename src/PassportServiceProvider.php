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
        'passport' => [
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
        //
        $this->extendAuthGuard();

        // Register Middlewares
        //
        $this->registerRouteMiddlewares();

        // Register Middleware Groups
        //
        $this->registerMiddlewareGroups();

        // Attach Routes
        //
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
        // Extending Guards
        //
        $this->app['auth']->extend('sso', function ($app, $name, array $config) {

            // Register new Guard
            //
            $guard = new PassportGuard($name, $app['auth']->createUserProvider($config['provider']), new ClientBrokerManager, $app['request']);

            // Set event dispatcher
            //
            if (method_exists($guard, 'setDispatcher')) {
                $guard->setDispatcher($this->app['events']);
            }

            // Update current request instance
            //
            if (method_exists($guard, 'setRequest')) {
                $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
            }

            // Return created Guard
            //
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
        foreach ($this->routeMiddleware as $alias => $middleware) {
            $this->app['router']->aliasMiddleware($alias, $middleware);
        }
    }

    /**
     * Register middleware groups.
     *
     * @return void
     */
    protected function registerMiddlewareGroups(): void
    {
        foreach ($this->middlewareGroups as $group => $middlewares) {
            $this->app['router']->middlewareGroup($group, $middlewares);
        }
    }
}
