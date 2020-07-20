<?php

namespace FeatureTest\SingPlus\Controllers;

use Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use FeatureTest\SingPlus\TestCase;
use FeatureTest\SingPlus\MongodbClearTrait;
use Mockery;

class ImageControllerTest extends TestCase
{
  use MongodbClearTrait; 

  //=================================
  //        upload
  //=================================
  public function testUploadSuccess()
  {
    $this->expectsEvents(\SingPlus\Events\UserImageUploaded::class);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $counterMock = Mockery::mock(\Illuminate\Contracts\Cache\Store::class);
    $counterMock->shouldReceive('increment')
                ->once()
                ->with('user_images', 100)
                ->andReturn(100);
    Cache::shouldReceive('driver')
         ->once()
         ->with('counter')
         ->andReturn($counterMock);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__), Mockery::on(function (array $options) {
                      return isset($options['prefix']);   // prefix should be set
                   }))
                   ->andReturn(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id));
    $storageService->shouldReceive('toHttpUrl')
                   ->once()
                   ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id))
                   ->andReturn(sprintf('http://image.sing.plus/%s/images/0305b27e047a11e7ac640800276e6868', $user->id));

    $response = $this->actingAs($user)
                     ->json('POST', 'v3/user/image/upload', [
                        'image' => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
                     ]);
    $response->assertJson(['code' => 0]);
    $data = (json_decode($response->getContent()))->data;
    self::assertEquals(sprintf('http://image.sing.plus/%s/images/0305b27e047a11e7ac640800276e6868', $user->id), $data->url);

    $this->assertDatabaseHas('user_images', [
      '_id'           => $data->imageId,
      'user_id'       => $user->id,
      'uri'           => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      'display_order' => 100,
    ], 'mongodb');
  }

  public function testUploadFailed_SaveUserImageFailed()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $storageService = $this->mockStorage();
    $storageService->shouldReceive('store')
                   ->once()
                   ->with(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__), \Mockery::on(function (array $options) {
                      return isset($options['prefix']);   // prefix should be set
                   }))
                   ->andReturn(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id));
    $storageService->shouldReceive('remove')
                   ->once()
                   ->with(sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id))
                   ->andReturn(null);
    
    // mock UserImageService
    $userImageService = Mockery::mock(\SingPlus\Contracts\Users\Services\UserImageService::class);
    $this->app[\SingPlus\Contracts\Users\Services\UserImageService::class] = $userImageService;
    $userImageService->shouldReceive('addUserImage')
                     ->once()
                     ->with($user->id, sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id))
                     ->andReturn(false);
    $userImageService->shouldReceive('countUserGalleryImage')
                     ->once()
                     ->andReturn(1);

    $response = $this->actingAs($user)
                     ->json('POST', 'v3/user/image/upload', [
                        'image' => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
                     ]);

    $response->assertJson(['code' => 10107]);
  }

  public function testUploadFailed_ExceedUploadMax()
  {
    config([
      'image.user_upload_max' => 1,
    ]);

    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
                     ->json('POST', 'v3/user/image/upload', [
                        'image' => $this->makeUploadFile(sprintf('%s/test_datas/tesla_logo.jpg', __DIR__)),
                     ]);

    $response->assertJson(['code' => 10111]);
  }

  //=================================
  //        getGallery
  //=================================
  public function testGetGallerySuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 07:12:13',
      'display_order'  => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
      'display_order'  => 100,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 09:12:13',
      'display_order'  => 300,
    ]);
    $imageFour = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $user->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 10:12:13',
      'display_order'  => 1,
    ]);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/user/image/gallery?%s', http_build_query([
                        'size'  => 2,
                     ])));

    // order by display order desc
    // siee is 2, but we get 3 images, cause we get first page, but avatar is not 
    // in this page, so we manual fetch avatar and prepend avatar in the first place
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'gallery' => [
          [
            'imageId' => $imageFour->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $user->id),
            'isAvatar'  => true,
          ],
          [
            'imageId' => $imageThree->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $user->id),
            'isAvatar'  => false,
          ],
          [
            'imageId' => $imageOne->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $user->id),
            'isAvatar'  => false,
          ],
        ],
      ],
    ]);
  }

  public function testGetGallerySuccess_OtherGallery()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $other = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $other->id,
      'uri' => sprintf('pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $other->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 07:12:13',
      'display_order'  => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $other->id,
      'uri' => sprintf('pizzas/images-origin/%s/0305b27e047a11e7ac640800276e6868', $other->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
      'display_order'  => 100,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $other->id,
      'uri' => sprintf('pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $other->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 09:12:13',
      'display_order'  => 300,
    ]);
    $imageFour = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $other->id,
      'uri' => sprintf('pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $other->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 10:12:13',
      'display_order'  => 1,
    ]);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/user/image/gallery?%s', http_build_query([
                        'userId'  => $other->id,
                        'size'    => 2,
                     ])));

    // order by display order desc
    // siee is 2, but we get 3 images, cause we get first page, but avatar is not 
    // in this page, so we manual fetch avatar and prepend avatar in the first place
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'gallery' => [
          [
            'imageId' => $imageFour->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $other->id),
            'isAvatar'  => true,
          ],
          [
            'imageId' => $imageThree->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $other->id),
            'isAvatar'  => false,
          ],
          [
            'imageId' => $imageOne->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $other->id),
            'isAvatar'  => false,
          ],
        ],
      ],
    ]);
  }

  public function testGetGallerySuccess_OtherGalleryWithoutLogin()
  {
      $other = factory(\SingPlus\Domains\Users\Models\User::class)->create();
      $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
          'user_id'     => $other->id,
          'uri' => sprintf('pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $other->id),
          'is_avatar'   => 0,
          'created_at'  => '2017-03-09 07:12:13',
          'display_order'  => 200,
      ]);
      $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
          'user_id'     => $other->id,
          'uri' => sprintf('pizzas/images-origin/%s/0305b27e047a11e7ac640800276e6868', $other->id),
          'is_avatar'   => 0,
          'created_at'  => '2017-03-09 08:12:13',
          'display_order'  => 100,
      ]);
      $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
          'user_id'     => $other->id,
          'uri' => sprintf('pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $other->id),
          'is_avatar'   => 0,
          'created_at'  => '2017-03-09 09:12:13',
          'display_order'  => 300,
      ]);
      $imageFour = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
          'user_id'     => $other->id,
          'uri' => sprintf('pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $other->id),
          'is_avatar'   => 1,
          'created_at'  => '2017-03-09 10:12:13',
          'display_order'  => 1,
      ]);

      config([
          'filesystems.disks.s3.region'  => 'eu-central-1',
          'filesystems.disks.s3.bucket'  => 'sing-plus',
      ]);

      $response = $this
          ->getJson(sprintf('v3/user/image/gallery?%s', http_build_query([
              'userId'  => $other->id,
              'size'    => 2,
          ])));

      // order by display order desc
      // siee is 2, but we get 3 images, cause we get first page, but avatar is not
      // in this page, so we manual fetch avatar and prepend avatar in the first place
      $response->assertJson([
          'code'  => 0,
          'data'  => [
              'gallery' => [
                  [
                      'imageId' => $imageFour->id,
                      'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $other->id),
                      'isAvatar'  => true,
                  ],
                  [
                      'imageId' => $imageThree->id,
                      'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $other->id),
                      'isAvatar'  => false,
                  ],
                  [
                      'imageId' => $imageOne->id,
                      'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/c69d91ae04aa11e7aeaf0800276e6868', $other->id),
                      'isAvatar'  => false,
                  ],
              ],
          ],
      ]);
  }

  public function testGetGallerySuccess_HasNotMorePage()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
      'display_order' => 100,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/8be0c23c049811e799d90800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 09:12:13',
      'display_order' => 200,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('pizzas/images-origin/%s/38a54752049b11e7b7df0800276e6868', $user->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 10:12:13',
      'display_order' => 1,
    ]);

    config([
      'filesystems.disks.s3.region'  => 'eu-central-1',
      'filesystems.disks.s3.bucket'  => 'sing-plus',
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/user/image/gallery?%s', http_build_query([
                        'imageId' => $imageTwo->id,
                        'isNext'  => 1,
                        'size'    => 1,
                     ])));

    // avatar not returned, cause which only returned at page 1
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'gallery' => [
          [
            'imageId' => $imageOne->id,
            'url' => sprintf('https://sing-plus.s3.eu-central-1.amazonaws.com/pizzas/images-origin/%s/0305b27e047a11e7ac640800276e6868', $user->id),
            'isAvatar'  => false,
          ],
        ],
      ],
    ]);
  }

  public function testGetGallerySuccess_HasNoRecord()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
      'display_order' => 200,
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('test-bucket/%s/images/8be0c23c049811e799d90800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 09:12:13',
      'display_order' => 100,
    ]);
    $imageThree = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 10:12:13',
      'display_order' => 1,
    ]);

    $response = $this->actingAs($user)
                     ->getJson(sprintf('v3/user/image/gallery?%s', http_build_query([
                        'imageId' => $imageTwo->id,
                        'isNext'  => 1,
                        'size'    => 1,
                     ])));

    // avatar display order is lowest, but current page not the first page,
    // so we don't fetch avatar
    $response->assertJson([
      'code'  => 0,
      'data'  => [
        'gallery' => [],
      ],
    ]);
  }

  //=================================
  //        setAvatar
  //=================================
  public function testSetAvatarSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    $profile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id,
      'avatar'  => 'avatar-old',
      'is_new'  => false,
    ]);
    $oldAvatar = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 08:12:13',
    ]);
    $avatar = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/avatar/update', [
                        'imageId' => $avatar->id,
                     ]);
    $response->assertJson(['code' => 0]);

    $this->assertDatabaseHas('user_images', [
      '_id'        => $oldAvatar->id,
      'is_avatar' => 0,
    ], 'mongodb');
    $this->assertDatabaseHas('user_images', [
      '_id'       => $avatar->id,
      'is_avatar' => 1,
    ], 'mongodb');
    $this->assertDatabaseHas('user_profiles', [
      '_id'     => $profile->id,
      'avatar'  => sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
    ], 'mongodb');
  }

  public function testSetAvatarFailed_FileNotExists()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    
    $response = $this->actingAs($user)
                     ->postJson('v3/user/avatar/update', [
                        'imageId' => '940430b6b4fe4c0790f5c1bd14f7230b',
                     ]);
    $response->assertJson(['code' => 10109]);
  }

  public function testSetAvatarFailed_ImageNotBelongToUser()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $otherAvatar = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => '286ee4787d1d4bc1a39b21de25dee653',
      'uri' => sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 08:12:13',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/avatar/update', [
                        'imageId' => $otherAvatar->id,
                        'key' => sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
                     ]);
    $response->assertJson(['code' => 10109]);
  }

  //=================================
  //        delete
  //=================================
  public function testDeleteSuccess()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $other = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $keys = [
      sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
      sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      //sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $other->id),
    ];

    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri'         => $keys[0],
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri'         => $keys[1],
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);
    $storageService = $this->mockStorage();
    $storageService->shouldReceive('remove')
                   ->with($keys[0])
                   ->once();
    $storageService->shouldReceive('remove')
                   ->with($keys[1])
                   ->once();

    $response = $this->actingAs($user)
                     ->postJson('v3/user/image/delete', [
                        'imageIds'  => [$imageOne->id, $imageTwo->id],
                     ]);
    $response->assertJson(['code' => 0]);
  }

  public function testDeleteFailed_ImageNotBelongUser()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);
    $other = factory(\SingPlus\Domains\Users\Models\User::class)->create();

    $keys = [
      sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
      sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $other->id),
    ];

    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => $keys[0],
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $other->id,
      'uri' => $keys[1],
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/image/delete', [
                        'imageIds'  => [$imageOne->id, $imageTwo->id],
                        'keys'  => $keys,
                     ]);
    $response->assertJson(['code' => 10109]);
  }

  public function testDeleteFailed_AvatarCantDelete()
  {
    $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
    factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
      'user_id' => $user->id, 
    ]);

    $keys = [
      sprintf('test-bucket/%s/images/38a54752049b11e7b7df0800276e6868', $user->id),
      sprintf('test-bucket/%s/images/0305b27e047a11e7ac640800276e6868', $user->id),
    ];

    $imageOne = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => $keys[0],
      'is_avatar'   => 1,
      'created_at'  => '2017-03-09 08:12:13',
    ]);
    $imageTwo = factory(\SingPlus\Domains\Users\Models\UserImage::class)->create([
      'user_id'     => $user->id,
      'uri' => $keys[1],
      'is_avatar'   => 0,
      'created_at'  => '2017-03-09 08:12:13',
    ]);

    $response = $this->actingAs($user)
                     ->postJson('v3/user/image/delete', [
                        'imageIds'  => [$imageOne->id, $imageTwo->id],
                     ]);
    $response->assertJson(['code' => 10110]);
  }

  private function mockStorage()
  {
    $storageService = Mockery::mock(\SingPlus\Contracts\Storages\Services\StorageService::class);
    $this->app[\SingPlus\Contracts\Storages\Services\StorageService::class ] = $storageService;

    return $storageService;
  }
}
