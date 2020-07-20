<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;
use SingPlus\Support\Helpers\Str;

class NationalityControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //    listAllCountries
  //=================================
  public function testListAllCountriesSuccess()
  {
    factory(\SingPlus\Domains\Nationalities\Models\Nationality::class)->create([
      'code'  => '254',
      'name'  => 'Kenya',
    ]);
    factory(\SingPlus\Domains\Nationalities\Models\Nationality::class)->create([
      'code'  => '86',
      'name'  => 'China',
    ]);

    $response = $this->getJson('v3/nationalities');
    $response->assertJson(['code' => 0]); 
    $response = json_decode($response->getContent());
    $nationalities = $response->data->nationalities;
    self::assertCount(249, $nationalities);
  }
}
