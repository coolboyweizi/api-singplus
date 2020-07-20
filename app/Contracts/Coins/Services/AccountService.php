<?php

namespace SingPlus\Contracts\Coins\Services;

use Illuminate\Support\Collection;

interface AccountService
{
    /**
     * Get user coin balance
     *
     * @param string $userId
     *
     * @return int
     */
    public function getUserBalance(string $userId) : int;


    /**
     * Deposit amount coin into user account
     *
     * @param string $userId
     * @param int $amount       amount must large or equal zero
     * @param int $source    depoit source, please to see:
     *                          \SingPlus\Contracts\Coins\Constants\Trans::SOURCE_XXX
     * @param ?string $operator
     * @param ?\stdClass $businessDetail        deposit business detail, differ by specific
     *                                          business logic, such as
     *                                          - order_id string   charge order id
     *
     * @return int              balance after deposit
     * @throw \SingPlus\Exceptions\Coins\AccountBalanceNotEnoughException
     */
    public function deposit(
        string $userId,
        int $amount,
        int $source,
        string $operator,
        ?\stdClass $businessDetail
    ) : int;

    /**
     * Withdraw amount coin from user account
     *
     * @param string $userId 
     * @param int $amount       amount must large or equal zero
     * @param int $source    depoit source, please to see:
     *                          \SingPlus\Contracts\Coins\Constants\Trans::SOURCE_XXX
     * @param ?string $operator
     * @param ?\stdClass $businessDetail        deposit business detail, differ by specific
     *                                          business logic, such as
     *                                          - order_id string   charge order id
     *
     * @return int              balance after withdraw
     * @throw \SingPlus\Exceptions\Coins\AccountBalanceNotEnoughException
     */
    public function withdraw(
        string $userId,
        int $amount,
        int $source,
        string $operator,
        ?\stdClass $businessDetail
    ) : int;

    /**
     * Get user transaction history
     *
     * @param string $userId
     * @param ?int $source   please to see:
     *                          \SingPlus\Contracts\Coins\Constants\Trans::SOURCE_XXX
     * @param ?string $transId  for pagination
     * @param int $size         for pagination
     * 
     * @return Collection       elements as below:
     *                          - transId string
     *                          - source string
     *                          - transTime \Carbon\Carbon
     *                          - amount int    positive stands for deposit
     *                                          negative stands for withdraw
     *                          - businessDetail ?\stdClass     business detail
     */
    public function getTransactions(
        string $userId,
        ?int $source = null,
        ?string $transId = null,
        int $size = 50
    ) : Collection;

    /**
     * @param string $taskId
     */
    public function isTaskIdExists(string $taskId) : bool;
}
