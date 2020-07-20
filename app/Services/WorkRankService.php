<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Works\Constants\WorkRank as WorkRankConstant;
use SingPlus\Contracts\Works\Services\WorkRankService as WorkRankServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;

class WorkRankService
{
  /**
   * @var UserProfileService
   */
  private $userProfileService;

  /**
   * @var MusicServiceContract
   */
  private $musicService;

  /**
   * @var WorkRankServiceContract
   */
  private $workRankService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  public function __construct(
    WorkRankServiceContract $workRankService,
    UserProfileServiceContract $userProfileService,
    MusicServiceContract $musicService,
    StorageServiceContract $storageService
  ) {
    $this->workRankService = $workRankService;
    $this->userProfileService = $userProfileService;
    $this->musicService = $musicService;
    $this->storageService = $storageService;
  }

  /**
   * Get global work rank list
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                        - id string       rank id
   *                        - workId string
   *                        - author \stdClass
   *                          - userId string
   *                          - avatar string
   *                          - nickname string
   *                          - popularity  int
   *                          - hierarchyIcon   string
   *                          - hierarchyName   string
   *                          - hierarchyGap    string
   *                        - music \stdClass
   *                          - musicId string
   *                          - name string
   *                        - rank int
   */
  public function getGlobal() : Collection
  {
    $ranks = $this->workRankService
                  ->getRanks(WorkRankConstant::TYPE_GLOBAL);
    return $this->assembleRankData($ranks);
  }

  /**
   * Get country work rank list
   *
   * @param string $countryAbbr
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                        - id string       rank id
   *                        - workId string
   *                        - author \stdClass
   *                          - userId string
   *                          - avatar string
   *                          - nickname string
   *                          - popularity  int
   *                          - hierarchyIcon   string
   *                          - hierarchyName   string
   *                          - hierarchyGap    string
   *                        - music \stdClass
   *                          - musicId string
   *                          - name string
   *                        - rank int
   */
  public function getCountry(string $countryAbbr) : Collection
  {
    $ranks = $this->workRankService
                  ->getRanks(WorkRankConstant::TYPE_COUNTRY, $countryAbbr);
    return $this->assembleRankData($ranks);
  }

  /**
   * Get rookie work rank list
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                        - id string       rank id
   *                        - workId string
   *                        - author \stdClass
   *                          - userId string
   *                          - avatar string
   *                          - nickname string
   *                          - popularity  int
   *                          - hierarchyIcon   string
   *                          - hierarchyName   string
   *                          - hierarchyGap    string
   *                          - hierarchyLogo   string
   *                          - hierarchyAlias  string
   *                        - music \stdClass
   *                          - musicId string
   *                          - name string
   *                        - rank int
   */
  public function getRookie() : Collection
  {
    $ranks = $this->workRankService
                  ->getRanks(WorkRankConstant::TYPE_ROOKIE);
    return $this->assembleRankData($ranks);
  }

  private function assembleRankData(Collection $ranks) : Collection
  {
    if ($ranks->isEmpty()) {
      return collect();
    }

    $userIds = $musicIds = [];
    $ranks->each(function ($rank, $_) use (&$userIds, &$musicIds) {
      $userIds[] = $rank->userId;
      $musicIds[] = $rank->musicId;
    });

    $users = $this->userProfileService->getUserProfiles(array_unique($userIds));
    $musics = $this->musicService->getMusics(array_unique($musicIds), true);

    return $ranks->map(function ($rank, $_) use ($users, $musics) {
      $user = $users->where('userId', $rank->userId)->first();
      $music = $musics->where('musicId', $rank->musicId)->first();
      if ( ! $user || ! $music) {
        return null;
      }

      return (object) [
        'id'      => $rank->id,
        'workId'  => $rank->workId,
        'author'  => (object) [
                        'userId'    => $user->userId,
                        'avatar'    => $this->storageService->toHttpUrl($user->avatar),
                        'nickname'  => $user->nickname,
                        'popularity' => $user->popularity_herarchy->popularity,
                        'hierarchyIcon' => $this->storageService->toHttpUrl($user->popularity_herarchy->icon),
                        'hierarchyName' => $user->popularity_herarchy->name,
                        'hierarchyGap' => $user->popularity_herarchy->gapPopularity,
                        'hierarchyLogo' => $this->storageService->toHttpUrl($user->popularity_herarchy->iconSmall),
                        'hierarchyAlias' => $user->popularity_herarchy->alias
                      ],
        'music'   => (object) [
                        'musicId' => $music->musicId,
                        'name'    => $music->name,
                      ],
        'rank'    => $rank->rank,
      ];
    })->filter(function ($rank, $_) {
      return ! is_null($rank);
    });
  }
}
