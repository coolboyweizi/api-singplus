<?php

return [
  'signature'   => env('ADMIN_SIGNATURE'),
  'endpoints'    => [
    'work_published_notification'         => env('ADMIN_INTERFACE_NOTIFY_WORK_PUBLISH'),
    'work_ranking_update_notification'    => env('ADMIN_INTERFACE_NOTIFY_WORK_RANKING_UPDATE'),
    'friend_gen_recommend_user_following' => env('ADMIN_INTERFACE_NOTIFY_FRIEND_GEN_RECOMMEND_USER_FOLLOWING'),
    'friend_user_followed_others'         => env('ADMIN_INTERFACE_NOTIFY_FRIEND_USER_FOLLOWED_OTHERS'),
  ],
];
