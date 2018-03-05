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


// Route::get('/holidays', 'HolidaysController@index');
// Route::get('/holidays/{country}/{year}', 'HolidaysController@index')->where('year', '[0-9]+');

// Route::prefix('holidays.')->group(function () {
//     Route::get('/', 'App\Http\Controllers\HolidaysController@index');
// });

Route::group(['prefix' => 'holidays'], function (){
    Route::get('/', 'HolidaysController@index');
    Route::get('{country}/{year}', 'HolidaysController@index')->where(['country' => '[A-Za-z]+', 'year' => '[0-9]+']);
});