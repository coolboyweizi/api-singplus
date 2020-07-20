<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/3/19
 * Time: 下午3:21
 */

namespace FeatureTest\SingPlus\Controllers;

use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;

class SyncInfoControllerTest extends TestCase
{
    use MongodbClearTrait;

    //=================================
    //        accompanimentSync
    //=================================
    public function testAccompanimentSyncSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $data = [
            [
                'id' => '1111',
                'name' => 'ssss',
            ],
            [
                'id' => '2222',
                'name' => 'aaaa',
            ]
        ];

        $response = $this->actingAs($user)
            ->postJson('v3/user/accompaniment/sync', $data);

        $response = json_decode($response->getContent());

        $this->assertDatabaseHas('user_sync_info', [
            'user_id' => $user->id,
            'type'    => 'accompaniment',
            'data'    => json_encode($data)
        ]);

    }

    public function testAccompanimentSyncSuccess_WithCombineAction(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $oldData = [
            [
                'id' => '3333',
                'name' => 'xxxx',
            ],
            [
                'id' => '2222',
                'name' => 'aaaa',
            ]
        ];

        $syncInfo = factory(\SingPlus\Domains\Sync\Models\SyncInfo::class)->create([
            'user_id' => $user->id,
            'data' => json_encode($oldData)
        ]);

        $data = [
            [
                'id' => '1111',
                'name' => 'ssss',
            ],
            [
                'id' => '2222',
                'name' => 'aaaa',
            ]
        ];

        $response = $this->actingAs($user)
            ->postJson('v3/user/accompaniment/sync', $data);

        $response = json_decode($response->getContent());

        $this->assertDatabaseHas('user_sync_info', [
            'user_id' => $user->id,
            'type'    => 'accompaniment',
            'data'    => json_encode([
                [
                    'id' => '3333',
                    'name' => 'xxxx',
                ],
                [
                    'id' => '2222',
                    'name' => 'aaaa',
                ],
                [
                    'id' => '1111',
                    'name' => 'ssss',
                ],
            ])
        ]);
    }

    //=================================
    //        accompanimentRemoveItem
    //=================================
    public function testAccompanimentRemoveSuccess(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();

        $oldData = [
            [
                'id' => '3333',
                'name' => 'xxxx',
            ],
            [
                'id' => '2222',
                'name' => 'aaaa',
            ]
        ];

        $syncInfo = factory(\SingPlus\Domains\Sync\Models\SyncInfo::class)->create([
            'user_id' => $user->id,
            'data' => json_encode($oldData)
        ]);

        $response = $this->actingAs($user)
            ->getJson('v3/user/accompaniment/delete?'.http_build_query([
                    'id' => '2222'
                ]));

        $response = json_decode($response->getContent());

        $this->assertDatabaseHas('user_sync_info', [
            'user_id' => $user->id,
            'type'    => 'accompaniment',
            'data'    => json_encode([
                [
                    'id' => '3333',
                    'name' => 'xxxx',
                ]
            ])
        ]);
    }

    public function testAccompanimentRemoveSuccess_WithoutOldData(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $response = $this->actingAs($user)
            ->getJson('v3/user/accompaniment/delete?'.http_build_query([
                    'id' => '2222'
                ]));
        $response = json_decode($response->getContent());

        $this->assertDatabaseMissing('user_sync_info', [
            'user_id' => $user->id,
            'type'    => 'accompaniment',
            'data'    => json_encode([
                [
                    'id' => '3333',
                    'name' => 'xxxx',
                ]
            ])
        ]);
    }

}