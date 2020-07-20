<?php

namespace SingPlus\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
    /*
        'SingPlus\Events\UserRegistered' => [
            'SingPlus\Listeners\UserRegistered\CompleteProfile',
        ],
            */
      'SingPlus\Events\UserImageUploaded' => [
        'SingPlus\Listeners\Users\RequestTailorUserImage',
      ],
      'SingPlus\Events\Feeds\FeedReaded' => [
        'SingPlus\Listeners\Feeds\SetUserFeedsRead',
      ],
      'SingPlus\Events\UserTriggerFavouriteWork'  => [
        'SingPlus\Listeners\Feeds\CreateWorkFavouriteFeed',
      ],
      'SingPlus\Events\UserCommentWork' => [
        'SingPlus\Listeners\Feeds\CreateWorkCommentFeed',
      ],
      'SingPlus\Events\Users\PushAliasBound' => [
        'SingPlus\Listeners\Users\RmNewActiveDeviceRecord',
      ],
      'SingPlus\Events\FeedCreated' => [
        'SingPlus\Listeners\Feeds\NotifyFeedToUser',
      ],
      \NotificationChannels\FCM\MessageWasSended::class => [
        'SingPlus\Listeners\Push\MessageWasSendedHandler',
      ],
      'SingPlus\Events\Friends\UserFollowed' => [
        'SingPlus\Listeners\Friends\NotifyFollowedToUser',
        'SingPlus\Listeners\Friends\UpdateUserFollowCount',
        'SingPlus\Listeners\Feeds\CreateUserFollowedFeed',
        'SingPlus\Listeners\Friends\NotifyAdminUserFollowAction',
      ],
      'SingPlus\Events\Friends\UserTriggerFollowed' => [
          'SingPlus\Listeners\Friends\UpdateUserFollowCount',
      ],
      'SingPlus\Events\Friends\UserUnfollowed' => [
        'SingPlus\Listeners\Friends\UpdateUserFollowCount',
      ],
      'SingPlus\Events\Friends\GetRecommendUserFollowingAction' => [
        'SingPlus\Listeners\Friends\NotifyAdminGenRecommendUserFollowing',
      ],
      'SingPlus\Events\Works\WorkPublished' => [
        'SingPlus\Listeners\Works\NotifyAdminWorkPublished',
        'SingPlus\Listeners\Works\ChorusJoinHandle',
        'SingPlus\Listeners\Users\AddWorkPublishedInAuthorProfile',
      ],
      'SingPlus\Events\Works\RankExpired' => [
        'SingPlus\Listeners\Works\NotifyAdminUpdateWorkRankingList',
      ],
      'SingPlus\Events\Works\WorkDeleted' => [
        'SingPlus\Listeners\Works\DoCleanAfterDelete',
      ],
      'Illuminate\Notifications\Events\NotificationSent' => [
        'SingPlus\Listeners\Notifications\LogNotificationSent',
      ],
      'SingPlus\Events\Startups\CommonInfoFetched' => [
        'SingPlus\Listeners\Startups\LogUserLocation',
      ],
      'SingPlus\Events\Gifts\UserSendGiftForWork' => [
            'SingPlus\Listeners\Feeds\CreateGiftSendForWorkFeed',
      ],
      'SingPlus\Events\Works\WorkUpdateTags' => [
          'SingPlus\Listeners\Works\UpdateWorkTags',
      ],
      'SingPlus\Events\Works\WorkUpdateCommentGiftCacheData' => [
          'SingPlus\Listeners\Works\UpdateWorkCommentGiftCacheData',
      ],
      'SingPlus\Events\Works\WorkUpdateUserFavouriteCacheData' => [
          'SingPlus\Listeners\Works\UpdateWorkUserFavouriteCacheData'
      ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
