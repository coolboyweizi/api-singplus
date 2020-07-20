<?php

namespace SingPlus\Domains\ClientSupports\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Contracts\ClientSupports\Constants\AppName as AppNameConst;

class VersionUpdateTip extends MongodbModel
{
  protected $collection = 'version_update_tips';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'version',
    'url',                  // 后台配置市场下载地址
    'apk_url',              // 后台上传apk时，系统自动生成的apk storage key
    'boomsing_url',
    'boomsing_apk_url',
    'content',
    'alert_content',        // 提示更新摘要信息
  ];

  /*********************************
   *        Accessor 
   ********************************/
  public function getContentAttribute($value)
  {
    return $this->translateField($value);
  }

  public function getAlertContentAttribute($value)
  {
    return $this->translateField($value);
  }

  public function getApkUri(string $appName) : ?string
  {
    switch ($appName) {
        case AppNameConst::NAME_SINGPLUS :
            $uri = object_get($this, 'apk_url');
            break;
        case AppNameConst::NAME_BOOMSING :
            $uri = object_get($this, 'boomsing_apk_url');
            break;
        default :
            $uri = object_get($this, 'apk_url');
    }

    return $uri;
  }

  public function getUrl(string $appName) : string
  {
    switch ($appName) {
        case AppNameConst::NAME_SINGPLUS :
            $url = object_get($this, 'url', '');
            break;
        case AppNameConst::NAME_BOOMSING :
            $url = object_get($this, 'boomsing_url', '');
            break;
        default :
            $url = object_get($this, 'url', '');
    }

    return $url;
  }
}
