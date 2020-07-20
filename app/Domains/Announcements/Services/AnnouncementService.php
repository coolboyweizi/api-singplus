<?php

namespace SingPlus\Domains\Announcements\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Announcements\Services\AnnouncementService as AnnouncementServiceContract;
use SingPlus\Domains\Announcements\Repositories\AnnouncementRepository;

class AnnouncementService implements AnnouncementServiceContract
{
  /**
   * @var AnnouncementRepository
   */
  private $announcementRepo;

  public function __construct(
    AnnouncementRepository $announcementRepo
  ) {
    $this->announcementRepo = $announcementRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function listAnnouncements(
    ?string $announcementId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $displayOrder = null;
    if ($announcementId) {
      $announcement = $this->announcementRepo->findOneById($announcementId, ['display_order']);
      $displayOrder = $announcement ? $announcement->display_order : null;
    }

    return $this->announcementRepo
                ->findAll($displayOrder, $isNext, $size, $countryAbbr)
                ->map(function ($announcement, $_) {
                  return (object) [
                    'announcementId'  => $announcement->id,
                    'title'           => $announcement->title,
                    'cover'           => $announcement->cover,
                    'summary'         => $announcement->summary,
                    'type'            => $announcement->type,
                    'attributes'      => $announcement->attributes,
                    'createdAt'       => $announcement->created_at,
                  ];
                });
  }
}
