<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/init-db', function () {
    Artisan::call('migrate:fresh --force');
    return "Database Migrated Successfully!";
});
