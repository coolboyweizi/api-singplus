<?php
/**
 * Created by PhpStorm.
 * User: karl
 * Date: 2018/1/8
 * Time: 下午6:54
 */

namespace SingPlus\Jobs;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use GuzzleHttp\Client;
use SingPlus\Services\IMService;
use LogTXIM;

class IMSendTopicMsg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $topic;

    public $type;

    public $customizeData;

    public $data;

    public function __construct(string $topic,
                                string $type,
                                array $data,
                                array $customizeData = [])
    {
        $this->topic = $topic;
        $this->type = $type;
        $this->customizeData = $customizeData;
        $this->data = $data;
    }

    public function handle(IMService $IMService)
    {
        LogTXIM::debug('IMSendTopicMsg  handle before',[]);
        $IMService->sendTopic($this->topic, $this->type, $this->data, $this->customizeData);
        LogTXIM::debug('IMSendTopicMsg  handle after',[]);
    }

}