<?php

namespace SingPlus\Contracts\Musics\Services;

use Illuminate\Support\Collection;

interface ArtistService
{
  /**
   * Get hot artists
   *
   * @param ?string $countryAbbr
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                        - id string         hot id
   *                        - artistId string   artist id
   *                        - avatar array      artist avatar storage keys
   *                        - name string       artist name
   */
  public function getHots(?string $countryAbbr) : Collection;

  /**
   * Get specified category artists
   *
   * @param array $nationalityIds   nationality ids
   * @param int $category           please see SingPlus\Contracts\Musics\Contracts\ArtistConstant::CATEGORY_xxxxxxx
   *
   * @return Collection     elements are \stdClass, properties as below:
   *                        - artistId string   artist id
   *                        - avatar string     artist avatar storage key
   *                        - name string       artist name
   *                        - abbreviation string   artist name initial letter.
   *                                                eg: Michal Jackson, who's abbreviation
   *                                                is mj
   */
  public function getCategoryArtists(array $nationalityIds, int $category) : Collection;

  /**
   * Get artist by id
   *
   * @param string $artistId
   *
   * @return \stdClass          properties as below
   *                            - artistId string     artist id
   *                            - name string         artist name
   *                            - gender ?string      artist gender: M|F
   *                            - avatar array        artist avatar uri
   *                            - nationality string  nationality ids
   */
  public function getArtist(string $artistId) : ?\stdClass;
}
