<?php

namespace FeatureTest\SingPlus\Commands;

use Carbon\Carbon;
use Cache;
use Artisan;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class ClearExpiredWorkUploadTaskTest extends TestCase
{
  use MongodbClearTrait;

  public function testClearSuccess_WithDefaultBefore()
  {
    $data = $this->prepareTask();

    Artisan::call('work:clear-upload-task');

    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->one->id,
    ]);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->two->id,
    ]);
    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $data->three->id,
    ]);
    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $data->four->id,
    ]);
  }

  public function testClearSuccess_BeforeInvalid()
  {
    $data = $this->prepareTask();

    Artisan::call('work:clear-upload-task', [
      'before'  => -1, 
    ]);

    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->one->id,
    ]);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->two->id,
    ]);
    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $data->three->id,
    ]);
    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $data->four->id,
    ]);
  }

  public function testClearSuccess_SpecifiedBefore()
  {
    $data = $this->prepareTask();

    Artisan::call('work:clear-upload-task', [
      'before'  => 3, 
    ]);

    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->one->id,
    ]);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->two->id,
    ]);
    $this->assertDatabaseHas('work_upload_tasks', [
      '_id' => $data->three->id,
    ]);
    $this->assertDatabaseMissing('work_upload_tasks', [
      '_id' => $data->four->id,
    ]);
  }

  private function prepareTask()
  {
    $one = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'created_at'  => Carbon::today(),
    ]);
    $two = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'created_at'  => Carbon::today()->subDays(1),
    ]);
    $three = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'created_at'  => Carbon::today()->subDays(2),
    ]);
    $four = factory(\SingPlus\Domains\Works\Models\WorkUploadTask::class)->create([
      'created_at'  => Carbon::today()->subDays(3),
    ]);

    return (object) [
      'one'   => $one,
      'two'   => $two,
      'three' => $three,
      'four'  => $four,
    ];
  }
}
