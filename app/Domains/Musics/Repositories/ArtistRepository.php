<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Musics\Models\Artist;

class ArtistRepository
{
  /**
   * @param array $ids
   *
   * @return Collection       elements are Artist 
   */
  public function findAllByIds(array $ids) : Collection
  {
    if (empty($ids)) {
      return collect();
    }

    return Artist::whereIn('_id', $ids)
                 ->where('status', Artist::STATUS_NORMAL)
                 ->get();
  }

  /**
   * @param string $id
   *
   * @return ?Artist
   */
  public function findOneById(string $id) : ?Artist
  {
    return Artist::find($id);
  }

  /**
   * @param array $condition        keys as below
   *                                - nationality array
   *                                  - ids array     nationality ids
   *                                  - include bool  whether include nationality ids or not
   *                                - is_band bool
   *                                - gender ?string  see Artist::GENDER_xxxx
   *                                  
   */
  public function findAllByCategory(array $condition) : Collection
  {
    $query = Artist::where('status', Artist::STATUS_NORMAL);

    $nationalityIds = (array) array_get($condition, 'nationality.ids');
    $include = array_get($condition, 'nationality.include', true);
    if ($nationalityIds) {
      if ($include) {
        $query->whereIn('nationality', $nationalityIds);
      } else {
        $query->whereNotIn('nationality', $nationalityIds)
              ->whereNotNull('nationality');
      }
    } else {
      if ($include) {
        return collect();
      }
    }

    $isBand = array_get($condition, 'is_band', false);
    $query->where('is_band', $isBand);

    if ( ! $isBand) {
      $gender = array_get($condition, 'gender');
      if ($gender) {
        $query->where('gender', $gender);
      }
    }

    return $query->get();
  }
}
