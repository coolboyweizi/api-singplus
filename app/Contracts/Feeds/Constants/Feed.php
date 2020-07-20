<?php

namespace SingPlus\Contracts\Feeds\Constants;

class Feed
{
  const TYPE_WORK_FAVOURITE         = 'work_favourite';
  const TYPE_WORK_FAVOURITE_CANCEL  = 'work_favourite_cancel';
  const TYPE_WORK_TRANSMIT          = 'work_transmit';
  const TYPE_WORK_COMMENT           = 'work_comment';
  const TYPE_WORK_COMMENT_DELETE    = 'work_comment_delete';
  const TYPE_WORK_CHORUS_JOIN       = 'work_chorus_join';
  const TYPE_USER_FOLLOWED          = 'user_followed';
  const TYPE_GIFT_SEND_FOR_WORK     = 'gift_send_for_work';

  const CHANNEL_FACEBOOK = 'facebook';
  const CHANNEL_WHATSAPP = 'whatsapp';
  const CHANNEL_INNER_SITE = 'innersite';

  static public $validChannels = [
    self::CHANNEL_FACEBOOK,
    self::CHANNEL_WHATSAPP,
    self::CHANNEL_INNER_SITE,
  ];
}
