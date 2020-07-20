<?php

namespace SingPlus\Exceptions;

/**
 * global exception code definitions
 */
final class ExceptionCode
{
  /** 
   * Code for general exception. When a general exception is received, nothing more
   * can be done but show the error message.
   *
   * @var int
   */
  const GENERAL = 10000;
  const ARGUMENTS_VALIDATION = 10001;
  const VERIFICATION_INCORRECT = 10002;
  const VERIFICATION_FREQUENCE = 10003;
  const EXTERNAL_EXECUTION_ERROR = 10004;
  const SYNTAX_ERROR           = 10010;
  const VERSION_DEPRECATED     = 10020;

  //**********************
  //      User
  //**********************
  const USER_UNAUTHENTICATION             = 10101;
  const USER_EXISTS                       = 10102;
  const USER_NOT_EXISTS                   = 10103;
  const USER_PASSWORD_INCORRECT           = 10104;
  const USER_MOBILE_BOUND                 = 10105;
  const USER_MOBILE_NOT_BOUND             = 10106;
  const USER_IMAGE_UPLOAD_FAILED          = 10107;
  const USER_IMAGE_EXISTS                 = 10108;
  const USER_IMAGE_NOT_EXISTS             = 10109;
  const USER_IMAGE_OPERATE_FORBIDDEN      = 10110;
  const USER_IMAGE_TOO_MANY               = 10111;      // gallery image at max limitaion
  const USER_NEW                          = 10112;      // new user, should be complete profile
  const USER_NICKNAME_BE_USED_BY_OTHER    = 10113;      // nickname aready be used by other user
  const USER_SOCIALITE_NOT_EXISTS         = 10120;
  const USER_AUTHENTICATE_FAILED          = 10130;      // login failed

  //**********************
  //      Storages
  //**********************
  const STORAGE_COMMON                    = 10201;
  const STORAGE_FILE_NOT_EXISTS           = 10202;
  const STORAGE_FILE_EXISTS               = 10203;
  const STORAGE_LOCAL_FILE_NOT_EXISTS     = 10204;
  const STORAGE_PATH_ILLEGAL              = 10205;

  //**********************
  //      Music
  //**********************
  const MUSIC_NOT_EXISTS                  = 10301;
  const MUSIC_OUT_OF_STOCK                = 10302;
  const MUSIC_DATA_MISSED                 = 10305;
  const MUSIC_RECOMMEND_SHEET_NOT_EXIST   = 10315;

  //**********************
  //      Work
  //**********************
  const WORK_UPLOAD_FAILED                = 10401;
  const WORK_NOT_EXISTS                   = 10402;
  const WORK_AREADY_UPLOADED              = 10403;
  const WORK_UPLOAD_TASK_NOT_EXISTS       = 10405;
  const WORK_SHEET_NOT_EXISTS             = 10406;
  const WORK_COMMENT_NOT_EXISTS           = 10410;
  const WORK_COMMENT_ACTION_FORBIDDEN     = 10411;
  const WORK_ACTION_FORBIDDEN             = 10420;
  const WORK_CHORUS_ACCOMPANIMENT_PREPARE = 10430;
  const WORK_MODIFY_ACTION_FORBIDDEN      = 10440;
  const WORK_TAG_NOT_EXISTS               = 10450;
  const WORK_SET_PRIVATE_FORBIDDEN        = 10460;
  const WORK_UNAVAILABLE_WHEN_PRIVATE     = 10470;

  //**********************
  //      Feed
  //**********************
  const FEED_TRANSMIT_CHANNEL_INVALID     = 10501;

  //**********************
  //      ADMIN
  //**********************
  const ADMIN_TASKID_MISSED               = 90001;
  const ADMIN_TASKID_EXISTS               = 90002;

  //**********************
  //      News
  //**********************
  const NEWS_NOT_EXISTS                   = 10601;
  const NEWS_ACTION_FORBIDDEN             = 10620;
  const NEWS_TYPE_INVALID                 = 10602;
  const NEWS_CREATE_FREQUENCY             = 10603;


  //*********************
  //     AccountTrans
  //*********************
  const ACCOUNT_BALANCE_NOT_ENOUGH        = 10701;

  //*********************
  //    Orders
  //*********************
  const SKU_NOT_EXISTS                    = 10801;


  //*********************
  //     Gifts
  //*********************
  const GIFT_NOT_EXIST                  = 10901;

  //*********************
  //     Boomcoin
  //*********************
  const BOOMCOIN_USER_NOT_EXSITS        = 11001;
  const BOOMCOIN_BALANCE_NOT_ENOUGH     = 11002;
  const BOOMCOIN_GENERAL                = 11000;
  const BOOMCOIN_EXCHANGE_EXCEPTION     = 11003;
}
