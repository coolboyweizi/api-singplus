<?php

return [
  'defaultChannel'  => 'singplus',
  'currentChannel'  => env('TUDC_CHANNEL', 'singplus'),

  'channels'  => [
    'singplus'  => [
      'appId'     => env('TUDC_SINGPLUS_APPID'),
      'appKey'    => env('TUDC_SINGPLUS_APPKEY'),
      'appSecret' => env('TUDC_SINGPLUS_APPSECRET'), 
    ],
    'boomsing'  => [
      'appId'     => env('TUDC_BOOMSING_APPID'),
      'appKey'    => env('TUDC_BOOMSING_APPKEY'),
      'appSecret' => env('TUDC_BOOMSING_APPSECRET'), 
    ],
  ],

  'domain'    => [
    's2s'     => env('TUDC_DOMAIN_S2S'),
    'service' => env('TUDC_DOMAIN_SERVICE'),
    'web'     => env('TUDC_DOMAIN_WEB'),
  ],
];
