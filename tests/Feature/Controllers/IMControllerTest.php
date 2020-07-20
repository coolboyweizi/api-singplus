<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 下午7:34
 */

namespace FeatureTest\SingPlus\Controllers;


use FeatureTest\SingPlus\TestCase;

class IMControllerTest extends TestCase
{

    public function testUpdateIMUserStatusSuccess_SYNC()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000001',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/im-user-status',[
                    'userId'  => $user->id,
                    'status'  => 1,
                ]);

        $response->assertJson([
            'code'  => 0,
            'message' => '',
            'data'  => [
                'status' => 1
            ],
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id'     => $user->id,
            'nickname'    => 'Martin',
            'is_new'  => false,
            'im_sync' => 1,
        ], 'mongodb');
    }

    public function testUpdateIMUserStatusSuccess_NOT_SYNC()
    {
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create([
            'country_code'  => '254',
            'mobile'        => '2547200000001',
        ]);
        factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id'   => $user->id,
            'nickname'  => 'Martin',
            'avatar'    => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
            'birth_date'  => '2002-02-03',
            'is_new'    => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('v3/user/im-user-status',[
                'userId'  => $user->id,
                'status'  => 0,
            ]);

        $response->assertJson([
            'code'  => 0,
            'message' => '',
            'data'  => [
                'status' => 0
            ],
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id'     => $user->id,
            'nickname'    => 'Martin',
            'is_new'  => false,
            'im_sync' => null,
        ], 'mongodb');
    }

}