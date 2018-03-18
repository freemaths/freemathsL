<?php

Route::post('react_ajax/marking', 'ReactController@ajax_marking');

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

$router->get('/', function () {return view('reactapp');});
$router->get('/mail/{token}', function () {return view('reactapp');});
$router->get('/reset/{token}', function () {return view('reactapp');});

$router->group(['prefix' => 'react_ajax'], function () use ($router) {
	$router->get('user', 'Controller@user');
	$router->post('login', 'Controller@login');
	$router->post('password', 'Controller@password');
	$router->get('logout', 'Controller@logout');
	$router->post('register', 'Controller@register');
	$router->post('forgot', 'Controller@forgot');
	$router->post('reset', 'Controller@reset');
	$router->post('contact', 'Controller@contact');
	$router->post('mail', 'Controller@mail');
	$router->post('help', 'Controller@help');
});

$router->group(['middleware' => 'auth','prefix' => 'react_ajax'], function () use ($router) {
	$router->get('data', 'Controller@data');
	$router->post('log_event', 'Controller@log_event');
	$router->post('students', 'Controller@students');
	$router->post('saveQ', 'Controller@saveQ');
	$router->post('saveHelp', 'Controller@saveHelp');
	$router->post('marking', 'Controller@marking');
	$router->post('tutor', 'Controller@tutor');
	$router->post('update', 'Controller@update');
	$router->post('update_data', 'Controller@update_data');
});