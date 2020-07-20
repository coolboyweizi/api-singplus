<?php

namespace SingPlus\Contracts\Works\Services;

use Illuminate\Support\Collection;

interface WorkRankService
{
  /**
   * Get work ranks
   *
   * @param string $type            please to see \SingPlus\Contracts\Works\Constants\WorkRank
   * @param ?string $countryAbbr
   *
   * @return Collection             properties as below:
   *                                - id string
   *                                - workId string
   *                                - workName ?string
   *                                - musicId ?string
   *                                - userId string
   *                                - rank int
   */
  public function getRanks(string $type, ?string $countryAbbr = null) : Collection;
}
