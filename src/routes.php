<?php

/**
 * SSo Routes
 */
Route::middleware('sso-api')->group(function() {
    Route::prefix('sso/server')->name('sso.server.')->group(function() {
        Route::get('attach', 'Jurager\Passport\Http\Controllers\ServerController@attach')->name('attach');

        Route::post('login', 'Jurager\Passport\Http\Controllers\ServerController@login')->name('login');
        Route::get('profile', 'Jurager\Passport\Http\Controllers\ServerController@profile')->name('profile');
        Route::post('logout', 'Jurager\Passport\Http\Controllers\ServerController@logout')->name('logout');
        Route::post('commands/{command}', 'Jurager\Passport\Http\Controllers\ServerController@commands')->name('command');
    });

    Route::prefix('sso/client')->name('sso.client.')->group(function() {
        Route::get('attach', 'Jurager\Passport\Http\Controllers\ClientController@attach')->name('attach');
    });
});
