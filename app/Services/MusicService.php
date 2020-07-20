<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Users\Services\UserImageService as UserImageServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Musics\Services\MusicService as MusicServiceContract;
use SingPlus\Contracts\Musics\Services\LanguageService as LanguageServiceContract;
use SingPlus\Contracts\Musics\Services\StyleService as StyleServiceContract;
use SingPlus\Contracts\Musics\Services\ArtistService as ArtistServiceContract;
use SingPlus\Contracts\Works\Services\WorkService as WorkServiceContract;
use SingPlus\Contracts\Helps\Services\HelpService as HelpServiceContract;
use SingPlus\Contracts\Searchs\Services\SearchService as SearchServiceContract;
use SingPlus\Exceptions\Musics\MusicNotExistsException;
use SingPlus\Events\Works\RankExpired as RankExpiredEvent;

class MusicService
{
  /**
   * @var UserImageServiceContract
   */
  private $userImageService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var MusicServiceContract
   */
  private $musicService;

  /**
   * @var LanguageServiceContract
   */
  private $languageService;

  /**
   * @var StyleServiceContract
   */
  private $styleService;

  /**
   * @var ArtistServiceContract
   */
  private $artistService;

  /**
   * @var HelpServiceContract
   */
  private $helpService;

  /**
   * @var WorkServiceContract
   */
  private $workService;

  /**
   * @var SearchServiceContract
   */
  private $searchService;

  public function __construct(
    UserImageServiceContract $userImageService,
    UserProfileServiceContract $userProfileService,
    StorageServiceContract $storageService,
    MusicServiceContract $musicService,
    LanguageServiceContract $languageService,
    StyleServiceContract $styleService,
    ArtistServiceContract $artistService,
    HelpServiceContract $helpService,
    WorkServiceContract $workService,
    SearchServiceContract $searchService
  ) {
    $this->userImageService = $userImageService;
    $this->userProfileService = $userProfileService;
    $this->storageService = $storageService;
    $this->musicService = $musicService;
    $this->languageService = $languageService;
    $this->styleService = $styleService;
    $this->artistService = $artistService;
    $this->helpService = $helpService;
    $this->workService = $workService;
    $this->searchService = $searchService;
  }

  /**
   * @param string $userId
   * @param ?string $countryAbbr
   *
   * @return Collection       elements as below
   *                          - id string         recommend id
   *                          - musicId string    music id
   *                          - cover string      music cover url 
   *                          - name string       music name
   *                          - artists array     music artists name
   *                          - size  object      music accompaniment size
   *                                              - raw int           song size
   *                                              - accompaniment int accompaniment size
   *                                              - total int         zip package size
   *                          - requestNum  int   music request count
   *
   */
  public function getRecommends(string $userId, ?string $countryAbbr) : Collection
  {
    $recommends = $this->musicService->getRecommends($countryAbbr);

    $self = $this;
    return $recommends->map(function ($music, $_) use ($self) {
      if ( ! count($music->cover)) {
        $music->cover = '';
      } else {
        $music->cover = $self->storageService->toHttpUrl($music->cover[0]);
      }
      return $music;
    });
  }

  /**
   * Get hot music
   *
   * @param string $userId
   * @param ?string $hotId      page start hot id
   * @param bool $isNext        page direction
   * @param int $size           how many music will be fetch
   * @param ?string $countryAbbr
   *
   * @return Collection         elements as below
   *                            - id string         hot id
   *                            - musicId string    music id
   *                            - cover string      music cover url 
   *                            - name string       music name
   *                            - artists array     music artists name
   *                            - size  object      music accompaniment size
   *                                                - raw int             song size (bytes)
   *                                                - accompaniment int   accompaniment size (types)
   *                                                - total int           zip package size (types)
   *                            - requestNum  int   music request count
   */
  public function getHots(
    string $userId,
    ?string $hotId = null,
    bool $isNext = true,
    int $size,
    ?string $countryAbbr
  ) {
    $hots = $this->musicService->getHots($hotId, $isNext, $size, $countryAbbr);

    $self = $this;
    return $hots->map(function ($music, $_) use ($self) {
      if ( ! count($music->cover)) {
        $music->cover = '';
      } else {
        $music->cover = $self->storageService->toHttpUrl($music->cover[0]);
      }
      return $music;
    });
  }

