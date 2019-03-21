<?php

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
    return view('welcome');
});

//用户注册
Route::get('/userreg','User\UserController@reg');
Route::post('/userreg','User\UserController@doreg');

//用户登录
Route::get('/userlogin','User\UserController@login');
Route::post('/userlogin','User\UserController@dologin');

//个人中心
Route::get('/center','User\UserController@center');
//退出
Route::get('/quit','User\UserController@quit');
