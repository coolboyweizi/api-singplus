<?php

namespace SingPlus\Domains\Users\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Contracts\Users\Models\UserProfile as UserProfileContract;
class UserProfile extends MongodbModel implements UserProfileContract
{
  const STATUS_NORMAL = 1;
  const PREF_FOLLOWED = 'notify_followed';
  const PREF_FAVOURITE = 'notify_favourite';
  const PREF_COMMENT = 'notify_comment';
  const PREF_GIFT = 'notify_gift';
  const PREF_IM_MSG = 'notify_im_msg';
  const PREF_UNFOLLOWED_MSG = 'privacy_unfollowed_msg';

  protected $collection = 'user_profiles';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id', 'nickname', 'gender', 'birth_date', 'signature',
    'follower_count', 'following_count', 'listener_count', 'status',
    'avatar', 'is_new', 'worker_listen_count','work_count',
    'location',   // array, key as below:
                  //        - longitude string
                  //        - latitude string
                  //        - country_code (optional) int
                  //        - modified_at string    last modified time, format as Y-m-d H:i:s
                  //        - modified_by_user  bool    true or false
                  //        - abbreviation  string
                  //        - city 用户手动设置上报
                  //        - country_name 用户手动设置上报
    'last_visit_at', 'client_version',
    'im_sync',      //  - IntField(default=0) im_sync.verbose_name = _("IM同步?")
    'last_dailytask_at',   // last dailytask time format Y-m-d H:i:s
    'popularity_info',      // (dict 用户和人气相关的信息
                            // - work_popularity    int
                            // - hierarchy_id     string
                            // - hierarchy_gap      int

    'coins',         // dict properties as below:
                    //      - balance int
                    //      - gift_consume_amount int
    'consume_coins_info',     // dict
                        // - consume_coins      int  the amount of consume coins
                        // - hierarchy_id     string  the wealth hierarchy name
                        // - hierarchy_gap      int    the gap coins to next wealth hierarchy
    'statistics_info',  // dict
                        //  latest_work_pub_at  string
                        //  latest_work_id      string
                        //  work_chorus_start_count  int
    'preferences_conf',     //dict
                            // - notify_followed    bool   true: on   false: off
                            // - notify_recommend
                            // - notify_favourite
                            // - notify_comment
                            // - notify_gift
                            // - notify_im_msg
                            // - privacy_unfollowed_msg
  ];

  protected $cast = [
    'is_new' => 'boolean',
  ];

  //=======================================
  //      implements contract method
  //=======================================
  public function getUserId() : string
  {
    return $this->user_id;
  }

  public function getNickname() : ?string
  {
    return $this->nickname;
  }

  public function getGender() : ?string
  {
    return $this->gender;
  }

  public function getSignature() : ?string
  {
    return $this->signature;
  }

  public function getBirthDate() : ?string
  {
    return $this->birth_date;
  }

  public function countFollowers() : int
  {
    return $this->follower_count ?: 0;
  }

  public function countFollowings() : int
  {
    return $this->following_count ?: 0;
  }
}
