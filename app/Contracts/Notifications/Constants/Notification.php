<?php

namespace SingPlus\Contracts\Notifications\Constants;

class Notification
{
  // 新评论通知/回复评论通知
  const TYPE_WORK_COMMENT             = 'work_comment';
  // 新赞通知
  const TYPE_WORK_FAVOURITE           = 'work_favourite';
  // 新转发
  const TYPE_WORK_TRANSMIT            = 'work_transmit';
  // 新合唱通知
  const TYPE_WORK_CHORUS_JOIN         = 'work_chorus_join';
  // 新关注通知
  const TYPE_FRIEND_FOLLOW            = 'friend_follow';
  // 未注册用户激活通知
  const TYPE_USER_UNREGISTER_ACTIVE   = 'user_unregister_active';
  // 新注册用户次日通知
  const TYPE_USER_NEW_NEXTDAY         = 'user_new_nextday';
  // 新注册用户七日通知
  const TYPE_USER_NEW_7DAY            = 'user_new_7day';
  // 新注册用户30通知
  const TYPE_USER_NEW_30DAY           = 'user_new_30day';
  // 转化用户
  const TYPE_USER_NEW_CONVERSION      = 'user_new_conversion';
  // 激活用户通知（首次）
  const TYPE_USER_ACTIVE_1ST          = 'user_active_1st';
  // 激活用户通知(唱歌)
  const TYPE_USER_ACTIVE_SING         = 'user_active_sing';
  // 激活用户通知(听歌)
  const TYPE_USER_ACTIVE_LISTEN       = 'user_active_listen';
  // 新礼物通知（作品收到新的礼物）
  const TYPE_GIFT_SEND_FOR_WORK       = 'gift_send_for_work';
  // 新私信通知 (用户收到IM消息）
  const TYPE_PRIVATE_MSG              = 'user_new_private_msg';

  // 公告通知
  const TOPIC_TYPE_ANNOUNCEMENT       = 'topic_announcement';
  // 活动通知
  const TOPIC_TYPE_ACTIVITY           = 'topic_activity';
  // #CoverOfTheDay
  const TOPIC_TYPE_COVER_OF_DAY       = 'topic_cover_of_day';
  // #NewSongAlert
  const TOPIC_TYPE_NEW_SONG           = 'topic_new_song';

  const TOPIC_ALL = 'topic_all_client';

  public static function countryTopic(?string $countryShortName)
  {
    $countryShortName = $countryShortName ? strtolower($countryShortName) : 'other';
    return sprintf('topic_country_%s', $countryShortName);
  }

  public static function isTopic(string $alias) : bool
  {
    return starts_with($alias, 'topic_');
  }

  public static function isTopicForIM(string $alias) : bool
  {
      return starts_with($alias, 'topic_');
  }

  public static function isTypeForIM(string $alias) : bool
  {
      return $alias == Notification::TYPE_USER_NEW_7DAY
          || $alias == Notification::TYPE_USER_NEW_CONVERSION
          || $alias == Notification::TYPE_USER_ACTIVE_1ST;
  }

  public static function isTopicCountryOther(string $topicName):bool
  {
      $operationAbbr = config('nationality.operation_country_abbr');

      if (starts_with($topicName,'topic_country_')){
          $abbr = strtoupper(substr($topicName, 14));
          if (in_array($abbr, $operationAbbr)){
              return false;
          }else {
              return true;
          }
      }else {
          return false;
      }
  }

  public static function getPushTitle():string{
      $appChannle = config('apiChannel.channel', 'singplus');
      $titles = [
          'singplus' => 'Sing+',
          'boomsing' => 'Boomsing',
          'gaaoplus' => 'Gaao+'
      ];
      return array_get($titles, $appChannle, 'Sing+');
  }

}
