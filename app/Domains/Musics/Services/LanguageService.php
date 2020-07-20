<?php

namespace SingPlus\Domains\Musics\Services;

use Carbon\Carbon;
use Cache;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Musics\Services\LanguageService as LanguageServiceContract;
use SingPlus\Domains\Musics\Repositories\LanguageRepository;

class LanguageService implements LanguageServiceContract
{
  /**
   * @var LanguageRepository
   */
  private $languageRepo;

  public function __construct(
    LanguageRepository $languageRepo
  ) {
    $this->languageRepo = $languageRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguages() : Collection
  {
    $adjustNums = $this->getLanguageAdjust();
    return $this->languageRepo
                ->findAll()
                ->map(function ($language, $_) use ($adjustNums) {
                  $adjustNum = (int) array_get($adjustNums, $language->id, 0); 
                  return (object) [
                    'id'        => $language->id,
                    'name'      => $language->name,
                    'cover'     => $language->cover_image,
                    'totalNum'  => $language->total_number + $adjustNum,
                  ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function updateLanguageAdjust()
  {
    $adjustNums = $this->getLanguageAdjust();
    $self = $this;
    $langs = $this->languageRepo->findAll();
    $languageIds = $langs->map(function ($language, $_) {
                      return $language->id;
                  })->toArray();
    $incrs = $this->getLanguageIncrAdjust($languageIds);
    $langs->each(function ($language, $_) use (&$adjustNums, $incrs, $self) {
      $realRequestNum = (int) $language->total_number;
      $realSongNum = (int) $language->total_song_number;
      $currentAdjust = (int) array_get($adjustNums, $language->id);
      $currentIncr = (int) array_get($incrs, $language->id);
      $adjustNums[$language->id] = $self->calculateAdjustNum(
        $realRequestNum, $realSongNum, $currentAdjust, $currentIncr 
      );
    });
    Cache::forever($this->languageAdjustKey(), $adjustNums);
    $this->clearLanguageIncrAdjust($languageIds);
  }

  private function calculateAdjustNum(
    int $realRequestNum,
    int $realSongNum,
    int $currentAdjust,
    int $currentIncr
  ) {
    if ($realRequestNum < 1000) {
      if ($realSongNum < 50) {
        $adjust = $currentAdjust + 2 * $currentIncr + 5;
      } else {
        $adjust = $currentAdjust + 2 * $currentIncr + 10;
      }
    } else {
      if ($realSongNum < 100) {
        $adjust = $currentAdjust + $currentIncr + 50;
      } else {
        $adjust = $currentAdjust + $currentIncr;
      }
    }

    return $adjust;
  }

  private function getLanguageIncrAdjust(array $languageIds = []) : array
  {
    $result = [];
    foreach ($languageIds as $languageId) {
      $result[$languageId] = (int) Cache::get($this->languageIncrAdjustKey($languageId));
    }

    return $result;
  }

  private function clearLanguageIncrAdjust(array $languageIds = [])
  {
    foreach ($languageIds as $languageId)
    {
      Cache::forever($this->languageIncrAdjustKey($languageId), 0);
    }
  }

  private function getLanguageAdjust()
  {
    return Cache::get($this->languageAdjustKey()) ?: [];
  }

  private function languageAdjustKey()
  {
    return 'lang:num:adjust';
  }

  private function languageIncrAdjustKey(string $languageId) : string
  {
    return sprintf('music:lang:%s:reqnum', $languageId);
  }
}
