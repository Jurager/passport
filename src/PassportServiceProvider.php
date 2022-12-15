<?php

namespace Jurager\Passport;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Jurager\Passport\Console\Commands\Prune;

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
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class
        ],
    ];

    public function boot(): void
    {
        // Publish Config
        //
        $this->publishes([
            __DIR__ . '/../config/passport.php' => config_path('passport.php'),
        ]);

        // Only on server
        // Load Migrations
        //
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

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
        $this->loadRoutesFrom(__DIR__ . '/../routes/passport.php');

        // Load Translations
        //
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'passport');

        // Schedule the commands
        //
        if ($this->app->runningInConsole()) {

            // Register command
            //
            $this->commands([ Prune::class]);

            // Wait until the application booted
            //
            $this->app->booted(function () {

                // Create new schedule
                //
                $schedule = $this->app->make(Schedule::class);

                // Run prunable command
                //
                //$schedule->command('history:prune')->everyMinute();
            });
        }
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
            $guard = new PassportGuard($name, $app['auth']->createUserProvider($config['provider']), new Broker($app['request']), $app['request']);

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
