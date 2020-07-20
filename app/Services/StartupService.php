<?php

namespace SingPlus\Services;

use LogLocation;
use Location;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Feeds\Services\FeedService as FeedServiceContract;
use SingPlus\Contracts\ClientSupports\Services\VersionUpdateService as VersionUpdateServiceContract;
use SingPlus\Contracts\PayPal\Services\PayPalConfigService as PayPalConfigServiceContract;
use SingPlus\Contracts\ClientSupports\Services\DeviceService as DeviceServiceContract;
use SingPlus\Contracts\ClientSupports\Constants\AppName as AppNameConst;
use SingPlus\Contracts\Ads\Services\AdService as AdServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Notifications\Constants\Notification as NotificationConstant;
use SingPlus\Events\Startups\CommonInfoFetched as CommonInfoFetchedEvent;

class StartupService
{
  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var FeedService
   */
  private $feedService;

  /**
   * @var VersionUpdateServiceContract
   */
  private $versionUpdateService;

    /**
     * @var PayPalConfigServiceContract
     */
    private $payPalConfigService;

  /**
   * @var DeviceServiceContract
   */
  private $deviceService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var AdServiceContract
   */
  private $adService;

  /**
   * @var Request;
   */
  private $request;

  public function __construct(
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    FeedServiceContract $feedService,
    VersionUpdateServiceContract $versionUpdateService,
    PayPalConfigServiceContract $payPalConfigService,
    DeviceServiceContract $deviceService,
    StorageServiceContract $storageService,
    AdServiceContract $adService,
    Request $request
  ) {
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->versionUpdateService = $versionUpdateService;
    $this->payPalConfigService = $payPalConfigService;
    $this->deviceService = $deviceService;
    $this->feedService = $feedService;
    $this->storageService = $storageService;
    $this->adService = $adService;
    $this->request = $request;
  }

  /**
   * Startup
   *
   * @param ?string $userId
   * @param ?string $version
   * @param ?\stdClass $deviceInfo
   *                      - alias string      notification token, identify a device
   *                      - mobile ?string
   *                      - countryCode ?string
   *                      - abbreviation ?string      country short name
   *                      - latitude ?string
   *                      - longitude ?string
   *
   * @return \stdClass          properties as below:
   *                            - logged bool
   *                            - user ?\stdClass
   *                              - isNewUser bool
   *                            - ads Collection
   *                              - adId string       ad id
   *                              - title string
   *                              - type string
   *                              - needLogin bool
   *                              - image string      image url
   *                              - link ?string
   *                              - startTime \Carbon\Carbon
   *                              - stopTime \Carbon\Carbon
   */
  public function startup(
    ?string $userId,
    ?string $version,
    ?\stdClass $deviceInfo,
    ?string $appName
  ) : \stdClass {
    $appName = in_array($appName, [AppNameConst::NAME_SINGPLUS, AppNameConst::NAME_BOOMSING])
                ? $appName : AppNameConst::$defaultName;

    $appChannel = config('tudc.currentChannel');
    $out = (object) [
      'logged'  => $userId ? true : false,
      'user'    => null,
    ];
    if ($userId) {
      $isNewUser = $this->userProfileService->isNewUser($userId);
      $out->user = (object) [
                      'isNewUser' => $isNewUser,
                    ];
    }

    // update info
    $updateInfo = (object) [
      'isInnerUpdateOn' => false,
      'recommendUpdate' => false,
      'forceUpdate'     => false,
      'updateTips'      => null,
    ];
    if ($version) {
      $updateStatus = $this->versionUpdateService->getUpdateStatus($version, $appName);
      $updateInfo->isInnerUpdateOn = $updateStatus->isInnerUpdateOn;
      $updateInfo->recommendUpdate = $updateStatus->recommend;
      $updateInfo->forceUpdate = $updateStatus->force;
      $updateInfo->updateTips = $this->versionUpdateService->getLatestUpdateTips($version, $appName);
      if ($updateInfo->updateTips) {
        // process apk uri
        $updateInfo->updateTips->apkUrl = $this->storageService->toHttpUrl($updateInfo->updateTips->apkUri) ?: '';
      }
    }
    $out->update = $updateInfo;

    $payPalInfo = (object)[
        'isOpen' => false,
        'url' => null,
    ];
    $payPalStatus = $this->payPalConfigService->getPayPalStatus($appName);
    $payPalInfo->isOpen=$payPalStatus->isOpen;
    $payPalInfo->url=$payPalStatus->url;
    $out->payPal = $payPalInfo;

    // record new active device info (用户下载了app，但未成功登录过一次)
    if ( ! $userId && $deviceInfo) {
      // check whether alias exist in users collection
      // 已经被用户绑定过的device，不再记录
      if ( ! $this->userService->isPushAliasBound($appChannel, $deviceInfo->alias)) {
        // todo
        // 临时屏蔽非singplus渠道的数据
        if ($appChannel == 'singplus') {
          $this->deviceService->saveNewActiveDeviceInfos(
            $deviceInfo->alias,
            $deviceInfo->mobile,
            $deviceInfo->abbreviation,
            $deviceInfo->countryCode,
            $deviceInfo->latitude,
            $deviceInfo->longitude,
            $version
          );
        }
      }
    }

    // get ads
    $countryAbbr = $this->request->headers->get('X-CountryAbbr');
    $out->ads = $this->adService
                ->getAds($countryAbbr)
                ->map(function ($ad, $_) {
                  array_walk($ad->specImages, function (&$image, $_) {
                    $image = $this->storageService->toHttpUrl($image);
                  });
                  return (object) [
                    'adId'        => $ad->adId,
                    'title'       => $ad->title,
                    'type'        => $ad->type,
                    'needLogin'   => $ad->needLogin,
                    'image'       => $this->storageService->toHttpUrl($ad->image),
                    'specImages'  => $ad->specImages,
                    'link'        => $ad->link,
                    'startTime'   => $ad->startTime,
                    'stopTime'    => $ad->stopTime,
                  ];
                });

    return $out;
  }

