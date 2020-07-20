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

Route::group(['prefix' => 'v3'], function () {
    if (in_array(config('app.env'), ['staging', 'testing'])) {
        Route::get('mobile/renew', 'UserController@renewMobile');
        Route::get('cache/reset', 'UserController@renewCache');
    }

    Route::get('nationalities', 'NationalityController@listAllCountries');

    Route::post('startup', 'StartupController@startup')->middleware('nation.operation', 'activity.check');

    Route::group(['prefix' => 'passport', 'namespace' => 'Auth'], function () {
        Route::post('login', 'LoginController@login');
        Route::get('logout', 'LoginController@logout');
        Route::post('register', 'RegisterController@register');
        Route::post('socialite/{provider}/login', 'SocialiteController@login')
            ->where([
                'provider' => 'facebook',
            ]);
        Route::post('tudc/login', 'TUDCController@login');
    });

    Route::group(['prefix' => 'user'], function () {
        Route::post('password/reset', 'UserController@resetPassword');
        Route::get('mobile-source', 'UserController@mobileUserSource');
    });

    Route::group(['prefix' => 'verification'], function () {
        Route::post('register/captcha', 'VerificationController@sendRegisterVerifyCode');
        Route::post('password-reset/captcha', 'VerificationController@sendPasswordResetVerifyCode');
    });

    Route::group(['prefix' => 'user'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('password/init', 'UserController@initLoginPassword');
            Route::post('password/update', 'UserController@changeLoginPassword');
            Route::post('info/update', 'UserController@modifyUserProfile');
            Route::post('info/complete', 'UserController@completeUserProfile');
            Route::post('info/auto-complete', 'UserController@autoCompleteUserInfo');
            Route::post('mobile/bind', 'UserController@bindMobile');
            Route::post('mobile/rebind', 'UserController@rebindMobile');
            Route::post('image/upload', 'ImageController@upload');
            Route::post('avatar/update', 'ImageController@setUserAvatar');
            Route::post('image/delete', 'ImageController@delete');
            Route::get('common-info', 'StartupController@getUserCommonInfo')->middleware('nation.operation', 'activity.check');
            Route::post('location', 'UserController@reportLocation');
            Route::get('recommend', 'FriendController@getRecommendUserFollowings');
            Route::post('info-multi', 'UserController@getUsersProfiles');
            Route::post('im-user-status', 'IMController@updateUserImStatus');
            Route::post('update-prefconf', 'UserController@updateUserPreferenceConf');
            Route::post('accompaniment/sync', 'SyncInfoController@accompanimentSync');
            Route::get('accompaniment/delete', 'SyncInfoController@accompanimentRemoveItem');
        });
        Route::get('info', 'UserController@getUserProfile');
        Route::get('image/gallery', 'ImageController@getGallery');
        Route::get('works', 'WorkController@getUserWorks');
        Route::get('{userId}/chorus-start-works', 'WorkController@getUserChorusStartWorks');
        Route::get('recommend/no-followed', 'FriendController@getRecommendWorksByCountry')->middleware('nation.operation');
    });

    Route::group(['prefix' => 'verification'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('mobile-bind/captcha', 'VerificationController@sendMobileBindVerifyCode');
            Route::post('mobile-unbind/captcha', 'VerificationController@sendMobileUnbindVerifyCode');
            Route::post('mobile-rebind/captcha', 'VerificationController@sendMobileRebindVerifyCode');
        });

    });
    Route::group(['prefix' => 'banners'], function () {
        Route::get('/', 'BannerController@listBanners')->middleware('nation.operation');
    });

    Route::group(['prefix' => 'musics'], function () {
        Route::get('recommends', 'MusicController@getRecommends')->middleware('nation.operation');
        Route::get('hots', 'MusicController@getHots')->middleware('nation.operation');
        Route::get('sheet/{sheetId}', 'MusicController@getRecommendMusicSheet');
        Route::get('categories', 'MusicController@getCategories');
        Route::get('/', 'MusicController@listMusics');
        Route::get('/search/suggest', 'MusicController@searchSuggest');
        Route::get('filter/style', 'MusicController@listMusicsByStyle');
        Route::get('tips', 'MusicController@getTips');
        Route::get('download', 'MusicController@getMusicDownloadAddress')->middleware('nation.operation');
        Route::get('{musicId}', 'MusicController@getMusicDetail');
        Route::get('{musicId}/chorus-start-work/existence', 'WorkController@musicChorusStartWorkExistence');
    });

    Route::group(['prefix' => 'works'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('2step/upload-task', 'WorkController@createTwoStepUploadTask');
            Route::post('2step/upload/{taskId}', 'WorkController@twoStepUpload')->middleware('nation.operation');
            Route::post('upload', 'WorkController@upload');
            Route::get('upload/status', 'WorkController@getWorkUploadStatus');
            Route::post('delete', 'WorkController@deleteWork');
            Route::post('comment', 'WorkController@commentWork');
            Route::post('comment/delete', 'WorkController@deleteWorkComment');
            Route::post('favourite', 'WorkController@favouriteWork');
            Route::post('modify-work', 'WorkController@modifyWorkInfo');
        });

        Route::post('{workId}/listen-count', 'WorkController@incrWorkListenCount');
        Route::get('selections', 'WorkController@getSelections')->middleware('nation.operation');
        Route::get('latest', 'WorkController@getLatests');
        Route::get('detail', 'WorkController@getDetail');
        Route::get('comments', 'WorkController@getComments');
        Route::get('favourites', 'WorkController@getWorkFavourites');
        Route::get('comments-favourites', 'WorkController@getMultiWorksCommentsAndFavourite');
        Route::get('{workId}/chorus-accompaniment', 'WorkController@getChorusStartAccompaniment');
        Route::get('chorus-start/{chorusStartWorkId}/chorus-joins', 'WorkController@getChorusJoinsOfChorusStart');
        Route::get('sheets/{sheetId}', 'WorkController@getRecommendWorkSheet');
        Route::get('ranks/global', 'WorkRankController@getGlobal');
        Route::get('ranks/country', 'WorkRankController@getCountry')->middleware('nation.operation');
        Route::get('ranks/new-personality', 'WorkRankController@getRookie');
        Route::get('comments-gifts', 'WorkController@getMultiWorksCommentsAndGifts');
        Route::get('search-tags', 'WorkTagController@searchWorkTags');
        Route::get('tag-info', 'WorkTagController@workTagDetail');
        Route::get('tag/{type}', 'WorkTagController@tagWorkList');
    });

    Route::group(['prefix' => 'messages'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::get('feeds/comments', 'FeedController@getUserCommentList');
            Route::get('feeds', 'FeedController@getNotificationList');
            Route::get('announcements', 'AnnouncementController@listAnnouncements')->middleware('nation.operation');
            Route::post('feeds/followed/read', 'FeedController@readUserFollowedFeed');
            Route::get('editor-recommends', 'NotificationController@getEditorRecommendList')->middleware('nation.operation');
            Route::get('comments', 'WorkController@getRelatedComments');
            Route::get('feeds/gifts', 'FeedController@getUserGiftForWorkList');
        });
        Route::post('transmit', 'FeedController@createWorkTransmitFeed');
    });

    Route::group(['prefix' => 'artists'], function () {
        Route::get('/', 'ArtistController@listArtists');
        Route::get('categories', 'ArtistController@getCategories')->middleware('nation.operation');
    });

    Route::group(['prefix' => 'help'], function () {
        Route::post('feedback', 'HelpController@commitFeedback')->middleware('nation.operation');
        Route::post('music/search/feedback', 'HelpController@commitMusicSearchFeedback')->middleware('nation.operation');
        Route::post('music/accompaniment/feedback', 'HelpController@commitAccompanimentFeedback')->middleware('nation.operation');
    });

    Route::group(['prefix' => 'notification'], function () {
        Route::post('user/push-alias', 'NotificationController@bindUserPushAlias')->middleware('auth');
    });

    Route::group(['prefix' => 'friends'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('follow', 'FriendController@follow');
            Route::post('unfollow', 'FriendController@unfollow');
            Route::get('users', 'FriendController@searchUsers');
            Route::get('followings/works/latest', 'FriendController@getFollowingLatestWorks');
            Route::get('socialite/{provider}/users', 'FriendController@getSocialiteUsersFriends')
                ->where([
                    'provider' => 'facebook',
                ]);
        });

        Route::get('followers', 'FriendController@getFollowers');
        Route::get('followings', 'FriendController@getFollowings');
    });

    Route::group(['prefix' => 'news'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('delete', 'NewsController@deleteNews');
            Route::post('create', 'NewsController@createNews')->middleware('news.throttle:1,10');
        });
        Route::get('latest', 'NewsController@getNewsList');
    });

    Route::group(['prefix' => 'pay'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::post('order/create', 'ChargeController@createOrder');
            Route::post('order/validate', 'ChargeController@validateOrder');
            Route::get('bill/list', 'CoinController@getBill');
        });
        Route::get('product/list', 'ChargeController@getSkus');
    });

    Route::group(['prefix' => 'dailyTask'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::get('list', 'DailyTaskController@getDailyTaskList')->middleware('nation.operation');
            Route::post('dailyTaskAward', 'DailyTaskController@getDailyTaskAward')->middleware('nation.operation');
            Route::post('dailyTaskInvite', 'DailyTaskController@finishedInviteDailyTask')->middleware('nation.operation');
        });
    });

    Route::group(['prefix' => 'gifts'], function(){

        Route::group(['middleware' => 'auth'], function (){
           Route::get('userWorkRank', 'GiftController@getWorkGiftRankForUser');
           Route::post('sendToWork', 'GiftController@sendGiftForWork')->middleware('nation.operation');
        });

        Route::get('lists', 'GiftController@getGiftList');
        Route::post('workRank', 'GiftController@getWorkGiftRank');
    });

    Route::group(['prefix' => 'hierarchy'], function(){
        Route::get('userHierarchyInfo', 'HierarchyController@userPopularityHierarchy');
        Route::get('userWealthInfo', 'HierarchyController@userWealthHierarchy');
        Route::get('wealthRank', 'HierarchyController@wealthRank');
    });

    Route::group(['prefix' => 'boomcoin'], function (){
        Route::group(['middleware' => 'auth'], function (){
            Route::get('products', 'BoomcoinController@getProductList');
            Route::post('exchange', 'BoomcoinController@exchangeCoins');
            Route::get('order/check', 'BoomcoinController@checkOrderStatus');
        });
    });
});

Route::group(['prefix' => 'v4'], function () {
    Route::group(['middleware' => 'auth'], function () {
        Route::group(['prefix' => 'messages'], function () {
            Route::get('feeds/comments', 'FeedController@getUserMixed');
        });
    });

    Route::group(['prefix' => 'friends'], function () {
        Route::group(['middleware' => 'auth'], function () {
            Route::get('followings/works/latest', 'FriendController@getFollowingLatestWorks_v4');
        });
        Route::get('followers', 'FriendController@getFollowers_v4');
        Route::get('followings', 'FriendController@getFollowings_v4');
    });

    Route::group(['prefix' => 'news'], function () {
        Route::get('latest', 'NewsController@getNewsList_v4');
    });
});

Route::group(['prefix' => 'c/page', 'namespace' => 'H5'], function () {
    Route::get('works/loadingPage', 'WorkController@getLoadingPage')->middleware('nation.operation');
    Route::get('works/{workdId}', 'WorkController@getUserWorkPage')->middleware('nation.operation');
});
