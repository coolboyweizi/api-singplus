<?php

namespace SingPlus\Domains\ClientSupports\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Contracts\ClientSupports\Constants\AppName as AppNameConst;

class VersionUpdate extends MongodbModel
{
  const INNER_UPDATE_OFF = 0;
  const INNER_UPDATE_ON = 1;

  protected $collection = 'version_updates';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'latest',
    'force',        //强制更新版本区间，空字符串表示未设置。 [min, max]，包含关系
    'alert',        //提示更新版本区间，空字符串表示未设置. [min, max], 包含关系
    'inner_update', // int, 0: 应用市场更新，1: 内部更新
    'boomsing_inner_update',    // int. for boomsing
  ];

  //==============================
  //        Logic
  //==============================
  public function isTriggerForce(string $version) : bool
  {
    if ( ! object_get($this, 'latest') || version_compare($version, $this->latest, '>=')) {
        return false;
    }

    $force = object_get($this, 'force');
    $min = '0.0.0';
    $max = '0.0.0';
    if ($force) {
        if (is_string($force)) {
            $max = $force;
        } elseif (is_array($force)) {
            list($minVal, $maxVal) = $force;
            $min = $minVal ?: $min;
            $max = $maxVal ?: $this->latest;
        }
    }

    return version_compare($version, $max, '<=') &&
           version_compare($version, $min, '>=');
  }

  public function isTriggerAlert(string $version) : bool  
  {
    if ( ! object_get($this, 'latest') || version_compare($version, $this->latest, '>=')) {
        return false;
    }

    $alert = object_get($this, 'alert');
    $min = '0.0.0';
    $max = '0.0.0';
    if ($alert) {
        if (is_string($alert)) {
            $max = $alert;
        } elseif (is_array($alert)) {
            list($minVal, $maxVal) = $alert;
            $min = $minVal ?: $min;
            $max = $maxVal ?: $this->latest;
        }
    }

    return version_compare($version, $max, '<=') &&
           version_compare($version, $min, '>=');
  }

  public function isInnerUpdateOn(?string $appName) : bool
  {
    switch ($appName) {
        case AppNameConst::NAME_SINGPLUS :    
            $innerUpdate = object_get($this, 'inner_update');
            break;
        case AppNameConst::NAME_BOOMSING :
            $innerUpdate = object_get($this, 'boomsing_inner_update');
            break;
        default :
            $innerUpdate = object_get($this, 'inner_update');
    }
    return $innerUpdate == self::INNER_UPDATE_ON;
  }
}
