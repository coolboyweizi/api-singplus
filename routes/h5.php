<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register h5 routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "h5" middleware group. Now create something great!
|
*/

Route::group(['namespace' => 'H5'], function () {
  Route::group(['prefix' => 'works'], function() {
    Route::get('/{workId}', 'WorkController@getUserWork')->middleware('nation.operation');
    Route::get('/{workdId}/comments', 'WorkController@getComments');
  });

  Route::group(['prefix' => 'help'], function () {
    Route::post('feedback', 'HelpController@commitFeedback')->middleware('nation.operation');
  });
});
