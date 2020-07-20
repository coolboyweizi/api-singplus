<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午4:03
 */

namespace FeatureTest\SingPlus\Jobs;


use FeatureTest\SingPlus\TestCase;
use Illuminate\Support\Facades\Queue;
use SingPlus\Jobs\IMSendTopicMsg;

class IMSendTopicMsgTest extends TestCase
{

    public function testImSendTopicMsgJobSuccess()
    {
        Queue::fake();

        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['topic_country_chr', 'topic_country_br'],
            'type'  => 'topic_cover_of_day',
            'data'  => [],
        ])
            ->assertJson(['code' => 0]);

        Queue::assertPushed(IMSendTopicMsg::class,function ($job){
            return $job->topic == 'topic_country_other'
                && $job->type == 'topic_cover_of_day'
                && $job->customizeData == []
                && $job->data == [];
        });

    }

    public function testImSendTopicMsgJobSuccess_WhenWithSameTaskIdAndEmptyData(){
        Queue::fake();
        $taskId = str_random(12);
        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['topic_country_chr', 'topic_country_br'],
            'type'  => 'topic_cover_of_day',
            'data'  => [],
            'taskId' => $taskId
        ])
            ->assertJson(['code' => 0]);

        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['topic_country_ko', 'topic_country_jp'],
            'type'  => 'topic_cover_of_day',
            'data'  => [],
            'taskId' => $taskId
        ])
            ->assertJson(['code' => 0]);

        self::assertTrue(Queue::pushed(IMSendTopicMsg::class,function ($job){
                return $job->topic == 'topic_country_other'
                    && $job->type == 'topic_cover_of_day'
                    && $job->customizeData == []
                    && $job->data == [];
            })->count() == 1);
    }

    public function testImSendTopicMsgJobSuccess_WhenDiffTaskIdAndHasData(){
        Queue::fake();
        $taskId = str_random(12);
        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['topic_country_chr', 'topic_country_br'],
            'type'  => 'topic_cover_of_day',
            'data'  => ['b' => 2, 'a' => 1],
            'taskId' => $taskId
        ])
            ->assertJson(['code' => 0]);

        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['topic_country_ko', 'topic_country_jp'],
            'type'  => 'topic_cover_of_day',
            'data'  => ['a' => 1, 'b' => 2],
            'taskId' => sprintf('%s1', $taskId)
        ])
            ->assertJson(['code' => 0]);

        self::assertTrue(Queue::pushed(IMSendTopicMsg::class,function ($job){
                return $job->topic == 'topic_country_other'
                    && $job->type == 'topic_cover_of_day'
                    && $job->customizeData == [ 'b'=> 2,'a' => 1]
                    && $job->data == [];
            })->count() == 1);
    }

}