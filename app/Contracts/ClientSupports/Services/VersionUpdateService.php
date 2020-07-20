<?php

namespace SingPlus\Contracts\ClientSupports\Services;

interface VersionUpdateService
{
  /**
   * Get updated status
   *
   * @param string $version     client version number
   * @param string $appName     app name, referer to \SingPlus\Contracts\ClientSupports\Contracts\AppName
   *
   * @return \stdClass        properties as below:
   *                          - recommend bool
   *                          - force     bool
   *                          - isInnerUpdateOn bool        是否使用apk下载更新
   */
  public function getUpdateStatus(string $version, string $appName) : \stdClass;

  /**
   * Get latest update tips
   *
   * @param string $version     client version number
   * @parram string $appName   please to see: SingPlus\Contracts\ClientSupports\Constants
   *
   * @return ?\stdClass        properties as below:
   *                          - version string
   *                          - tips    string
   *                          - url     string
   *                          - apkUri  string
   */
  public function getLatestUpdateTips(string $version, string $appName) : ?\stdClass;
}
