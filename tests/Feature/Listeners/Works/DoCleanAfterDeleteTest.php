<?php

namespace FeatureTest\SingPlus\Listeners\Works;

use Mockery;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use SingPlus\Events\Works\WorkDeleted as WorkDeletedEvent;

class DoCleanAfterDeleteTest extends TestCase
{
    use MongodbClearTrait; 


    public function testDoCleanAfterDeletedSuccess()
    {
        $music = factory(\SingPlus\Domains\Musics\Models\Music::class)->create([
            'name'  => 'my-music', 
        ]);
        $workStart = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id'       => '2af8aa3d7fe3401d9b9194dc102c9c48',
            'music_id'      => $music->id,
            'chorus_type'   => 1,
            'chorus_start_info' => [
                'chorus_count'  => 1,
            ],
            'status'        => 1,
        ]);
        $workJoin = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'name'              => 'work-join',
            'user_id'           => 'e231be0180b9411aafbc5b73d1b67901',
            'music_id'          => $music->id,
            'chorus_type'       => 10,
            'chorus_join_info'  => [
                'origin_work_id'  => $workStart->id,
            ],
            'description'       => 'morning!',
            'status'            => -1,
        ]);
        Cache::shouldReceive('get')
             ->once()
             ->with(sprintf('work:%s:listennum', $workJoin->id))
             ->andReturn(100);

        $event = new WorkDeletedEvent($workJoin->id);
        $success = $this->getListener()->handle($event);

        self::assertDatabaseHas('works', [
            '_id'               => $workStart->id,
            'chorus_start_info' => [
                'chorus_count'  => 0,
            ],
        ]);
    }

    private function getListener()
    {
        return $this->app->make(\SingPlus\Listeners\Works\DoCleanAfterDelete::class);
    }
}