  /**
   * Get user common info
   *
   * @param string $userId
   * @param ?string $abbreviation
   *
   * @return \stdClass        properties as below:
   *                          - feedCounts \stdClass
   *                            - workFavourite int
   *                            - workTransmit int
   *                            - workComment int
   *                            - followed int
   */
  public function getUserCommonInfo(string $userId, ?string $abbreviation) : \stdClass
  {
    $feedCounts = $this->feedService->getUserFeedCounts($userId);

    event(new CommonInfoFetchedEvent($userId, (object) [
      'ip'  => $this->request->ip(),
    ]));

    $bindTopics = [
      NotificationConstant::TOPIC_ALL,
    ];
    if ($abbreviation) {
      $bindTopics[] = NotificationConstant::countryTopic($abbreviation);
    }

    return (object) [
      'feedCounts'  => (object) [
        'workFavourite' => (int) object_get($feedCounts, 'workFavourite'), 
        'workTransmit' => (int) object_get($feedCounts, 'workTransmit'), 
        'workComment' => (int) object_get($feedCounts, 'workComment'), 
        'followed'    => (int) object_get($feedCounts, 'followed'),
        'workChorusJoin'  => (int) object_get($feedCounts, 'workChorusJoin'),
        'giftSendForWork'  => (int)object_get($feedCounts, 'giftSendForWork'),
      ],
      'bindTopics'  => $bindTopics,
    ];
  }

  /**
   * log user ip and location
   */
  public function logUserIpLocation(string $userId, ?string $ip)
  {
    $reportUserLocation = $this->userProfileService->getUserLocation($userId);     
    $reportAbbr = object_get($reportUserLocation, 'abbreviation');
    $location = Location::get($ip);
    $abbr = object_get($location, 'countryCode');

    $message = sprintf('ip: %s, abbr: %s, reportAbbr: %s', $ip, $abbr, $reportAbbr);

    LogLocation::info($message, [
      'location'  => (array) $location,
      'report'    => (array) $reportUserLocation,
    ]);
  }
}
