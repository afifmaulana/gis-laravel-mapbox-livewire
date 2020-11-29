<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\MyTest;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/test', MyTest::class)->name('test');
