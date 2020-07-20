<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['namespace' => 'Api'], function () {
  Route::group(['middleware' => 'api.sign'], function () {
    Route::post('notification/announcement', 'NotificationController@notifyAnnouncementCreated');

    Route::group(['prefix' => 'users'], function () {
      Route::post('synthetic-users', 'UserController@createSyntheticUser');
    });

    Route::group(['prefix' => 'works'], function () {
      Route::post('{workId}/comment', 'WorkController@syntheticUserCommentWork');
      Route::post('{workId}/favourite', 'WorkController@syntheticUserFavouriteWork');
    });
  });

  Route::group(['middleware' => ['api.sign:json', 'api.taskid']], function () {
    Route::post('notification/push-messages', 'NotificationController@pushMessages');
    Route::post('coins/trans', 'CoinController@makeTrans');
    Route::post('notification/notify-private-msg', 'NotificationController@pushPrivateMsgNotify');
  });
});
