<?php

namespace SingPlus\Contracts\Musics\Services;

use Illuminate\Support\Collection;

interface MusicService
{
  /**
   * @param ?string $countryAbbr
   *
   * @return Collection       elements as below
   *                          - id string         recommend id
   *                          - musicId string    music id
   *                          - cover array       music cover uri
   *                          - name string       music name
   *                          - artists array     music artists name
   *                          - size \stdClass
   *                              - raw int               raw size (bytes)
   *                              - accompaniment int     accompaniment size (bytes)
   *                              - total int             zip package size (bytes)
   *                          - requestNum  int   music request count
   */
  public function getRecommends(?string $countryAbbr) : Collection;

  /**
   *
   * @param ?string $hotId    for pagination
   * @param bool $isNext      for pagination
   * @param int $size         for pagination
   * @param ?string $countryAbbr
   *
   * @return Collection       elements as below
   *                          - id string         hot id
   *                          - musicId string    music id
   *                          - cover array       music cover uri
   *                          - name string       music name
   *                          - artists array     music artists name
   *                          - size \stdClass
   *                              - raw int               raw size (bytes)
   *                              - accompaniment int     accompaniment size (bytes)
   *                              - total int             zip package size (types)
   *                          - requestNum  int   music request count
   */
  public function getHots(
    ?string $hotId,
    bool $isNext,
    int $size,
    ?string $countryAbbr) : Collection;

  /**
   * Query musics
   *
   * @param array $querys     allowed keys as below:
   *                                - artistId string     artist id
   *                                - languageId string   music language id
   *                                - styleId string      music style id
   *                                - search string       search content
   * @param ?string $musicId  music id, for pagination
   * @param bool $isNext      for pagination
   * @param int $size         music number per page, for pagination
   *
   * @return Collection       elements are \stdClass, properties as below:
   *                          - musicId string            music id
   *                          - name string               music name
   *                          - size \stdClass
   *                              - raw int               raw size (bytes)
   *                              - accompaniment int     accompaniment size (bytes)
   *                              - total int             zip package size (types)
   *                          - artists array             elements are string
   *                              - artistId string       artist id
   *                              - name string           artist name
   */
  public function queryMusics(array $querys, ?string $musicId, bool $isNext, int $size) : Collection;

  /**
   * Get music zip download address
   *
   * @param string $musicId     music id
   * @param ?string $countryAbbr
   */
  public function getMusicDownloadAddress(string $musicId, ?string $countryAbbr) : ?string;

  /**
   * Whether music exists or not
   *
   * @param string $musicId     music id
   *
   * @return bool
   */
  public function musicExists(string $musicId) : bool;

  /**
   * Get fake music
   *
   * @return \stdClass      properties as below
   *                        - musicId string    music id
   */
  public function getFakeMusic() : \stdClass;

  /**
   * Get all music by music ids
   *
   * @param array $musicIds       music ids
   * @param bool $force           whether fetch music even through which deleted or not
   *
   * @return Collection           elements are \stdClass, properties as below:
   *                              - musicId string          music id
   *                              - name string             music name
   *                              - lyric string            simple lyric uri (sync by sentence)
   *                              - artists array           music artists name
   */
  public function getMusics(array $musicIds, $force = false) : Collection;

  /**
   * Get one music
   *
   * @param string $musicId
   * @param bool $force        deleted music will be return if force is true
   *
   * @return ?\stdClass        properties as below:
   *                           - name string    music name
   *                           - covers array    elements are cover uri
   *                           - artistsName string   music artists name
   *                           - size \stdClass
   *                              - total int   music total size, unit: Bytes
   *                           - etag ?string   music resource zip etag
   *                           - workRankExpiredAt \Carbon\Carbon     rank list expired time
   */
  public function getMusic(string $musicId, bool $force = false) : ?\stdClass;

  /**
   * Get specified recommend music sheet
   *
   * @param string $sheetId
   *
   * @return ?\stdClass         properties as below:
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
   *                              - requestNum  int   music request count
   */
  public function getRecommendMusicSheet(string $sheetId) : ?\stdClass;
}
