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
    Route::get('/reload/{id}/{playerId}', 'GameController@reload');
    Route::get('/reload/{id}', 'GameController@reload');
    Route::get('/remove/{id}', 'GameController@delete');
    Route::get('/timeout/{id}', 'GameController@timeout');
    Route::get('/changeMap/{id}', 'GameController@changeMap');
    Route::get('/createNewMap', 'EditorController@createNewMap');
    Route::get('/editor/{mapId}', 'EditorController@editor');
    Route::get('/removeMap/{mapId}', 'EditorController@removeMap');
    Route::get('/resetMap/{mapId}', 'EditorController@resetMap');
    Route::post('/saveMap/{mapId}', 'EditorController@saveMap');
    Route::get('/create', 'GameController@create');
    Route::get('/createAndStart', 'GameController@createAndStart');
    Route::post('/update/{id}', 'GameController@update');
    Route::post('/renew/{id}', 'GameController@renew');
    Route::get('/start/{id}', 'GameController@start');
    Route::get('messages/{id}', 'ChatsController@fetchMessages');
    Route::post('message/{id}', 'ChatsController@sendMessage');
});
