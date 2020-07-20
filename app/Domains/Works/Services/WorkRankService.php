<?php

namespace SingPlus\Domains\Works\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Works\Services\WorkRankService as WorkRankServiceContract;
use SingPlus\Contracts\Works\Constants\WorkRank as WorkRankConstant;
use SingPlus\Domains\Works\Repositories\WorkRankRepository;
use SingPlus\Domains\Works\Repositories\WorkRepository;

class WorkRankService implements WorkRankServiceContract
{
  /**
   * @var WorkRepository
   */
  private $workRepo;

  /**
   * @var WorkRankRepository
   */
  private $workRankRepo;

  public function __construct(
    WorkRepository $workRepo,
    WorkRankRepository $workRankRepo
  ) {
    $this->workRepo = $workRepo;
    $this->workRankRepo = $workRankRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getRanks(string $type, ?string $countryAbbr = null) : Collection
  {
    $ranks = $this->workRankRepo
                  ->findAllByType($type, [
                    'countryAbbr' => $countryAbbr,
                  ]);
    if ($ranks->isEmpty()) {
      return collect();
    }

    $workIds = $ranks->unique('work_id')->map(function ($rank, $_) {
      return $rank->work_id;
    })->toArray();

    $works = $this->workRepo
                  ->findAllByIds($workIds, false, ['user_id', 'music_id', 'name']);

    return $ranks->map(function ($rank, $_) use ($works) {
      $work = $works->where('_id', $rank->work_id)->first();
      if ( ! $work) {
        return null;
      }
      return (object) [
        'id'        => $rank->id,
        'workId'    => $rank->work_id,
        'workName'  => $work->name,
        'musicId'   => $work->music_id,
        'userId'    => $work->user_id,
        'rank'      => $rank->rank,
      ];
    })->filter(function ($rank, $_) {
      return ! is_null($rank);
    });
  }
}
