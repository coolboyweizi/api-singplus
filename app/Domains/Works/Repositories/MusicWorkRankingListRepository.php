<?php

namespace SingPlus\Domains\Works\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Works\Models\MusicWorkRankingList;

class MusicWorkRankingListRepository
{
  /**
   * @param string $musicId
   * @param string $type
   *
   * @return Collection       elements are MusicWorkRankingList
   */
  public function findAllMusicIdAndType(string $musicId, int $type) : Collection
  {
    $maxNumber = 100;
    return MusicWorkRankingList::where('music_id', $musicId)
                               ->where('type', $type)
                               ->where('status', MusicWorkRankingList::STATUS_NORMAL)
                               ->orderBy('display_order', 'desc')
                               ->take($maxNumber)
                               ->get();
  }
}