  /**
   * Get recommend music sheet
   *
   * @param string $userId
   * @param string $sheetId     sheet id
   *
   * @return ?\stdClass         elements as below
   *                            - cover array         cover image uri
   *                            - title string
   *                            - requestNum int      sheet requested number
   *                            - musics  Collection  properties as below:
   *                              - musicId string    music id
   *                              - name string       music name
   *                              - lyric string      simple lyric uri
   *                              - artists array     music artists name
   *                              - size \stdClass
   *                                  - raw int               raw size (bytes)
   *                                  - accompaniment int     accompaniment size (bytes)
   *                                  - total int             zip package size (bytes)
   *                            - requestNum  int   music request count
   */
  public function getRecommendMusicSheet(string $userId, string $sheetId) : ?\stdClass
  {
    $sheet = $this->musicService->getRecommendMusicSheet($sheetId);
    if ( ! $sheet) {
      return null;
    }

    foreach ($sheet->cover as &$cover) {
      $cover = $this->storageService->toHttpUrl($cover);
    }

    return $sheet;
  }

  /**
   * Get music categories
   *
   * @param string $userId
   *
   * @return \stdClass        properties as below
   *                          - languages collection  elements properties as below:
   *                            - id string     language id
   *                            - cover string  language cover image url
   *                            - name string   language name
   *                            - totalNum int  total music number
   *                          - styles collection  elements properties as below:
   *                            - id string     language id
   *                            - cover string  language cover image url
   *                            - name string   language name
   *                            - totalNum int  total music number
   */
  public function getCategories(string $userId) : \stdClass
  {
    $self = $this;
    $languages = $this->languageService
                      ->getLanguages()
                      ->map(function($language, $_) use ($self) {
                        $language->cover = $self->storageService->toHttpUrl($language->cover);
                        return $language;
                      });
    $styles = $this->styleService
                   ->getStyles()
                   ->map(function ($style, $_) use ($self) {
                      $style->cover = $self->storageService->toHttpUrl($style->cover);
                      return $style;
                   });
    return (object) [
      'languages' => $languages,
      'styles'    => $styles,
    ];
  }

  /**
   * Get specified artists musics
   *
   * @param string $userId
   * @param array $querys           allowed keys as below:
   *                                - artistId string     artist id
   *                                - languageId string   music language id
   *                                - styleId string      music style id
   *                                - search string       search content
   * @param string $musicId         be used for pagination
   * @param bool $isNext            pagination direction
   * @param int $size               music number per page
   *
   * @return Collection             elements are \stdClass, properties as below:
   *                                  - musicId string            music id
   *                                  - name string               music name
   *                                  - size \stdClass
   *                                      - raw int               raw size (bytes)
   *                                      - accompaniment int     accompaniment size (bytes)
   *                                      - total int             zip package size (bytes)
   *                                  - artists array             elements are \stdClass
   *                                      - artistId string       artist id
   *                                      - name string           artist name
   */
  public function getMusics(
    string $userId,
    array $querys,
    ?string $musicId,
    bool $isNext,
    int $size,
    int $page = 0
  ) : Collection {
    $querys = [
      'artistId'    => array_get($querys, 'artistId'),
      'languageId'  => array_get($querys, 'languageId'),
      'styleId'     => array_get($querys, 'styleId'),
      'search'      => array_get($querys, 'search'),
    ];

    if (array_get($querys, 'search')) {
        $res= $this->searchService->musicSearch(array_get($querys, 'search'), $page, $size);
    } else {
        $res = $this->musicService->queryMusics($querys, $musicId, $isNext, $size);
    }

    $search = array_get($querys, 'search');
    if ($search && ! $musicId && $res->isEmpty()) {
      $this->helpService->commitMusicSearchAutoFeedback($userId, $search);
    }

    return $res;
  }

  /**
   * Get style musics
   *
   * @param string $userId
   * @param ?string $styleId        music style id
   * @param string $musicId         be used for pagination
   * @param bool $isNext            pagination direction
   * @param int $size               music number per page
   *
   * @return Collection             elements are \stdClass, properties as below:
   *                                  - musicId string            music id
   *                                  - name string               music name
   *                                  - size \stdClass
   *                                      - raw int               raw size (bytes)
   *                                      - accompaniment int     accompaniment size (bytes)
   *                                      - total int             zip package size (bytes)
   *                                  - artists array             elements are \stdClass
   *                                      - artistId string       artist id
   *                                      - name string           artist name
   */
  public function getStyleMusics(
    string $userId,
    ?string $styleId,
    ?string $musicId,
    bool $isNext,
    int $size  
  ) {
    if ( ! $styleId) {
      $styleIds = $this->styleService
                          ->getOtherStyles()
                          ->map(function ($style) {
                            return $style->id;
                          })
                          ->toArray();
    } else {
      $styleIds = (array) $styleId;
    }

    if (empty($styleIds)) {
      return collect();
    }

    return $this->musicService->queryMusics([
              'styleId' => $styleIds,
            ], $musicId, $isNext, $size);
  }

