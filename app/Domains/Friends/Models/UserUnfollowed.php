<?php

namespace SingPlus\Domains\Friends\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Friends\Models\Friend;

class UserUnfollowed extends MongodbModel
{
    protected $collection = 'user_unfollowed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'unfollowed',             // array, elements are followed user id
        'unfollowed_details',      // array, elements are \stdClass
        //          - user_id string
        //          - unfollow_at timestamp
        'display_order',
    ];

    static function buildUnFollowedItem(string $userId) : \stdClass
    {
        return (object) [
            'user_id'   => $userId,
            'unfollow_at' => time(),
        ];
    }
}
