<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Storages\Services\StorageService as StorageServiceContract;
use SingPlus\Contracts\Musics\Services\ArtistService as ArtistServiceContract;
use SingPlus\Contracts\Users\Services\UserService as UserServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Contracts\Nationalities\Services\NationalityService as NationalityServiceContract;

class ArtistService
{
  /**
   * @var ArtistServiceContract
   */
  private $artistService;

  /**
   * @var StorageServiceContract
   */
  private $storageService;

  /**
   * @var UserServiceContract
   */
  private $userService;

  /**
   * @var NationalityServiceContract
   */
  private $nationalityService;

  /**
   * @var UserProfileServiceContract
   */
  private $userProfileService;

  public function __construct(
    ArtistServiceContract $artistService,
    StorageServiceContract $storageService,
    UserServiceContract $userService,
    UserProfileServiceContract $userProfileService,
    NationalityServiceContract $nationalityService
  ) {
    $this->artistService = $artistService;
    $this->storageService = $storageService;
    $this->userService = $userService;
    $this->userProfileService = $userProfileService;
    $this->nationalityService = $nationalityService;
  }

  /**
   * Get hot artists
   *
   * @param string $userId
   * @param ?string $countryAbbr
   *
   * @return Collection       elements as below
   *                          - id string         hot id
   *                          - artistId string   artist id
   *                          - avatar string     avatar image url
   *                          - name string       artist name
   */
  public function getHots(string $userId, ?string $countryAbbr) : Collection
  {
    $hots = $this->artistService->getHots($countryAbbr);
    return $this->genAvatar($hots);
  }

  /**
   * Get specified category artists
   *
   * @param string $userId
   * @param int $category
   *
   * @return Collection         elements as below
   *                            - artistId string        artist id
   *                            - avatar string          avatar image url
   *                            - name string            artist name
   *                            - abbreviation string    artist abbreviation letter
   */
  public function getCategoryArtists(string $userId, int $category, ?string $defaultCode) : Collection
  {
    $countryCode = $defaultCode;
    if ($userId != ""){
        $user = $this->userService->fetchUser($userId);

        // fetch country code
        $location = $this->userProfileService->getUserLocation($userId);
        $countryCode = object_get($location, 'countryCode');
        if (is_null($countryCode)) {
            $countryCode = $user->country_code;
        }
    }

    $nationalities = $this->nationalityService->getNationalityByCode((array) $countryCode);
    $nationalityIds = $nationalities->map(function ($nationality, $_) {
                                        return $nationality->id;
                                      })->toArray();
    $artists = $this->artistService->getCategoryArtists($nationalityIds, $category);
    return $this->genAvatar($artists);
  }

  private function genAvatar(Collection $artists) : Collection
  {
    $self = $this;
    return $artists->map(function ($artist, $_) use ($self) {
      $artist->avatar = count($artist->avatar)
              ? $self->storageService->toHttpUrl($artist->avatar[0])
              : '';
      return $artist;
    });
  }
}
