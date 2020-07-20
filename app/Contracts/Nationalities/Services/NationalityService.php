<?php

namespace SingPlus\Contracts\Nationalities\Services;

use Illuminate\Support\Collection;

interface NationalityService
{
  /**
   * Get nationality by code
   *
   * @param array $codes        elements are nationality code
   *
   * @return Collection         elements are \stdClass, properties as below
   *                            - id string     nationality id
   *                            - code string   nationality code
   *                            - name string   nationality name
   *                            - flagUri string   flag url storage key
   */
  public function getNationalityByCode(array $codes) : Collection;

  /**
   * Get all nationalities
   *
   * @return Collection         elements are \stdClass, properties as below:
   *                            - id string     nationality id
   *                            - code string   nationality code
   *                            - name string   nationality name
   *                            - flagUri string   flag url storage key
   */
  public function getAllNationalities() : Collection;
}
