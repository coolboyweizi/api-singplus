<?php

namespace FeatureTest\SingPlus\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;
use SingPlus\Support\Helpers\Str;

class ArtistControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        getHots
  //=================================
  public function testGetHotsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistHotOne = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
      'artist_id'     => $artistOne->id,
      'display_order' => 100,
    ]);
    $artistHotTwo = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
      'artist_id'     => $artistTwo->id,
      'display_order' => 200,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/artists/categories');

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $hotArtists = $response->data->hotArtists;
    $categories = $response->data->categories;
    self::assertCount(2, $hotArtists);
    self::assertEquals($artistHotTwo->id, $hotArtists[0]->id);
    self::assertEquals($artistTwo->id, $hotArtists[0]->artistId);
    self::assertEquals($artistTwo->name, $hotArtists[0]->name);
    self::assertEquals($artistHotOne->id, $hotArtists[1]->id);
    self::assertCount(2, $categories);
    self::assertCount(3, $categories[0]);
  }

  public function testGetHotsSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => [],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
    $artistHotOne = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
      'artist_id'     => $artistOne->id,
      'display_order' => 100,
      'country_abbr'  => '-*',
    ]);
    $artistHotTwo = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
      'artist_id'     => $artistTwo->id,
      'display_order' => 200,
      'country_abbr'  => 'TZ',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/artists/categories', [
                      'X-CountryAbbr' => 'IN', 
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $hotArtists = $response->data->hotArtists;
    $categories = $response->data->categories;
    self::assertCount(1, $hotArtists);
    self::assertEquals($artistHotOne->id, $hotArtists[0]->id);
    self::assertCount(2, $categories);
    self::assertCount(3, $categories[0]);
  }

  public function testGetHotsSuccess_Withoutlogin()
  {
        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistHotOne = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
            'artist_id'     => $artistOne->id,
            'display_order' => 100,
        ]);
        $artistHotTwo = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
            'artist_id'     => $artistTwo->id,
            'display_order' => 200,
        ]);

        $response = $this->getJson('v3/artists/categories');

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $hotArtists = $response->data->hotArtists;
        $categories = $response->data->categories;
        self::assertCount(2, $hotArtists);
        self::assertEquals($artistHotTwo->id, $hotArtists[0]->id);
        self::assertEquals($artistTwo->id, $hotArtists[0]->artistId);
        self::assertEquals($artistTwo->name, $hotArtists[0]->name);
        self::assertEquals($artistHotOne->id, $hotArtists[1]->id);
        self::assertCount(2, $categories);
        self::assertCount(3, $categories[0]);
  }

    public function testGetHotsSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => [],
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create();
        $artistHotOne = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
            'artist_id'     => $artistOne->id,
            'display_order' => 100,
            'country_abbr'  => '-*',
        ]);
        $artistHotTwo = factory(\SingPlus\Domains\Musics\Models\ArtistHot::class)->create([
            'artist_id'     => $artistTwo->id,
            'display_order' => 200,
            'country_abbr'  => 'TZ',
        ]);

        $response = $this->getJson('v3/artists/categories', [
                'X-CountryAbbr' => 'IN',
            ]);

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $hotArtists = $response->data->hotArtists;
        $categories = $response->data->categories;
        self::assertCount(1, $hotArtists);
        self::assertEquals($artistHotOne->id, $hotArtists[0]->id);
        self::assertCount(2, $categories);
        self::assertCount(3, $categories[0]);
    }



  //=================================
  //        listArtists
  //=================================
  public function testListArtistsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254', 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'longitude'     => '123.123',
        'latitude'      => '222.222',
        'country_code'  => '86',
      ],
    ]);
    $data = $this->prepareData($user->id);

    // get civil male
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 1, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->four->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Brain',  $artists[0]->name);

    // get civil female
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 2, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->five->id, $artists[0]->artistId);
    self::assertEquals('L', $artists[0]->abbreviation);
    self::assertEquals('Lily',  $artists[0]->name);

    // get civil band
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 3, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->six->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Big Bang',  $artists[0]->name);
  }

  public function testListArtistsSuccess_LocationCountryCodeMissed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'country_code'  => '254', 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareData($user->id);

    // get civil male
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 1, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->one->id, $artists[0]->artistId);
    self::assertEquals('J', $artists[0]->abbreviation);
    self::assertEquals('John', $artists[0]->name);

    // get civil female
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 2, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->two->id, $artists[0]->artistId);
    self::assertEquals('A', $artists[0]->abbreviation);
    self::assertEquals('Amy',  $artists[0]->name);

    // get civil female
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 3, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->three->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Back Street Boys',  $artists[0]->name);

    // get abroad male
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 4, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->four->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Brain',  $artists[0]->name);

    // get abroad female
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 5, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->five->id, $artists[0]->artistId);
    self::assertEquals('L', $artists[0]->abbreviation);
    self::assertEquals('Lily',  $artists[0]->name);

    // get abroad band
    $response = $this->actingAs($user)
                     ->getJson('v3/artists?' . http_build_query([
                        'category'  => 6, 
                     ]))->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->six->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Big Bang',  $artists[0]->name);
  }

  public function testListArtistsSuccess_WithoutLogin()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
        'country_code'  => '254',
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
        'user_id'   => $user->id,
        'location'  => [
            'longitude'     => '123.123',
            'latitude'      => '222.222',
            'country_code'  => '86',
        ],
    ]);
    $data = $this->prepareData($user->id);

    // get civil male
    $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 1,
            ]),['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->four->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Brain',  $artists[0]->name);

    // get civil female
    $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 2,
            ]),['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->five->id, $artists[0]->artistId);
    self::assertEquals('L', $artists[0]->abbreviation);
    self::assertEquals('Lily',  $artists[0]->name);

    // get civil band
    $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 3,
            ]),['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
    $artists = (json_decode($response->getContent()))->data->artists;
    self::assertCount(1, $artists);
    self::assertEquals($data->artist->six->id, $artists[0]->artistId);
    self::assertEquals('B', $artists[0]->abbreviation);
    self::assertEquals('Big Bang',  $artists[0]->name);
  }

    public function testListArtistsSuccess_LocationCountryCodeMissed_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareData($user->id);

        // get civil male
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 1,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->one->id, $artists[0]->artistId);
        self::assertEquals('J', $artists[0]->abbreviation);
        self::assertEquals('John', $artists[0]->name);

        // get civil female
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 2,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->two->id, $artists[0]->artistId);
        self::assertEquals('A', $artists[0]->abbreviation);
        self::assertEquals('Amy',  $artists[0]->name);

        // get civil female
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 3,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->three->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Back Street Boys',  $artists[0]->name);

        // get abroad male
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 4,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->four->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Brain',  $artists[0]->name);

        // get abroad female
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 5,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->five->id, $artists[0]->artistId);
        self::assertEquals('L', $artists[0]->abbreviation);
        self::assertEquals('Lily',  $artists[0]->name);

        // get abroad band
        $response = $this->getJson('v3/artists?' . http_build_query([
                    'category'  => 6,
                ]))->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->six->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Big Bang',  $artists[0]->name);
    }

    public function testListArtistsSuccess_WithCountryAbbr()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareData($user->id);

        // get civil male
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 1,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->four->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Brain', $artists[0]->name);

        // get civil female
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 2,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->five->id, $artists[0]->artistId);
        self::assertEquals('L', $artists[0]->abbreviation);
        self::assertEquals('Lily',  $artists[0]->name);

        // get civil band
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 3,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->six->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Big Bang',  $artists[0]->name);

        // get abroad male
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 4,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->one->id, $artists[0]->artistId);
        self::assertEquals('J', $artists[0]->abbreviation);
        self::assertEquals('John',  $artists[0]->name);

        // get abroad female
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 5,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->two->id, $artists[0]->artistId);
        self::assertEquals('A', $artists[0]->abbreviation);
        self::assertEquals('Amy',  $artists[0]->name);

        // get abroad band
        $response = $this->getJson('v3/artists?' . http_build_query([
                'category'  => 6,
            ]), ['X-CountryAbbr' => 'CN'])->assertJson(['code' => 0]);
        $artists = (json_decode($response->getContent()))->data->artists;
        self::assertCount(1, $artists);
        self::assertEquals($data->artist->three->id, $artists[0]->artistId);
        self::assertEquals('B', $artists[0]->abbreviation);
        self::assertEquals('Back Street Boys',  $artists[0]->name);
    }

  private function prepareData(string $userId)
  {
    $nationalityOne = factory(\SingPlus\Domains\Nationalities\Models\Nationality::class)->create([
      'code'  => '254',
      'name'  => 'Kenya',
    ]);
    $nationalityTwo = factory(\SingPlus\Domains\Nationalities\Models\Nationality::class)->create([
      'code'  => '86',
      'name'  => 'China',
    ]);
    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'John',
      'is_band'   => false,
      'gender'    => 'M',
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03601',
        'ad7145e4ea544535b7087c2869f03601',
      ],
      'nationality' => $nationalityOne->id,
      'abbreviation'  => 'J',
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'Amy',
      'is_band'   => false,
      'gender'    => 'F',
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03602',
        'ad7145e4ea544535b7087c2869f03602',
      ],
      'nationality' => $nationalityOne->id,
      'abbreviation'  => 'A',
    ]);
    $artistThree = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'Back Street Boys',
      'is_band'   => true,
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03603',
        'ad7145e4ea544535b7087c2869f03603',
      ],
      'nationality' => $nationalityOne->id,
      'abbreviation'  => 'B',
    ]);
    $artistFour = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'Brain',
      'is_band'   => false,
      'gender'    => 'M',
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03604',
        'ad7145e4ea544535b7087c2869f03604',
      ],
      'nationality' => $nationalityTwo->id,
      'abbreviation'  => 'b',
    ]);
    $artistFive = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'Lily',
      'is_band'   => false,
      'gender'    => 'F',
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03605',
        'ad7145e4ea544535b7087c2869f03605',
      ],
      'nationality' => $nationalityTwo->id,
      'abbreviation'  => 'l',
    ]);
    $artistSix = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'      => 'Big Bang',
      'is_band'   => true,
      'avatar'  => [
        'ad7145e4ea544535b7087c2869f03606',
        'ad7145e4ea544535b7087c2869f03606',
      ],
      'nationality' => $nationalityTwo->id,
      'abbreviation'  => 'b',
    ]);

    return (object) [
      'nationality' => (object) [
        'one' => $nationalityOne,
        'two' => $nationalityTwo,
      ],
      'artist'  => (object) [
        'one'   => $artistOne,
        'two'   => $artistTwo,
        'three' => $artistThree,
        'four'  => $artistFour,
        'five'  => $artistFive,
        'six'   => $artistSix,
      ],
    ];
  }
}
