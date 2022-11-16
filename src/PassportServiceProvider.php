<?php

namespace Jurager\Passport;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
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

    protected bool $is_server;

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

    public function boot(): void
    {
        // Publish Config
        //
        $this->publishes([
            __DIR__ . '/../config/passport.php' => config_path('passport.php'),
        ]);

        // Package working mode
        //
        $this->is_server =  !config('passport.broker.client_id');

        // Only on server
        // Load Migrations
        //
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register event subscribers
        //
        Event::subscribe(\Jurager\Passport\Listeners\AuthEventSubscriber::class);

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
        $this->loadRoutesFrom(__DIR__ . '/routes/passport.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/passport.php', 'passport');
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
            $guard = new PassportGuard($name, $app['auth']->createUserProvider($config['provider']), new Broker, $app['request']);

            // Set event dispatcher
            //
            $guard->setDispatcher($this->app['events']);

            // Update current request instance
            //
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));

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
