<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/2/26
 * Time: 下午7:02
 */

use SingPlus\Contracts\Hierarchy\Constants\PopularityCoefs;

return [
    'popularity_coefs' => [
        PopularityCoefs::TYPE_LISTEN_COUNT => 0.1,
        PopularityCoefs::TYPE_FAVOURITE_COUNT => 1,
        PopularityCoefs::TYPE_COMMENT_COUNT => 1,
        PopularityCoefs::TYPE_DURATION_ONE_MIN => 0,
        PopularityCoefs::TYPE_DURATION_TWO_MIN => 10,
        PopularityCoefs::TYPE_DURATION_THREE_MIN => 20,
        PopularityCoefs::TYPE_DURATION_OTHER_MIN => 30,
    ],

];