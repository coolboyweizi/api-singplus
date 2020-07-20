<?php

namespace SingPlus\Http\Controllers;

use Illuminate\Http\Request;
use SingPlus\Http\Controllers\Controller;
use SingPlus\Contracts\Nationalities\Services\NationalityService as NationalityServiceContract;

class NationalityController extends Controller
{
  /**
   * list all nationalities
   */
  public function listAllCountries(
    NationalityServiceContract $nationalityService
  ) {
    //$nationalities = $nationalityService->getAllNationalities();

    return $this->render('nationalities.listNationalites');
  }
}
