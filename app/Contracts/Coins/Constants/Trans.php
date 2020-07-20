<?php

namespace SingPlus\Contracts\Coins\Constants;

class Trans
{
    // source which less 1001 stands for deposit, else stands for withdraw
    const SOURCE_DEPOSIT_CHARGE = 1;
    const SOURCE_DEPOSIT_TASK_DAILY = 51;
    const SOURCE_DEPOSIT_ADMIN_GIVE = 101;
    const SOURECE_DEPOSIT_BOOMCOIN = 201;

    const SOURCE_WITHDRAW_GIVE_GIFT = 1001;

    static private $sourceMaps = [
        self::SOURCE_DEPOSIT_CHARGE         => 'Purchase coins',    // 充值金币,
        self::SOURCE_DEPOSIT_TASK_DAILY     => 'Daily tasks',       // 每日任务,
        self::SOURCE_DEPOSIT_ADMIN_GIVE     => 'Sent by Admin',     // 管理员赠送,
        self::SOURECE_DEPOSIT_BOOMCOIN      => 'Boomcoin Exchange', // boomcoin 兑换

        self::SOURCE_WITHDRAW_GIVE_GIFT     => 'Send gifts',        // 赠送礼物,
    ];

    static public function validAdminSource() : array
    {
        return [
            self::SOURCE_DEPOSIT_ADMIN_GIVE,
        ];
    }

    static public function isDeposit(int $source) : bool
    {
        return $source <= 1000;
    }

    static public function isSourceValid(int $source) : bool
    {
        return array_key_exists($source, self::$sourceMaps);
    }

    static public function source2Name(int $source) : string
    {
        return array_get(self::$sourceMaps, $source, 'Invalid trans source');
    }
}
