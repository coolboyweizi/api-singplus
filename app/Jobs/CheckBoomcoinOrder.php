<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/4/9
 * Time: 下午2:46
 */

namespace SingPlus\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\BoomcoinService;

class CheckBoomcoinOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $userId;

    /**
     * @var string
     */
    public $orderId;

    public function __construct(string $userId, string $orderId)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;
    }

    public function handle(BoomcoinService $boomcoinService){
        $boomcoinService->checkBoomcoinOrder($this->userId, $this->orderId);
    }
}