<?php

namespace SingPlus\Domains\Works\Repositories;

use SingPlus\Domains\Works\Models\RecommendWorkSheet;

class RecommendWorkSheetRepository
{
  /**
   * @param string $sheetId
   *
   * @return ?RecommendWorkSheet
   */
  public function findOneById(string $sheetId) : ?RecommendWorkSheet
  {
    return RecommendWorkSheet::where('_id', $sheetId)
                             ->where('status', RecommendWorkSheet::STATUS_NORMAL)
                             ->first();
                              
  }
}
