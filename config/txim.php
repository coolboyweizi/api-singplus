<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/9
 * Time: 上午10:30
 */
return [
    'baseUrl'  => env('TX_IM_BASE_URL'),
    'senders'  => [
        'Contests' => env('TX_IM_CONTESTS_ID'),
        'EditorPicks' => env('TX_IM_EDITOR_PICKS_ID'),
        'Annoucements' => env('TX_IM_ANNOUCEMENTS_ID'),
    ],
];