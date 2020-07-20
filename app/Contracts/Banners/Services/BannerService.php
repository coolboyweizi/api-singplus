<?php

namespace SingPlus\Contracts\Banners\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Banners\Contracts\ModelConstant;

interface BannerService
{
  /**
   * List all effective banners
   *
   * @param ?string $countryAbbr
   *
   * @return Collection         elements are banner (\stdClass)
   *                            - id string     banner id
   *                            - image string  image storage key
   *                            - type string   see ModelConstant::TYPE_XXXX
   *                            - attributes object
   *                              - url string    only exists on type equals to
   *                                              ModelConstant::TYPE_URL
   *                              - action string only exists on type equals to
   *                                              ModelConstant::TYPE_NATIVE
   *                  
   */
  public function listEffectiveBanners(?string $countryAbbr);
}
