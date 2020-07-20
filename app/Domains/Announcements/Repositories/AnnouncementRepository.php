<?php

namespace SingPlus\Domains\Announcements\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Announcements\Models\Announcement;

class AnnouncementRepository
{
  /**
   * @param string $announcementId
   *
   * @return ?Announcement
   */
  public function findOneById(string $announcementId, array $fields) : ?Announcement
  {
    return Announcement::select(...$fields)->find($announcementId);
  }

  /**
   * @param ?int $displayOrder  used for pagination
   * @param bool $isNext        used for pagination
   * @param int $size           used for pagination
   * @param ?string $countryAbbr
   *
   * @return Collection         elements are Announcement
   */
  public function findAll(
    ?int $displayOrder,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $query = Announcement::where('status', Announcement::STATUS_NORMAL);
    if ($countryAbbr) {
      $query = $query->where('country_abbr', $countryAbbr);
    }
    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }
    return $query->get();
  }
}
