<?php

namespace SingPlus\Services;

use Illuminate\Support\Collection;
use SingPlus\Contracts\Coins\Constants\Trans as TransConst;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Contracts\Users\Services\UserProfileService as UserProfileServiceContract;
use SingPlus\Exceptions\Users\UserNotExistsException;


class CoinService
{
    /**
     * @var AccountServiceContract
     */
    private $accountService;

    /**
     * @var UserProfileServiceContract
     */
    private $profileService;

    public function __construct(
        AccountServiceContract $accountService,
        UserProfileServiceContract $profileService
    ) {
        $this->accountService = $accountService;
        $this->profileService = $profileService;
    }


    /**
     * Get user bills
     *
     * @param string $loginUserId 
     * @param ?string $billId           for pagination
     * @param int $size                 for pagination
     *
     * @return Collection               elements as below:
     *                                  - id string
     *                                  - amount int
     *                                  - source int
     *                                  - transTime \Carbon\Carbon
     */
    public function getUserBills(string $loginUserId, ?string $billId, int $size) : Collection
    {
        return $this->accountService
                    ->getTransactions($loginUserId, null, $billId, $size)
                    ->map(function ($trans, $_) {
                      return (object) [
                          'id'        => $trans->transId,
                          'amount'    => $trans->amount,
                          'source'    => $trans->source,
                          'transTime' => $trans->transTime,
                      ];
                    });
    }

    /**
     * make trans by admin
     *
     * @param string $taskId
     * @param string $userId        user whose account will be deposit or withdraw
     * @param string $operator      admin operator
     * @param int $amount           coin number
     * @param int $source           trans source
     * @param array $details        trans detail
     */
    public function makeTransByAdmin(
        string $taskId,
        string $userId,
        string $operator,
        int $amount,
        int $source,
        array $details
    ) {
        if ( ! $this->profileService->fetchUserProfile($userId)) {
            throw new UserNotExistsException('profile not exist');
        }

        // checkout taskId
        if ($this->accountService->isTaskIdExists($taskId)) {
            return; 
        }

        $details['taskId'] = $taskId;
        $details = (object) $details;
        if (TransConst::isDeposit($source)) {
            $this->accountService->deposit(
                $userId, $amount, $source, $operator, $details
            );
        } else {
            $this->accountService->withdraw(
                $userId, $amount, $source, $operator, $details
            );
        }
    }
}
