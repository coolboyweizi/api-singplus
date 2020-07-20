<?php

namespace SingPlus\Domains\Coins\Services;

use Illuminate\Support\Collection;
use SingPlus\Support\Database\SeqCounter;
use SingPlus\Contracts\Coins\Services\AccountService as AccountServiceContract;
use SingPlus\Domains\Coins\Repositories\CoinTransRepository;
use SingPlus\Domains\Users\Repositories\UserProfileRepository;
use SingPlus\Domains\Coins\Models\CoinTransaction;
use SingPlus\Domains\Users\Models\UserProfile;
use SingPlus\Exceptions\Coins\AccountBalanceNotEnoughException;

class AccountService implements AccountServiceContract
{
    /**
     * @var CoinTransRepository
     */
    private $coinTransRepo;

    /**
     * @var UserProfileRepository
     */
    private $profileRepo;

    public function __construct(
        CoinTransRepository $coinTransRepo,
        UserProfileRepository $profileRepo
    ) {
        $this->coinTransRepo = $coinTransRepo;
        $this->profileRepo = $profileRepo;
    }


    /**
     * {@inheritdoc}
     */
    public function getUserBalance(string $userId) : int
    {
        $profile = $this->profileRepo->findOneByUserId($userId);

        if ( ! $profile) {
            return 0;
        }

        $coins = object_get($profile, 'coins', ['balance' => 0]);

        return (int) array_get($coins, 'balance', 0);
    }


    /**
     * {@inheritdoc}
     */
    public function deposit(
        string $userId,
        int $amount,
        int $source,
        string $operator,
        ?\stdClass $businessDetail
    ) : int {
        $amount = abs($amount);
        CoinTransaction::create([
            'user_id'       => $userId,
            'operator'      => $operator,
            'amount'        => $amount,
            'source'        => $source,
            'display_order' => SeqCounter::getNext('cointrans'),
            'details'       => $businessDetail ? (array) $businessDetail : null,
        ]);

        $this->profileRepo->updateUserBalance($userId, $amount);

        return $this->getUserBalance($userId);
    }


    /**
     * {@inheritdoc}
     */
    public function withdraw(
        string $userId,
        int $amount,
        int $source,
        string $operator,
        ?\stdClass $businessDetail
    ) : int {
        $balance = $this->getUserBalance($userId);
        $amount = -abs($amount);
        $calBalance = $balance + $amount;
        if ($calBalance < 0) {
            throw new AccountBalanceNotEnoughException();
        }

        CoinTransaction::create([
            'user_id'       => $userId,
            'operator'      => $operator,
            'amount'        => $amount,
            'source'        => $source,
            'display_order' => SeqCounter::getNext('cointrans'),
            'details'       => $businessDetail ? (array) $businessDetail : null,
        ]);

        $this->profileRepo->updateUserBalance($userId, $amount);

        return $calBalance;
    }


    /**
     * {@inheritdoc}
     */
    public function getTransactions(
        string $userId,
        ?int $source = null,
        ?string $transId = null,
        int $size = 50
    ) : Collection {
        $displayOrder = null;
        if ($transId) {
            $trans = $this->coinTransRepo->findOneById($transId);
            $displayOrder = $trans ? $trans->display_order : null;
        }
        return $this->coinTransRepo
                    ->findAllForPaginationByUserId($userId, $source, $displayOrder, $size)
                    ->map(function ($trans, $_) {
                        return (object) [
                            'transId'           => $trans->id,
                            'source'            => $trans->source,
                            'transTime'         => $trans->created_at,
                            'amount'            => (int) $trans->amount,
                            'businessDetail'    => $trans->details,
                        ];
                    });
                                    
    }

    /**
     * {@inheritdoc}
     */
    public function isTaskIdExists(string $taskId) : bool
    {
        return $this->coinTransRepo
                    ->findOneByTaskId($taskId) ? true : false;
    }
}
