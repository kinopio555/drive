<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/is-login', fn() => Auth::check())
    ->name('is-login');

Route::get('/get-user', fn() => Auth::User())
    ->middleware('auth')->name('get-user');


require __DIR__.'/auth.php';
