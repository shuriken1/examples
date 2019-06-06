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

/*
// Generic resource
Route::get('link', 'LinksController@index')->name('link.index');
Route::post('link', 'LinksController@store')->name('link.store');
Route::get('link/create', 'LinksController@create')->name('link.create');
Route::delete('link/{id}', 'LinksController@destroy')->name('link.destroy');
Route::get('link/{id}', 'LinksController@show')->name('link.show');
Route::match(['put', 'patch'], 'link/{id}', 'LinksController@update')->name('link.update');
Route::get('link/{id}/edit', 'LinksController@edit')->name('link.edit');
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/user/{id}', 'UsersController@index')->name('user.show')->middleware('auth');
Route::get('/user/{id}/edit', 'UsersController@edit')->name('user.edit')->middleware('auth');
Route::match(['put', 'patch'], '/user/{id}', 'UsersController@update')->name('user.update')->middleware('auth');

Route::get('/home', 'HomeController@index')->name('home');

//Route::resource('food', 'FoodsController');

Route::get('/food', 'FoodsController@index')->name('food.index');
Route::post('/food', 'FoodsController@store')->name('food.store')->middleware('auth');
Route::get('/food/create', 'FoodsController@create')->name('food.create')->middleware('auth');
Route::delete('/food/{id}', 'FoodsController@destroy')->name('food.destroy')->middleware('auth');
Route::get('/food/{id}', 'FoodsController@show')->name('food.show');
Route::match(['put', 'patch'], '/food/{id}', 'FoodsController@update')->name('food.update')->middleware('auth');
Route::get('/food/{id}/edit', 'FoodsController@edit')->name('food.edit')->middleware('auth');
Route::get('food/{id}/check', 'FoodsController@check')->name('food.check');
//Route::get('food/{id}/manage', 'FoodsController@manage')->name('food.manage')->middleware('auth');
Route::get('food/{id}/links', 'FoodsController@links')->name('food.links')->middleware('auth');

Route::get('/link', 'LinksController@index')->name('link.index');
Route::post('/link', 'LinksController@store')->name('link.store')->middleware('auth');
Route::post('/link/create', 'LinksController@create')->name('link.create')->middleware('auth');
Route::delete('/link/{id}', 'LinksController@destroy')->name('link.destroy')->middleware('auth');
Route::get('/link/{id}', 'LinksController@show')->name('link.show');
Route::get('/l/{id}', 'LinksController@show')->name('link.show.short');
Route::match(['put', 'patch'], '/link/{id}', 'LinksController@update')->name('link.update')->middleware('auth');
Route::match(['get', 'post'],'/link/{id}/edit', 'LinksController@edit')->name('link.edit')->middleware('auth');

Route::get('/link/{id}/qr', 'LinksController@viewQR')->name('link.viewqr');

// Menus

//Route::get('/org', 'OrganisationsController@index')->name('org.index');
//Route::post('/org', 'OrganisationsController@store')->name('org.store')->middleware('auth');
//Route::post('/org/create', 'OrganisationsController@create')->name('org.create')->middleware('auth');
Route::delete('/org/{id}', 'OrganisationsController@destroy')->name('org.destroy')->middleware('auth');
Route::get('/org/{id}', 'OrganisationsController@show')->name('org.show')->middleware('auth');
Route::get('/org/{id}/foods', 'OrganisationsController@showFoods')->name('org.foods')->middleware('auth');
Route::get('/org/{id}/menus', 'OrganisationsController@showMenus')->name('org.menus')->middleware('auth');
Route::get('/org/{id}/users', 'OrganisationsController@showUsers')->name('org.users')->middleware('auth');
Route::match(['put', 'patch'], '/org/{id}', 'OrganisationsController@update')->name('org.update')->middleware('auth');
Route::get('/org/{id}/edit', 'OrganisationsController@edit')->name('org.edit')->middleware('auth');