<?php

namespace SingPlus\Contracts\Ads\Services;

use Illuminate\Support\Collection;

interface AdService
{
  /**
   * Get all validate ads
   *
   * @param ?string $countryAbbr
   *
   * @return Collection   elements as below
   *                      - adId string       ad id
   *                      - title string
   *                      - type string
   *                      - needLogin bool
   *                      - image string      image uri
   *                      - specImages array  key is specification, value is storage_key
   *                      - link ?string
   *                      - startTime Carbon\Carbon
   *                      - stopTime Carbon\Carbon
   */
  public function getAds(?string $countryAbbr) : Collection;
}
