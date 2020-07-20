<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\Eloquent\Pagination;
use SingPlus\Domains\Musics\Models\Music;

class MusicLibraryRepository
{
  /**
   * @param array $ids
   * @param bool $force       fetch music, even though music was deleted
   *
   * @return Collection       elements are Music 
   */
  public function findAllByIds(array $ids, $force = false) : Collection
  {
    if (empty($ids)) {
      return collect();
    }

    $query = Music::whereIn('_id', $ids);
    if ( ! $force) {
        $query->where('status', Music::STATUS_NORMAL);
    }
    $res = $query->orderBy('created_at', 'desc')->get();
    if ($this->fakeMusicExists($ids)) {
      $res->push($this->genFakeMusic());
    }

    return $res;
  }

  /**
   * @param string $id      music id
   */
  public function findOneById(string $id) : ?Music
  {
    if ($this->fakeMusicExists((array) $id)) {
      return $this->genFakeMusic();
    }
    return Music::find($id);
  }

  /**
   * @param array $querys         allowed keys as below 
   *                                - artistId string     artist id
   *                                - languageId string   music language id
   *                                - styleId string      music style id
   *                                - search string       search content
   * @param ?int $displayOrder    display order,  for pagination
   * @param bool $isNext          for pagination
   * @param int $size
   *
   * @return Collection           elements are Music
   */
  public function findAllByQuerys(
    array $querys,
    ?int $displayOrder,
    bool $isNext,
    int $size
  ) : Collection {
    $query = Music::where('status', Music::STATUS_NORMAL);
    if ($artistId = array_get($querys, 'artistId')) {
      $query->where('artists', 'all', (array) $artistId);
    }
    if ($languageId = array_get($querys, 'languageId')) {
      $query->where('languages', 'all', (array) $languageId);
    }
    if ($styleId = array_get($querys, 'styleId')) {
      $con = ['$in' => (array) $styleId];
      $query->where('styles', 'elemmatch', $con);
    }
    if ($search = array_get($querys, 'search')) {
      $query->whereRaw([
        '$text' => [
          '$search'   => $search,
          '$language' => 'none',
          '$caseSensitive'  => false,
          '$diacriticSensitive' => false,
        ]
      ]);
    }

    $query = Pagination::paginate($query, ['base' => $displayOrder], $isNext, $size);
    if ( ! $query) {
      return collect();
    }

    return $query->get();
  }

  public function searchPreciseWord(string $search) : Collection
  {
    $query = Music::query();

    $pattern = sprintf('/%s/i', $search);
    return $query->where(function ($query) use ($pattern) {
                    $query->where('name', 'regexp', $pattern)
                          ->where('status', Music::STATUS_NORMAL);
                 })
                 ->orWhere(function ($query) use ($pattern) {
                    $query->where('artists_name', 'regexp', $pattern)
                          ->where('status', Music::STATUS_NORMAL);
                 })
                 ->limit(10)
                 ->get();
  }

  public function findOneFakeMusic() : Music
  {
    return $this->genFakeMusic();
  }

  private function fakeMusicExists(array $musicIds) : bool
  {
    $fakeMusic = config('business-logic.fakemusic');
    return in_array($fakeMusic['id'], $musicIds);
  }

  private function genFakeMusic() : Music
  {
    $fakeMusic = config('business-logic.fakemusic');
    $music = new Music([
      'name'    => array_get($fakeMusic, 'name'),
      'status'  => Music::STATUS_NORMAL,
      'artists' => [],
    ]);
    $music->id = array_get($fakeMusic, 'id');
    $music->isFake = true;

    return $music;
  }
}
