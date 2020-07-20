<?php

namespace SingPlus\Domains\Nationalities\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Nationalities\Services\NationalityService as NationalityServiceContract;
use SingPlus\Domains\Nationalities\Repositories\NationalityRepository;

class NationalityService implements NationalityServiceContract
{
  /**
   * @var NationalityRepository
   */
  private $nationalityRepo;

  public function __construct(
    NationalityRepository $nationalityRepo
  ) {
    $this->nationalityRepo = $nationalityRepo;
  }

  /**
   * {@inheritdoc}
   */
  public function getNationalityByCode(array $codes) : Collection
  {
    $nationalities = $this->nationalityRepo->findAllByCode($codes);

    return $nationalities->map(function ($nationality, $_) {
      return (object) [
        'id'        => $nationality->id,
        'code'      => $nationality->code,
        'name'      => $nationality->name,
        'flagUri'   => $nationality->flag_uri,
      ];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getAllNationalities() : Collection
  {
    return $this->nationalityRepo
                ->findAll()
                ->map(function ($nationality, $_) {
                  return (object) [
                    'id'      => $nationality->id,
                    'code'    => $nationality->code,
                    'name'    => $nationality->name,
                    'flagUri' => $nationality->flag_uri,
                  ];
                });
  }
}
