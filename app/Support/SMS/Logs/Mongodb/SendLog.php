<?php

namespace SingPlus\SMS\Logs\Mongodb;

use SingPlus\Support\Database\Eloquent\MongodbModel;

class SendLog extends MongodbModel
{
  const STATUS_ACCEPTED         = 'accepted';
  const STATUS_PENDING          = 'pending';
  const STATUS_UNDELIVERABLE    = 'undeliverable';
  const STATUS_DELIVERED        = 'delivered';
  const STATUS_EXPIRED          = 'expired';
  const STATUS_REJECTED         = 'rejected';

  protected $collection = 'sms_send_logs';

  public function __construct(array $attributes = [])
  {
    $this->collection = 'sms_send_logs_' . Date('Ymd');
    parent::__construct($attributes);
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'transport',            // @see transports defined in config/sms.php
    'bulk_id',              // 
    'message_id',
    'from',
    'to',
    'message',
    'sms_count',
    'status',               // status, please see self::STATUS_XXXX
    'status_detail',        // status object
    'send_at',              // invalid after sms send, Y-m-d H:i:s
    'errors',
  ];
}
