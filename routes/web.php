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

//$router->get('/mail/{token}', function () {return view('index');});
//$router->get('/', function () {return view('reactapp',['v'=>'3.1']);});
//$router->get('/mail/{token}', function () {return view('reactapp',['v'=>'3.1']);});
//$router->get('/reset/{token}', function () {return view('reactapp',['v'=>'3.1']);});
//$router->get('/contact', function () {return view('reactapp',['v'=>'3.1']);});

$router->group(['prefix' => 'react_ajax'], function () use ($router) {
	//$router->get('user', 'Controller@user');
	$router->post('login', 'Controller@login');
	//$router->get('logout', 'Controller@logout');
	$router->get('versions', 'Controller@versions');
	$router->post('register', 'Controller@register');
	$router->post('forgot', 'Controller@forgot');
	$router->post('reset', 'Controller@reset');
	$router->post('contact', 'Controller@contact');
	$router->post('mail', 'Controller@mail');
	$router->post('help', 'Controller@help');
	$router->post('get_file', 'Controller@get_file');
});

$router->group(['middleware' => 'auth','prefix' => 'react_ajax'], function () use ($router) {
	$router->post('password', 'Controller@password');
	$router->post('user', 'Controller@user');
	$router->post('photo', 'Controller@photo');
	$router->get('logout', 'Controller@logout');
	$router->get('data', 'Controller@data');
	$router->get('data2', 'Controller@data');
	$router->post('log_event', 'Controller@log_event');
	$router->post('students', 'Controller@students');
	$router->post('saveQ', 'Controller@saveQ');
	$router->post('saveHelp', 'Controller@saveHelp');
	$router->post('marking', 'Controller@marking');
	$router->post('tutor', 'Controller@tutor');
	$router->post('update', 'Controller@update');
	$router->post('challenge', 'Controller@challenge');
	$router->post('save', 'Controller@save');
	$router->post('update_data', 'Controller@update_data');
	$router->post('past', 'Controller@past');
	$router->post('book', 'Controller@book');
	$router->get('users', 'Controller@users');
});