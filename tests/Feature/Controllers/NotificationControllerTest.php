<?php

namespace FeatureTest\SingPlus\Controllers;

use Mockery;
use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Contracts\Notifications\Constants\PushMessage as PushMessageConstant;
use SingPlus\Domains\Notifications\Models\PushMessage;

class NotificationControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        bindUserPushAlias
  //=================================
  public function testBindUserPushAliasSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Users\PushAliasBound::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
      'boomsing_push_alias' => 'mmmmmm',
    ]);

    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $this->actingAs($user)
         ->postJson('v3/notification/user/push-alias', [
            'alias' => 'cccccccccc',
         ])
         ->assertJson(['code' => 0]);

    $this->assertDatabaseHas('users', [
      '_id'         => $user->id,
      'push_alias'  => 'abcdefg',
      'boomsing_push_alias'  => 'cccccccccc',
    ]);
  }

  public function testBindUserPushAliasSuccess_AliasNone()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'push_alias'  => 'abcdefg',
      'boomsing_push_alias' => 'dddddddd',
    ]);

    config([
      'tudc.currentChannel' => 'boomsing',
    ]);
    $this->actingAs($user)
         ->postJson('v3/notification/user/push-alias')
         ->assertJson(['code' => 0]);

    $this->assertDatabaseHas('users', [
      '_id'         => $user->id,
      'push_alias'  => 'abcdefg',
      'boomsing_push_alias' => null,
    ]);
  }

  //=================================
  //        getEditorRecommendList
  //=================================
  public function testGetEditorRecommendListSuccess()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ', 'IN'],
    ]);

    $this->mockUserPushInboxFinder();
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
      'created_at'  => \Carbon\Carbon::yesterday(),
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'     => $user->id, 
      'location'    => [
        'abbreviation'  => 'IN',
      ],
    ]);

    $data = $this->preparePushMessageData($user->id);
    $response = $this->actingAs($user)
                     ->getJson('v3/messages/editor-recommends?' . http_build_query([
                        'id'  => $data->messages->four->id,
                     ]))
                     ->assertJson(['code' => 0]);
    $messages = (json_decode($response->getContent()))->data->messages;
    self::assertCount(3, $messages);
    self::assertEquals('new_music', $messages[0]->type);
    self::assertEquals('cccccccccccccccccc', $messages[0]->payload->musicId);
    self::assertEquals('music_sheet', $messages[2]->type);  // all user also fetched
    self::assertEquals('aaaaaaaaaaaaaaaaa', $messages[2]->payload->musicSheetId);
    self::assertTrue(ends_with($messages[2]->payload->cover, 'music-sheet-cover-one'));
  }

  private function preparePushMessageData(string $userId) : \stdClass
  {
    $messageOne = (new PushMessage([
      'user_id'       => '64ca9d1697fe4ca69494b8ba9633b62c',    // all user message
      'type'          => PushMessageConstant::TYPE_MUSIC_SHEET,
      'payload'       => [
                            'music_sheet_id'  => 'aaaaaaaaaaaaaaaaa',
                            'title'           => 'music sheet one',
                            'cover'           => 'music-sheet-cover-one',
                            'text'            => 'music sheet one',
                          ],
      'status'        => 1,
      'country_abbr'  => 'IN',
      'display_order' => 100,
    ]))->selectTable($userId);
    $messageOne->created_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    $messageOne->save();

    $messageTwo = (new PushMessage([
      'user_id'       => $userId,
      'type'          => PushMessageConstant::TYPE_WORK_SHEET,
      'payload'       => [
                            'work_sheet_id'   => 'bbbbbbbbbbbbbbbbb',
                            'title'           => 'work sheet one',
                            'cover'           => 'work-sheet-cover-one',
                            'text'            => 'work sheet one',
                          ],
      'status'        => 1,
      'country_abbr'  => 'IN',
      'display_order' => 200,
    ]))->selectTable($userId);
    $messageTwo->created_at = \Carbon\Carbon::now()->addDays(-20)->format('Y-m-d H:i:s');
    $messageTwo->save();

    $messageThree = (new PushMessage([
      'user_id'       => $userId,
      'type'          => PushMessageConstant::TYPE_NEW_MUSIC,
      'payload'       => [
                            'music_id'  => 'cccccccccccccccccc',
                            'text'      => 'music one',
                          ],
      'status'        => 1,
      'country_abbr'  => 'TZ',
      'display_order' => 300,
    ]))->selectTable($userId);
    $messageThree->created_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    $messageThree->save();

    $messageFour = (new PushMessage([
      'user_id'       => $userId,
      'type'          => PushMessageConstant::TYPE_NEW_WORK,
      'payload'       => [
                            'music_id'  => 'dddddddddddddddddd',
                            'text'      => 'work one',
                          ],
      'status'        => 1,
      'display_order' => 400,
      'country_abbr'  => 'CN',
    ]))->selectTable($userId);
    $messageFour->created_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    $messageFour->save();

    $messageFive = (new PushMessage([
      'user_id'       => '64ca9d1697fe4ca69494b8ba9633b62c',    // all user message
      'type'          => PushMessageConstant::TYPE_MUSIC_SHEET,
      'payload'       => [
                            'music_sheet_id'  => 'eeeeeeeeeeeeeeeeeee',
                            'title'           => 'music sheet five',
                            'cover'           => 'music-sheet-cover-five',
                            'text'            => 'music sheet five',
                          ],
      'status'        => 1,
      'country_abbr'  => 'IN',
      'display_order' => 10,
    ]))->selectTable($userId);
    $messageFive->created_at = \Carbon\Carbon::now()->addDays(-20)->format('Y-m-d H:i:s');
    $messageFive->save();

    $messageSix = (new PushMessage([
      'user_id'       => '64ca9d1697fe4ca69494b8ba9633b62c',    // all user message
      'type'          => PushMessageConstant::TYPE_MUSIC_SHEET,
      'payload'       => [
                            'music_sheet_id'  => 'fffffffffffffffffff',
                            'title'           => 'music sheet six',
                            'cover'           => 'music-sheet-cover-six',
                            'text'            => 'music sheet six',
                          ],
      'status'        => 1,
      'country_abbr'  => 'TZ',
      'display_order' => 100,
    ]))->selectTable($userId);
    $messageSix->created_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
    $messageSix->save();

    return (object) [
      'messages'  => (object) [
        'one'   => $messageOne,
        'two'   => $messageTwo,
        'three' => $messageThree,
        'four'  => $messageFour,
        'five'  => $messageFive,
      ],
    ];
  }

  private function mockUserPushInboxFinder()
  {
    $finder = Mockery::mock(\SingPlus\Support\Notification\UserPushInboxFinder::class);
    $finder->shouldReceive('getCollection')
           ->andReturn('user_push_inbox');
    $this->app[\SingPlus\Support\Notification\UserPushInboxFinder::class] = $finder;
  }
}
