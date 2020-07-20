<?php

namespace SingPlus\Domains\Musics\Repositories;

use SingPlus\Domains\Musics\Models\RecommendMusicSheet;

class RecommendMusicSheetRepository
{
  public function findOneById(string $id) : ?RecommendMusicSheet
  {
    return RecommendMusicSheet::find($id);
  }
}
