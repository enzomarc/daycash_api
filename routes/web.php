<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'api'], function () {
	Route::get('/', function () {
		return "DayCash API v1.0.0";
	});
	
	Route::post('login', 'AuthController@login');
	Route::get('logout/{token}', 'AuthController@logout');
	Route::get('check', 'AuthController@check');
	
	Route::get('users', 'UserController@index');
	Route::get('users/{user}', 'UserController@show');
	Route::post('users', 'UserController@store');
	Route::put('users/{user}', 'UserController@update');
	Route::delete('users/{user}', 'UserController@destroy');
	Route::get('users/{phone}/exists', 'UserController@exists');
	
	Route::get('bets', 'BetController@index');
	Route::get('bets/{bet}', 'BetController@show');
	Route::post('bets', 'BetController@store');
	Route::put('bets/{bet}', 'BetController@update');
	Route::delete('bets/{bet}', 'BetController@destroy');
	Route::get('users/{user}/bets', 'BetController@bets');
	Route::get('percents', 'BetController@percents');
});
