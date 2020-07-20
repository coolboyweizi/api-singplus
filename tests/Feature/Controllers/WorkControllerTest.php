<?php

namespace FeatureTest\SingPlus\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Cache;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Location;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Contracts\DailyTask\Constants\DailyTask;
use SingPlus\Events\UserTriggerFavouriteWork as UserTriggerFavouriteWorkEvent;

class WorkControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        two-steps upload
  //        createTwoStepUploadTask
  //=================================
  public function testCreateTwoStepUploadTaskSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    self::assertNull($response->data->presigned);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 0,
    ]);

    $task = \SingPlus\Domains\Works\Models\WorkUploadTask::find($response->data->taskId);
    $slides = $task->slides;
    sort($slides);
    self::assertEquals(['image one', 'image two'], $slides);
  }

  public function testCreateTwoStepUploadTaskSuccess_WithChorusStart()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                        'chorusType'    => 1,                   // chorus start
                        'originWorkId'  => '573d283e7fe54c0c8f09716d71e0f0cc',
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    self::assertNull($response->data->presigned);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 0,
      'chorus_type'   => 1,      // chorus start work
    ]);

    $task = \SingPlus\Domains\Works\Models\WorkUploadTask::find($response->data->taskId);
    $slides = $task->slides;
    sort($slides);
    self::assertEquals(['image one', 'image two'], $slides);
    self::assertFalse(isset($task->originWorkId));    // chorus start work hasn't origin work id
  }

  public function testCreateTwoStepUploadTaskSuccess_WithChorusJoin()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $chorusWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'chorus_type' => 1,   // chorus start work
      'status'      => 1,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                        'chorusType'    => 10,                   // chorus start
                        'originWorkId'  => $chorusWork->id,
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    self::assertNull($response->data->presigned);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 0,
      'chorus_type'   => 10,      // chorus start work
      'origin_work_id'  => $chorusWork->id,
    ]);
  }

  public function testCreateTwoStepUploadTaskFailed_WithChorusJoin()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $chorusWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'status'      => 1,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                        'chorusType'    => 10,                   // chorus start
                        'originWorkId'  => $chorusWork->id,
                     ]);

    $response->assertJson(['code' => 10402]);   // chorusWork is not chorus start work
  }

  public function testCreateTwoStepUploadTaskSuccess_NeedClientUploadToS3()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                        'needGetUploadInfo' => true,
                        'secret'        => 1,           // private upload work
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    self::assertNotNull($response->data->presigned);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com', $response->data->presigned->url);
    self::assertCount(2, $response->data->presigned->formData[0]);

    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
      'is_private'    => 1,
    ]);

    $task = \SingPlus\Domains\Works\Models\WorkUploadTask::find($response->data->taskId);
    $slides = $task->slides;
    sort($slides);
    self::assertEquals(['image one', 'image two'], $slides);
  }

  public function testCeateTwoStepUploadTask_UsingDefaultSlides()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 100,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image three',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
      'display_order' => 300,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'cover_images'  => [
        'music-cover-one',
        'music-cover-two',
      ],
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'music-cover-one',     // 默认图片为music cover
      'is_default_cover'  => 1,
      'slides'        => ['image three', 'image one', 'image two'],
      'no_accompaniment'  => 0,
    ]);
  }

  public function testCeateTwoStepUploadTask_UsingFakeMusic()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
      'image' => [
        'default_work_cover'  => 'default_work_cover',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 100,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image three',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_YES,
      'display_order' => 300,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'workName'      => 'God bless me',
                        'duration'      => 354,
                        'description'   => 'hello every one',
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertTrue(is_string($response->data->taskId));
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id'           => $response->data->taskId,
      'status'        => 1,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0',
      'name'          => 'God bless me',
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'default_work_cover',     // 歌曲不存在时，使用默认图
      'is_default_cover'  => 1,
      'slides'        => ['image three', 'image one', 'image two'],
      'no_accompaniment'  => 1,
    ]);
  }

  public function testCreateTwoStepUploadTaskFailed_CoverImageNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload-task', [
                        'musicId'       => $music->id,
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => '8ec8b1454fbe4658b0381e89338fa767',
                     ]);

    $response->assertJson(['code' => 10109]);
  }

  public function testCreateTwoStepUploadTaskFailed_MusicNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $this->actingAs($user)
         ->postJson('v3/works/2step/upload-task', [
            'musicId' => '965bfe9e1a9349bdb75bc2a9be1bb29f',
            'duration'      => 354,
            'description'   => 'hello every one',
            'coverImageId'  => $imageOne->id,
            'slides'        => [
                                  $imageOne->id,
                                  $imageTwo->id,
                                ],
         ])->assertJson(['code' => 10301]);   // music not found
  }

  //=================================
  //        two-steps upload
  //        twoStepUpload
  //=================================
  public function testTwoStepUploadSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'is_private'  => 1,       // private work, not fire workPublished event
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->never()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/work.aac', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == 'works/' . $user->id &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 1,
      'country_abbr'  => null,
    ]);

    $this->assertDatabaseMissing('news',[
       'work_id' => $response->data->workId,
       'status' => 1,
       'display_order' => 100
    ]);

    $work = \SingPlus\Domains\Works\Models\Work::find($response->data->workId);
    $slides = $work->slides;
    sort($slides);
    self::assertEquals(['slide-one'], $slides);

    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $task->id,
    ]);
  }

  public function testTwoStepUploadSuccess_FromCountryOperation()
  { 
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'is_private'  => 1,       // private work, not fire workPublished event
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->never()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/work.aac', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == 'works/' . $user->id &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ], [
                        'X-CountryAbbr' => 'IN', 
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 1,
      'country_abbr'  => '-*',
    ]);


    $this->assertDatabaseMissing('news',[
        'work_id' => $response->data->workId,
        'status' => 1,
        'display_order' => 100
    ]);


    $work = \SingPlus\Domains\Works\Models\Work::find($response->data->workId);
    $slides = $work->slides;
    sort($slides);
    self::assertEquals(['slide-one'], $slides);

    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $task->id,
    ]);
  }

  public function testTwoStepUploadSuccess_ChorusStart()
  { 
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'is_private'  => 1,       // private work, not fire workPublished event
      'chorus_type' => 1,       // chorus start
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->never()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/work.aac', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == 'works/' . $user->id &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));
      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 2,       // waiting chorus start work accompaniment prepare
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 1,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 0,
      ],
    ]);


    $this->assertDatabaseMissing('news',[
        'work_id' => $response->data->workId,
        'status' => 1,
        'display_order' => 100
    ]);

  }

  public function testTwoStepUploadSuccess_ChorusJoin()
  { 
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'is_private'  => 1,       // private work, not fire workPublished event
      'chorus_type' => 10,      // chorus join 
      'origin_work_id'  => 'c44cdacef13849a293a3e164789422af',
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->never()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/work.aac', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == 'works/' . $user->id &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,       // waiting chorus start work accompaniment prepare
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'is_default_cover'  => 0,
      'no_accompaniment'  => 0,
      'is_private'    => 1,
      'chorus_type'   => 10,
      'chorus_join_info' => [
        'origin_work_id'  => 'c44cdacef13849a293a3e164789422af',
      ],
    ]);


    $this->assertDatabaseMissing('news',[
        'work_id' => $response->data->workId,
        'status' => 1,
        'display_order' => 100
    ]);


  }

  public function testTwoStepUploadSuccess_WithTaskResource()
  { 
    $this->expectsEvents(\SingPlus\Events\Works\WorkPublished::class);
    $this->expectsEvents(\SingPlus\Events\Works\WorkUpdateTags::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'name'      => 'God bless me',
      'duration'  => 250,
      'cover'     => 'work-cover',
      'is_default_cover'  => 1,
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'resource'    => 'a\b\c',
      'no_accompaniment'  => 1,
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->once()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->twice()
         ->with('counter')
         ->andReturn($counterMock);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('has')
                   ->once()
                   ->with($task->resource)
                   ->andReturn(true);

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with('a\b\c')
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

    $dailyTaskService = $this->mockDailyTaskService();
    $dailyTaskService->shouldReceive('resetDailyTaskLists')
        ->once()
        ->with($user->id, null)
        ->andReturn();
    $dailyTaskService->shouldReceive('finisheDailyTask')
        ->once()
        ->with($user->id, DailyTask::TYPE_PUBLISH)
        ->andReturn();

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'name'          => 'God bless me',
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'is_default_cover'  => 1,
      'resource'      => 'a\b\c',
      'no_accompaniment'  => 1,
    ]);


    $this->assertDatabaseHas('news',[
        'user_id'=>$user->id,
        'detail' => ['work_id' =>$response->data->workId],
        'status' => 1,
        'display_order' => 100
    ]);


      $work = \SingPlus\Domains\Works\Models\Work::find($response->data->workId);
    $slides = $work->slides;
    sort($slides);
    self::assertEquals(['slide-one'], $slides);

    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $task->id,
    ]);
  }

  public function testTwoStepUploadSuccess_WithTaskResourceAndUpload()
  { 
    $this->expectsEvents(\SingPlus\Events\Works\WorkPublished::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'resource'    => 'a\b\c',
      'no_accompaniment'  => 0,
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    $counterMock->shouldReceive('increment')
        ->once()
        ->with('news', 100)
        ->andReturn(100);
    Cache::shouldReceive('driver')
         ->twice()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/work.aac', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('has')
                   ->once()
                   ->with($task->resource)
                   ->andReturn(true);
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == 'works/' . $user->id &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

      $dailyTaskService = $this->mockDailyTaskService();
      $dailyTaskService->shouldReceive('resetDailyTaskLists')
          ->once()
          ->with($user->id, null)
          ->andReturn();
      $dailyTaskService->shouldReceive('finisheDailyTask')
          ->once()
          ->with($user->id, DailyTask::TYPE_PUBLISH)
          ->andReturn();

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hh',
      'duration'      => 250,
      'cover'         => 'work-cover',
      'no_accompaniment'  => 0,
    ]);


    $this->assertDatabaseHas('news',[
        'user_id'=>$user->id,
        'detail' => ['work_id' =>$response->data->workId],
        'status' => 1,
        'display_order' => 100
    ]);


      $work = \SingPlus\Domains\Works\Models\Work::find($response->data->workId);
    $slides = $work->slides;
    sort($slides);
    self::assertEquals(['slide-one'], $slides);

    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $task->id,
    ]);
  }

  public function testTwoStepUploadFailed_TaskNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => '1f8986c79bef480ab967d02fd7241061',    // other user
      'music_id'  => '15b35dd52f0d46499b2d0b58f596f4cd',
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
    ]);
    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);
    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id, [
                        'work'          => $this->makeUploadFile($workPath),
                     ]);

    $response->assertJson(['code' => 10405]);     // upload work not exists
  }

  public function testTwoStepUploadFailed_WithoutTaskResourceAndUpload()
  { 
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $task = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id,
      'duration'  => 250,
      'cover'     => 'work-cover',
      'slides'    => [
        'slide-one',
      ],
      'description' => 'hh',
      'resource'    => 'a\b\c',
    ]);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('has')
                   ->once()
                   ->with($task->resource)
                   ->andReturn(false);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/' . $task->id);

    $response->assertJson(['code' => 10401]);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $task->id,
    ]);
  }

  public function testTwoStepUploadFailed_MimeNotAllowed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $workPath = sprintf('%s/test_datas/tesla_logo.jpg', __DIR__);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/2step/upload/71402ec0a1cb4d6ea00134a15e001e30', [
                        'work'    => $this->makeUploadFile($workPath),
                     ]);
    $response->assertJson(['code' => 10001]);   // input validation violated
  }

  //=================================
  //        upload
  //=================================
  public function testUploadSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    config([
      'storage.buckets.works' => 'works',
    ]);

    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == sprintf('works/%s', $user->id, $music->id) &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

    $response = $this->actingAs($user)
                     ->postJson('v3/works/upload', [
                        'musicId'       => $music->id,
                        'work'          => $this->makeUploadFile($workPath),
                        'clientId'      => 'c95e0c2f42a142689ee46611952304c6',
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
    ], 'mongodb');

    $work = \SingPlus\Domains\Works\Models\Work::find($response->data->workId);
    $slides = $work->slides;
    sort($slides);
    self::assertEquals(['image one', 'image two'], $slides);
  }

  public function testUploadSuccess_UsingDefaultSlides()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'is_avatar'     => \SingPlus\Domains\Users\Models\UserImage::AVATAR_NO,
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('works', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(
                    $workPath,
                    Mockery::on(function (array $options) use ($user, $music) {
                      return isset($options['prefix']) &&
                             $options['prefix'] == sprintf('works/%s', $user->id) &&
                             isset($options['mime']);
                    }))
                   ->andReturn(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id));

    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('works/%s/0cafceb6a88749a99219532c1e4608b3', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/xxxxxxxx'));

    $response = $this->actingAs($user)
                     ->postJson('v3/works/upload', [
                        'musicId'       => $music->id,
                        'work'          => $this->makeUploadFile($workPath),
                        'clientId'      => 'c95e0c2f42a142689ee46611952304c6',
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals('http://image.sing.plus/xxxxxxxx', $response->data->url);
    self::assertTrue(is_string($response->data->workId));
    $this->assertDatabaseHas('works', [
      '_id'           => $response->data->workId,
      'display_order' => 100,
      'status'        => 1,
      'description'   => 'hello every one',
      'duration'      => 354,
      'cover'         => 'image one',
      'is_default_cover'  => 0,
      'slides'        => ['image one', 'image two'],
    ], 'mongodb');
  }

  public function testUploadFailed_CoverImageNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();

    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);
    $response = $this->actingAs($user)
                     ->postJson('v3/works/upload', [
                        'musicId'       => $music->id,
                        'work'          => $this->makeUploadFile($workPath),
                        'clientId'      => 'c95e0c2f42a142689ee46611952304c6',
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => '8ec8b1454fbe4658b0381e89338fa767',
                     ]);

    $response->assertJson(['code' => 10109]);
  }

  public function testUploadFailed_MimeNotAllowed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $workPath = sprintf('%s/test_datas/tesla_logo.jpg', __DIR__);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/upload', [
                        'musicId' => $music->id,
                        'work'    => $this->makeUploadFile($workPath),
                        'clientId'  => 'c95e0c2f42a142689ee46611952304c6',
                        'duration'      => 354,
                        'description'   => 'hello every one',
                        'coverImageId'  => $imageOne->id,
                        'slides'        => [
                                              $imageOne->id,
                                              $imageTwo->id,
                                            ],
                     ]);
    $response->assertJson(['code' => 10001]);   // input validation violated
  }

  public function testUploadFailed_MusicNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);

    $this->actingAs($user)
         ->postJson('v3/works/upload', [
            'musicId' => '965bfe9e1a9349bdb75bc2a9be1bb29f',
            'work'    => $this->makeUploadFile($workPath),
            'clientId'  => 'c95e0c2f42a142689ee46611952304c6',
            'duration'      => 354,
            'description'   => 'hello every one',
            'coverImageId'  => $imageOne->id,
            'slides'        => [
                                  $imageOne->id,
                                  $imageTwo->id,
                                ],
         ])->assertJson(['code' => 10301]);   // music not found
  }

  public function testUploadFailed_WorkAreadyUploaded()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image one',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'       => $user->id,
      'uri'           => 'image two',
      'display_order' => 100,
    ]);
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'client_id' => '7b3a2c7503d04f4f9dd658494933c999',
    ]);

    $workPath = sprintf('%s/test_datas/cold-cold.mp3', __DIR__);

    $this->actingAs($user)
         ->postJson('v3/works/upload', [
            'musicId' => '965bfe9e1a9349bdb75bc2a9be1bb29f',
            'work'    => $this->makeUploadFile($workPath),
            'clientId'  => '7b3a2c7503d04f4f9dd658494933c999',
            'duration'      => 354,
            'description'   => 'hello every one',
            'coverImageId'  => $imageOne->id,
            'slides'        => [
                                  $imageOne->id,
                                  $imageTwo->id,
                                ],
         ])->assertJson(['code' => 10403]);   // music not found
  }

  //=================================
  //        getWorkUploadStatus
  //=================================
  public function testGetWorkUploadStatusSuccess_Uploaded()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'client_id' => '7b3a2c7503d04f4f9dd658494933c999',
    ]);

    $this->actingAs($user)
         ->getJson('v3/works/upload/status?' . http_build_query([
            'clientId'  => $work->client_id,
         ]))
         ->assertJson([
            'code'  => 0,
            'data'  => [
              'isFinished'  => true,
              'workId'      => $work->id,
            ],
         ]);
  }

  public function testGetWorkUploadStatusSuccess_NotUploaded()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $this->actingAs($user)
         ->getJson('v3/works/upload/status?' . http_build_query([
            'clientId'  => '7b3a2c7503d04f4f9dd658494933c999',
         ]))
         ->assertJson([
            'code'  => 0,
            'data'  => [
              'isFinished'  => false,
              'workId'      => null,
            ],
         ]);
  }

  //=================================
  //        getSelections
  //=================================
  public function testGetSelectionsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();
    $data->work->one->is_default_cover = 1;
    $data->work->one->save();
    $privateWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0', 
      'name'          => 'God bless me. private',
      'display_order' => 9000000000000,
      'is_private'    => 1,
    ]);
    $selectThree = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id'       => $privateWork->id,
      'display_order' => 100000000,
    ]);

    $userFollowing = factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'     => $user->id,
      'followings'  => [
        $data->user->two->id,
      ],
      'following_details' => [
        [
          'user_id'     => $data->user->two->id,
          'follow_at'   => \Carbon\Carbon::parse('2016-05-01')->getTimestamp(),
        ],
      ],
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(1888);

    $musicOne = $data->music->one;
    $musicOne->status = -1;
    $musicOne->save();

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-05-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections');
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(2, $selections);      // private work not output
    self::assertEquals($data->user->two->id, $selections[0]->userId);   // order by display_order
    self::assertEquals($data->work->two->id, $selections[0]->workId);
    self::assertFalse($selections[0]->verified->verified);
    self::assertEquals(1888, $selections[0]->listenNum);
    self::assertTrue(ends_with($selections[0]->avatar, 'avatar-two'));
    self::assertEquals('lisi', $selections[0]->nickname);
    self::assertEquals('musicTwo', $selections[0]->musicName);
    self::assertTrue(ends_with($selections[0]->cover, 'work-cover-two'));
    self::assertTrue(ends_with($selections[0]->resource, 'work-two'));
    self::assertEquals(1, $selections[0]->chorusType);
    self::assertEquals(123, $selections[0]->chorusCount);
    self::assertTrue($selections[0]->author->isFollowing);
    self::assertFalse($selections[0]->author->isFollower);
    self::assertTrue(ends_with($selections[1]->cover, 'work-cover-one'));
    self::assertNull($selections[1]->chorusType);
    self::assertEquals(0, $selections[1]->chorusCount);
  }

  public function testGetSelectionsSuccess_StartJoin()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user',
      'nickname'  => 'login-user',
    ]);
    $data = $this->prepareWorks();
    $data->work->one->is_default_cover = 1;
    $data->work->one->save();
    $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => $data->work->two->music_id, 
      'cover'         => 'work-join-cover',
      'name'          => 'God bless me',
      'chorus_type'   => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $data->work->two->id,
      ],
      'display_order' => 9000000000000,
    ]);
    $selectThree = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id'       => $workChorusJoin->id,
      'display_order' => 100000000,
    ]);

    factory(\SingPlus\Domains\Users\Models\UserVerification::class)->create([
        'profile_id'    => $profile->id,
        'user_id'       => $user->id,
        'verified_as'   => ['A', 'B'],
        'status'        => 1,
    ]);

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $workChorusJoin->id))
         ->andReturn(2000);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-05-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections');
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(3, $selections);      // private work not output
    self::assertEquals($user->id, $selections[0]->userId);   // order by display_order
    self::assertTrue($selections[0]->verified->verified);
    self::assertEquals(['A', 'B'], $selections[0]->verified->names);
    self::assertEquals($workChorusJoin->id, $selections[0]->workId);
    self::assertEquals(2000, $selections[0]->listenNum);
    self::assertTrue(ends_with($selections[0]->avatar, 'login-user'));
    self::assertEquals('login-user', $selections[0]->nickname);
    self::assertEquals('God bless me', $selections[0]->musicName);
    self::assertTrue(ends_with($selections[0]->cover, 'work-join-cover'));
    self::assertEquals(10, $selections[0]->chorusType);
    self::assertEquals(0, $selections[0]->chorusCount);
    self::assertEquals($data->user->two->id, $selections[0]->originWorkUser->userId);
    self::assertEquals('lisi', $selections[0]->originWorkUser->nickname);
    self::assertTrue(ends_with($selections[1]->cover, 'work-cover-two'));
    self::assertEquals(1, $selections[1]->chorusType);
    self::assertEquals(123, $selections[1]->chorusCount);
  }

  public function testGetSelectionsSuccess_WithFakeMusic()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(1888);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => '99a8ab1b34764481bd642534b615d7b0', 
      'name'      => 'God bless me',
    ]);
    $select = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id'       => $work->id,
      'display_order' => 100,
    ]);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', '99a8ab1b34764481bd642534b615d7b0'))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(1888);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2016-05-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections');
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(3, $selections);
    self::assertEquals($data->user->two->id, $selections[0]->userId);   // order by display_order
    self::assertEquals($data->work->two->id, $selections[0]->workId);
    self::assertEquals(1888, $selections[0]->listenNum);
    self::assertTrue(ends_with($selections[0]->avatar, 'avatar-two'));
    self::assertEquals('lisi', $selections[0]->nickname);
    self::assertEquals('musicTwo', $selections[0]->musicName);
    self::assertTrue(ends_with($selections[0]->cover, 'work-cover-two'));
    self::assertEquals('God bless me', $selections[2]->musicName);
  }

  public function testGetSelectionsSuccess_Pagination()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections?' . http_build_query([
                          'selectionId' => $data->selections->two->id,
                          'isNext'      => true,
                          'size'        => 1,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(1, $selections);
    self::assertEquals($data->user->one->id, $selections[0]->userId);   // order by display_order
    self::assertEquals($data->work->one->id, $selections[0]->workId);
    self::assertEquals(25, $selections[0]->listenNum);
    self::assertTrue(ends_with($selections[0]->avatar, 'avatar-one'));
    self::assertEquals('zhangsan', $selections[0]->nickname);
    self::assertEquals('musicOne "hell"', $selections[0]->musicName);
    self::assertTrue(ends_with($selections[0]->cover, 'work-cover-one'));
  }

  public function testGetSelectionsSuccess_CountryOperation_FromProfile()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'abbreviation'  => 'TZ',
      ],
    ]);
    $data = $this->prepareWorks();

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(25);
    // ip2nation not triggered, cause user profile location exists
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('ip2nation:%s', '127.0.0.1'));

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections?' . http_build_query([
                          'size'        => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(1, $selections);
    // only country_abbr selection record fetched
    self::assertEquals($data->work->two->id, $selections[0]->workId);
    // X-CountryAbbr must come from user profile
    $request = app()->make(\Illuminate\Http\Request::class);
    self::assertEquals('TZ', $request->headers->get('X-CountryAbbr'));
  }

  public function testGetSelectionsSuccess_CountryOperation_FromHeaders()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'location'  => [
        'abbreviation'  => 'TZ',
      ],
    ]);
    $data = $this->prepareWorks();

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    // ip2nation not triggered, cause user profile location exists
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('ip2nation:%s', '127.0.0.1'));

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections?' . http_build_query([
                          'size'        => 2,
                     ]), [
                        'X-CountryAbbr' => 'IN', 
                     ]);
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(1, $selections);
    // only country_abbr -* selection record fetched
    self::assertEquals($data->work->one->id, $selections[0]->workId);
    // X-CountryAbbr must come from headers, IN not in config, so -* fetched
    $request = app()->make(\Illuminate\Http\Request::class);
    self::assertEquals('-*', $request->headers->get('X-CountryAbbr'));
  }

  public function testGetSelectionsSuccess_CountryOperation_FromLocation()
  {
    $this->enableNationOperationMiddleware();
    config([
      'nationality.operation_country_abbr'  => ['TZ'],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);
    $data = $this->prepareWorks();

    $position = new \Stevebauman\Location\Position();
    $position->countryCode = 'IN';
    Location::shouldReceive('get')
            ->once()
            ->with('127.0.0.1')
            ->andReturn($position);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    // ip2nation not triggered, cause user profile location exists
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('ip2nation:%s', '127.0.0.1'))
         ->andReturn(null);
    Cache::shouldReceive('put')
         ->once()
         ->with(sprintf('ip2nation:%s', '127.0.0.1'), 'IN', 30);
    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/selections?' . http_build_query([
                          'size'        => 2,
                     ]));
    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $selections = $response->data->selections;
    self::assertCount(1, $selections);
    // only country_abbr -* selection record fetched
    self::assertEquals($data->work->one->id, $selections[0]->workId);
    // X-CountryAbbr must come from headers, IN not in config, so -* fetched
    $request = app()->make(\Illuminate\Http\Request::class);
    self::assertEquals('-*', $request->headers->get('X-CountryAbbr'));
  }

  public function testGetSelectionSuccess_WithoutLogin()
  {
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $user->id,
      ]);
      $data = $this->prepareWorks();
      $data->work->one->is_default_cover = 1;
      $data->work->one->save();
      $privateWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id'       => $user->id,
          'music_id'      => '99a8ab1b34764481bd642534b615d7b0',
          'name'          => 'God bless me. private',
          'display_order' => 9000000000000,
          'is_private'    => 1,
      ]);
      $selectThree = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
          'work_id'       => $privateWork->id,
          'display_order' => 100000000,
      ]);

      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
          ->andReturn(123);
      Cache::shouldReceive('get')
          ->once()
          ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
          ->andReturn(124);
      Cache::shouldReceive('get')
          ->with(sprintf('work:%s:listennum', $data->work->one->id))
          ->andReturn(25);
      Cache::shouldReceive('get')
          ->with(sprintf('work:%s:listennum', $data->work->two->id))
          ->andReturn(1888);

        $this->mockHttpClient(json_encode([
            'code'  => 0,
            'data'  => [
                'relation'  => [
                    $data->user->one->id    => [
                        'is_following'=> false,
                        'follow_at'=> null,
                        'is_follower'=> false,
                        'followed_at'=> null,
                    ],
                    $data->user->two->id    => [
                        'is_following'=> true,
                        'follow_at'=> '2016-05-01 00:00:00',
                        'is_follower'=> false,
                        'followed_at'=> null,
                    ],
                ],
            ],
        ]));

      $response = $this->getJson('v3/works/selections');
      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $selections = $response->data->selections;
      self::assertCount(2, $selections);      // private work not output
      self::assertEquals($data->user->two->id, $selections[0]->userId);   // order by display_order
      self::assertEquals($data->work->two->id, $selections[0]->workId);
      self::assertEquals(1888, $selections[0]->listenNum);
      self::assertTrue(ends_with($selections[0]->avatar, 'avatar-two'));
      self::assertEquals('lisi', $selections[0]->nickname);
      self::assertEquals('musicTwo', $selections[0]->musicName);
      self::assertTrue(ends_with($selections[0]->cover, 'work-cover-two'));
      self::assertTrue(ends_with($selections[0]->resource, 'work-two'));
      self::assertEquals(1, $selections[0]->chorusType);
      self::assertEquals(123, $selections[0]->chorusCount);
      self::assertTrue(ends_with($selections[1]->cover, 'work-cover-one'));
      self::assertNull($selections[1]->chorusType);
      self::assertEquals(0, $selections[1]->chorusCount);
  }

    public function testGetSelectionsSuccess_StartJoin_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-user',
            'nickname'  => 'login-user',
        ]);
        $data = $this->prepareWorks();
        $data->work->one->is_default_cover = 1;
        $data->work->one->save();
        $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => $user->id,
            'music_id'      => $data->work->two->music_id,
            'cover'         => 'work-join-cover',
            'name'          => 'God bless me',
            'chorus_type'   => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $data->work->two->id,
            ],
            'display_order' => 9000000000000,
        ]);
        $selectThree = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
            'work_id'       => $workChorusJoin->id,
            'display_order' => 100000000,
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
            ->andReturn(124);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->two->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $workChorusJoin->id))
            ->andReturn(2000);

        $response = $this->getJson('v3/works/selections');
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $selections = $response->data->selections;
        self::assertCount(3, $selections);      // private work not output
        self::assertEquals($user->id, $selections[0]->userId);   // order by display_order
        self::assertEquals($workChorusJoin->id, $selections[0]->workId);
        self::assertEquals(2000, $selections[0]->listenNum);
        self::assertTrue(ends_with($selections[0]->avatar, 'login-user'));
        self::assertEquals('login-user', $selections[0]->nickname);
        self::assertEquals('God bless me', $selections[0]->musicName);
        self::assertTrue(ends_with($selections[0]->cover, 'work-join-cover'));
        self::assertEquals(10, $selections[0]->chorusType);
        self::assertEquals(0, $selections[0]->chorusCount);
        self::assertEquals($data->user->two->id, $selections[0]->originWorkUser->userId);
        self::assertEquals('lisi', $selections[0]->originWorkUser->nickname);
        self::assertTrue(ends_with($selections[1]->cover, 'work-cover-two'));
        self::assertEquals(1, $selections[1]->chorusType);
        self::assertEquals(123, $selections[1]->chorusCount);
    }

    public function testGetSelectionsSuccess_WithFakeMusic_WithoutLogin()
    {
        config([
            'business-logic.fakemusic'  => [
                'id'    => '99a8ab1b34764481bd642534b615d7b0',
                'name'  => 'without accompaniment',
            ],
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareWorks();

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
            ->andReturn(124);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->two->id))
            ->andReturn(1888);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $user->id,
            'music_id'  => '99a8ab1b34764481bd642534b615d7b0',
            'name'      => 'God bless me',
        ]);
        $select = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
            'work_id'       => $work->id,
            'display_order' => 100,
        ]);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', '99a8ab1b34764481bd642534b615d7b0'))
            ->andReturn(124);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(1888);

        $response = $this->getJson('v3/works/selections');
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $selections = $response->data->selections;
        self::assertCount(3, $selections);
        self::assertEquals($data->user->two->id, $selections[0]->userId);   // order by display_order
        self::assertEquals($data->work->two->id, $selections[0]->workId);
        self::assertEquals(1888, $selections[0]->listenNum);
        self::assertTrue(ends_with($selections[0]->avatar, 'avatar-two'));
        self::assertEquals('lisi', $selections[0]->nickname);
        self::assertEquals('musicTwo', $selections[0]->musicName);
        self::assertTrue(ends_with($selections[0]->cover, 'work-cover-two'));
        self::assertEquals('God bless me', $selections[2]->musicName);
    }

    public function testGetSelectionsSuccess_Pagination_WithoutLogin()
    {
        $data = $this->prepareWorks();

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);

        $response = $this->getJson('v3/works/selections?' . http_build_query([
                    'selectionId' => $data->selections->two->id,
                    'isNext'      => true,
                    'size'        => 1,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $selections = $response->data->selections;
        self::assertCount(1, $selections);
        self::assertEquals($data->user->one->id, $selections[0]->userId);   // order by display_order
        self::assertEquals($data->work->one->id, $selections[0]->workId);
        self::assertEquals(25, $selections[0]->listenNum);
        self::assertTrue(ends_with($selections[0]->avatar, 'avatar-one'));
        self::assertEquals('zhangsan', $selections[0]->nickname);
        self::assertEquals('musicOne "hell"', $selections[0]->musicName);
        self::assertTrue(ends_with($selections[0]->cover, 'work-cover-one'));
    }

    public function testGetSelectionsSuccess_CountryOperation_FromHeaders_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $data = $this->prepareWorks();

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);
        // ip2nation not triggered, cause user profile location exists
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('ip2nation:%s', '127.0.0.1'));

        $response = $this->getJson('v3/works/selections?' . http_build_query([
                    'size'        => 2,
                ]), [
                'X-CountryAbbr' => 'IN',
            ]);
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $selections = $response->data->selections;
        self::assertCount(1, $selections);
        // only country_abbr -* selection record fetched
        self::assertEquals($data->work->one->id, $selections[0]->workId);
        // X-CountryAbbr must come from headers, IN not in config, so -* fetched
        $request = app()->make(\Illuminate\Http\Request::class);
        self::assertEquals('-*', $request->headers->get('X-CountryAbbr'));
    }

    public function testGetSelectionsSuccess_CountryOperation_FromLocation_WithoutLogin()
    {
        $this->enableNationOperationMiddleware();
        config([
            'nationality.operation_country_abbr'  => ['TZ'],
        ]);

        $data = $this->prepareWorks();

        $position = new \Stevebauman\Location\Position();
        $position->countryCode = 'IN';
        Location::shouldReceive('get')
            ->once()
            ->with('127.0.0.1')
            ->andReturn($position);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);
        // ip2nation not triggered, cause user profile location exists
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('ip2nation:%s', '127.0.0.1'))
            ->andReturn(null);
        Cache::shouldReceive('put')
            ->once()
            ->with(sprintf('ip2nation:%s', '127.0.0.1'), 'IN', 30);

        $response = $this->getJson('v3/works/selections?' . http_build_query([
                    'size'        => 2,
                ]));
        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $selections = $response->data->selections;
        self::assertCount(1, $selections);
        // only country_abbr -* selection record fetched
        self::assertEquals($data->work->one->id, $selections[0]->workId);
        // X-CountryAbbr must come from headers, IN not in config, so -* fetched
        $request = app()->make(\Illuminate\Http\Request::class);
        self::assertEquals('-*', $request->headers->get('X-CountryAbbr'));
    }

  //=================================
  //        getLatests
  //=================================
  public function testGetLatestsSuccess()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-avatar',
      'nickname'  => 'login-avatar',
    ]);
    $data = $this->prepareWorks();
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $data->user->one,
      'followings'        => [$user->id],
      'following_details' => [
        [
          'user_id'   => $user->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
        ]
      ]
    ]);
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $user->id,
      'followings'        => [$data->user->two->id],
      'following_details' => [
        [
          'user_id'   => $data->user->two->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-12')->getTimestamp(),
        ]
      ]
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(123);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(25);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->two->id))
         ->andReturn(1888);

    $musicOne = $data->music->one;
    $musicOne->status = -1;
    $musicOne->save();

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => '99a8ab1b34764481bd642534b615d7b0', 
      'name'      => 'God bless me',
      'display_order' => 1000000000000,
      'resource'      => 'work-local',
      'is_private'    => 0,
    ]);
    $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->work->two->music_id, 
      'name'      => 'God bless me',
      'display_order' => 1,
      'resource'      => 'work-local',
      'is_private'    => 0,
      'chorus_type'   => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
      'chorus_join_info'  => [
        'origin_work_id'  => $work->id,
      ],
    ]);
    $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0', 
      'name'          => 'God bless me. private',
      'display_order' => 9000000000000,
      'is_private'    => 1,
      'resource'      => 'work-local-private',
    ]);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', '99a8ab1b34764481bd642534b615d7b0'))
         ->andReturn(124);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $workChorusJoin->id))
         ->andReturn(1889);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/latest');

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $works = $response->data->latests;
    self::assertCount(4, $works);
    self::assertEquals('God bless me', $works[0]->musicName);
    self::assertNull($works[0]->chorusType);
    self::assertEquals($data->work->two->id, $works[1]->workId); // order by display order desc
    self::assertEquals('lisi', $works[1]->nickname);
    self::assertTrue(ends_with($works[1]->cover, 'work-cover-two'));
    self::assertEquals(1888, $works[1]->listenNum);
    self::assertTrue($works[1]->author->isFollowing);
    self::assertFalse($works[1]->author->isFollower);
    self::assertEquals(1, $works[1]->chorusType);
    self::assertEquals(123, $works[1]->chorusCount);
    self::assertFalse($works[2]->author->isFollowing);
    self::assertTrue($works[2]->author->isFollower);
    self::assertNull($works[2]->chorusType);
    self::assertNull($works[2]->originWorkUser);
    self::assertEquals(10, $works[3]->chorusType);
    self::assertEquals(0, $works[3]->chorusCount);
    self::assertEquals($user->id, $works[3]->originWorkUser->userId);
  }

    public function testGetLatestsSuccess_WithoutLogin()
    {
        config([
            'business-logic.fakemusic'  => [
                'id'    => '99a8ab1b34764481bd642534b615d7b0',
                'name'  => 'without accompaniment',
            ],
        ]);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-avatar',
            'nickname'  => 'login-avatar',
        ]);
        $data = $this->prepareWorks();
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $data->user->one,
            'followings'        => [$user->id],
            'following_details' => [
                [
                    'user_id'   => $user->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $user->id,
            'followings'        => [$data->user->two->id],
            'following_details' => [
                [
                    'user_id'   => $data->user->two->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-12')->getTimestamp(),
                ]
            ]
        ]);

        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(123);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->two->music_id))
            ->andReturn(124);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(25);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->two->id))
            ->andReturn(1888);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $user->id,
            'music_id'  => '99a8ab1b34764481bd642534b615d7b0',
            'name'      => 'God bless me',
            'display_order' => 1000000000000,
            'resource'      => 'work-local',
            'is_private'    => 0,
        ]);
        $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $user->id,
            'music_id'  => $data->work->two->music_id,
            'name'      => 'God bless me',
            'display_order' => 1,
            'resource'      => 'work-local',
            'is_private'    => 0,
            'chorus_type'   => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
            'chorus_join_info'  => [
                'origin_work_id'  => $work->id,
            ],
        ]);
        $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => $user->id,
            'music_id'      => '99a8ab1b34764481bd642534b615d7b0',
            'name'          => 'God bless me. private',
            'display_order' => 9000000000000,
            'is_private'    => 1,
            'resource'      => 'work-local-private',
        ]);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', '99a8ab1b34764481bd642534b615d7b0'))
            ->andReturn(124);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $workChorusJoin->id))
            ->andReturn(1889);

        $response = $this->getJson('v3/works/latest');

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $works = $response->data->latests;
        self::assertCount(4, $works);
        self::assertEquals('God bless me', $works[0]->musicName);
        self::assertNull($works[0]->chorusType);
        self::assertEquals($data->work->two->id, $works[1]->workId); // order by display order desc
        self::assertEquals('lisi', $works[1]->nickname);
        self::assertTrue(ends_with($works[1]->cover, 'work-cover-two'));
        self::assertEquals(1888, $works[1]->listenNum);
        self::assertFalse($works[1]->author->isFollowing);
        self::assertFalse($works[1]->author->isFollower);
        self::assertEquals(1, $works[1]->chorusType);
        self::assertEquals(123, $works[1]->chorusCount);
        self::assertFalse($works[2]->author->isFollowing);
        self::assertFalse($works[2]->author->isFollower);
        self::assertNull($works[2]->chorusType);
        self::assertNull($works[2]->originWorkUser);
        self::assertEquals(10, $works[3]->chorusType);
        self::assertEquals(0, $works[3]->chorusCount);
        self::assertEquals($user->id, $works[3]->originWorkUser->userId);
    }

  //=================================
  //        getUserWorks
  //=================================
  public function testGetUserWorksSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareWorks($user);

    $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0', 
      'name'          => 'God bless me. private',
      'display_order' => 9000000000000,
      'is_private'    => 1,
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->work->two->music_id, 
      'name'      => 'God bless me',
      'display_order' => 1,
      'resource'      => 'work-local',
      'is_private'    => 0,
      'chorus_type'   => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
      'chorus_join_info'  => [
        'origin_work_id'  => $data->work->two->id,
      ],
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/user/works');

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $works = $response->data->works;
    self::assertCount(3, $works);
    self::assertEquals($workPrivate->id, $works[0]->workId);     // private work
    self::assertTrue($works[0]->isPrivate);
    self::assertEquals($data->work->one->id, $works[1]->workId); // order by display order desc
    self::assertTrue(ends_with($works[1]->cover, 'work-cover-one'));
    self::assertFalse($works[1]->isPrivate);
    self::assertNull($works[1]->originWorkUser);
    self::assertEquals(10, $works[2]->chorusType);
    self::assertEquals($data->user->two->id, $works[2]->originWorkUser->userId);
    self::assertEquals('lisi', $works[2]->originWorkUser->nickname);
  }

  public function testGetUserWorksSuccess_FetchDeletedMusic()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareWorks($user);

    $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0', 
      'name'          => 'God bless me. private',
      'display_order' => 9000000000000,
      'is_private'    => 1,
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->work->two->music_id, 
      'name'      => 'God bless me',
      'display_order' => 1,
      'resource'      => 'work-local',
      'is_private'    => 0,
      'chorus_type'   => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_JOIN,
      'chorus_join_info'  => [
        'origin_work_id'  => $data->work->two->id,
      ],
    ]);
    // set delete flag for music two
    $musicTwo = $data->music->two;
    $musicTwo->status = -1;
    $musicTwo->save();

    $response = $this->actingAs($user)
                     ->getJson('v3/user/works');

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $works = $response->data->works;
    self::assertCount(3, $works);
    self::assertEquals($workPrivate->id, $works[0]->workId);     // private work
    self::assertTrue($works[0]->isPrivate);
    self::assertEquals($data->work->one->id, $works[1]->workId); // order by display order desc
    self::assertTrue(ends_with($works[1]->cover, 'work-cover-one'));
    self::assertFalse($works[1]->isPrivate);
    self::assertNull($works[1]->originWorkUser);
    self::assertEquals(10, $works[2]->chorusType);
    self::assertEquals($data->user->two->id, $works[2]->originWorkUser->userId);
    self::assertEquals('lisi', $works[2]->originWorkUser->nickname);
  }

  public function testGetUserWorksSuccess_OtherWorks()
  {
    $authUser = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $authUserProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $authUser->id,
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareWorks($user);

    $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $user->id,
      'music_id'      => '99a8ab1b34764481bd642534b615d7b0', 
      'name'          => 'God bless me. private',
      'display_order' => 9000000000000,
      'is_private'    => 1,
    ]);
    $response = $this->actingAs($authUser)
                     ->getJson('v3/user/works?' . http_build_query([
                        'userId'  => $user->id, 
                     ]));

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $works = $response->data->works;
    self::assertCount(1, $works);       // only one work output, private work be hidden
    self::assertEquals($data->work->one->id, $works[0]->workId); // order by display order desc
    self::assertTrue(ends_with($works[0]->cover, 'work-cover-one'));
  }

  public function testGetUserWorksSuccess_OtherWorksWithoutLogin()
  {

      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      $data = $this->prepareWorks($user);

      $workPrivate = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id'       => $user->id,
          'music_id'      => '99a8ab1b34764481bd642534b615d7b0',
          'name'          => 'God bless me. private',
          'display_order' => 9000000000000,
          'is_private'    => 1,
      ]);
      $response = $this->getJson('v3/user/works?' . http_build_query([
                  'userId'  => $user->id,
              ]));

      $response->assertJson(['code' => 0]);
      $response = json_decode($response->getContent());
      $works = $response->data->works;
      self::assertCount(1, $works);       // only one work output, private work be hidden
      self::assertEquals($data->work->one->id, $works[0]->workId); // order by display order desc
      self::assertTrue(ends_with($works[0]->cover, 'work-cover-one'));
  }

  public function testGetUserWorksSuccess_WithFakeMusic()
  {
    config([
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $data = $this->prepareWorks($user);

    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => '99a8ab1b34764481bd642534b615d7b0', 
      'name'      => 'God bless me',
      'display_order' => 1000000000000,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/user/works');

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    $works = $response->data->works;
    self::assertCount(2, $works);
    self::assertEquals('God bless me', $works[0]->musicName);
    self::assertEquals($data->work->one->id, $works[1]->workId); // order by display order desc
    self::assertTrue(ends_with($works[1]->cover, 'work-cover-one'));
  }

  //=================================
  //        incrWorkListenCount
  //=================================
  public function testIncrWorkListenCountSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user-avatar',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => $user->id, 
    ]);
    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $work->id));
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $user->id))
         ->andReturn(188);
      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $this->actingAs($user)
         ->postJson(sprintf('v3/works/%s/listen-count', $work->id))
         ->assertJson([
          'code' => 0,
          'message' => 'success',
         ]);
  }

  public function testIncrWorkListenCountSuccess_WithoutLogin()
  {
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id' => $user->id,
          'avatar'  => 'login-user-avatar',
      ]);
      $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id' => $user->id,
      ]);
      Cache::shouldReceive('increment')
          ->with(sprintf('work:%s:listennum', $work->id));
      Cache::shouldReceive('increment')
          ->with(sprintf('user:%s:listennum', $user->id))
          ->andReturn(188);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

      $this->postJson(sprintf('v3/works/%s/listen-count', $work->id))
          ->assertJson([
              'code' => 0,
              'message' => 'success',
          ]);
  }

  //=================================
  //        getDetail
  //=================================
  public function testGetDetailSuccuss()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user-avatar',
    ]);
    $data = $this->prepareWorks();
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $user->id,
      'work_id' => $data->work->one->id,
      'display_order' => 100,
      'updated_at'  => '2016-01-01',
    ]);
    $favouriteTwo = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $data->user->one->id,
      'work_id' => $data->work->one->id,
      'display_order' => 200,
      'updated_at'  => '2016-02-01',
    ]);
    $favouriteThree = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $data->user->two->id,
      'work_id' => $data->work->one->id,
      'display_order' => 300,
      'updated_at'  => '2016-03-01',
    ]);

    $favourite->delete();

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $data->work->one->id)))
                 ->andReturn('http://goo.gl/abcde');
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$data->user->one->id],
      'following_details' => [
        [
          'user_id' => $data->user->one->id,
          'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
        ]
      ],
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $data->work->one->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id), 'http://goo.gl/abcde');

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($data->work->one->id)
          ->andReturn();

    $workOne = $data->work->one;
    $workOne->is_private = 0;
    $workOne->save();

    $musicOne = $data->music->one;
    $musicOne->status = -1;
    $musicOne->save();

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $data->work->one->id,
                     ]));
    $response->assertJson(['code' => 0]);     // private work also autput
    $work = (json_decode($response->getContent()))->data->work;
    self::assertEquals($data->work->one->id, $work->workId);
    self::assertCount(2, $work->slides);
    self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
    self::assertEquals('zhangsan', $work->nickname);
    self::assertEquals('Simon Plum', $work->artists);
    self::assertTrue(ends_with($work->avatar, 'avatar-one'));
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/music-lyric-one', $work->lyric);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-cover-one', $work->cover);
    self::assertFalse($work->isFavourite);
    self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
    self::assertEquals(128, $work->duration);
    self::assertFalse($work->noAccompaniment);
    self::assertTrue($work->author->isFollowing);
    self::assertFalse($work->author->isFollower);
    self::assertCount(2, $work->favourites);
    self::assertEquals($data->user->two->id, $work->favourites[0]->userId);
  }
    public function testGetDetailSuccuss_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-user-avatar',
        ]);
        $data = $this->prepareWorks();
        $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $user->id,
            'work_id' => $data->work->one->id,
            'display_order' => 100,
            'updated_at'  => '2016-01-01',
        ]);
        $favouriteTwo = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $data->user->one->id,
            'work_id' => $data->work->one->id,
            'display_order' => 200,
            'updated_at'  => '2016-02-01',
        ]);
        $favouriteThree = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $data->user->two->id,
            'work_id' => $data->work->one->id,
            'display_order' => 300,
            'updated_at'  => '2016-03-01',
        ]);

        $favourite->delete();

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never()
            ->with(secure_url(sprintf('c/page/works/%s', $data->work->one->id)))
            ->andReturn('http://goo.gl/abcde');
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id' => $user->id,
            'followings'  => [$data->user->one->id],
            'following_details' => [
                [
                    'user_id' => $data->user->one->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
                ]
            ],
        ]);

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $data->work->one->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id), 'http://goo.gl/abcde');

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($data->work->one->id)
            ->andReturn();

        $workOne = $data->work->one;
        $workOne->is_private = 0;
        $workOne->save();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                'workId'  => $data->work->one->id,
            ]));
        $response->assertJson(['code' => 0]);     // private work also autput
        $work = (json_decode($response->getContent()))->data->work;
        self::assertEquals($data->work->one->id, $work->workId);
        self::assertCount(2, $work->slides);
        self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
        self::assertEquals('zhangsan', $work->nickname);
        self::assertEquals('Simon Plum', $work->artists);
        self::assertTrue(ends_with($work->avatar, 'avatar-one'));
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/music-lyric-one', $work->lyric);
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-cover-one', $work->cover);
        self::assertFalse($work->isFavourite);
        self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
        self::assertEquals(128, $work->duration);
        self::assertFalse($work->noAccompaniment);
        self::assertFalse($work->author->isFollowing);
        self::assertFalse($work->author->isFollower);
        self::assertCount(2, $work->favourites);
        self::assertEquals($data->user->two->id, $work->favourites[0]->userId);
    }


  public function testGetDetailSuccuss_ChorusStart()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user-avatar',
      'nickname'  => 'login-user',
      'signature' => 'good',
    ]);

    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $userOne->id, 
      'avatar'  => 'user-one-avatar',
      'nickname'  => 'user-one',
      'signature' => 'hello world',
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'my-music',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $music->id,
      'cover'     => 'work-cover',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 1,
      'chorus_type'   => 1,
      'chorus_start_info'  => [
        'chorus_count'  => 100,
      ],
    ]);

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
                 ->andReturn('http://goo.gl/abcde');
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$userOne->id],
      'following_details' => [
        [
          'user_id' => $userOne->id,
          'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
        ]
      ],
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $userOne->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2017-03-02 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $work->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $work->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $work->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $work->id,
                     ]));
    $response->assertJson(['code' => 0]);     // private work also autput
    $resWork = (json_decode($response->getContent()))->data->work;
    self::assertEquals($work->id, $resWork->workId);
    self::assertEquals(1, $resWork->chorusType);
    self::assertEquals(100, $resWork->chorusStartInfo->chorusCount);
  }

  public function testGetDetailSuccuss_ChorusJoin()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user-avatar',
      'nickname'  => 'login-user',
      'signature' => 'good',
    ]);

    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $userOne->id, 
      'avatar'  => 'user-one-avatar',
      'nickname'  => 'user-one',
      'signature' => 'hello world',
    ]);
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $userTwo->id, 
      'avatar'  => 'user-two-avatar',
      'nickname'  => 'user-two',
      'signature' => 'hello world two',
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'my-music',
    ]);
    $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'           => $userTwo->id,
      'music_id'          => $music->id,
      'chorus_type'       => 1,
      'chorus_start_info' => [
        'chorus_count'    => 2,
      ],
      'status'            => 1,
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $music->id,
      'cover'     => 'work-cover',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 1,
      'chorus_type'   => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $originWork->id,
      ],
    ]);

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
                 ->andReturn('http://goo.gl/abcde');
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$userOne->id, $userTwo->id],
      'following_details' => [
        [
          'user_id' => $userOne->id,
          'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
        ],
        [
          'user_id' => $userTwo->id,
          'follow_at' => \Carbon\Carbon::parse('2017-03-03')->getTimestamp(),
        ],
      ],
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $userOne->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $userTwo->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-02-02 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $work->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $work->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $work->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $originWork->id))
         ->andReturn(1001);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $work->id,
                     ]));
    $response->assertJson(['code' => 0]);     // private work also autput
    $resWork = (json_decode($response->getContent()))->data->work;
    self::assertEquals($work->id, $resWork->workId);
    self::assertEquals(10, $resWork->chorusType);
    self::assertEquals($originWork->id, $resWork->chorusJoinInfo->workId);
    self::assertEquals('user-two', $resWork->chorusJoinInfo->author->nickname);
    self::assertEquals('hello world two', $resWork->chorusJoinInfo->author->signature);
    self::assertTrue($resWork->chorusJoinInfo->author->isFollowing);
  }

  public function testGetDetailSuccuss_ChorusJoinUserSameAsStartUser()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'avatar'  => 'login-user-avatar',
      'nickname'  => 'login-user',
      'signature' => 'good',
    ]);

    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $userOne->id, 
      'avatar'  => 'user-one-avatar',
      'nickname'  => 'user-one',
      'signature' => 'hello world',
    ]);

    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'  => 'my-music',
    ]);
    $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'           => $userOne->id,
      'music_id'          => $music->id,
      'chorus_type'       => 1,
      'chorus_start_info' => [
        'chorus_count'    => 2,
      ],
      'status'            => 1,
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $music->id,
      'cover'     => 'work-cover',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 1,
      'chorus_type'   => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $originWork->id,
      ],
    ]);

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
                 ->andReturn('http://goo.gl/abcde');
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id' => $user->id,
      'followings'  => [$userOne->id],
      'following_details' => [
        [
          'user_id' => $userOne->id,
          'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
        ]
      ],
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $userOne->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $work->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $work->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $work->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $work->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $originWork->id))
         ->andReturn(1001);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $work->id,
                     ]));
    $response->assertJson(['code' => 0]);     // private work also autput
    $resWork = (json_decode($response->getContent()))->data->work;
    self::assertEquals($work->id, $resWork->workId);
    self::assertEquals(10, $resWork->chorusType);
    self::assertEquals($originWork->id, $resWork->chorusJoinInfo->workId);
    self::assertEquals('user-one', $resWork->chorusJoinInfo->author->nickname);
    self::assertEquals('hello world', $resWork->chorusJoinInfo->author->signature);
    self::assertTrue($resWork->chorusJoinInfo->author->isFollowing);
  }


  public function testGetDetailSuccuss_WithFakeMusic()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
      'business-logic.fakemusic'  => [
        'id'    => '99a8ab1b34764481bd642534b615d7b0',
        'name'  => 'without accompaniment',
      ],
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();
    $work = $data->work->one;
    $work->music_id = '99a8ab1b34764481bd642534b615d7b0';
    $work->no_accompaniment = 1;
    $work->name = 'God bless me';
    $work->save();
    $work = $work->fresh();
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $user->id,
      'work_id' => $data->work->one->id,
    ]);
    $favourite->delete();

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never()
                 ->with(secure_url(sprintf('c/page/works/%s', $data->work->one->id)))
                 ->andReturn('http://goo.gl/abcde');

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $data->work->one->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id))
         ->andReturn(null);
    Cache::shouldReceive('forever')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id), 'http://goo.gl/abcde');

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($data->work->one->id)
          ->andReturn();

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $data->work->one->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $work = (json_decode($response->getContent()))->data->work;
    self::assertEquals($data->work->one->id, $work->workId);
    self::assertCount(2, $work->slides);
    self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
    self::assertEquals('zhangsan', $work->nickname);
    self::assertEquals('', $work->artists);
    self::assertTrue(ends_with($work->avatar, 'avatar-one'));
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
    self::assertEquals('', $work->lyric);
    self::assertEquals('God bless me', $work->musicName);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-cover-one', $work->cover);
    self::assertFalse($work->isFavourite);
    self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
    self::assertEquals(128, $work->duration);
    self::assertTrue($work->noAccompaniment);
  }

  public function testGetDetailSuccuss_IsFavourite()
  {
    config([
      'storage.riakcs.base_url' => 'http://base',
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();

    $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
    $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
    $urlShortener->shouldReceive('shorten')
                 ->never();

    factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $user->id,
      'work_id' => $data->work->one->id,
    ]);

    Cache::shouldReceive('increment')
         ->with(sprintf('work:%s:listennum', $data->work->one->id));
    Cache::shouldReceive('get')
         ->with(sprintf('work:%s:listennum', $data->work->one->id))
         ->andReturn(1888);
    Cache::shouldReceive('get')
         ->once()
         ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
         ->andReturn(1973);
    Cache::shouldReceive('increment')
         ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
         ->andReturn(188);
    Cache::shouldReceive('get')
         ->never()
         ->with(sprintf('work:%s:surl', $data->work->one->id))
         ->andReturn('http://goo.gl/abcde');
    Cache::shouldReceive('forever')
         ->never();

    $popularityService = $this->mockPopularityHierarchyService();
    $popularityService->shouldReceive('updatePopularity')
        ->once()
        ->with($data->work->one->id)
        ->andReturn();

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> false,
                    'follow_at'=> null,
                    'is_follower'=> true,
                    'followed_at'=> '2015-01-01 00:00:00',
                ],
                $data->user->two->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => $data->work->one->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $work = (json_decode($response->getContent()))->data->work;
    self::assertEquals($data->work->one->id, $work->workId);
    self::assertCount(2, $work->slides);
    self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
    self::assertEquals('zhangsan', $work->nickname);
    self::assertTrue(ends_with($work->avatar, 'avatar-one'));
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
    self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/music-lyric-one', $work->lyric);
    self::assertTrue($work->isFavourite);
    self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
  }

  public function testGetDetailFailed_NotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/works/detail?' . http_build_query([
                        'workId'  => '1fe1cf9cf3854e539b947e725b266baa',
                     ]))
                     ->assertJson(['code' => 10402]);
  }

    public function testGetDetailSuccuss_ChorusStart_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-user-avatar',
            'nickname'  => 'login-user',
            'signature' => 'good',
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'avatar'  => 'user-one-avatar',
            'nickname'  => 'user-one',
            'signature' => 'hello world',
        ]);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'my-music',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userOne->id,
            'music_id'  => $music->id,
            'cover'     => 'work-cover',
            'slides'    => [
                'work-one-one', 'work-one-two',
            ],
            'display_order' => 100,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-one',
            'duration'      => 128,
            'status'        => 1,
            'chorus_type'   => 1,
            'chorus_start_info'  => [
                'chorus_count'  => 100,
            ],
        ]);

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never()
            ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
            ->andReturn('http://goo.gl/abcde');
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id' => $user->id,
            'followings'  => [$userOne->id],
            'following_details' => [
                [
                    'user_id' => $userOne->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
                ]
            ],
        ]);

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $work->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $work->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $work->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => $work->id,
                ]));
        $response->assertJson(['code' => 0]);     // private work also autput
        $resWork = (json_decode($response->getContent()))->data->work;
        self::assertEquals($work->id, $resWork->workId);
        self::assertEquals(1, $resWork->chorusType);
        self::assertEquals(100, $resWork->chorusStartInfo->chorusCount);
    }

    public function testGetDetailSuccuss_ChorusJoin_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-user-avatar',
            'nickname'  => 'login-user',
            'signature' => 'good',
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'avatar'  => 'user-one-avatar',
            'nickname'  => 'user-one',
            'signature' => 'hello world',
        ]);
        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userTwo->id,
            'avatar'  => 'user-two-avatar',
            'nickname'  => 'user-two',
            'signature' => 'hello world two',
        ]);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'my-music',
        ]);
        $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'           => $userTwo->id,
            'music_id'          => $music->id,
            'chorus_type'       => 1,
            'chorus_start_info' => [
                'chorus_count'    => 2,
            ],
            'status'            => 1,
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userOne->id,
            'music_id'  => $music->id,
            'cover'     => 'work-cover',
            'slides'    => [
                'work-one-one', 'work-one-two',
            ],
            'display_order' => 100,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-one',
            'duration'      => 128,
            'status'        => 1,
            'chorus_type'   => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $originWork->id,
            ],
        ]);

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never()
            ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
            ->andReturn('http://goo.gl/abcde');
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id' => $user->id,
            'followings'  => [$userOne->id, $userTwo->id],
            'following_details' => [
                [
                    'user_id' => $userOne->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
                ],
                [
                    'user_id' => $userTwo->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-03-03')->getTimestamp(),
                ],
            ],
        ]);

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $work->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $work->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $work->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $originWork->id))
            ->andReturn(1001);

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => $work->id,
                ]));
        $response->assertJson(['code' => 0]);     // private work also autput
        $resWork = (json_decode($response->getContent()))->data->work;
        self::assertEquals($work->id, $resWork->workId);
        self::assertEquals(10, $resWork->chorusType);
        self::assertEquals($originWork->id, $resWork->chorusJoinInfo->workId);
        self::assertEquals('user-two', $resWork->chorusJoinInfo->author->nickname);
        self::assertEquals('hello world two', $resWork->chorusJoinInfo->author->signature);
        self::assertFalse($resWork->chorusJoinInfo->author->isFollowing);
    }

    public function testGetDetailSuccuss_ChorusJoinUserSameAsStartUser_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'avatar'  => 'login-user-avatar',
            'nickname'  => 'login-user',
            'signature' => 'good',
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id,
            'avatar'  => 'user-one-avatar',
            'nickname'  => 'user-one',
            'signature' => 'hello world',
        ]);

        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'my-music',
        ]);
        $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'           => $userOne->id,
            'music_id'          => $music->id,
            'chorus_type'       => 1,
            'chorus_start_info' => [
                'chorus_count'    => 2,
            ],
            'status'            => 1,
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $userOne->id,
            'music_id'  => $music->id,
            'cover'     => 'work-cover',
            'slides'    => [
                'work-one-one', 'work-one-two',
            ],
            'display_order' => 100,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-one',
            'duration'      => 128,
            'status'        => 1,
            'chorus_type'   => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $originWork->id,
            ],
        ]);

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never()
            ->with(secure_url(sprintf('c/page/works/%s', $work->id)))
            ->andReturn('http://goo.gl/abcde');
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id' => $user->id,
            'followings'  => [$userOne->id],
            'following_details' => [
                [
                    'user_id' => $userOne->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-03-02')->getTimestamp(),
                ]
            ],
        ]);

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $work->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $work->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $work->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $work->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $work->id), 'http://goo.gl/abcde');
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $originWork->id))
            ->andReturn(1001);

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => $work->id,
                ]));
        $response->assertJson(['code' => 0]);     // private work also autput
        $resWork = (json_decode($response->getContent()))->data->work;
        self::assertEquals($work->id, $resWork->workId);
        self::assertEquals(10, $resWork->chorusType);
        self::assertEquals($originWork->id, $resWork->chorusJoinInfo->workId);
        self::assertEquals('user-one', $resWork->chorusJoinInfo->author->nickname);
        self::assertEquals('hello world', $resWork->chorusJoinInfo->author->signature);
        self::assertFalse($resWork->chorusJoinInfo->author->isFollowing);
    }

    public function testGetDetailSuccuss_WithFakeMusic_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
            'business-logic.fakemusic'  => [
                'id'    => '99a8ab1b34764481bd642534b615d7b0',
                'name'  => 'without accompaniment',
            ],
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareWorks();
        $work = $data->work->one;
        $work->music_id = '99a8ab1b34764481bd642534b615d7b0';
        $work->no_accompaniment = 1;
        $work->name = 'God bless me';
        $work->save();
        $work = $work->fresh();
        $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $user->id,
            'work_id' => $data->work->one->id,
        ]);
        $favourite->delete();

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never()
            ->with(secure_url(sprintf('c/page/works/%s', $data->work->one->id)))
            ->andReturn('http://goo.gl/abcde');

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $data->work->one->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id))
            ->andReturn(null);
        Cache::shouldReceive('forever')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id), 'http://goo.gl/abcde');

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($data->work->one->id)
            ->andReturn();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => $data->work->one->id,
                ]));
        $response->assertJson(['code' => 0]);
        $work = (json_decode($response->getContent()))->data->work;
        self::assertEquals($data->work->one->id, $work->workId);
        self::assertCount(2, $work->slides);
        self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
        self::assertEquals('zhangsan', $work->nickname);
        self::assertEquals('', $work->artists);
        self::assertTrue(ends_with($work->avatar, 'avatar-one'));
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
        self::assertEquals('', $work->lyric);
        self::assertEquals('God bless me', $work->musicName);
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-cover-one', $work->cover);
        self::assertFalse($work->isFavourite);
        self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
        self::assertEquals(128, $work->duration);
        self::assertTrue($work->noAccompaniment);
    }

    public function testGetDetailSuccuss_IsFavourite_WithoutLogin()
    {
        config([
            'storage.riakcs.base_url' => 'http://base',
        ]);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareWorks();

        $urlShortener = \Mockery::mock(\SingPlus\Contracts\Supports\UrlShortener::class);
        $this->app[\SingPlus\Contracts\Supports\UrlShortener::class ] = $urlShortener;
        $urlShortener->shouldReceive('shorten')
            ->never();

        factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'user_id' => $user->id,
            'work_id' => $data->work->one->id,
        ]);

        Cache::shouldReceive('increment')
            ->with(sprintf('work:%s:listennum', $data->work->one->id));
        Cache::shouldReceive('get')
            ->with(sprintf('work:%s:listennum', $data->work->one->id))
            ->andReturn(1888);
        Cache::shouldReceive('get')
            ->once()
            ->with(sprintf('music:%s:reqnum', $data->work->one->music_id))
            ->andReturn(1973);
        Cache::shouldReceive('increment')
            ->with(sprintf('user:%s:listennum', $data->work->one->user_id))
            ->andReturn(188);
        Cache::shouldReceive('get')
            ->never()
            ->with(sprintf('work:%s:surl', $data->work->one->id))
            ->andReturn('http://goo.gl/abcde');
        Cache::shouldReceive('forever')
            ->never();

        config([
            'filesystems.disks.s3.region'  => 'eu-central-1',
            'filesystems.disks.s3.bucket'  => 'sing-plus',
        ]);

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($data->work->one->id)
            ->andReturn();

        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => $data->work->one->id,
                ]));
        $response->assertJson(['code' => 0]);
        $work = (json_decode($response->getContent()))->data->work;
        self::assertEquals($data->work->one->id, $work->workId);
        self::assertCount(2, $work->slides);
        self::assertTrue(ends_with($work->slides[0], 'work-one-one'));
        self::assertEquals('zhangsan', $work->nickname);
        self::assertTrue(ends_with($work->avatar, 'avatar-one'));
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/work-one', $work->resource);
        self::assertEquals('https://sing-plus.s3.eu-central-1.amazonaws.com/music-lyric-one', $work->lyric);
        self::assertFalse($work->isFavourite);
        self::assertEquals(secure_url(sprintf('c/page/works/%s', $data->work->one->id)), $work->shareLink);
    }

    public function testGetDetailFailed_NotExists_WithoutLogin()
    {
        $response = $this->getJson('v3/works/detail?' . http_build_query([
                    'workId'  => '1fe1cf9cf3854e539b947e725b266baa',
                ]))
            ->assertJson(['code' => 10402]);
    }

  //=================================
  //        deleteWork
  //=================================
  public function testDeleteWorkSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\Works\WorkDeleted::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => $user->id,
      'status'  => 1,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/delete', [
                        'workId'  => $work->id,
                     ])
                     ->assertJson(['code' => 0]);

    $this->assertDatabaseHas('works', [
      '_id'     => $work->id,
      'status'  => 0,             // deleted
    ]);
  }

  public function testDeleteWorkFailed_WorkNotExist()
  {
    $this->doesntExpectEvents(\SingPlus\Events\Works\WorkDeleted::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => $user->id,
      'status'  => 0,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/delete', [
                        'workId'  => $work->id,
                     ])
                     ->assertJson(['code' => 10402]);
  }

  public function testDeleteWorkFailed_WorkBelongOther()
  {
    $this->doesntExpectEvents(\SingPlus\Events\Works\WorkDeleted::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $other = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id' => $other->id,
      'status'  => 1,
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/works/delete', [
                        'workId'  => $work->id,
                     ])
                     ->assertJson(['code' => 10420]);
  }

  //=================================
  //        commentWork
  //=================================
  public function testCommentWorkSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\UserCommentWork::class);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('comments', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'comment_count' => 0,
    ]);

      $dailyTaskService = $this->mockDailyTaskService();
      $dailyTaskService->shouldReceive('resetDailyTaskLists')
          ->once()
          ->with($user->id, null)
          ->andReturn();
      $dailyTaskService->shouldReceive('finisheDailyTask')
          ->once()
          ->with($user->id, DailyTask::TYPE_COMMENT)
          ->andReturn();

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/comment', [
                        'comment' => 'Perfect song',
                        'workId'  => $work->id,
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals(32, strlen($response->data->commentId));

    $this->assertDatabaseHas('works', [
      '_id'           => $work->id,
      'user_id'       => $work->user_id,
      'music_id'      => $work->music_id,
      'comment_count' => 1,         // count added to 1
    ], 'mongodb');
    $this->assertDatabaseHas('comments', [
      'work_id'         => $work->id,
      'content'         => 'Perfect song',
      'author_id'       => $user->id,
      'replied_user_id' => $work->user_id,
      'status'          => 1,
      'display_order'   => 100,
    ], 'mongodb');
  }

    public function testCommentWorkSuccess_SelfComment()
    {
        $this->doesntExpectEvents(\SingPlus\Events\UserCommentWork::class);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('comments', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => $user->id,
            'comment_count' => 0,
        ]);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->once()
            ->with($user->id, null)
            ->andReturn();
        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->once()
            ->with($user->id, DailyTask::TYPE_COMMENT)
            ->andReturn();
        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();
        $response = $this->actingAs($user)
            ->postJson('v3/works/comment', [
                'comment' => 'Perfect song',
                'workId'  => $work->id,
            ]);

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(32, strlen($response->data->commentId));

        $this->assertDatabaseHas('works', [
            '_id'           => $work->id,
            'user_id'       => $work->user_id,
            'music_id'      => $work->music_id,
            'comment_count' => 1,         // count added to 1
        ], 'mongodb');
        $this->assertDatabaseHas('comments', [
            'work_id'         => $work->id,
            'content'         => 'Perfect song',
            'author_id'       => $user->id,
            'replied_user_id' => $work->user_id,
            'status'          => 1,
            'display_order'   => 100,
        ], 'mongodb');
    }

  public function testCommentWorkSuccess_CommentComment()
  {
    $this->expectsEvents(\SingPlus\Events\UserCommentWork::class);
    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('comments', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'       => $userOne->id,
      'comment_count' => 1,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'author_id'       => $userTwo->id,
      'work_id'         => $work->id,
      'replied_user_id' => $work->user_id,
    ]);

      $dailyTaskService = $this->mockDailyTaskService();
      $dailyTaskService->shouldReceive('resetDailyTaskLists')
          ->never()
          ->with($user->id, null)
          ->andReturn();
      $dailyTaskService->shouldReceive('finisheDailyTask')
          ->never()
          ->with($user->id, DailyTask::TYPE_COMMENT)
          ->andReturn();
      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->once()
          ->with($work->id)
          ->andReturn();
    $response = $this->actingAs($user)
                     ->postJson('v3/works/comment', [
                        'comment' => 'Perfect song',
                        'workId'  => $work->id,
                        'commentId' => $comment->id,
                     ]);

    $response->assertJson(['code' => 0]);
    $response = json_decode($response->getContent());
    self::assertEquals(32, strlen($response->data->commentId));

    $newComment = \SingPlus\Domains\Works\Models\Comment::where('comment_id', $comment->id)->first();
    self::assertEquals($userTwo->id, $newComment->replied_user_id);
    self::assertEquals($comment->id, $newComment->comment_id);
    self::assertEquals($user->id, $newComment->author_id);

    $this->assertDatabaseHas('works', [
      '_id'           => $work->id,
      'user_id'       => $work->user_id,
      'music_id'      => $work->music_id,
      'comment_count' => 2,
    ], 'mongodb');
  }

  public function testCommentWorkFailed_WorkNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $this->actingAs($user)
         ->postJson('v3/works/comment', [
            'comment' => 'Perfect song',
            'workId'  => 'fc0482540b0f4bc28dff8878b1eb1293',
         ])
         ->assertJson(['code' => 10402]);
  }


    public function testCommentWorkSuccess_WithCommentType()
    {
        $this->expectsEvents(\SingPlus\Events\UserCommentWork::class);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('comments', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => $userOne->id,
            'comment_count' => 0,
        ]);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->never()
            ->with($user->id, null)
            ->andReturn();
        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->never()
            ->with($user->id, DailyTask::TYPE_COMMENT)
            ->andReturn();
        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/works/comment', [
                'comment' => 'Perfect song',
                'workId'  => $work->id,
                'commentType' => 2,
            ]);

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(32, strlen($response->data->commentId));

        $this->assertDatabaseHas('works', [
            '_id'           => $work->id,
            'user_id'       => $work->user_id,
            'music_id'      => $work->music_id,
            'comment_count' => 1,         // count added to 1
        ], 'mongodb');
        $this->assertDatabaseHas('comments', [
            'work_id'         => $work->id,
            'content'         => 'Perfect song',
            'author_id'       => $user->id,
            'replied_user_id' => $work->user_id,
            'status'          => 1,
            'display_order'   => 100,
            'type'       => 2,
        ], 'mongodb');
    }

    public function testCommentWorkSuccess_CommentForSendGift()
    {
        $this->expectsEvents(\SingPlus\Events\UserCommentWork::class);
        $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
        $counterMock->shouldReceive('increment')
            ->once()
            ->with('comments', 100)
            ->andReturn(100);
        Cache::shouldReceive('driver')
            ->once()
            ->with('counter')
            ->andReturn($counterMock);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => $userOne->id,
            'comment_count' => 0,
        ]);

        $dailyTaskService = $this->mockDailyTaskService();
        $dailyTaskService->shouldReceive('resetDailyTaskLists')
            ->never()
            ->with($user->id, null)
            ->andReturn();
        $dailyTaskService->shouldReceive('finisheDailyTask')
            ->never()
            ->with($user->id, DailyTask::TYPE_COMMENT)
            ->andReturn();

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->once()
            ->with($work->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/works/comment', [
                'comment' => 'Perfect song',
                'workId'  => $work->id,
                'commentType' => 4,
                'repliedUserId' => $userOne->id,
                'giftFeedId' => 'dde519fac9b611e7bb4252540085b9d0'
            ]);

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        self::assertEquals(32, strlen($response->data->commentId));

        $this->assertDatabaseHas('works', [
            '_id'           => $work->id,
            'user_id'       => $work->user_id,
            'music_id'      => $work->music_id,
            'comment_count' => 1,         // count added to 1
        ], 'mongodb');
        $this->assertDatabaseHas('comments', [
            'work_id'         => $work->id,
            'content'         => 'Perfect song',
            'author_id'       => $user->id,
            'replied_user_id' => $userOne->id,
            'status'          => 1,
            'display_order'   => 100,
            'gift_feed_id'    => 'dde519fac9b611e7bb4252540085b9d0'
        ], 'mongodb');
    }


  //=================================
  //        deleteComment
  //=================================
  public function testDeleteCommentSuccess()
  {
    $this->doesntExpectEvents(\SingPlus\Events\UserCommentWork::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'status'        => 1,
      'comment_count' => 10,
      'status'        => 1,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'   => $work->id,
      'author_id' => $user->id,
      'status'    => 1,
    ]);

    $response = $this->actingAs($user)
              ->postJson('v3/works/comment/delete', [
                 'commentId' => $comment->id,
              ])
              ->assertJson(['code' => 0]);

    $this->assertDatabaseHas('comments', [
      '_id'     => $comment->id,
      'status'  => 0,                 // aready deleted
    ]);

    $this->assertDatabaseHas('works', [
      '_id'           => $work->id,
      'comment_count' => 9,           // 10 - 1
      'status'        => 1,
    ]);
  }

  public function testDeleteCommentFailed_CommentAreadyDeleted()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'status'        => 1,
      'comment_count' => 10,
      'status'        => 1,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'   => $work->id,
      'author_id' => $user->id,
      'status'    => 0,
    ]);

    $response = $this->actingAs($user)
              ->postJson('v3/works/comment/delete', [
                 'commentId' => $comment->id,
              ])
              ->assertJson(['code' => 10410]);
  }

  public function testDeleteCommentFailed_CommentBelongOther()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $author = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'status'        => 1,
      'comment_count' => 10,
      'status'        => 1,
    ]);
    $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'   => $work->id,
      'author_id' => $author->id,
      'status'    => 1,
    ]);

    $response = $this->actingAs($user)
              ->postJson('v3/works/comment/delete', [
                 'commentId' => $comment->id,
              ])
              ->assertJson(['code' => 10411]);
  }

  //=================================
  //        getComments
  //=================================
  public function testGetCommentsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => null,
      'content'         => 'comment one',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $data->work->one->user_id,
      'status'          => 1,
      'display_order'   => 100,
    ]);
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment two',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $commentOne->author_id,
      'status'          => 1,
      'display_order'   => 200,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/comments?' . http_build_query([
                        'workId'  => $data->work->one->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $comments = (json_decode($response->getContent()))->data->comments;
    self::assertCount(2, $comments);
    self::assertEquals($commentTwo->id, $comments[0]->commentId);   // order by display_order desc
    self::assertEquals($data->user->two->id, $comments[0]->authorId);
    self::assertEquals('lisi', $comments[0]->nickname);
    self::assertEquals('lisi', $comments[0]->repliedUserNickname);
    self::assertEquals('comment two', $comments[0]->content);
  }

  public function testGetCommentsSuccess_CommentNotFound()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $response = $this->actingAs($user)
                     ->getJson('v3/works/comments?' . http_build_query([
                        'workId'  => '690a69eac2c14180a2cf75ddbcfaa89e',
                     ]))
                     ->assertJson([
                        'code'  => 0,
                        'message' => '',
                        'data' => [
                          'comments'  => [],
                        ],
                     ]);
  }

  public function testGetCommentsSuccess_Pagination()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $data = $this->prepareWorks();
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => null,
      'content'         => 'comment one',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $data->work->one->user_id,
      'status'          => 1,
      'display_order'   => 100,
    ]);
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment two',
      'author_id'       => $data->user->two->id,
      'replied_user_id' => $commentOne->author_id,
      'status'          => 1,
      'display_order'   => 200,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/comments?' . http_build_query([
                        'workId'    => $data->work->one->id,
                        'commentId' => $commentTwo->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $comments = (json_decode($response->getContent()))->data->comments;
    self::assertCount(1, $comments);
    self::assertEquals($commentOne->id, $comments[0]->commentId);   // order by display_order desc
    self::assertEquals($data->user->two->id, $comments[0]->authorId);
    self::assertEquals('lisi', $comments[0]->nickname);
    self::assertEquals('zhangsan', $comments[0]->repliedUserNickname);
    self::assertEquals('comment one', $comments[0]->content);
  }

    public function testGetCommentsSuccess_WithoutLogin()
    {
        $data = $this->prepareWorks();
        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => null,
            'content'         => 'comment one',
            'author_id'       => $data->user->two->id,
            'replied_user_id' => $data->work->one->user_id,
            'status'          => 1,
            'display_order'   => 100,
        ]);
        $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => $commentOne->id,
            'content'         => 'comment two',
            'author_id'       => $data->user->two->id,
            'replied_user_id' => $commentOne->author_id,
            'status'          => 1,
            'display_order'   => 200,
        ]);

        $response = $this->getJson('v3/works/comments?' . http_build_query([
                    'workId'  => $data->work->one->id,
                ]));
        $response->assertJson(['code' => 0]);
        $comments = (json_decode($response->getContent()))->data->comments;
        self::assertCount(2, $comments);
        self::assertEquals($commentTwo->id, $comments[0]->commentId);   // order by display_order desc
        self::assertEquals($data->user->two->id, $comments[0]->authorId);
        self::assertEquals('lisi', $comments[0]->nickname);
        self::assertEquals('lisi', $comments[0]->repliedUserNickname);
        self::assertEquals('comment two', $comments[0]->content);
    }

    public function testGetCommentsSuccess_CommentNotFound_WithoutLogin()
    {

        $response = $this->getJson('v3/works/comments?' . http_build_query([
                    'workId'  => '690a69eac2c14180a2cf75ddbcfaa89e',
                ]))
            ->assertJson([
                'code'  => 0,
                'message' => '',
                'data' => [
                    'comments'  => [],
                ],
            ]);
    }

    public function testGetCommentsSuccess_Pagination_WithoutLogin()
    {

        $data = $this->prepareWorks();
        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => null,
            'content'         => 'comment one',
            'author_id'       => $data->user->two->id,
            'replied_user_id' => $data->work->one->user_id,
            'status'          => 1,
            'display_order'   => 100,
        ]);
        $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => $commentOne->id,
            'content'         => 'comment two',
            'author_id'       => $data->user->two->id,
            'replied_user_id' => $commentOne->author_id,
            'status'          => 1,
            'display_order'   => 200,
        ]);

        $response = $this->getJson('v3/works/comments?' . http_build_query([
                    'workId'    => $data->work->one->id,
                    'commentId' => $commentTwo->id,
                ]));
        $response->assertJson(['code' => 0]);
        $comments = (json_decode($response->getContent()))->data->comments;
        self::assertCount(1, $comments);
        self::assertEquals($commentOne->id, $comments[0]->commentId);   // order by display_order desc
        self::assertEquals($data->user->two->id, $comments[0]->authorId);
        self::assertEquals('lisi', $comments[0]->nickname);
        self::assertEquals('zhangsan', $comments[0]->repliedUserNickname);
        self::assertEquals('comment one', $comments[0]->content);
    }

    public function testGetCommentsSuccess_CompatCommentTypeForOldClient(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);
        $data = $this->prepareWorks();
        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => null,
            'content'         => 'innersite transmit desc',
            'author_id'       => $data->user->two->id,
            'replied_user_id' => $data->work->one->user_id,
            'status'          => 1,
            'display_order'   => 100,
            'type'            => 3,
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/works/comments?' . http_build_query([
                    'workId'  => $data->work->one->id,
                ]), ['X-Version' => '3.0.1']);
        $response->assertJson(['code' => 0]);
        $comments = (json_decode($response->getContent()))->data->comments;
        self::assertCount(1, $comments);
        self::assertEquals($commentOne->id, $comments[0]->commentId);   // order by display_order desc
        self::assertEquals($data->user->two->id, $comments[0]->authorId);
        self::assertEquals('lisi', $comments[0]->nickname);
        self::assertEquals('zhangsan', $comments[0]->repliedUserNickname);
        self::assertEquals('innersite transmit desc', $comments[0]->content);
        self::assertEquals(2, $comments[0]->commentType);
    }

  //=================================
  //        getUserRelatedComments
  //=================================
  public function testGetUserRelatedCommentsSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id,
      'avatar'    => 'user-one-avatar',
      'nickname'  => 'user one',
    ]);
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userTwo->id,
      'avatar'    => 'user-two-avatar',
      'nickname'  => 'user two',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $music->id, 
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $music->id, 
    ]);
    // comment current user work, should pick
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => null,
      'author_id'   => $userOne->id,
      'replied_user_id' => $user->id,
      'display_order' => 100,
      'content'     => 'content 1',
    ]);
    // comment current user work, should pick
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => null,
      'author_id'   => $userTwo->id,
      'replied_user_id' => $user->id,
      'display_order' => 200,
      'content'     => 'content 2',
    ]);
    // comment other user's comment, should not pick
    $commentThree = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => $commentOne->id,
      'author_id'   => $userTwo->id,
      'replied_user_id' => $userOne->id,
      'display_order' => 300,
      'content'     => 'content 3',
    ]);
    // current user comment other user's comment, should not pick
    $commentFour = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => $commentOne->id,
      'author_id'   => $user->id,
      'replied_user_id' => $userOne->id,
      'display_order' => 400,
      'content'     => 'content 4',
    ]);
    // comment current user's comment, should pick
    $commentFive = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => $commentFour->id,
      'author_id'   => $userOne->id,
      'replied_user_id' => $user->id,
      'display_order' => 500,
      'content'     => 'content 5',
    ]);
    // current user comment current user comment, should not pick
    $commentSix = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'     => $workOne->id,
      'comment_id'  => $commentFour->id,
      'author_id'   => $user->id,
      'replied_user_id' => $user->id,
      'display_order' => 500,
      'content'     => 'content 6',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/messages/comments');
    $response->assertJson(['code' => 0]);
    $comments = (json_decode($response->getContent()))->data->comments;
    self::assertCount(3, $comments);
    self::assertEquals($commentFive->id, $comments[0]->commentId);    // order by display order desc
    self::assertEquals('content 5', $comments[0]->content);
    self::assertEquals('content 4', $comments[0]->repliedCommentContent);
    self::assertTrue(ends_with($comments[0]->avatar, 'user-one-avatar'));
    self::assertNull($comments[1]->repliedCommentContent);
  }

  //=================================
  //        favouriteWork
  //=================================
  public function testFavouriteWorkSuccess_OtherWorkAndAddFavourite()
  {
    $this->expectsEvents(UserTriggerFavouriteWorkEvent::class);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'is_new'  => false,
    ]);

    $data = $this->prepareWorks();
    $work = $data->work->one;
    $work->favourite_count = 1;
    $work->save();

    $otherFavourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $data->user->one->id,
      'work_id' => $work->id,
    ]);

    $popularityService = $this->mockPopularityHierarchyService();
    $popularityService->shouldReceive('updatePopularity')
        ->once()
        ->with($work->id)
        ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/favourite', [
                        'workId'  => $work->id,
                     ]);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'actionNumber'  => 1,
      ],
    ]);

    $this->assertDatabaseHas('work_favourites', [
      'user_id'     => $user->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,
    ], 'mongodb');
    $this->assertDatabaseHas('work_favourites', [
      '_id'         => $otherFavourite->id,
      'user_id'     => $data->user->one->id,
      'work_id'     => $work->id,
      'deleted_at'  => null,
    ], 'mongodb');                        // other favourite still exists
    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 2,             // from 1 add to 2
    ], 'mongodb');
  }

    public function testFavouriteWorkSuccess_SelfWorkAndAddFavourite()
    {
        $this->doesntExpectEvents(UserTriggerFavouriteWorkEvent::class);

        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
            'is_new'  => false,
        ]);

        $data = $this->prepareWorks();

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $user->id,
            'music_id'  => $data->music->one->id,
            'cover'     => 'work-cover-one',
            'slides'    => [
                'work-one-one', 'work-one-two',
            ],
            'display_order' => 100,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-one',
            'duration'      => 128,
            'status'        => 2,
        ]);
        $work->favourite_count = 1;
        $work->save();

        $popularityService = $this->mockPopularityHierarchyService();
        $popularityService->shouldReceive('updatePopularity')
            ->never()
            ->with($work->id)
            ->andReturn();

        $response = $this->actingAs($user)
            ->postJson('v3/works/favourite', [
                'workId'  => $work->id,
            ]);
        $response->assertJson([
            'code'  => 0,
            'data'  => [
                'actionNumber'  => 1,
            ],
        ]);

        $this->assertDatabaseHas('work_favourites', [
            'user_id'     => $user->id,
            'work_id'     => $work->id,
            'deleted_at'  => null,
        ], 'mongodb');
        $this->assertDatabaseHas('works', [
            '_id'             => $work->id,
            'favourite_count' => 2,             // from 1 add to 2
        ], 'mongodb');
    }

  public function testFavouriteWorkSuccess_OtherWorkAndCancelFavourite()
  {
    $this->doesntExpectEvents(UserTriggerFavouriteWorkEvent::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'is_new'  => false,
    ]);
    $data = $this->prepareWorks();
    $work = $data->work->one;
    $work->favourite_count = 1;
    $work->save();
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $user->id,
      'work_id' => $work->id,
    ]);

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->never()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/favourite', [
                        'workId'  => $work->id,
                     ]);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'actionNumber'  => -1,
      ],
    ]);

    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 0,             // from 1 add to 0 
    ], 'mongodb');

    self::assertTrue($favourite->fresh()->trashed()); // soft deleted
  }

  public function testFavouriteWorkSuccess_OtherWorkAndRestoreFavourite()
  {
    $this->doesntExpectEvents(UserTriggerFavouriteWorkEvent::class);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
      'is_new'  => false,
    ]);
    $data = $this->prepareWorks();
    $work = $data->work->one;
    $work->favourite_count = 1;
    $work->save();
    $favourite = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'user_id' => $user->id,
      'work_id' => $work->id,
    ]);
    $favourite->delete();         // aready soft deleted

      $popularityService = $this->mockPopularityHierarchyService();
      $popularityService->shouldReceive('updatePopularity')
          ->never()
          ->with($work->id)
          ->andReturn();

    $response = $this->actingAs($user)
                     ->postJson('v3/works/favourite', [
                        'workId'  => $work->id,
                     ]);
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'actionNumber'  => 1,
      ],
    ]);

    $this->assertDatabaseHas('works', [
      '_id'             => $work->id,
      'favourite_count' => 2,             // from 1 add to 2
    ], 'mongodb');

    $this->assertDatabaseHas('work_favourites', [
      'user_id' => $user->id,
      'work_id' => $work->id,
      'deleted_at'  => null,              // soft deleted aready restored
    ], 'mongodb');
  }

  //===============================================
  //        getWorkFavourites
  //===============================================
  public function testGetWorkFavouritesSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'nickname'  => 'login-user',
      'avatar'    => 'login-avatar',
      'is_new'    => false,
    ]);
    $data = $this->prepareWorks();

    // favourites
    $favouriteOne = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $data->work->one->id,
      'user_id'       => $user->id,
      'display_order' => 100,
      'updated_at'    => '2016-01-01',
    ]);
    $favouriteTwo = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $data->work->one->id,
      'user_id'       => $data->profile->one->user_id,
      'display_order' => 200,
      'updated_at'    => \Carbon\Carbon::parse('2016-02-01'),
    ]);
    $favouriteThree = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $user->id,
      'user_id'       => $data->profile->one->user_id,
      'display_order' => 300,
      'updated_at'    => '2016-03-01',
    ]);
    $favouriteFour = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $user->id,
      'user_id'       => $data->profile->two->user_id,
      'display_order' => 400,
      'updated_at'    => '2016-04-01',
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/favourites?' . http_build_query([
                        'workId'    => $data->work->one->id,
                        'id'        => $favouriteThree->id,
                     ]));
    $response->assertJson(['code' => 0]);
    $favourites = (json_decode($response->getContent()))->data->favourites;
    self::assertCount(2, $favourites);
    self::assertEquals($favouriteTwo->id, $favourites[0]->id);
  }

  //===============================================
  //        getMultiWorksCommentsAndFavourite
  //===============================================
  public function testGetMultiWorksCommentsAndFavouriteSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
      'nickname'  => 'login-user',
      'is_new'    => false,
      'avatar'    => 'avatar',
    ]);
    
    $data = $this->prepareWorks();

    // comments
    $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => null,
      'content'         => 'comment one',
      'author_id'       => $user->id,
      'replied_user_id' => $data->profile->one->user_id,
      'display_order'   => 100,
    ]);
    $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->one->id,
      'comment_id'      => $commentOne->id,
      'content'         => 'comment two',
      'author_id'       => $data->profile->one->user_id,
      'replied_user_id' => $user->id,
      'display_order'   => 200,
    ]);
    $commentThree = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
      'work_id'         => $data->work->two->id,
      'comment_id'      => null,
      'content'         => 'comment three',
      'author_id'       => $data->profile->two->user_id,
      'replied_user_id' => $data->profile->one->user_id,
      'display_order'   => 300,
    ]);

    // favourites
    $favouriteOne = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $data->work->one->id,
      'user_id'       => $user->id,
      'display_order' => 100,
      'updated_at'    => '2016-01-01',
    ]);
    $favouriteTwo = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
      'work_id'       => $data->work->one->id,
      'user_id'       => $data->profile->one->user_id,
      'display_order' => 200,
      'updated_at'    => \Carbon\Carbon::parse('2016-02-01'),
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/comments-favourites?' . http_build_query([
                        'workIds' => [$data->work->one->id, $data->work->two->id],
                     ]));

    $response->assertJson(['code' => 0]);

    $response = json_decode($response->getContent());
    $res = $response->data->data;
    self::assertCount(2, $res);
    self::assertEquals($data->work->one->id, $res[0]->workId);
    self::assertTrue($res[0]->isFavourite);
    self::assertCount(2, $res[0]->favourites);
    self::assertEquals($data->profile->one->user_id, $res[0]->favourites[0]->userId);
    self::assertTrue(ends_with($res[0]->favourites[0]->avatar, 'avatar-one'));
    self::assertEquals($user->id, $res[0]->favourites[1]->userId);
    self::assertCount(2, $res[0]->comments);
    self::assertEquals($commentTwo->id, $res[0]->comments[0]->commentId);
    self::assertEquals($commentOne->id, $res[0]->comments[0]->repliedCommentId);
    self::assertEquals($data->profile->one->user_id, $res[0]->comments[0]->author->userId);
    self::assertEquals('zhangsan', $res[0]->comments[0]->author->nickname);
    self::assertEquals($user->id, $res[0]->comments[0]->repliedUser->userId);
    self::assertEquals('login-user', $res[0]->comments[0]->repliedUser->nickname);
    self::assertFalse($res[1]->isFavourite);
    self::assertCount(0, $res[1]->favourites);
    self::assertCount(1, $res[1]->comments);
  }

    public function testGetMultiWorksCommentsAndFavouriteSuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $data = $this->prepareWorks();

        // comments
        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => null,
            'content'         => 'comment one',
            'author_id'       => $user->id,
            'replied_user_id' => $data->profile->one->user_id,
            'display_order'   => 100,
        ]);
        $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => $commentOne->id,
            'content'         => 'comment two',
            'author_id'       => $data->profile->one->user_id,
            'replied_user_id' => $user->id,
            'display_order'   => 200,
        ]);
        $commentThree = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->two->id,
            'comment_id'      => null,
            'content'         => 'comment three',
            'author_id'       => $data->profile->two->user_id,
            'replied_user_id' => $data->profile->one->user_id,
            'display_order'   => 300,
        ]);

        // favourites
        $favouriteOne = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'work_id'       => $data->work->one->id,
            'user_id'       => $user->id,
            'display_order' => 100,
            'updated_at'    => '2016-01-01',
        ]);
        $favouriteTwo = factory(\SingPlus\Domains\Works\Models\WorkFavourite::class)->create([
            'work_id'       => $data->work->one->id,
            'user_id'       => $data->profile->one->user_id,
            'display_order' => 200,
            'updated_at'    => \Carbon\Carbon::parse('2016-02-01'),
        ]);

        $response = $this->getJson('v3/works/comments-favourites?' . http_build_query([
                    'workIds' => [$data->work->one->id, $data->work->two->id],
                ]));

        $response->assertJson(['code' => 0]);

        $response = json_decode($response->getContent());
        $res = $response->data->data;
        self::assertCount(2, $res);
        self::assertEquals($data->work->one->id, $res[0]->workId);
        self::assertFalse($res[0]->isFavourite);
        self::assertCount(2, $res[0]->favourites);
        self::assertEquals($data->profile->one->user_id, $res[0]->favourites[0]->userId);
        self::assertTrue(ends_with($res[0]->favourites[0]->avatar, 'avatar-one'));
        self::assertEquals($user->id, $res[0]->favourites[1]->userId);
        self::assertCount(2, $res[0]->comments);
        self::assertEquals($commentTwo->id, $res[0]->comments[0]->commentId);
        self::assertEquals($commentOne->id, $res[0]->comments[0]->repliedCommentId);
        self::assertEquals($data->profile->one->user_id, $res[0]->comments[0]->author->userId);
        self::assertEquals('zhangsan', $res[0]->comments[0]->author->nickname);
        self::assertEquals($user->id, $res[0]->comments[0]->repliedUser->userId);
        self::assertEquals('login-user', $res[0]->comments[0]->repliedUser->nickname);
        self::assertFalse($res[1]->isFavourite);
        self::assertCount(0, $res[1]->favourites);
        self::assertCount(1, $res[1]->comments);
    }

  //=========================================
  //        getChorusStartAccompaniment
  //=========================================
  public function testGetChorusStartAccompanimentSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id, 
      'avatar'    => 'user-one-avatar',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => $userOne->id,
      'chorus_type' => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
      'status'      => 1,
      'chorus_start_info' => [
        'chorus_count'  => 0,
        'resource'      => [
          'zip' => 'chorus-accompaniment',
        ],
      ],
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
                     ->assertJson(['code' => 0]);
    $response = (json_decode($response->getContent()))->data;
    self::assertEquals($userOne->id, $response->author->userId);
    self::assertTrue(ends_with($response->author->avatar, 'user-one-avatar'));
    self::assertTrue(ends_with($response->resource, 'chorus-accompaniment'));
  }

  public function testGetChorusStartAccompanimentFailed_AccompanimentPrepare()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id, 
      'avatar'    => 'user-one-avatar',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => $userOne->id,
      'chorus_type' => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
      'status'      => 2,
      'chorus_start_info' => [
        'chorus_count'  => 0,
      ],
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
                     ->assertJson(['code' => 10430]);
  }

  public function testGetChorusStartAccompanimentFailed_ChorusStartWorkNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);
    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id, 
      'avatar'    => 'user-one-avatar',
    ]);
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'     => $userOne->id,
      'status'      => 2,
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
                     ->assertJson(['code' => 10402]);
  }

    public function testGetChorusStartAccompanimentSuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
        ]);
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'avatar'    => 'user-one-avatar',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'     => $userOne->id,
            'chorus_type' => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
            'status'      => 1,
            'chorus_start_info' => [
                'chorus_count'  => 0,
                'resource'      => [
                    'zip' => 'chorus-accompaniment',
                ],
            ],
        ]);

        $response = $this->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
            ->assertJson(['code' => 0]);
        $response = (json_decode($response->getContent()))->data;
        self::assertEquals($userOne->id, $response->author->userId);
        self::assertTrue(ends_with($response->author->avatar, 'user-one-avatar'));
        self::assertTrue(ends_with($response->resource, 'chorus-accompaniment'));
    }

    public function testGetChorusStartAccompanimentFailed_AccompanimentPrepare_WithoutLogin()
    {

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'avatar'    => 'user-one-avatar',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'     => $userOne->id,
            'chorus_type' => \SingPlus\Contracts\Works\Constants\WorkConstant::CHORUS_TYPE_START,
            'status'      => 2,
            'chorus_start_info' => [
                'chorus_count'  => 0,
            ],
        ]);

        $response = $this->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
            ->assertJson(['code' => 10430]);
    }

    public function testGetChorusStartAccompanimentFailed_ChorusStartWorkNotExists_WithoutLogin()
    {

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'avatar'    => 'user-one-avatar',
        ]);
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'     => $userOne->id,
            'status'      => 2,
        ]);

        $response = $this->getJson(sprintf('v3/works/%s/chorus-accompaniment', $work->id))
            ->assertJson(['code' => 10402]);
    }

  //=========================================
  //        getUserChorusStartWorks
  //=========================================
  public function testGetUserChorusStartWorksSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);

    $data = $this->prepareWorks($user);
    $workChorusStartOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->music->one->id,
      'cover'     => 'chorus-start-cover-one',
      'slides'    => [
        'chorus-one-one', 'chorus-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 2,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 300,
      ],
      'display_order'   => 1000,
    ]);
    $workChorusStartTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->music->one->id,
      'cover'     => 'chorus-start-cover-one',
      'slides'    => [
        'chorus-one-one', 'chorus-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 2,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 500,
      ],
      'display_order'   => 1001,
    ]);
    $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $user->id,
      'music_id'  => $data->music->one->id,
      'cover'     => 'chorus-start-cover-one',
      'slides'    => [
        'chorus-one-one', 'chorus-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 2,
      'chorus_type'   => 10,
      'chorus_join_info' => [
        'origin_work_id'  => $workChorusStartOne->id,
      ],
    ]);

    $musicOne = $data->music->one;
    $musicOne->status = -1;
    $musicOne->save();

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/user/%s/chorus-start-works?', $user->id) . http_build_query([
                        'id'    => $workChorusStartTwo->id,
                        'size'  => 2, 
                     ]))
                     ->assertJson(['code' => 0]);
    $works = (json_decode($response->getContent()))->data->works;
    self::assertCount(1, $works);
    self::assertEquals($workChorusStartOne->id, $works[0]->id);
    self::assertEquals($workChorusStartOne->id, $works[0]->workId);
    self::assertEquals('musicOne "hell"', $works[0]->musicName);
    self::assertEquals(300, $works[0]->chorusCount);
  }


  public function testGetUserChorusStartWorksSuccess_WithoutLogin()
  {
      $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
          'user_id'   => $user->id,
      ]);

      $data = $this->prepareWorks($user);
      $workChorusStartOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id'   => $user->id,
          'music_id'  => $data->music->one->id,
          'cover'     => 'chorus-start-cover-one',
          'slides'    => [
              'chorus-one-one', 'chorus-one-two',
          ],
          'display_order' => 100,
          'comment_count' => 0,
          'favourite_count' => 1,
          'resource'      => 'work-one',
          'duration'      => 128,
          'status'        => 2,
          'chorus_type'   => 1,
          'chorus_start_info' => [
              'chorus_count'  => 300,
          ],
          'display_order'   => 1000,
      ]);
      $workChorusStartTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id'   => $user->id,
          'music_id'  => $data->music->one->id,
          'cover'     => 'chorus-start-cover-one',
          'slides'    => [
              'chorus-one-one', 'chorus-one-two',
          ],
          'display_order' => 100,
          'comment_count' => 0,
          'favourite_count' => 1,
          'resource'      => 'work-one',
          'duration'      => 128,
          'status'        => 2,
          'chorus_type'   => 1,
          'chorus_start_info' => [
              'chorus_count'  => 500,
          ],
          'display_order'   => 1001,
      ]);
      $workChorusJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'user_id'   => $user->id,
          'music_id'  => $data->music->one->id,
          'cover'     => 'chorus-start-cover-one',
          'slides'    => [
              'chorus-one-one', 'chorus-one-two',
          ],
          'display_order' => 100,
          'comment_count' => 0,
          'favourite_count' => 1,
          'resource'      => 'work-one',
          'duration'      => 128,
          'status'        => 2,
          'chorus_type'   => 10,
          'chorus_join_info' => [
              'origin_work_id'  => $workChorusStartOne->id,
          ],
      ]);

      $response = $this->getJson(sprintf('v3/user/%s/chorus-start-works?', $user->id) . http_build_query([
                  'id'    => $workChorusStartTwo->id,
                  'size'  => 2,
              ]))
          ->assertJson(['code' => 0]);
      $works = (json_decode($response->getContent()))->data->works;
      self::assertCount(1, $works);
      self::assertEquals($workChorusStartOne->id, $works[0]->id);
      self::assertEquals($workChorusStartOne->id, $works[0]->workId);
      self::assertEquals('musicOne "hell"', $works[0]->musicName);
      self::assertEquals(300, $works[0]->chorusCount);
  }

  //=========================================
  //        getChorusJoinsOfChorusStart
  //=========================================
  public function testGetChorusJoinsOfChorusStartSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);

    $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id,
      'nickname'  => 'user-one',
      'avatar'    => 'user-one-avatar',
    ]);
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'chorus_type' => 1,
      'chorus_start_info' => [
        'chorus_count'  => 100,
      ],
    ]);
    $chorusJoinOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'user_id'     => $userOne->id,
      'name'        => 'chorus-join-one',
      'created_at'  => '2016-07-01 00:00:00',
      'chorus_type' => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $originWork->id,
      ],
      'display_order' => 100,
    ]);
    $chorusJoinTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'user_id'     => $userOne->id,
      'name'        => 'chorus-join-one',
      'created_at'  => '2016-07-02 00:00:00',
      'chorus_type' => 10,
      'chorus_join_info'  => [
        'origin_work_id'  => $originWork->id,
      ],
      'display_order' => 200,
    ]);
    $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'user_id'     => $userOne->id,
      'name'        => 'chorus-join-one',
      'created_at'  => '2016-07-03 00:00:00',
      'display_order' => 300,
    ]);

    $url = sprintf('v3/works/chorus-start/%s/chorus-joins', $originWork->id);
    $response = $this->actingAs($user)
                     ->getJson($url . '?' . http_build_query([
                        'size'  => 1,
                     ]))
                     ->assertJson(['code' => 0]);
    $joinWorks = (json_decode($response->getContent()))->data->works;
    self::assertCount(1, $joinWorks);
    self::assertEquals($chorusJoinTwo->id, $joinWorks[0]->id);
    self::assertEquals($chorusJoinTwo->id, $joinWorks[0]->workId);
    self::assertEquals($userOne->id, $joinWorks[0]->author->userId);
    self::assertEquals(strtotime('2016-07-02 00:00:00'), $joinWorks[0]->publishedTimestamp);
  }

    public function testGetChorusJoinsOfChorusStartSuccess_WithoutLogin()
    {
        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $userOne->id,
            'nickname'  => 'user-one',
            'avatar'    => 'user-one-avatar',
        ]);
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
        $originWork = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'    => $music->id,
            'chorus_type' => 1,
            'chorus_start_info' => [
                'chorus_count'  => 100,
            ],
        ]);
        $chorusJoinOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'    => $music->id,
            'user_id'     => $userOne->id,
            'name'        => 'chorus-join-one',
            'created_at'  => '2016-07-01 00:00:00',
            'chorus_type' => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $originWork->id,
            ],
            'display_order' => 100,
        ]);
        $chorusJoinTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'    => $music->id,
            'user_id'     => $userOne->id,
            'name'        => 'chorus-join-one',
            'created_at'  => '2016-07-02 00:00:00',
            'chorus_type' => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $originWork->id,
            ],
            'display_order' => 200,
        ]);
        $workThree = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'    => $music->id,
            'user_id'     => $userOne->id,
            'name'        => 'chorus-join-one',
            'created_at'  => '2016-07-03 00:00:00',
            'display_order' => 300,
        ]);

        $url = sprintf('v3/works/chorus-start/%s/chorus-joins', $originWork->id);
        $response = $this->getJson($url . '?' . http_build_query([
                    'size'  => 1,
                ]))
            ->assertJson(['code' => 0]);
        $joinWorks = (json_decode($response->getContent()))->data->works;
        self::assertCount(1, $joinWorks);
        self::assertEquals($chorusJoinTwo->id, $joinWorks[0]->id);
        self::assertEquals($chorusJoinTwo->id, $joinWorks[0]->workId);
        self::assertEquals($userOne->id, $joinWorks[0]->author->userId);
        self::assertEquals(strtotime('2016-07-02 00:00:00'), $joinWorks[0]->publishedTimestamp);
    }

  //=========================================
  //        musicChorusStartWorkExistence
  //=========================================
  public function testMusicChorusStartWorkExistenceSuccess_Existence()
  {
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
      'chorus_type' => 1,
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id, 
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/musics/%s/chorus-start-work/existence', $music->id))
                     ->assertJson([
                      'code'  => 0,
                      'data'  => [
                        'existence' => true,
                      ]
                     ]);
  }

  public function testMusicChorusStartWorkExistenceSuccess_NotExistence()
  {
    $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
    $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'music_id'    => $music->id,
    ]);
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/musics/%s/chorus-start-work/existence', $music->id))
                     ->assertJson([
                      'code'  => 0,
                      'data'  => [
                        'existence' => false,
                      ]
                     ]);
  }

  public function testMusicChorusStartWorkExistenceSuccess_WithoutLogin()
  {
      $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
      $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
          'music_id'    => $music->id,
          'chorus_type' => 1,
      ]);

      $response = $this->getJson(sprintf('v3/musics/%s/chorus-start-work/existence', $music->id))
          ->assertJson([
              'code'  => 0,
              'data'  => [
                  'existence' => true,
              ]
          ]);
  }

    public function testMusicChorusStartWorkExistenceSuccess_NotExistence_WithoutLogin()
    {
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create();
        $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'music_id'    => $music->id,
        ]);

        $response = $this->getJson(sprintf('v3/musics/%s/chorus-start-work/existence', $music->id))
            ->assertJson([
                'code'  => 0,
                'data'  => [
                    'existence' => false,
                ]
            ]);
    }

  //=================================
  //        getRecommendWorkSheet
  //=================================
  public function testGetRecommendWorkSheetSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
    ]);
    $data = $this->prepareWorks();
    $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $data->work->two->user_id,
      'music_id'  => $data->work->two->music_id,
      'cover'     => 'work-cover-three',
      'slides'    => [
        'work-three-one', 'work-three-two',
      ],
      'display_order' => 10,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-three',
      'status'        => 1,
      'chorus_type'   => 10,
      'chorus_join_info' => [
        'origin_work_id'  => $data->work->two->id,
      ],
      'description'   => 'work three desc',
    ]);
    $sheet = factory(\SingPlus\Domains\Works\Models\RecommendWorkSheet::class)->create([
      'title'     => 'my sheet',
      'cover'     => 'sheet-cover',
      'comments'  => 'Good morning!',
      'works_ids' => [$data->work->two->id, $data->work->one->id, $work->id],
    ]);
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $user->id,
      'followings'        => [$data->user->one->id],
      'following_details' => [
        [
          'user_id'   => $data->user->one->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
        ]
      ]
    ]);
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $data->user->two->id,
      'followings'        => [$user->id],
      'following_details' => [
        [
          'user_id'   => $user->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
        ]
      ]
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $data->user->two->id    => [
                    'is_following'  => false,
                    'follow_at'     => null,
                    'is_follower'   => true,
                    'followed_at'   => '2015-02-02 00:00:00',
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/works/sheets/%s', $sheet->id))
                     ->assertJson(['code' => 0]);
    $res = (json_decode($response->getContent()))->data->sheet;

    self::assertEquals('my sheet', $res->title);
    self::assertTrue(ends_with($res->cover, 'sheet-cover'));
    self::assertEquals('Good morning!', $res->comments);
    self::assertCount(3, $res->works);
    self::assertEquals($data->work->two->id, $res->works[0]->workId);
    self::assertEquals('musicTwo', $res->works[0]->name);
    self::assertEquals('work two desc', $res->works[0]->description);
    self::assertEquals(1, $res->works[0]->favouriteNum);
    self::assertEquals(0, $res->works[0]->listenNum);
    self::assertEquals($data->user->two->id, $res->works[0]->author->userId);
    self::assertEquals('lisi', $res->works[0]->author->nickname);
    self::assertFalse($res->works[0]->author->isFollowing);
    self::assertTrue($res->works[0]->author->isFollower);
    self::assertTrue($res->works[1]->author->isFollowing);
    self::assertFalse($res->works[1]->author->isFollower);
    self::assertEquals($work->id, $res->works[2]->workId);
    self::assertEquals(10, $res->works[2]->chorusType);
    self::assertNotNull($res->works[2]->originWorkUser);
    self::assertEquals($data->user->two->id, $res->works[2]->originWorkUser->userId);
  }

  public function testGetRecommendWorkSheetSuccess_NolyNormalWorkShow()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
    ]);
    $data = $this->prepareWorks();
    $workOne = $data->work->one;
    $workOne->status = -1;
    $workOne->save();
    $sheet = factory(\SingPlus\Domains\Works\Models\RecommendWorkSheet::class)->create([
      'title'     => 'my sheet',
      'cover'     => 'sheet-cover',
      'comments'  => 'Good morning!',
      'works_ids' => [$data->work->one->id, $data->work->two->id],
    ]);
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $user->id,
      'followings'        => [$data->user->one->id],
      'following_details' => [
        [
          'user_id'   => $data->user->one->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
        ]
      ]
    ]);
    factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
      'user_id'           => $data->user->two->id,
      'followings'        => [$user->id],
      'following_details' => [
        [
          'user_id'   => $user->id,
          'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
        ]
      ]
    ]);

    $this->mockHttpClient(json_encode([
        'code'  => 0,
        'data'  => [
            'relation'  => [
                $data->user->one->id    => [
                    'is_following'=> true,
                    'follow_at'=> '2015-01-01 00:00:00',
                    'is_follower'=> false,
                    'followed_at'=> null,
                ],
                $data->user->two->id    => [
                    'is_following'  => false,
                    'follow_at'     => null,
                    'is_follower'   => true,
                    'followed_at'   => '2015-02-02 00:00:00',
                ],
            ],
        ],
    ]));

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/works/sheets/%s', $sheet->id))
                     ->assertJson(['code' => 0]);
    $res = (json_decode($response->getContent()))->data->sheet;

    self::assertEquals('my sheet', $res->title);
    self::assertTrue(ends_with($res->cover, 'sheet-cover'));
    self::assertEquals('Good morning!', $res->comments);
    self::assertCount(1, $res->works);
  }

  public function testGetRecommendWorkSheetFailed_SheetNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $user->id,
    ]);

    $response = $this->actingAs($user)
                     ->getJson('v3/works/sheets/d4d09f25cefe47cd9c1c82bd97b63303')
                     ->assertJson(['code' => 10406]);
  }

    public function testGetRecommendWorkSheetSuccess_WithoutLogiin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
        ]);
        $data = $this->prepareWorks();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $data->work->two->user_id,
            'music_id'  => $data->work->two->music_id,
            'cover'     => 'work-cover-three',
            'slides'    => [
                'work-three-one', 'work-three-two',
            ],
            'display_order' => 10,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-three',
            'status'        => 1,
            'chorus_type'   => 10,
            'chorus_join_info' => [
                'origin_work_id'  => $data->work->two->id,
            ],
            'description'   => 'work three desc',
        ]);
        $sheet = factory(\SingPlus\Domains\Works\Models\RecommendWorkSheet::class)->create([
            'title'     => 'my sheet',
            'cover'     => 'sheet-cover',
            'comments'  => 'Good morning!',
            'works_ids' => [$data->work->two->id, $data->work->one->id, $work->id],
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $user->id,
            'followings'        => [$data->user->one->id],
            'following_details' => [
                [
                    'user_id'   => $data->user->one->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $data->user->two->id,
            'followings'        => [$user->id],
            'following_details' => [
                [
                    'user_id'   => $user->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);

        $response = $this->getJson(sprintf('v3/works/sheets/%s', $sheet->id))
            ->assertJson(['code' => 0]);
        $res = (json_decode($response->getContent()))->data->sheet;

        self::assertEquals('my sheet', $res->title);
        self::assertTrue(ends_with($res->cover, 'sheet-cover'));
        self::assertEquals('Good morning!', $res->comments);
        self::assertCount(3, $res->works);
        self::assertEquals($data->work->two->id, $res->works[0]->workId);
        self::assertEquals('musicTwo', $res->works[0]->name);
        self::assertEquals('work two desc', $res->works[0]->description);
        self::assertEquals(1, $res->works[0]->favouriteNum);
        self::assertEquals(0, $res->works[0]->listenNum);
        self::assertEquals($data->user->two->id, $res->works[0]->author->userId);
        self::assertEquals('lisi', $res->works[0]->author->nickname);
        self::assertFalse($res->works[0]->author->isFollowing);
        self::assertFalse($res->works[0]->author->isFollower);
        self::assertFalse($res->works[1]->author->isFollowing);
        self::assertFalse($res->works[1]->author->isFollower);
        self::assertEquals($work->id, $res->works[2]->workId);
        self::assertEquals(10, $res->works[2]->chorusType);
        self::assertNotNull($res->works[2]->originWorkUser);
        self::assertEquals($data->user->two->id, $res->works[2]->originWorkUser->userId);
    }

    public function testGetRecommendWorkSheetSuccess_NolyNormalWorkShow_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
        ]);
        $data = $this->prepareWorks();
        $workOne = $data->work->one;
        $workOne->status = -1;
        $workOne->save();
        $sheet = factory(\SingPlus\Domains\Works\Models\RecommendWorkSheet::class)->create([
            'title'     => 'my sheet',
            'cover'     => 'sheet-cover',
            'comments'  => 'Good morning!',
            'works_ids' => [$data->work->one->id, $data->work->two->id],
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $user->id,
            'followings'        => [$data->user->one->id],
            'following_details' => [
                [
                    'user_id'   => $data->user->one->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $data->user->two->id,
            'followings'        => [$user->id],
            'following_details' => [
                [
                    'user_id'   => $user->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);

        $response = $this->getJson(sprintf('v3/works/sheets/%s', $sheet->id))
            ->assertJson(['code' => 0]);
        $res = (json_decode($response->getContent()))->data->sheet;

        self::assertEquals('my sheet', $res->title);
        self::assertTrue(ends_with($res->cover, 'sheet-cover'));
        self::assertEquals('Good morning!', $res->comments);
        self::assertCount(1, $res->works);
    }

    public function testGetRecommendWorkSheetFailed_SheetNotExists_WithoutLogin()
    {
        $response = $this->getJson('v3/works/sheets/d4d09f25cefe47cd9c1c82bd97b63303')
            ->assertJson(['code' => 10406]);
    }

    public function testGetRecommendWorkSheetSuccess_WithoutLogin()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
        ]);
        $data = $this->prepareWorks();
        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'   => $data->work->two->user_id,
            'music_id'  => $data->work->two->music_id,
            'cover'     => 'work-cover-three',
            'slides'    => [
                'work-three-one', 'work-three-two',
            ],
            'display_order' => 10,
            'comment_count' => 0,
            'favourite_count' => 1,
            'resource'      => 'work-three',
            'status'        => 1,
            'chorus_type'   => 10,
            'chorus_join_info' => [
                'origin_work_id'  => $data->work->two->id,
            ],
            'description'   => 'work three desc',
        ]);
        $sheet = factory(\SingPlus\Domains\Works\Models\RecommendWorkSheet::class)->create([
            'title'     => 'my sheet',
            'cover'     => 'sheet-cover',
            'comments'  => 'Good morning!',
            'works_ids' => [$data->work->two->id, $data->work->one->id, $work->id],
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $user->id,
            'followings'        => [$data->user->one->id],
            'following_details' => [
                [
                    'user_id'   => $data->user->one->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);
        factory(\SingPlus\Domains\Friends\Models\UserFollowing::class)->create([
            'user_id'           => $data->user->two->id,
            'followings'        => [$user->id],
            'following_details' => [
                [
                    'user_id'   => $user->id,
                    'follow_at' => \Carbon\Carbon::parse('2017-07-11')->getTimestamp(),
                ]
            ]
        ]);

        $response = $this->getJson(sprintf('v3/works/sheets/%s', $sheet->id))
            ->assertJson(['code' => 0]);
        $res = (json_decode($response->getContent()))->data->sheet;

        self::assertEquals('my sheet', $res->title);
        self::assertTrue(ends_with($res->cover, 'sheet-cover'));
        self::assertEquals('Good morning!', $res->comments);
        self::assertCount(3, $res->works);
        self::assertEquals($data->work->two->id, $res->works[0]->workId);
        self::assertEquals('musicTwo', $res->works[0]->name);
        self::assertEquals('work two desc', $res->works[0]->description);
        self::assertEquals(1, $res->works[0]->favouriteNum);
        self::assertEquals(0, $res->works[0]->listenNum);
        self::assertEquals($data->user->two->id, $res->works[0]->author->userId);
        self::assertEquals('lisi', $res->works[0]->author->nickname);
        self::assertFalse($res->works[0]->author->isFollowing);
        self::assertFalse($res->works[0]->author->isFollower);
        self::assertFalse($res->works[1]->author->isFollowing);
        self::assertFalse($res->works[1]->author->isFollower);
        self::assertEquals($work->id, $res->works[2]->workId);
        self::assertEquals(10, $res->works[2]->chorusType);
        self::assertNotNull($res->works[2]->originWorkUser);
        self::assertEquals($data->user->two->id, $res->works[2]->originWorkUser->userId);
    }

    //===============================================
    //        getMultiWorksCommentsAndGifts
    //===============================================
    public function testGetMultiWorksCommentsAndGifts()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $data = $this->prepareWorks();

        // comments
        $commentOne = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => null,
            'content'         => 'comment one',
            'author_id'       => $user->id,
            'replied_user_id' => $data->profile->one->user_id,
            'display_order'   => 100,
        ]);
        $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->one->id,
            'comment_id'      => $commentOne->id,
            'content'         => 'comment two',
            'author_id'       => $data->profile->one->user_id,
            'replied_user_id' => $user->id,
            'display_order'   => 200,
        ]);
        $commentThree = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'work_id'         => $data->work->two->id,
            'comment_id'      => null,
            'content'         => 'comment three',
            'author_id'       => $data->profile->two->user_id,
            'replied_user_id' => $data->profile->one->user_id,
            'display_order'   => 300,
        ]);

        // contributions

        $giftContribOne = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'work_id' => $data->work->one->id,
            'sender_id' => $data->profile->one->user_id,
            'receiver_id' => $data->work->one->user_id,
            'update_at' => '2018-01-01',
        ]);


        $giftContribTwo = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'work_id' => $data->work->one->id,
            'sender_id' => $data->profile->two->user_id,
            'receiver_id' => $data->work->one->user_id,
            'update_at' => '2018-01-02',
        ]);

        $giftContribThree = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'work_id' => $data->work->two->id,
            'sender_id' => $data->profile->one->user_id,
            'receiver_id' => $data->work->two->user_id,
            'update_at' => '2018-01-03',
        ]);

        $giftContribFour = factory(\SingPlus\Domains\Gifts\Models\GiftContribution::class)->create([
            'work_id' => $data->work->two->id,
            'sender_id' => $data->profile->two->user_id,
            'receiver_id' => $data->work->two->user_id,
            'update_at' => '2018-01-04',
        ]);



        $response = $this->actingAs($user)
            ->getJson('v3/works/comments-gifts?' . http_build_query([
                    'workIds' => [$data->work->one->id, $data->work->two->id],
                ]));

        $response->assertJson(['code' => 0]);
        $response = json_decode($response->getContent());
        $res = $response->data->data;
        self::assertCount(2, $res);
        self::assertEquals($data->work->one->id, $res[0]->workId);
        self::assertCount(2, $res[0]->gifts);
        self::assertTrue(ends_with($res[0]->gifts[0]->avatar, 'avatar-one'));
        self::assertCount(2, $res[0]->comments);
        self::assertEquals($commentTwo->id, $res[0]->comments[0]->commentId);
        self::assertEquals($commentOne->id, $res[0]->comments[0]->repliedCommentId);
        self::assertEquals($data->profile->one->user_id, $res[0]->comments[0]->author->userId);
        self::assertEquals('zhangsan', $res[0]->comments[0]->author->nickname);
        self::assertEquals($user->id, $res[0]->comments[0]->repliedUser->userId);
        self::assertEquals('login-user', $res[0]->comments[0]->repliedUser->nickname);
        self::assertCount(2, $res[1]->gifts);
        self::assertCount(1, $res[1]->comments);
    }

    //===============================================
    //              modifyWorkInfo
    //===============================================
    public function testModifyWorkInfoSuccess(){
        $this->expectsEvents(\SingPlus\Events\Works\WorkUpdateTags::class);
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/works/modify-work', [
                'workId' => $work->id,
                'secret' => 1,
                'coverImageId' => $image->id,
                'description'  => 'desc two'
            ])->assertJson(['code' => 0]);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'is_private' => 1,
            'description' => 'desc two',
            'cover' => 'images-origin/f9e65558153c11e79ba952540085b9d0',
        ]);
    }

    public function testModifyWorkInfoSuccess_OnlyPrivate(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/works/modify-work', [
                'workId' => $work->id,
                'secret' => 1,
            ])->assertJson(['code' => 0]);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'is_private' => 1,
            'description' => 'desc one',
            'cover' => '33d593e870dc4ea1b88404fb0856e43e',
        ]);
    }

    public function testModifyWorkInfoFailed_NotLogin(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->postJson('v3/works/modify-work', [
            'workId' => $work->id,
            'secret' => 1,
        ])->assertJson(['code' => 10101]);

    }


    public function testModifyWorkInfoFailed_WithOthersWork(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userTwo->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/works/modify-work', [
                'workId' => $work->id,
                'secret' => 1,
                'coverImageId' => $image->id,
                'description'  => 'desc two'
            ])->assertJson(['code' => 10440]);
    }

    public function testModifyWorkInfoFailed_WorkNotExist(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $userTwo->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/works/modify-work', [
                'workId' => '33d593e870dc4ea1b88404fb0856e43e',
                'secret' => 1,
                'coverImageId' => $image->id,
                'description'  => 'desc two'
            ])->assertJson(['code' => 10402]);
    }

    public function testModifyWorkInfoFailed_ImageNotExist(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'login-user',
            'is_new'    => false,
            'avatar'    => 'avatar',
        ]);

        $image = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
            'user_id' => $user->id,
            'uri' => 'images-origin/f9e65558153c11e79ba952540085b9d0'
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id,
            'cover'  => '33d593e870dc4ea1b88404fb0856e43e',
            'description' => 'desc one',
            'is_private' => 0,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/works/modify-work', [
                'workId' => $work->id,
                'secret' => 1,
                'coverImageId' => 'f9e65558153c11e79ba952540085b9d0',
                'description'  => 'desc two'
            ])->assertJson(['code' => 10109]);

        $this->assertDatabaseHas('works', [
            'user_id' => $user->id,
            'is_private' => 0,
            'description' => 'desc one',
            'cover' => '33d593e870dc4ea1b88404fb0856e43e',
        ]);
    }


  private function prepareWorks($user = null)
  {
    $userOne = $user ?: factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $userTwo = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $artistOne = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Simon',
    ]);
    $artistTwo = factory(\SingPlus\Domains\Musics\Models\Artist::class)->create([
      'name'  => 'Plum',
    ]);

    $profileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userOne->id,
      'nickname'  => 'zhangsan', 
      'gender'    => 'M',
      'avatar'    => 'avatar-one',
    ]);
    $profileTwo = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id'   => $userTwo->id,
      'nickname'  => 'lisi', 
      'avatar'    => 'avatar-two',
    ]);
    $musicOne = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicOne "hell"',
      'lyrics'  => 'music-lyric-one',
      'artists' => [$artistOne->id, $artistTwo->id],
      'covers'  => ['music-one-cover-one', 'music-one-cover-two'],
    ]);
    $musicTwo = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
      'name'    => 'musicTwo',
      'lyrics'  => 'music-lyric-two',
      'artists' => [$artistOne->id],
    ]);
    $workOne = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userOne->id,
      'music_id'  => $musicOne->id,
      'cover'     => 'work-cover-one',
      'slides'    => [
        'work-one-one', 'work-one-two',
      ],
      'display_order' => 100,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-one',
      'duration'      => 128,
      'status'        => 2,
    ]);
    $workTwo = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
      'user_id'   => $userTwo->id,
      'music_id'  => $musicTwo->id,
      'cover'     => 'work-cover-two',
      'slides'    => [
        'work-two-one', 'work-two-two',
      ],
      'display_order' => 200,
      'comment_count' => 0,
      'favourite_count' => 1,
      'resource'      => 'work-two',
      'status'        => 2,
      'chorus_type'   => 1,
      'chorus_start_info' => [
        'chorus_count'  => 123,
      ],
      'description'   => 'work two desc',
    ]);
    $selectOne = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id'       => $workOne->id,
      'display_order' => 100,
      'country_abbr'  => '-*',
    ]);
    $selectTwo = factory(\SingPlus\Domains\Works\Models\WorkSelection::class)->create([
      'work_id' => $workTwo->id,
      'display_order' => 200,
      'country_abbr'  => 'TZ',
    ]);

    return (object) [
      'user'   => (object) [
        'one' => $userOne,
        'two' => $userTwo,
      ],
      'profile' => (object) [
        'one' => $profileOne,
        'two' => $profileTwo,
      ],
      'music' => (object) [
        'one' => $musicOne,
        'two' => $musicTwo,
      ],
      'work'  => (object) [
        'one' => $workOne,
        'two' => $workTwo,
      ],
      'selections'  => (object) [
        'one' => $selectOne,
        'two' => $selectTwo,
      ],
    ];
  }

    private function mockStorage()
    {
        $storageService = Mockery::mock(\SingPlus\Contracts\Storages\Services\StorageService::class);
        $this->app[\SingPlus\Contracts\Storages\Services\StorageService::class ] = $storageService;

        return $storageService;
    }

    private function mockDailyTaskService(){
        $dailyTaskService = Mockery::mock(\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class);
        $this->app[\SingPlus\Contracts\DailyTask\Services\DailyTaskService::class ] = $dailyTaskService;
        return $dailyTaskService;
    }

  private function mockPopularityHierarchyService(){
      $popularityHierarchyService = Mockery::mock(\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class);
      $this->app[\SingPlus\Contracts\Hierarchy\Services\PopularityHierarchyService::class ] = $popularityHierarchyService;
      return $popularityHierarchyService;
  }

  /** 
   * mock http client and response
   * 模拟http响应
   *
   * Usage: $this->mockHttpClient(json_encode([
   *          'code' => 0,
   *          'data' => [],
   *          'message'   => 'ok',
   *          ]))
   *
   * @param string $respBody 模拟响应body
   * @param int $respCode 模拟响应http status
   * @param array $respHeader 模拟响应http header
   */
  protected function mockHttpClient(
    $respBody,
    $respCode = 200,
    array $respHeader = []
  ) {
    $mock = new MockHandler();
    $mock->append(new Response($respCode, $respHeader, $respBody));

    $handler = HandlerStack::create($mock);

    $this->app[\GuzzleHttp\ClientInterface::class] = new Client(['handler' => $handler]);
  }
}
