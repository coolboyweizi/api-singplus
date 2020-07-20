<?php

namespace SingPlus\Domains\Musics\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Musics\Services\ArtistService as ArtistServiceContract;
use SingPlus\Contracts\Musics\Constants\ArtistConstant;
use SingPlus\Domains\Musics\Repositories\ArtistRepository;
use SingPlus\Domains\Musics\Repositories\ArtistHotRepository;
use SingPlus\Domains\Musics\Models\Artist;

class ArtistService implements ArtistServiceContract
{
  /**
   * @var ArtistRepository
   */
  private $artistRepo;

  /**
   * @var ArtistHotRepository
   */
  private $artistHotRepo;

  public function __construct(
    ArtistRepository $artistRepo,
    ArtistHotRepository $artistHotRepo
  ) {
    $this->artistRepo = $artistRepo;
    $this->artistHotRepo = $artistHotRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getHots(?string $countryAbbr) : Collection
  {
    $hots = $this->artistHotRepo->findAll($countryAbbr);
    $hotArtistIds = $hots->map(function ($hot, $_) {
                      return $hot->artist_id;
                    })->toArray();
    $artists = $this->artistRepo->findAllByIds($hotArtistIds); 

    // keep the orders
    $result = collect();
    foreach ($hotArtistIds as $artistId) {
      $artist = $artists->where('id', $artistId)->first();
      if ( ! $artist) {
        // todo log artist missing
        continue;
      }
      
      $hotId = $hots->where('artist_id', $artistId)->first()->id;
      $result->push((object) [
        'id'        => $hotId,
        'artistId'  => $artist->id,
        'avatar'    => $artist->avatar,
        'name'      => $artist->name,
      ]);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoryArtists(array $nationalityIds, int $category) : Collection
  {
    $condition = $this->getConditionFromCategory($nationalityIds, $category);
    if (is_null($condition)) {
      return collect();
    }

    $artists = $this->artistRepo->findAllByCategory($condition);

    return $artists->map(function ($artist, $_) {
      return (object) [
        'artistId'      => $artist->id,
        'avatar'        => $artist->avatar,
        'name'          => $artist->name,
        'abbreviation'  => $artist->abbreviation,
      ];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getArtist(string $artistId) : ?\stdClass
  {
    $artist = $this->findOneById($artistId);

    return $artist ? (object) [
                        'artistId'    => $artist->id,
                        'name'        => $artist->name,
                        'gender'      => $artist->gender,
                        'avatar'      => $artist->avatar ?: [],
                        'nationality' => $artist->nationality,
                      ] : null;
  }

  private function getConditionFromCategory(array $nationalityIds, int $category) : ?array
  {
    $condition = null;
    switch ($category) {
      case ArtistConstant::CATEGORY_CIVIL_MALE :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => true,
                             ],
          'is_band'     => false,
          'gender'      => Artist::GENDER_MALE,
        ];
        break;
      case ArtistConstant::CATEGORY_CIVIL_FEMALE :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => true,
                             ],
          'is_band'     => false,
          'gender'      => Artist::GENDER_FEMALE,
        ];
        break;
      case ArtistConstant::CATEGORY_CIVIL_BAND :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => true,
                             ],
          'is_band'     => true,
        ];
        break;
      case ArtistConstant::CATEGORY_ABROAD_MALE :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => false,
                             ],
          'is_band'     => false,
          'gender'      => Artist::GENDER_MALE,
        ];
        break;
      case ArtistConstant::CATEGORY_ABROAD_FEMALE :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => false,
                             ],
          'is_band'     => false,
          'gender'      => Artist::GENDER_FEMALE,
        ];
        break;
      case ArtistConstant::CATEGORY_ABROAD_BAND :
        $condition = [
          'nationality'   => [
                                'ids' => $nationalityIds,
                                'include' => false,
                             ],
          'is_band'     => true,
        ];
        break;
    }

    return $condition;
  }
}
