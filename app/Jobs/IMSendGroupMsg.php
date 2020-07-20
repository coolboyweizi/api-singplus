<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/8
 * Time: 下午6:42
 */

namespace SingPlus\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use SingPlus\Services\IMService;

class IMSendGroupMsg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $receivers;

    public $type;

    public $customizeData;

    public $data;

    public function __construct( array $receptors,
                                 string $type,
                                 array $data,
                                 array $customizeData = [])
    {
        $this->receivers = $receptors;
        $this->type = $type;
        $this->customizeData = $customizeData;
        $this->data = $data;
    }

    public function handle(IMService $IMService)
    {
        $IMService->sendGroup($this->receivers, $this->type, $this->data, $this->customizeData);
    }

}