<?php

namespace SingPlus\Contracts\Announcements\Services;

use Illuminate\Support\Collection;

interface AnnouncementService
{
  /**
   * @param ?string $announcementId     used for pagination
   * @param bool $isNext                used for pagination
   * @param int $size                   used for pagination
   * @param ?string $countryAbbr
   *
   * @return Collection                 elements are \stdClass, properties as below:
   *                                    - announcementId string
   *                                    - title string
   *                                    - cover string    cover image url
   *                                    - summary string
   *                                    - type string     see AnnouncementConstant::TYPE_XXXX
   *                                    - createdAt \Carbon\Carbon
   *                                    - attributes object
   *                                      - url string      only exists on type equals to
   *                                                        AnnouncementConstant::TYPE_URL
   *                                      - musicId string  only exists on type equals to
   *                                                        AnnouncementConstant::TYPE_MUSIC
   */
  public function listAnnouncements(
    ?string $announcementId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection;
}
