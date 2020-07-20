<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午4:02
 */

namespace FeatureTest\SingPlus\Jobs;


use FeatureTest\SingPlus\TestCase;
use Illuminate\Support\Facades\Queue;
use SingPlus\Jobs\IMSendGroupMsg;

class IMSendGroupMsgTest extends TestCase
{

    public function testImSendGroupMsgJobSuccess()
    {
        Queue::fake();

        $response = $this->postJson('api/notification/push-messages', [
            'tos'   => ['aaaaaaaa', 'bbbbbbbb'],
            'type'  => 'user_new_conversion',
            'data'  => [],
            'toUserIds' => ['aaaa1aaaa', 'bbbb1bbbb'],
        ])
            ->assertJson(['code' => 0]);

        Queue::assertPushed(IMSendGroupMsg::class,function ($job){
           return $job->receivers == ['aaaa1aaaa', 'bbbb1bbbb']
               && $job->type == 'user_new_conversion'
               && $job->customizeData == []
               && $job->data == [];
        });

    }

}