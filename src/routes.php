<?php

use Illuminate\Support\Facades\Route;

Route::middleware('passport')->group(function() {

    if(config('passport.broker.client_id')) {

        // Passport Client Routes
        //
        Route::prefix(config('passport.routes_prefix_client'))->name('sso.client.')->group(function() {
            Route::get('attach', 'Jurager\Passport\Http\Controllers\ClientController@attach')->name('attach');
        });
    }

    // Not empty value of broker.client_id indicates that we are using passport as server
    //
    if(!config('passport.broker.client_id')) {

        // Passport Server Routes
        //
        Route::prefix(config('passport.routes_prefix_server'))->name('sso.server.')->group(function() {
            Route::get('attach', 'Jurager\Passport\Http\Controllers\ServerController@attach')->name('attach');

            Route::post('login', 'Jurager\Passport\Http\Controllers\ServerController@login')->name('login');
            Route::get('profile', 'Jurager\Passport\Http\Controllers\ServerController@profile')->name('profile');
            Route::post('logout', 'Jurager\Passport\Http\Controllers\ServerController@logout')->name('logout');
            Route::post('commands/{command}', 'Jurager\Passport\Http\Controllers\ServerController@commands')->name('command');
        });
    }
});
