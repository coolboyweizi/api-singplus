<?php

namespace SingPlus\Domains\Musics\Services;

use Log;
use Cache;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Domains\Musics\Repositories\MusicRecommendRepository;
use SingPlus\Domains\Musics\Repositories\MusicLibraryRepository;
use SingPlus\Domains\Musics\Repositories\ArtistRepository;
use SingPlus\Domains\Musics\Repositories\MusicHotRepository;
use SingPlus\Domains\Musics\Repositories\RecommendMusicSheetRepository;
use SingPlus\Exceptions\Musics\MusicNotExistsException;
use SingPlus\Exceptions\Musics\MusicDataMissedException;
use SingPlus\Exceptions\Musics\MusicOutOfStockException;

class MusicService implements MusicServiceContract
{
  /**
   * @var MusicRecommendRepository
   */
  private $musicRecommendRepo;

  /**
   * @var MusicLibraryRepository
   */
  private $musicLibRepo;

  /**
   * @var ArtistRepository
   */
  private $artistRepo;

  /**
   * @var MusicHotRepository
   */
  private $musicHotRepo;

  /**
   * @var RecommendMusicSheetRepository
   */
  private $recommendMusicSheetRepo;

  public function __construct(
    MusicRecommendRepository $musicRecommendRepo,
    MusicLibraryRepository $musicLibRepo,
    ArtistRepository $artistRepo,
    MusicHotRepository $musicHotRepo,
    RecommendMusicSheetRepository $recommendMusicSheetRepo
  ) {
    $this->musicRecommendRepo = $musicRecommendRepo;
    $this->musicLibRepo = $musicLibRepo;
    $this->artistRepo = $artistRepo;
    $this->musicHotRepo = $musicHotRepo;
    $this->recommendMusicSheetRepo = $recommendMusicSheetRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommends(?string $countryAbbr) : Collection
  {
    $recommends = $this->musicRecommendRepo->findAll($countryAbbr);
    $recommendMusicIds = $recommends->map(function ($recommend, $_) {
                            return $recommend->music_id;
                         })->toArray();
    $musics = $this->getMusicsByIds($recommendMusicIds);
    return $musics->map(function ($music, $_) use ($recommends) {
      $recommendId = $recommends->where('music_id', $music->musicId)->first()->id;
      $music->id = $recommendId;
      return $music;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getHots(
    ?string $hotId,
    bool $isNext,
    int $size,
    ?string $countryAbbr
  ) : Collection {
    $displayOrder = null;
    if ($hotId) {
      $music = $this->musicHotRepo->findOneById($hotId);
      $displayOrder = $music ? (int) $music->display_order : null;
    }

    $hots = $this->musicHotRepo
                   ->findAllForPagination($displayOrder, $isNext, $size, $countryAbbr);
    $hotIds = $hots->map(function ($hot, $_) {
                return $hot->music_id;
              })->toArray();

    $musics = $this->getMusicsByIds($hotIds);
    return $musics->map(function ($music, $_) use ($hots) {
      $hotId = $hots->where('music_id', $music->musicId)->first()->id;
      $music->id = $hotId;
      return $music;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function queryMusics(array $querys, ?string $musicId, bool $isNext, int $size) : Collection
  {
    $displayOrder = null;
    if ($musicId) {
      $music = $this->musicLibRepo->findOneById($musicId);
      $displayOrder = $music ? (int) $music->display_order : null;
    }

    $musics = $this->musicLibRepo->findAllByQuerys($querys, $displayOrder, $isNext, $size);
    if (($searchWord = array_get($querys, 'search')) && empty($musicId)) {
      $preciseMusics = $this->musicLibRepo->searchPreciseWord($searchWord);
      $musics = $preciseMusics->merge($musics)->unique('id');
    }

    $artistIds = $musics->map(function ($music, $_) {
                    return $music->artists;
                  })->flatten()->unique()->toArray();
    $artists = $this->artistRepo
                   ->findAllByIds($artistIds)
                   ->groupBy('id');

    return $musics->map(function ($music, $_) use ($artists) {
      $newArtists = [];
      // fill artist info
      if ($music->artists) {
        foreach ($music->artists as $artistId) {
          $artist = $artists->get($artistId);

          if ( ! $artist) {
            Log::alert('Data missed. music miss artist', [
                        'music_id'  => $music->id,
                        'artist_id' => $artistId,
                      ]);
          }

          $newArtists[] = (object) [
            'artistId'  => $artistId,
            'name'      => $artist ? $artist->first()->name : null,
          ];
        }
      }

      $resource = $music->resource;
      return (object) [
        'musicId' => $music->id,
        'name'    => $music->name,
        'size'    => (object) [
                      'raw'           => isset($resource['raw']['size']) ? $resource['raw']['size'] : 0,
                      'accompaniment' => isset($resource['accompaniment']['size']) ? $resource['accompaniment']['size'] : 0,
                      'total'         => isset($resource['size']) ? $resource['size'] : 0,
                    ],
        'artists' => $newArtists,
      ];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getMusicDownloadAddress(string $musicId, ?string $countryAbbr) : ?string
  {
    $music = $this->musicLibRepo->findOneById($musicId);
    if ( ! $music) {
      throw new MusicNotExistsException();
    }
    if ($music && isset($music->isFake) && $music->isFake) {
      return null;
    }
    if ( ! $music->isNormal()) {
      throw new MusicOutOfStockException('The karaoke is not available for now');
    }

    $resource = $music && $music->resource ? $music->resource : [];

    if ( ! isset($resource['zip']) || ! $resource['zip']) {
      Log::alert('Data missed. music miss resource zip', [
                  'music_id'  => $music->id,
                ]);
      throw new MusicDataMissedException('music not exists');
    }

    $this->addMusicRequstNum($musicId, $countryAbbr);

    return $resource['zip'];
  }

  /**
   * {@inheritdoc}
   */
  public function musicExists(string $musicId) : bool
  {
    $music = $this->musicLibRepo->findOneById($musicId);

    return $music ? true : false;
  }

  /**
   * {@inheritdoc}
   */
  public function getFakeMusic() : \stdClass
  {
    $music = $this->musicLibRepo->findOneFakeMusic();

    return (object) [
      'musicId' => $music->id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMusics(array $musicIds, $force = false) : Collection
  {
    if (empty($musicIds)) {
      return collect();
    }
    return $this->getMusicsByIds($musicIds, $force);
  }

  /**
   * {@inheritdoc}
   */
  public function getMusic(string $musicId, bool $force = false) : ?\stdClass
  {
    $music = $this->musicLibRepo->findOneById($musicId);

    return (( ! $force && $music && $music->isNormal()) || ($force && $music)) ?
            (object) [
              'name'    => $music->name,
              'covers'  => $music->cover_images,
              'artistsName'  => $music->artists_name,
              'size'    => (object) [
                              'total' => (int) array_get($music->resource, 'size', 0),
                            ],
              'etag'    => array_get($music->resource, 'etag'),
              'workRankExpiredAt' => isset($music->work_rank_expired_at) ?
                                        Carbon::parse($music->work_rank_expired_at) :
                                        null,
              'isFake'  => (bool) object_get($music, 'isFake'),
            ] : null;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendMusicSheet(string $sheetId) : ?\stdClass
  {
    $sheet = $this->recommendMusicSheetRepo->findOneById($sheetId);
    if ( ! $sheet || ! $sheet->isNormal()) {
      return null;
    }

    $musics = $this->getMusicsByIds($sheet->music_ids);

    return (object) [
      'cover'       => (array) $sheet->cover,
      'title'       => (string) $sheet->title,
      'requestNum'  => (int) $sheet->request_count,
      'musics'      => $musics,
    ];
  }

  private function getMusicsByIds(array $musicIds, $force = false) : Collection
  {
    if (empty($musicIds)) {
      return collect();
    }

    $musics = $this->musicLibRepo->findAllByIds($musicIds, $force);
    if ( ! $musics->count()) {
      return collect();
    }

    $artistIds = $musics->map(function ($music, $_) {
                        return $music->artists; 
                    })->flatten()->toArray();
    $artists = collect();
    if ($artistIds) {
      $artists = $this->artistRepo->findAllByIds($artistIds);
    }

    // we shold keep music display order
    $result = collect();
    foreach ($musicIds as $musicId) {
      $music = $musics->where('id', $musicId)->first();
      if ( ! $music) {
        // todo log music missing
        continue;
      }

      $resource = $music->resource;
      $names = [];
      foreach ($music->artists as $artistId) {
        if ($artist = $artists->where('id', $artistId)->first()) {
          $names[] = $artist->name;
        }
      };

      $result->push((object) [
        'musicId'         => $music->id,
        'name'            => $music->name,
        'cover'           => $music->cover_images,
        'artists'         => $names,
        'lyric'           => $music->lyrics,
        'size'            => (object) [
                              'raw'           => isset($resource['raw']['size']) ? $resource['raw']['size'] : 0,
                              'accompaniment' => isset($resource['accompaniment']['size']) ? $resource['accompaniment']['size'] : 0,
                              'total'         => isset($resource['size']) ? $resource['size'] : 0,
                              ],
        'requestNum'      => (int) $music->request_count + $this->getMusicRequstNum($music->id),
      ]);
    }

    return $result;
  }

  private function addMusicRequstNum(string $musicId, ?string $countryAbbr)
  {
    $key = $this->musicRequestNumKey($musicId);
    $countryKey = $this->musicCountryRequestNumKey($musicId, $countryAbbr);
    Cache::increment($key);
    Cache::increment($countryKey);
  }

  private function getMusicRequstNum(string $musicId) : int
  {
    $key = $this->musicRequestNumKey($musicId);
    return (int) Cache::get($key);
  }

  private function musicRequestNumKey(string $musicId)
  {
    return sprintf('music:%s:reqnum', $musicId);
  }

  private function musicCountryRequestNumKey(
    string $musicId,
    ?string $countryAbbr
  ) {
    return sprintf('music:%s:%s:reqnum', $musicId, $countryAbbr);
  }
}
