<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Jurager\Passport\Http\Controllers\BrokerController;
use Jurager\Passport\Http\Controllers\ServerController;

Route::middleware('passport')->group(function() {

    if(config('passport.broker.client_id')) {

        // Passport Broker Routes
        //
        Route::controller(BrokerController::class)
            ->prefix(config('passport.routes_prefix_client'))
            ->name('sso.broker.')
            ->group(function() {
                Route::get('attach', 'attach')->name('attach');
            });
    }

    // Not empty value of broker.client_id indicates that we are using passport as server
    //
    if(!config('passport.broker.client_id')) {

        // Passport Server Routes
        //
        Route::controller(ServerController::class)
            ->prefix(config('passport.routes_prefix_server'))
            ->name('sso.server.')
            ->group(function() {
                Route::get('attach', 'attach')->name('attach');
                Route::post('login', 'login')->name('login');
                Route::get('profile', 'profile')->name('profile');
                Route::post('logout', 'logout')->name('logout');
                Route::post('commands/{command}', 'commands')->name('command');
            });
    }
});
