<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Announcements\Services\AnnouncementService as AnnouncementServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;

class AnnouncementService
{
  /**
   * @var AnnouncementServiceContract 
   */
  private $announcementService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  public function __construct(
    AnnouncementServiceContract $announcementService,
    StorageServiceContract $storageService
  ) {
    $this->announcementService = $announcementService;
    $this->storageService = $storageService;
  }

  /**
   * Get announcements list
   *
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
  public function getAnnouncements(
    ?string $announcementId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $self = $this;
    return $this->announcementService
                ->listAnnouncements($announcementId, $isNext, $size, $countryAbbr)
                ->map(function ($announcement, $_) use ($self) {
                  $announcement->cover = $announcement->cover
                                          ? $self->storageService->toHttpUrl($announcement->cover)
                                          : null;
                  return $announcement;
                });
  }
}
