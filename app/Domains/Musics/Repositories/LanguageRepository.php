<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Musics\Models\Language;

class LanguageRepository
{
  /**
   * @return Collection     elements are Language 
   */
  public function findAll() : Collection
  {
    return Language::where('status', Language::STATUS_NORMAL)
                   ->orderBy('display_order', 'desc')
                   ->get();
  }
}
