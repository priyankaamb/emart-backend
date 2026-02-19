<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\auth\AuthController;
Route::get('/', function () {
    return view('welcome');
});
