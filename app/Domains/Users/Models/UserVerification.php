<?php

namespace SingPlus\Domains\Users\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class UserVerification extends MongodbModel
{
    const STATUS_NEW = 0;
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = -1;
    const STATUS_DELETED = -2;

    protected $collection = 'user_verification';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'profile_id',
        'user_id',
        'verified_as',     // array
        'status',
    ]; 
}
