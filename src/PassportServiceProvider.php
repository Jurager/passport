<?php

namespace Jurager\Passport;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\ServiceProvider;
use Jurager\Passport\Models\History;
use Jurager\Passport\Models\Token;

class PassportServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */

    /**
     * The middleware aliases.
     */
    protected array $routeMiddleware = [];

    /**
     * The middleware groups.
     */
    protected array $middlewareGroups = [
        'passport' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
        ],
    ];

    public function boot(): void
    {
        // Publish Config
        //
        $this->publishes([
            __DIR__.'/../config/passport.php' => config_path('passport.php'),
        ]);

        // Empty 'broker.client_id' indicates that we are working as server
        //
        $mode = ! config('passport.broker.client_id') ? 'server' : 'broker';

        // Load Migrations
        //
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/'.$mode);

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
        $this->loadRoutesFrom(__DIR__.'/../routes/passport.php');

        // Load Translations
        //
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'passport');

        // Schedule the commands
        //
        if ($this->app->runningInConsole()) {

            // Wait until the application booted
            //
            $this->app->booted(function () use ($mode) {

                // Create new schedule
                //
                $schedule = $this->app->make(Schedule::class);

                // Run prunable commands
                //
                $model = ($mode === 'broker') ? Token::class : History::class;

                // Run prunable model command
                //
                $schedule->command('model:prune', ['--model' => [$model]])->everyMinute();

            });
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passport.php', 'passport');
    }

    /**
     * Extend Laravel Auth.
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
     */
    protected function registerRouteMiddlewares(): void
    {
        foreach ($this->routeMiddleware as $alias => $middleware) {
            $this->app['router']->aliasMiddleware($alias, $middleware);
        }
    }

    /**
     * Register middleware groups.
     */
    protected function registerMiddlewareGroups(): void
    {
        foreach ($this->middlewareGroups as $group => $middlewares) {
            $this->app['router']->middlewareGroup($group, $middlewares);
        }
    }
}
