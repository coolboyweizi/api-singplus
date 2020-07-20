<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/2
 * Time: 上午11:49
 */

namespace FeatureTest\SingPlus\Listeners\Works;

use Cache;
use FeatureTest\SingPlus\MongodbClearTrait;
use FeatureTest\SingPlus\TestCase;
use SingPlus\Domains\Works\Models\Comment;
use SingPlus\Events\Works\WorkUpdateCommentGiftCacheData;

class UpdateWorkCommentGiftCacheDataTest extends TestCase
{
    use MongodbClearTrait;


    public function testUpdateCacheSuccess_WithCachedData(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $userOne->id,
            'replied_user_id' => $user->id,
            'work_id' => $work->id,
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent'
        ]);

        $commentTwo = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $user->id,
            'replied_user_id' => $userOne->id,
            'work_id' => $work->id,
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent 22'
        ]);

       $cacheKey = sprintf('worklist:%s:gifts:comment', $work->id);
       $cacheData = (object)[
           'workId'      => $work->id,
           'gifts'  => [
               (object)[
                   'userId' => $user->id,
                   'avatar'  => 'htttps://singplus.com/avatar.png',
               ]
           ],
           'comments'    => [
               (object)[
                   'commentId'         => $commentTwo->commentId,
                   'repliedCommentId'  => $commentTwo->repliedCommentId,
                   'author'            => (object) [
                       'userId'    => $commentTwo->authorId,
                       'nickname'  => 'author name ',
                   ],
                   'repliedUser'       => (object) [
                       'userId'    => $commentTwo->repliedUserId,
                       'nickname'  => 'replied nickname',
                   ],
                   'content'           => $commentTwo->content,
               ],

               (object)[
                   'commentId'         => $commentTwo->commentId,
                   'repliedCommentId'  => $commentTwo->repliedCommentId,
                   'author'            => (object) [
                       'userId'    => $commentTwo->authorId,
                       'nickname'  => 'author name  1',
                   ],
                   'repliedUser'       => (object) [
                       'userId'    => $commentTwo->repliedUserId,
                       'nickname'  => 'replied nickname 1',
                   ],
                   'content'           => $commentTwo->content,
               ],

               (object)[
                   'commentId'         => $commentTwo->commentId,
                   'repliedCommentId'  => $commentTwo->repliedCommentId,
                   'author'            => (object) [
                       'userId'    => $commentTwo->authorId,
                       'nickname'  => 'author name 2 ',
                   ],
                   'repliedUser'       => (object) [
                       'userId'    => $commentTwo->repliedUserId,
                       'nickname'  => 'replied nickname 2 ',
                   ],
                   'content'           => $commentTwo->content,
               ]
           ],
       ] ;
        Cache::put($cacheKey, $cacheData, 30);

        $event = new WorkUpdateCommentGiftCacheData($work->id, $userOne->id, $comment->id);
        $success = $this->getListener()->handle($event);

        $cache= Cache::get($cacheKey);
        self::assertCount(2, $cache->gifts);
        self::assertCount(3, $cache->comments);

    }

    public function testUpdateCacheFailed_WithoutCachedData(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $userOne->id,
            'replied_user_id' => $user->id,
            'work_id' => $work->id,
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent'
        ]);

        $event = new WorkUpdateCommentGiftCacheData($work->id, $userOne->id, $comment->id);
        $success = $this->getListener()->handle($event);
        $cacheKey = sprintf('worklist:%s:gifts:comment', $work->id);
        $cache= Cache::get($cacheKey);
        self::assertTrue($cache == null);
    }

    public function testUpdateCacheFailed_WithoutUserProfile(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $userOne->id,
            'replied_user_id' => $user->id,
            'work_id' => $work->id,
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent'
        ]);

        $cacheKey = sprintf('worklist:%s:gifts:comment', $work->id);
        $cacheData = (object)[
            'workId'      => $work->id,
            'gifts'  => [],
            'comments'    => []
        ];
        Cache::put($cacheKey, $cacheData, 30);

        $event = new WorkUpdateCommentGiftCacheData($work->id, $userOne->id, $comment->id);
        $success = $this->getListener()->handle($event);


        $cache= Cache::get($cacheKey);
        self::assertCount(0, $cache->comments);
    }

    public function testUpdateCacheFailed_WithoutComment(){
        $user = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfile = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $user->id,
        ]);

        $work = factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'user_id' => $user->id
        ]);

        $userOne = factory(\SingPlus\Domains\Users\Models\User::class)->create();
        $userProfileOne = factory(\SingPlus\Domains\Users\Models\UserProfile::class)->create([
            'user_id' => $userOne->id
        ]);
        $comment = factory(\SingPlus\Domains\Works\Models\Comment::class)->create([
            'author_id' => $userOne->id,
            'replied_user_id' => $user->id,
            'work_id' => $work->id,
            'status' => Comment::STATUS_NORMAL,
            'content' => 'contsdsdent'
        ]);

        $cacheKey = sprintf('worklist:%s:gifts:comment', $work->id);
        $cacheData = (object)[
            'workId'      => $work->id,
            'gifts'  => [],
            'comments'    => []
        ];
        Cache::put($cacheKey, $cacheData, 30);

        $event = new WorkUpdateCommentGiftCacheData($work->id, $userOne->id, sprintf('da%s1',$comment->id));
        $success = $this->getListener()->handle($event);

        $cache= Cache::get($cacheKey);
        self::assertCount(0, $cache->comments);
        self::assertCount(1, $cache->gifts);

    }

    private function getListener()
    {
        return $this->app->make(\SingPlus\Listeners\Works\UpdateWorkCommentGiftCacheData::class);
    }
}