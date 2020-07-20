<?php

namespace FeatureTest\SingPlus\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class HelpControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        commitFeedback
  //=================================
  public function testCommitFeedbackSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/feedback', [
                        'message' => 'Sing+ is wonderful',
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 1,
                    'message'       => [
                                        'content' => 'Sing+ is wonderful',
                                      ],
                    'status'        => 1,
                    'country_abbr'  => null,
                  ]);
  }

  public function testCommitFeedbackSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/feedback', [
                        'message' => 'Sing+ is wonderful',
                      ], [
                        'X-CountryAbbr' => 'IN', 
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 1,
                    'message'       => [
                                        'content' => 'Sing+ is wonderful',
                                      ],
                    'status'        => 1,
                    'country_abbr'  => '-*',
                  ]);
  }

  public function testCommitFeedbackSuccess_WithoutLogin()
  {

        $response = $this->postJson('v3/help/feedback', [
                'message' => 'Sing+ is wonderful',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('feedbacks', [
            'user_id'       => "",
            'type'          => 1,
            'message'       => [
                'content' => 'Sing+ is wonderful',
            ],
            'status'        => 1,
            'country_abbr'  => null,
        ]);
   }

    public function testCommitFeedbackSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $response = $this->postJson('v3/help/feedback', [
                'message' => 'Sing+ is wonderful',
            ], [
                'X-CountryAbbr' => 'IN',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('feedbacks', [
            'user_id'       => "",
            'type'          => 1,
            'message'       => [
                'content' => 'Sing+ is wonderful',
            ],
            'status'        => 1,
            'country_abbr'  => '-*',
        ]);
    }

  //===================================
  //        commitMusicSearchFeedback
  //===================================
  public function testCommitMusicSearchFeedbackSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/music/search/feedback', [
                        'musicName'   => 'hello world',
                        'artistName'  => 'zhangsan',
                        'other' => 'Sing+ is wonderful',
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 2,
                    'message'       => [
                                        'musicName'   => 'hello world',
                                        'artistName'  => 'zhangsan',
                                        'language'    => '',
                                        'content'     => 'Sing+ is wonderful',
                                      ],
                    'status'        => 1,
                    'country_abbr'  => null,
                  ]);
  }

  public function testCommitMusicSearchFeedbackSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/music/search/feedback', [
                        'musicName'   => 'hello world',
                        'artistName'  => 'zhangsan',
                        'other' => 'Sing+ is wonderful',
                      ], [
                        'X-CountryAbbr' => 'TZ', 
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id' => $user->id,
                    'type'    => 2,
                    'message' => [
                                  'musicName'   => 'hello world',
                                  'artistName'  => 'zhangsan',
                                  'language'    => '',
                                  'content'     => 'Sing+ is wonderful',
                                ],
                    'status'  => 1,
                    'country_abbr'  => 'TZ',
                  ]);
  }

  public function testCommitMusicSearchFeedbackSuccess_WithoutLogin()
  {
      $response = $this->postJson('v3/help/music/search/feedback', [
              'musicName'   => 'hello world',
              'artistName'  => 'zhangsan',
              'other' => 'Sing+ is wonderful',
          ]);
      $response->assertJson(['code' => 0]);
      $this->assertDatabaseHas('feedbacks', [
          'user_id'       => "",
          'type'          => 2,
          'message'       => [
              'musicName'   => 'hello world',
              'artistName'  => 'zhangsan',
              'language'    => '',
              'content'     => 'Sing+ is wonderful',
          ],
          'status'        => 1,
          'country_abbr'  => null,
      ]);
  }

    public function testCommitMusicSearchFeedbackSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);


        $response = $this->postJson('v3/help/music/search/feedback', [
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'other' => 'Sing+ is wonderful',
            ], [
                'X-CountryAbbr' => 'TZ',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('feedbacks', [
            'user_id' => "",
            'type'    => 2,
            'message' => [
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'language'    => '',
                'content'     => 'Sing+ is wonderful',
            ],
            'status'  => 1,
            'country_abbr'  => 'TZ',
        ]);
    }

  //===================================
  //        commitAccompanimentFeedback
  //===================================
  public function testCommitAccompanimentFeedbackSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/music/accompaniment/feedback', [
                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                        'musicName'   => 'hello world',
                        'artistName'  => 'zhangsan',
                        'accompanimentVersion'  => 'v12',
                        'type'        => 1,
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 4,
                    'message'       => [
                                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                                        'musicName'   => 'hello world',
                                        'artistName'  => 'zhangsan',
                                        'accompanimentVersion'  => 'v12',
                                        'type'        => 1,
                                      ],
                    'status'        => 1,
                    'country_abbr'  => null,
                  ]);
  }

  public function testCommitAccompanimentFeedbackSuccess_TypeMissed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/music/accompaniment/feedback', [
                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                        'musicName'   => 'hello world',
                        'artistName'  => 'zhangsan',
                        'accompanimentVersion'  => 'a9993e364706816aba3e25717850c26c9cd0d89d',
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 4,
                    'message'       => [
                                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                                        'musicName'   => 'hello world',
                                        'artistName'  => 'zhangsan',
                                        'accompanimentVersion'  => 'a9993e364706816aba3e25717850c26c9cd0d89d',
                                        'type'        => 0,
                                      ],
                    'status'        => 1,
                    'country_abbr'  => null,
                  ]);
  }

  public function testCommitAccompanimentFeedbackSuccess_FromCountryOperation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/help/music/accompaniment/feedback', [
                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                        'musicName'   => 'hello world',
                        'artistName'  => 'zhangsan',
                        'accompanimentVersion'  => 'v12',
                        'type'        => 1,
                      ], [
                        'X-CountryAbbr' => 'IN', 
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id'       => $user->id,
                    'type'          => 4,
                    'message'       => [
                                        'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                                        'musicName'   => 'hello world',
                                        'artistName'  => 'zhangsan',
                                        'accompanimentVersion'  => 'v12',
                                        'type'        => 1,
                                      ],
                    'status'        => 1,
                    'country_abbr'  => '-*',
                  ]);
  }

  public function testCommitAccompanimentFeedbackSuccess_WithoutLogin()
  {

      $response = $this->postJson('v3/help/music/accompaniment/feedback', [
              'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
              'musicName'   => 'hello world',
              'artistName'  => 'zhangsan',
              'accompanimentVersion'  => 'v12',
              'type'        => 1,
          ]);
      $response->assertJson(['code' => 0]);
      $this->assertDatabaseHas('feedbacks', [
          'user_id'       => "",
          'type'          => 4,
          'message'       => [
              'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
              'musicName'   => 'hello world',
              'artistName'  => 'zhangsan',
              'accompanimentVersion'  => 'v12',
              'type'        => 1,
          ],
          'status'        => 1,
          'country_abbr'  => null,
      ]);
  }

    public function testCommitAccompanimentFeedbackSuccess_TypeMissed_WithoutLogin()
    {

        $response = $this->postJson('v3/help/music/accompaniment/feedback', [
                'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'accompanimentVersion'  => 'a9993e364706816aba3e25717850c26c9cd0d89d',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('feedbacks', [
            'user_id'       => "",
            'type'          => 4,
            'message'       => [
                'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'accompanimentVersion'  => 'a9993e364706816aba3e25717850c26c9cd0d89d',
                'type'        => 0,
            ],
            'status'        => 1,
            'country_abbr'  => null,
        ]);
    }

    public function testCommitAccompanimentFeedbackSuccess_FromCountryOperation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $response = $this->postJson('v3/help/music/accompaniment/feedback', [
                'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'accompanimentVersion'  => 'v12',
                'type'        => 1,
            ], [
                'X-CountryAbbr' => 'IN',
            ]);
        $response->assertJson(['code' => 0]);
        $this->assertDatabaseHas('feedbacks', [
            'user_id'       => "",
            'type'          => 4,
            'message'       => [
                'musicId'     => 'd5a7662ed2054eb782290ffa608ce09d',
                'musicName'   => 'hello world',
                'artistName'  => 'zhangsan',
                'accompanimentVersion'  => 'v12',
                'type'        => 1,
            ],
            'status'        => 1,
            'country_abbr'  => '-*',
        ]);
    }

}
