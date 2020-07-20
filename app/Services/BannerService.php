<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Banners\Services\BannerService as BannerServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;

class BannerService
{
  /**
   * @var BannerServiceContract
   */
  private $bannerService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  public function __construct(
    BannerServiceContract $bannerService,
    StorageServiceContract $storageService
  ) {
    $this->bannerService = $bannerService;
    $this->storageService = $storageService;
  }

  /**
   * List effective banners
   *
   * @param ?string $countryAbbr
   *
   * @return Collection         elements are banner (\stdClass)
   *                            - id string     banner id
   *                            - image string  image url
   *                            - type string   see ModelConstant::TYPE_XXXX
   *                            - attributes object
   *                              - url string    only exists on type equals to
   *                                              ModelConstant::TYPE_URL
   *                              - action string only exists on type equals to
   *                                              ModelConstant::TYPE_NATIVE
   */
  public function listEffectiveBanners(?string $countryAbbr) : Collection
  {
    $banners = $this->bannerService->listEffectiveBanners($countryAbbr);

    $self = $this;
    return $banners->map(function ($banner, $_) use ($self) {
      $banner->image = $self->storageService->toHttpUrl($banner->image);
      return $banner;
    });
  }
}
