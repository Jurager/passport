<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Jurager\Passport\Http\Controllers\ServerController;

Route::middleware('passport')->group(function() {

    // Not empty value of broker.client_id indicates that we are using passport as server
    //
    if(!Config::get('passport.broker.client_id')) {

        // Passport Server Routes
        //
        Route::controller(ServerController::class)
            ->prefix(Config::get('passport.routes_prefix_server'))
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
