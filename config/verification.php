<?php

return [
  // the minimum time interval (in seconds) after which message is
  // allowed to be sent since last. 0 indicates no limit
  'send_interval' => env('VERIFY_SEND_INTERVAL', 60),


  // after expired_at seconds, message should be expired
  'expired_after' => env('VERIFY_EXPIRED_AFTER', 600),
  
  // the maximum number of messages that can be sent within a period
  // of time (in seconds). for instance, ['limit_period' => 86400, 'limit_count' => 10]
  // tells that user can be sent 10 messages per day. 
  // 0 of either field indicates no limit.
  'limit_period' => env('VERIFY_LIMIT_PERIOD', 86400),
  'limit_count'  => env('VERIFY_LIMIT_COUNT',  10),

  // the total maximum number of messages that can be sent within a period
  // of time (in seconds).
  'total_limit_period'  => env('VERIFY_TOTAL_LIMIT_PERIOD', 86400),
  'total_limit_count'   => env('VERIFY_TOTAL_LIMIT_COUNT', 2000),
];
