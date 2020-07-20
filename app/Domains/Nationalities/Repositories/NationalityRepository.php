<?php

namespace SingPlus\Domains\Nationalities\Repositories;

use Illuminate\Support\Collection;
use SingPlus\Domains\Nationalities\Models\Nationality;

class NationalityRepository
{
  /**
   * @param array $codes      elements are code
   *
   * @return Collection       elements are Nationality
   */
  public function findAllByCode(array $codes) : Collection
  {
    return Nationality::whereIn('code', $codes)->get();
  }

  /**
   * @return Collection       elements are Nationality
   */
  public function findAll() : Collection
  {
    return Nationality::all();
  }
}
