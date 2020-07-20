<?php

namespace FeatureTest\SingPlus\Controllers\H5;

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
                     ->postJson('c/api/help/feedback', [
                        'message' => 'Sing+ is wonderful',
                      ]);
    $response->assertJson(['code' => 0]);
    $this->assertDatabaseHas('feedbacks', [
                    'user_id' => null, 
                    'message' => [
                      'content' => 'Sing+ is wonderful',
                    ]
                  ]);
  }
}
