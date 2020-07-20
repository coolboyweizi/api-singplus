<?php

namespace SingPlus\Domains\Musics\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Musics\Models\Style;

class StyleRepository
{
  /**
   * @return Collection     elements are Style 
   */
  public function findAll() : Collection
  {
    return Style::where('status', Style::STATUS_NORMAL)
                ->orderBy('display_order', 'desc')
                ->get();
  }

  /**
   * @return Collection     elements are Style
   */
  public function findAllNotShow() : Collection
  {
    return Style::where('status', Style::STATUS_NORMAL)
                ->where('need_show', '<>', Style::NEED_SHOW_YES)
                ->get();
  }
}
