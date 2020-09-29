<?php

use Illuminate\Http\Request;


Route::group(['prefix' => 'user', 'namespace' => 'User'], function ($router) {

    Route::post('login', 'AuthController@login');
    Route::post('register', 'AuthController@register');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('/', 'AuthController@me');

});
