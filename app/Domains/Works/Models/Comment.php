<?php

namespace SingPlus\Domains\Works\Models;

use SingPlus\Support\Database\Eloquent\MongodbModel;
use SingPlus\Domains\Works\Models\Work;

class Comment extends MongodbModel
{
  const STATUS_DELETED = 0;
  const STATUS_NORMAL = 1;
  const TYPE_NORMAL = 0;
  const TYPE_COLLAB = 1;
  const TYPE_TRANSIMIT = 2;
  const TYPE_TRANSIMIT_INNER = 3;
  const TYPE_SEND_GIFT = 4;

  protected $collection = 'comments';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'work_id',
    'comment_id',         // null stands for comment for work, not comment
    'content',
    'author_id',
    'at_users',           // 需要通知的user id，@用户
    'replied_user_id',    // 被回复用户user id，对作品评论时，该字段为作品作者
    'display_order',
    'status',
    'type',               // 评论的类型
    'gift_feed_id',       // 礼物feed消息中回复送礼物而创建的评论时，带入这条该条feed id.
  ];

  public function isNormal() : bool
  {
    return $this->status == self::STATUS_NORMAL;
  }

  //======================================
  //        Relations
  //======================================
  public function work()
  {
    return $this->belongsTo(Work::class, 'work_id', '_id');
  }

  public function repliedComment()
  {
    return $this->belongsTo(Comment::class, 'comment_id', '_id');
  }
}
