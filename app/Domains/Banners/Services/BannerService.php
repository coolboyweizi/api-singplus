<?php

namespace SingPlus\Domains\Banners\Services;

use SingPlus\Contracts\Banners\Services\BannerService as BannerServiceContract;
use SingPlus\Domains\Banners\Repositories\BannerRepository;

class BannerService implements BannerServiceContract
{
  /**
   * @var BannerRepository
   */
  private $bannerRepo;

  public function __construct(BannerRepository $bannerRepo)
  {
    $this->bannerRepo = $bannerRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function listEffectiveBanners(?string $countryAbbr)
  {
    $banners = $this->bannerRepo->findAll($countryAbbr);

    return $banners->map(function ($banner, $_) {
      return (object) [
        'id'    => $banner->id,
        'image' => $banner->image,
        'type'  => $banner->type,
        'attributes'  => $banner->attributes ?: (object) [],
      ];
    });
  }
}
