<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(SingPlus\Domains\Users\Models\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'mobile'          => '1' . $faker->numerify('##########'),
        'password'        => $password ?: $password = bcrypt('secret'),
        'remember_token'  => str_random(10),
        'push_alias'      => str_random(32),
        'source'          => 'mobile',
    ];
});

$factory->define(SingPlus\Domains\Users\Models\UserProfile::class, function (Faker\Generator $faker) {
  return [
    'user_id' => '36b6e806ffbe11e6a8d70800276e6868',
    'avatar'  => '28cd741cffbe11e6a0590800276e6868',
    'is_new'  => false,
  ];
});

$factory->define(SingPlus\Domains\Verifications\Models\Verification::class, function (Faker\Generator $faker) {
  return [
    'mobile'      => '1' . $faker->numerify('##########'),
    'code'        => '1234',
    'expired_at'  => strtotime('+120 seconds'),
  ]; 
});

$factory->define(SingPlus\Domains\Users\Models\UserImage::class, function (Faker\Generator $faker) {
  return [
    'user_id'     => '36b6e806ffbe11e6a8d70800276e6868',
    'uri' => str_random(10),
    'is_avatar'   => false,
  ];
});

$factory->define(SingPlus\Domains\Banners\Models\Banner::class, function (Faker\Generator $faker) {
  return [
    'image'       => 'ba3a7f4135344009b2603b222e05008b',
    'type'        => 'url',
    'attributes'  => (object) [
      'url' => 'http://www.163.com',
    ],
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\Artist::class, function (Faker\Generator $faker) {
  return [
    'name'    => str_random(5),
    'is_band' => false,
    'avatar'  => [
      'ad7145e4ea544535b7087c2869f03653',
      'ad7145e4ea544535b7087c2869f03653',
    ],
    'nationality' => 'ad7145e4ea544535b7087c2869f03653',
    'status'  => 1,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\Music::class, function (Faker\Generator $faker) {
  return [
    'name'          => str_random(5),
    'cover_images'  => [
      'ad7145e4ea544535b7087c2869f03653',
      'ad7145e4ea544535b7087c2869f03653',
    ],
    'artists'       => [
      '8467740cfc514c9280d572921b635636',
      '2cb2190893ae47568ed4bf92540fa4c4',
    ],
    'request_count' => 0,
    'lyrics'        => '8f477ee2b6fc43279883787d070fbaa0',
    'resource'      => (object) [
                          'raw' => [
                                  'uri'   => 'ad7145e4ea544535b7087c2869f03653',
                                  'size'  => 1234,
                                ],
                          'accompaniment' => [
                                  'uri'   => 'ad7145e4ea544535b7087c2869f03653',
                                  'size'  => 1234,
                                ],
    ],
    'status'  => 1,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\MusicRecommend::class, function (Faker\Generator $faker) {
  return [
    'music_id'      => '536071cc46774441a2a0d09847b308dd',
    'display_order' => 100,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\RecommendMusicSheet::class, function (Faker\Generator $faker) {
  return [
    'title' => 'zhangsan',
    'cover' => 'test cover',
    'request_count' => 123,
    'music_ids' => [],
    'status'    => 1,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\MusicHot::class, function (Faker\Generator $faker) {
  return [
    'music_id'      => '536071cc46774441a2a0d09847b308dd',
    'display_order' => 100,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\ArtistHot::class, function (Faker\Generator $faker) {
  return [
    'artist_id'     => '536071cc46774441a2a0d09847b308dd',
    'display_order' => 100,
  ];
});

$factory->define(SingPlus\Domains\Nationalities\Models\Nationality::class, function (Faker\Generator $faker) {
  return [
    'code'      => '254',
    'name'      => 'Kenya',
    'flag'      => '',
    'flag_uri'  => 'test-bucket/xxxxxxxxxxxxxxxxxxxx',
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\Language::class, function (Faker\Generator $faker) {
  return [
    'name'          => 'Swahili',
    'cover_image'   => 'images/language/xxxxxxx',
    'total_number'  => 1234,
    'status'        => 1,
  ];
});

$factory->define(SingPlus\Domains\Musics\Models\Style::class, function (Faker\Generator $faker) {
  return [
    'name'          => 'hip-hop',
    'cover_image'   => 'images/language/xxxxxxx',
    'total_number'  => 1234,
    'status'        => 1,
  ];
});

$factory->define(SingPlus\Domains\Works\Models\Work::class, function (Faker\Generator $faker) {
  return [
    'user_id'       => '36b6e806ffbe11e6a8d70800276e6868',
    'music_id'      => 'c1d4d4e2412d440dae180c4611998625',
    'resource'      => 'test-bucket/241633b65b1d427b8d30f0b090874edc',
    'duration'      => 300,
    'cover'         => '3a72e8254da9473cb76636350fd906c3',
    'slides'        => [],
    'description'   => 'hello world!',
    'listens'       => [],
    'listen_count'  => 0,
    'comment_count' => 0,
    'favourites'    => [],
    'favourite_count' => 0,
    'transmits'     => [],
    'transmit_count'  => 0,
    'status'        => 1,
  ];
});

$factory->define(SingPlus\Domains\Works\Models\WorkSelection::class, function (Faker\Generator $faker) {
  return [
    'work_id' => 'b87c8f2086644a21a81a22f6358e0433',
    'status'  => 1,
  ];
});

$factory->define(SingPlus\Domains\Works\Models\H5WorkSelection::class, function (Faker\Generator $faker) {
  return [
    'work_id' => 'b87c8f2086644a21a81a22f6358e0433',
    'status'  => 1,
  ];
});

$factory->define(SingPlus\Domains\Works\Models\Comment::class, function (Faker\Generator $faker) {
  return [
    'work_id'     => 'b87c8f2086644a21a81a22f6358e0433',
    'comment_id'  => null,
    'content'     => 'Good job',
    'author_id'   => '503ef5a34d2149168cd021008b19f8d1',
    'replied_user_id' => '0b80478f18ef45d588e0c29f27976f42',
    'at_users'   => [
      '19df72a94e344cd4b31f2929d08a583b',
    ],
    'status'      => 1,
  ];
});

$factory->define(SingPlus\Domains\Announcements\Models\Announcement::class, function (Faker\Generator $faker) {
  return [
    'title'       => 'Good morning',
    'cover'       => 'announcement-cover',
    'summary'     => 'Good morning, everyone, welcome to Kenya',
    'type'        => \SingPlus\Contracts\Announcements\Constants\Announcement::TYPE_URL,
    'attributes'  => [
                        'url' => 'http://www.163.com',
                      ],
    'status'      => 1,
    'display_order' => 100,
  ];
});

$factory->define(SingPlus\Domains\Users\Models\SocialiteUser::class, function (Faker\Generator $faker) {
  return [
    'user_id'           => '145f73cbc4c24a0195f60b06ee51bce4',
    'provider'          => 'facebook',
    'channels'          => [],
  ];
});

$factory->define(SingPlus\Domains\Works\Models\WorkFavourite::class, function (Faker\Generator $faker) {
  return [
    'user_id' => '145f73cbc4c24a0195f60b06ee51bce4',
    'work_id' => '0107f6c80d6142a1a63c0cb016127285',
  ];
});

$factory->define(SingPlus\Domains\Works\Models\WorkUploadTask::class, function (Faker\Generator $faker) {
  return [
    'user_id'       => '36b6e806ffbe11e6a8d70800276e6868',
    'music_id'      => 'c1d4d4e2412d440dae180c4611998625',
    'duration'      => 300,
    'cover'         => '3a72e8254da9473cb76636350fd906c3',
    'slides'        => [],
    'description'   => 'hello world!',
  ];
});

$factory->define(SingPlus\Domains\ClientSupports\Models\VersionUpdate::class, function (Faker\Generator $faker) {
  return [
    'latest'  => '0.0.0',
    'force'   => '0.0.0',
  ];
});

$factory->define(SingPlus\Domains\ClientSupports\Models\VersionUpdateTip::class, function (Faker\Generator $faker) {
  return [
    'version'   => '0.0.0',
    'content'   => 'one two three',
  ];
});

$factory->define(SingPlus\Domains\Feeds\Models\Feed::class, function (Faker\Generator $faker) {
  return [
    'operator_user_id'  => '145f73cbc4c24a0195f60b06ee51bce4',
    'work_id'           => '0107f6c80d6142a1a63c0cb016127285',
    'type'              => 'favourite',
    'detail'            => [],
    'status'            => 1,
  ];
});

$factory->define(SingPlus\Domains\Friends\Models\UserFollowing::class, function (Faker\Generator $faker) {
  return [
    'user_id'           => '36b6e806ffbe11e6a8d70800276e6868',
    'followings'        => [],
    'following_details' => [],
  ];
});

$factory->define(SingPlus\Domains\Works\Models\MusicWorkRankingList::class, function (Faker\Generator $faker) {
  return [
    'music_id'        => '36b6e806ffbe11e6a8d70800276e6868',
    'work_id'         => '0107f6c80d6142a1a63c0cb016127285',
    'type'            => SingPlus\Domains\Works\Models\MusicWorkRankingList::TYPE_SOLO,
    'status'          => 1,
  ];
});

$factory->define(SingPlus\Domains\Ads\Models\Advertisement::class, function (Faker\Generator $faker) {
  return [
    'type'        => 'startup',
    'image'       => 'xxxxxxxxxxx',
    'need_login'  => 0,
    'status'      => 1,
  ];
});

$factory->define(SingPlus\Domains\ClientSupports\Models\NewActiveDeviceInfo::class, function (Faker\Generator $faker) {
  return [
    'alias'           => 'xxxxxxxxxxxxxxxxx',
    'mobile'          => null,
    'abbreviation'    => null,
    'country_code'    => null,
    'latitude'        => null,
    'longitude'       => null,
    'client_version'  => 'v1.2.0',
  ];
});

$factory->define(SingPlus\Domains\Works\Models\RecommendWorkSheet::class, function (Faker\Generator $faker) {
  return [
    'title'         => 'work sheet',
    'cover'         => 'sheet',
    'comments'      => 'Hello!',
    'works_ids'     => [],
    'request_count' => 0,
    'status'        => 1,
  ];
});

$factory->define(SingPlus\Domains\Admins\Models\AdminTask::class, function (Faker\Generator $faker) {
  return [
    'task_id' => '15fc9ce4561f4cbb973043a5e942c401',
    'data'    => [],
  ];
});

$factory->define(SingPlus\Domains\Users\Models\TUDCUser::class, function (Faker\Generator $faker) {
  return [
    'user_id'   => str_random(32),
    'channels'  => [],
  ];
});

$factory->define(SingPlus\Domains\Works\Models\WorkRank::class, function (Faker\Generator $faker) {
  return [
    'user_id'       => str_random(32),
    'country_abbr'  => 'CN',
    'is_global'     => 0,
    'is_new_comer'  => 0,
    'rank'          => 1,
    'status'        => 1,
  ];
});

$factory->define(SingPlus\Domains\Friends\Models\UserFollowingRecommend::class, function (Faker\Generator $faker) {
  return [
    'user_id'           => str_random(32),
    'following_user_id' => str_random(32),
    'is_auto_recommend' => 1,
    'status'            => 1,
  ];
});

$factory->define(SingPlus\Domains\Friends\Models\UserRecommend::class, function (Faker\Generator $faker) {
  return [
    'orig_uuid'         => str_random(32),
    'user_id'           => str_random(32),
    'works_ids'         => [],
    'country_abbr'      => 'TZ',
    'is_auto_recommend' => 1,
    'status'            => 1,
  ];
});

$factory->define(SingPlus\Domains\Friends\Models\UserUnfollowed::class, function (Faker\Generator $faker) {
    return [
        'user_id'           => '36b6e806ffbe11e6a8d70800276e6868',
        'unfollowed'        => [],
        'unfollowed_details' => [],
    ];
});

$factory->define(SingPlus\Domains\News\Models\News::class, function (Faker\Generator $faker) {
    return [
        'user_id'           => str_random(32),
        'detail'            => null,
        'status'            => 1,
        'display_order' => 100,
    ];
});

$factory->define(SingPlus\Domains\DailyTask\Models\DailyTask::class, function (Faker\Generator $faker) {
    return [
        'user_id'           => str_random(32),
        'status'            => 1,
        'finished_status'   => 1,
        'days'              => 1,
    ];
});

$factory->define(SingPlus\Domains\Gifts\Models\Gift::class, function (Faker\Generator $faker) {
    return [
        'icon'            => [
            "icon_small" => "xxxx.png",
            "icon_big"   => "xxxx.png"
        ],
        'coins' => 10,
        'status' => 1,
        "popularity"  => 20,
        "animation"   => [
            "url" => "xxxx.gift",
            "type" => 1,
            "duration" => 1,
        ],
        'sold_amount' => 0,
        'sold_coin_amount' => 0,
    ];
});

$factory->define(SingPlus\Domains\Gifts\Models\GiftContribution::class, function (Faker\Generator $faker) {
    return [
        'gift_ids' => [],
        'gift_detail' => [],
    ];
});

$factory->define(SingPlus\Domains\Gifts\Models\GiftHistory::class, function (Faker\Generator $faker) {
    return [
        'gift_info' => [],
        'gift_ids' => [],
        'gift_detail' => [],
        'display_order' => 100,
        'amount'  => 1
    ];
});

$factory->define(SingPlus\Domains\DailyTask\Models\DailyTaskHistory::class, function (Faker\Generator $faker) {
    return [
        'user_id'           => str_random(32),
        'finished_status'   => 1,
        'days'              => 1,
    ];
});


$factory->define(SingPlus\Domains\DailyTask\Models\Task::class, function (Faker\Generator $faker) {
    return [
        'status'           => 1,
    ];
});
$factory->define(SingPlus\Domains\Orders\Models\ChargeOrder::class, function (Faker\Generator $faker) {
    return [
        'user_id'           => str_random(32),
        'pay_order_id'      => null,
        'amount'            => 100000000,
        'sku_count'         => 1,
        'pay_order_details' => [
            'currency_amount'   => 600000000,
            'currency_code'     => 'CHY',
        ],
        'status'            => 1,
        'status_histories'  => [
            [
                'status'    => 1,
                'time'      => \Carbon\Carbon::now()->timestamp,
            ],
        ],
        'sku'               => [
            'sku_id'        => str_random(16),
            'price'         => 100000000,
            'coins'         => 100,
            'title'         => '100金币',
        ],
    ];
});

$factory->define(SingPlus\Domains\Orders\Models\CoinSku::class, function (Faker\Generator $faker) {
    return [
        'sku_id'    => str_random(32),
        'coins'     => 100,
        'title'     => '100金币',
        'price'     => 100000000,
        'status'    => 1,
    ];
});

$factory->define(SingPlus\Domains\Coins\Models\CoinTransaction::class, function (Faker\Generator $faker) {
    return [
        'user_id'   => str_random(32),
        'operator'  => str_random(32),
        'amount'    => 100,
        'source'    => 1,
        'details'   => [
            'order_id'  => str_random(32),
        ],
        'display_order' => 100,
    ];
});

$factory->define(SingPlus\Domains\Hierarchy\Models\WealthRank::class, function (Faker\Generator $faker){
   return [
       'user_id'   => str_random(32),
       'display_order' => 100,
   ];
});

$factory->define(SingPlus\Domains\Hierarchy\Models\Hierarchy::class, function (Faker\Generator $faker){
    return [
        'icon'   => 'icon.jpg',
        'icon_small' => 'icon.jpg',
    ];
});

$factory->define(SingPlus\Domains\Sync\Models\SyncInfo::class, function (Faker\Generator $faker){
    return [
        'type' => 'accompaniment'
    ];
});

$factory->define(SingPlus\Domains\Works\Models\WorkTag::class, function (Faker\Generator $faker){
   return [
       'source'   => 'user',
       'status'   => 1
   ];
});

$factory->define(SingPlus\Domains\Works\Models\TagWorkSelection::class, function (Faker\Generator $faker) {
    return [
        'work_id' => 'b87c8f2086644a21a81a22f6358e0433',
        'status'  => 1,
    ];
});

$factory->define(SingPlus\Domains\Boomcoin\Models\Product::class, function(Faker\Generator $faker){
    return [
      'status' => 1
    ];
});

$factory->define(SingPlus\Domains\Boomcoin\Models\Order::class, function (Faker\Generator $faker){
   return [
       'display_order' => 100
   ] ;
});

$factory->define(SingPlus\Domains\Users\Models\UserVerification::class, function (Faker\Generator $faker) {
    return [
        'profile_id'    => str_random(32),
        'user_id'       => str_random(32),
        'verified_as'   => ['A', 'B'],
        'status'        => 1,
    ];
});
