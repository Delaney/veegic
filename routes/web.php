<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth:sanctum', 'verified'])->get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::middleware(['auth'])->get('/dashboard', 'WebController@index');

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/videos', 'VideoController@index');
    Route::post('/upload', 'VideoController@upload');
    Route::get('/videos/{src}', 'VideoController@download');
});