  /**
   * Get music download url
   *
   * @param string $userId      user id
   * @param string $musicId     music id
   * @param ?string $countryAbbr
   *
   * @return ?string            music download address
   * @return \stdClass          properties as below:
   *                            - resource url        music zip download url
   *                            - cover url           music cover url
   */
  public function getMusicDownloadAddress(
    string $userId,
    string $musicId,
    ?string $countryAbbr
  ) : \stdClass {
    $uri = $this->musicService->getMusicDownloadAddress($musicId, $countryAbbr);
    $userAvatar = $this->userImageService->getAvatar($userId);

    return (object) [
      'resource'  => $this->storageService->toHttpUrl($uri),
      'cover'     => $this->storageService->toHttpUrl($userAvatar),
    ];
  }

  /**
   * Adjust catetory (language & style) total number. Only affect incremental data
   */
  public function adjustCategoryTotalNumber()
  {
    $this->languageService->updateLanguageAdjust();
    $this->styleService->updateStyleAdjust();
  }

  /**
   * Get specified music detail, including chorus recommend list
   * and solo ranking list
   *
   * @param string $musicId
   *
   * @return \stdClass    music detail, elements as below:
   *                      - music \stdClass
   *                        - name string   music name
   *                        - cover string  music cover
   *                        - size int      music size, unit: Bytes
   *                        - etag ?string  music resource zip etag
   *                        - artists array music artists name
   *                      - chorusRecommends array    chorus start recommend list
   *                        - workId string   chorus start work id
   *                        - chorusCount int chorus count by others
   *                        - author \stdClass
   *                          - userId string
   *                          - nickname string
   *                          - avatar string   user avatar url
   *                      - soloRankinglists array    solo work ranking list
   *                        - workId string   solo work id 
   *                        - listenCount int solo work listen count by others
   *                        - author \stdClass
   *                          - userId string
   *                          - nickname string
   *                          - avatar string   user avatar url
   */
  public function getMusicDetail(string $musicId, bool $basic = false) : \stdClass
  {
    $music = $this->musicService->getMusic($musicId);
    if (object_get($music, 'isFake')) {
      throw new MusicNotExistsException('Music not exists');
    }

    $musicRes = (object) [
      'name'  => $music->name,
      'cover' => $this->storageService->toHttpUrl(array_get($music->covers, 0)),
      'size'  => $music->size->total,
      'etag'  => $music->etag,
      'artistsName' => $music->artistsName,
    ];
    if ($basic) {
      return (object) [
        'music' => $musicRes,
      ];
    }

    if (
      ! $music->workRankExpiredAt ||
      ($music->workRankExpiredAt->getTimestamp() - 30) < time()
    ) {
      event(new RankExpiredEvent($musicId));
    }

    $soloWorkRankingList = $this->workService->getMusicSoloRankingList($musicId);
    $chorusRecommends = $this->workService->getMusicChorusRankingList($musicId);
    $userIds = $soloWorkRankingList->map(function ($work, $_) {
                    return $work->userId;
                  });
    $chorusRecommends->each(function ($work, $_) use (&$userIds) {
                    if ( ! $userIds->contains($work->userId)) {
                      $userIds->push($work->userId);
                    }
                  });
    $profiles = $this->userProfileService->getUserProfiles($userIds->toArray());

    return (object) [
      'music'             => $musicRes,
      'chorusRecommends'  => $chorusRecommends->map(function ($work, $_) use ($profiles) {
                $profile = $profiles->where('userId', $work->userId)->first();
                return (object) [
                  'workId'      => $work->workId,
                  'chorusCount' => $work->chorusCount,
                  'author'      => (object) [
                        'userId'    => $profile->userId,
                        'nickname'  => $profile->nickname,
                        'avatar'    => $this->storageService->toHttpUrl($profile->avatar),
                      ],
                ];
              })->toArray(),
      'soloRankinglists'  => $soloWorkRankingList->map(function ($work, $_) use ($profiles) {
                $profile = $profiles->where('userId', $work->userId)->first();
                return (object) [
                  'workId'      => $work->workId,
                  'listenCount' => $work->listenCount,
                  'author'      => (object) [
                        'userId'    => $profile->userId,
                        'nickname'  => $profile->nickname,
                        'avatar'    => $this->storageService->toHttpUrl($profile->avatar),
                      ],
                ];
              })->toArray(),
    ];
  }
}
