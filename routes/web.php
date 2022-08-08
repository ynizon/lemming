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

Route::get('/', function () {
    return redirect('/home');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/whoplay/{id}', 'GameController@whoplay');
    Route::get('/join/{id}', 'GameController@join');
    Route::get('/game/{id}', 'GameController@game');
    Route::get('/game/{id}/removePlayer/{playerId}', 'GameController@removePlayer');
    Route::get('/replay/{id}', 'GameController@replay');
    Route::get('/remove/{id}', 'GameController@delete');
    Route::get('/create', 'GameController@create');
    Route::get('/createAndStart', 'GameController@createAndStart');
    Route::post('/update/{id}', 'GameController@update');
    Route::post('/renew/{id}', 'GameController@renew');
    Route::get('/start/{id}', 'GameController@start');
    Route::get('messages/{id}', 'ChatsController@fetchMessages');
    Route::post('message/{id}', 'ChatsController@sendMessage');
});
