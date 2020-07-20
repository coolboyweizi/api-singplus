<?php

namespace SingPlus\Domains\Ads\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Ads\Services\AdService as AdServiceContract;
use SingPlus\Domains\Ads\Repositories\AdRepository;

class AdService implements AdServiceContract
{
  /**
   * @var AdRepository
   */
  private $adRepo;

  public function __construct(
    AdRepository $adRepo
  ) {
    $this->adRepo = $adRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getAds(?string $countryAbbr) : Collection
  {
    return $this->adRepo
                ->getAll($countryAbbr)
                ->map(function ($ad, $_) {
                  $specImages = is_array(object_get($ad, 'spec_images'))
                                  ? $ad->spec_images : [];
                  return (object) [
                    'adId'        => $ad->id,
                    'title'       => (string) $ad->title,
                    'type'        => $ad->type,
                    'needLogin'   => $ad->needLogin(),
                    'image'       => object_get($ad, 'image'),
                    'specImages'  => $specImages,
                    'link'        => $ad->link,
                    'startTime'   => $ad->start_time,
                    'stopTime'    => $ad->stop_time,
                  ];
                });
  }
}
