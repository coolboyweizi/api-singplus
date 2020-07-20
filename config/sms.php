<?php

return [
  'config'  => [
    'pretending'  => env('SMS_PRETENDING', false),
  ],

  /*
   |--------------------------------------------------------------------------
   | SMS Transport
   |--------------------------------------------------------------------------
   |
   | Supported: "infobip", "log", "array"
   */
  'default'  => env('SMS_TRANSPORT', 'infobip'),

  'transports' => [
    /*
     * How to generate apikey, please see: https://dev.infobip.com/docs/api-key-create
     */
    'infobip' => [
      'driver'    => 'infobip',
      'authtype'  => 'apikey',        // available values are: apkkey, basic
      'apikey'    => env('SMS_INFOBIP_APIKEY'),
      'notifyurl' => env('SMS_INFOBIP_NOTIFY_URL'),
    ],
  ],

  /*
   |--------------------------------------------------------------------------
   | Global "From" Phone Number
   |--------------------------------------------------------------------------
   |
   | You may wish for all sms sent by your application to be send from
   | the same phone number. Here, you may specify a phone number that is
   | used global for all sms that are sent by your application
   |
   */
  'from'  => 'singplus',

  'from_for_china_message'  => '106909001236282',
  'from_for_india_message'  => 'SingIN',

  /*
   |--------------------------------------------------------------------------
   | Global "To" Phone Number
   |--------------------------------------------------------------------------
   |
   | This setting only be used for testing
   | You may wish for all sms sent by your application to be send to
   | the same phone number. Here, you may specify a phone number that is
   | used global for all sms that are sent to by your application
   |
   */
  'to'    => [],
];
