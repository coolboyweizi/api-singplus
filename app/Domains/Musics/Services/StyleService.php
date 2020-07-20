<?php

namespace SingPlus\Domains\Musics\Services;

use Carbon\Carbon;
use Cache;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Musics\Services\StyleService as StyleServiceContract;
use SingPlus\Domains\Musics\Repositories\StyleRepository;

class StyleService implements StyleServiceContract
{
  /**
   * @var StyleRepository
   */
  private $styleRepo;

  public function __construct(
    StyleRepository $styleRepo
  ) {
    $this->styleRepo = $styleRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles() : Collection
  {
    $adjustNums = $this->getStyleAdjust();
    
    $other = (object) [
      'id'        => null,
      'name'      => 'other',
      'cover'     => null,
      'totalNum'  => 0,
    ];
    $styles = collect();

    $this->styleRepo
         ->findAll()
         ->each(function ($style, $_) use ($adjustNums, &$styles, &$other) {
            if (strtolower($style->name) == 'other') {
              $other->cover = $style->cover_image;
            }

            $adjustNum = (int) array_get($adjustNums, $style->id, 0);
            $styleTotalNum = $style->total_number + $adjustNum;
            if ($style->needShow()) {
              $styles->push((object) [
                'id'        => $style->id,
                'name'      => $style->name,
                'cover'     => $style->cover_image,
                'totalNum'  => $styleTotalNum,
              ]);
            } else {
              $other->totalNum += $styleTotalNum;
            }
         });

    $styles->push($other);

    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getOtherStyles() : Collection
  {
    return $this->styleRepo
                ->findAllNotShow()
                ->map(function ($style) {
                    return (object) [
                      'id'  => $style->id,
                    ];
                });
  }

  /**
   * {@inheritdoc}
   */
  public function updateStyleAdjust()
  {
    $adjustNums = $this->getStyleAdjust();
    $self = $this;
    $styles = $this->styleRepo->findAll();
    $styleIds = $styles->map(function ($style, $_) {
                    return $style->id;
                  })->toArray();
    $incrs = $this->getStyleIncrAdjust($styleIds);
    $styles->each(function ($style, $_) use (&$adjustNums, $incrs, $self) {
      $realRequestNum = (int) $style->total_number;
      $realSongNum = (int) $style->total_song_number;
      $currentAdjust = (int) array_get($adjustNums, $style->id);
      $currentIncr = (int) array_get($incrs, $style->id);
      $adjustNums[$style->id] = $self->calculateAdjustNum(
        $realRequestNum, $realSongNum, $currentAdjust, $currentIncr 
      );
    });
    Cache::forever($this->styleAdjustKey(), $adjustNums);
    $this->clearStyleIncrAdjust($styleIds);
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

  private function getStyleIncrAdjust(array $styleIds = []) : array
  {
    $result = [];
    foreach ($styleIds as $styleId) {
      $result[$styleId] = (int) Cache::get($this->styleIncrAdjustKey($styleId));
    }

    return $result;
  }

  private function clearStyleIncrAdjust(array $styleIds = [])
  {
    foreach ($styleIds as $styleId)
    {
      Cache::forever($this->styleIncrAdjustKey($styleId), 0);
    }
  }

  private function getStyleAdjust()
  {
    return Cache::get($this->styleAdjustKey()) ?: [];
  }

  private function styleAdjustKey()
  {
    return 'style:num:adjust';
  }

  private function styleIncrAdjustKey(string $styleId) : string
  {
    return sprintf('music:style:%s:reqnum', $styleId);
  }
}
