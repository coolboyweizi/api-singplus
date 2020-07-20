<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/5/10
 * Time: 下午10:34
 */

namespace SingPlus\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\IMService;
use LogTXIM;

class IMSendSimpleMsg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $receiver;

    public $sender;

    public $msg;

    public $desc;

    public function __construct(string $sender,
                                string $receiver,
                                string $msg,
                                string $desc)
    {
        $this->receiver = $receiver;
        $this->sender = $sender;
        $this->msg = $msg;
        $this->desc = $desc;
    }

    public function handle(IMService $IMService)
    {
        $IMService->sendSimpleMsg($this->sender, $this->receiver, $this->msg, $this->desc);
    }
}
