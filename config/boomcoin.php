<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/8
 * Time: 上午9:38
 */

return [
    'exchange' => [
        'NG' => 361,
        'GH' => 439,
        'KE' => 103,
        'TZ' => 2248,
        'UG' => 3603,
        'ET' => 23,
        'ZA' => 13,
        'SN' => 560,
        'CM' => 555,
        'MZ' => 61,
        'EG' => 17,
        'MA' =>  9,
        'TN' => 2476,
        'DZ' => 115,
        'SA' => 3,
        'QA' => 3,
        'IN' => 65,
        'PK' => 105,
        'BD' => 80,
        'LK' => 152,
        'CI' => 560,
        'AE' => 3
    ],
    'defaultExchange' => 361,
    'exchangeList' => [
        '1' => 1000,
        '5' => 5000,
        '10' => 10000,
        '20' => 20000,
        '50' => 50000,
        '100' => 100000,
    ],
    'channels' => [
        'singplus' => [
            'consumerKey'    => env('BOOMCOIN_KEY'),
            'consumerSecret' => env('BOOMCOIN_SECRET'),
        ]
    ],
    'domain' => env('BOOMCOIN_DOMAIN')
    

];