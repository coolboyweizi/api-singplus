<?php

namespace SingPlus\Domains\ClientSupports\Services;

use SingPlus\Contracts\ClientSupports\Services\VersionUpdateService as VersionUpdateServiceContract;
use SingPlus\Contracts\ClientSupports\Constants\AppName as AppNameConst;
use SingPlus\Domains\ClientSupports\Repositories\VersionUpdateRepository;
use SingPlus\Domains\ClientSupports\Repositories\VersionUpdateTipRepository;

class VersionUpdateService implements VersionUpdateServiceContract
{

  /**
   * @var VersionUpdateRepository
   */
  private $versionUpdateRepo;

  /**
   * @var VersionUpdateTipRepository
   */
  private $versionUpdateTipRepo;

  public function __construct(
    VersionUpdateRepository $versionUpdateRepo,
    VersionUpdateTipRepository $versionUpdateTipRepo
  ) {
    $this->versionUpdateRepo = $versionUpdateRepo;
    $this->versionUpdateTipRepo = $versionUpdateTipRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateStatus(string $version, string $appName) : \stdClass
  {
    $versionInfo = $this->versionUpdateRepo->findOne();
    if ( ! $versionInfo) {
        return (object) [
            'recommend'         => false,
            'force'             => false,
            'isInnerUpdateOn'   => false,
        ];
    }

    return (object) [
      'recommend'   => $versionInfo->isTriggerAlert($version),
      'force'       => $versionInfo->isTriggerForce($version),
      'isInnerUpdateOn' => $versionInfo->isInnerUpdateOn($appName),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestUpdateTips(string $version, string $appName) : ?\stdClass
  {
    $versionInfo = $this->versionUpdateRepo->findOne();

    if ( ! $versionInfo ||
         ( ! $versionInfo->isTriggerAlert($version) && ! $versionInfo->isTriggerForce($version))
    ) {
      return null;
    }

    $tips = $this->versionUpdateTipRepo->findOneByVersion($versionInfo->latest);
    $content = '';
    if ($tips) {
        $content = $versionInfo->isTriggerForce($version) ? $tips->content : $tips->alert_content;
    }

    return (object) [
      'version' => $versionInfo->latest,
      'url'     => $tips ? $tips->getUrl($appName) : '',
      'apkUri'  => $tips ? $tips->getApkUri($appName) : null,
      'tips'    => $content,
    ];
  }
}
